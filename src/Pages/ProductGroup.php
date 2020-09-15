<?php

namespace Sunnysideup\Ecommerce\Pages;

use Page;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\Tab;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Cms\ProductsAndGroupsModelAdmin;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Forms\Fields\ProductProductImageUploadField;
use Sunnysideup\Ecommerce\Forms\Gridfield\Configs\GridFieldBasicPageRelationConfig;
use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

/**
 * Product Group is a 'holder' for Products within the CMS
 * It contains functions for versioning child products.
 *
 * The way the products are selected:
 *
 * Controller calls:
 * ProductGroup::ProductsShowable($extraFilter = "")
 *
 * ProductsShowable runs currentInitialProducts.  This selects ALL the applicable products
 * but it does NOT PAGINATE (limit) or SORT them.
 * After that, it calls currentFinalProducts, this sorts the products and notes the total
 * count of products (removing ones that can not be shown for one reason or another)
 *
 * Pagination is done in the controller.
 *
 * For each product page, there is a default:
 *  - filter
 *  - sort
 *  - number of levels to show (e.g. children, grand-children, etc...)
 * and these settings can be changed in the CMS, depending on what the
 * developer makes available to the content editor.
 *
 * In extending the ProductGroup class, it is recommended
 * that you override the following methods (as required ONLY!):
 * - getBuyableClassName
 * - getGroupFilter
 * - getStandardFilter
 * - getGroupJoin
 * - currentSortSQL
 * - limitCurrentFinalProducts
 * - removeExcludedProductsAndSaveIncludedProducts
 *
 * To filter products, you have three options:
 *
 * (1) getGroupFilter
 * - the standard product groups from which the products are selected
 * - if you extend Product Group this is the one you most likely want to change
 * - for example, rather than children, you set it to "yellow" products
 * - goes hand in hand with changes to showProductLevels / LevelOfProductsToShow
 * - works out the group filter based on the LevelOfProductsToShow value
 * - it also considers the other group many-many relationship
 * - this filter ALWAYS returns something: 1 = 1 if nothing else.
 *
 * (2) getStandardFilter
 * - these are the standard (user selectable) filters
 * - available options set via config
 * - the standard filter is updated by controller
 * - options can show above / below product lists to let user select alternative filter.
 *
 * (3) the extraWhere in ProductsShowable
 * - provided by the controller for specific ('on the fly') sub-sets
 * - this is for example for search results
 * - set in ProductShowable($extraWhere)
 *
 *
 * Caching
 * ==================
 *
 * There are two type of caching available:
 *
 * (1) caching of Product SQL queries
 *     - turned on and off by variable: ProductGroup->allowCaching
 *     - this is not a static so that you can create different settings for ProductGroup extensions.
 * (2) caching of product lists
 *     - see Product_Controller::ProductGroupListAreCacheable
 *
 * You can also ajaxify the product list, although this has nothing to do with
 * caching, it is related to it.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: Pages

 **/
class ProductGroup extends Page
{
    /**
     * list of sort / filter / display variables.
     *
     * @var array
     */
    protected $sortFilterDisplayNames = [
        'SORT' => [
            'value' => 'default',
            'configName' => 'sort_options',
            'sessionName' => 'session_name_for_sort_preference',
            'getVariable' => 'sort',
            'dbFieldName' => 'DefaultSortOrder',
            'translationCode' => 'SORT_BY',
        ],
        'FILTER' => [
            'value' => 'default',
            'configName' => 'filter_options',
            'sessionName' => 'session_name_for_filter_preference',
            'getVariable' => 'filter',
            'dbFieldName' => 'DefaultFilter',
            'translationCode' => 'FILTER_FOR',
        ],
        'DISPLAY' => [
            'value' => 'default',
            'configName' => 'display_styles',
            'sessionName' => 'session_name_for_display_style_preference',
            'getVariable' => 'display',
            'dbFieldName' => 'DisplayStyle',
            'translationCode' => 'DISPLAY_STYLE',
        ],
    ];

    /**
     * @var array
     *            List of options to show products.
     *            With it, we provide a bunch of methods to access and edit the options.
     *            NOTE: we can not have an option that has a zero key ( 0 => "none"), as this does not work
     *            (as it is equal to not completed yet - not yet entered in the Database).
     */
    protected $showProductLevels = [
        99 => 'All Child Products (default)',
        -2 => 'None',
        -1 => 'All products',
        1 => 'Direct Child Products',
        2 => 'Direct Child Products + Grand Child Products',
        3 => 'Direct Child Products + Grand Child Products + Great Grand Child Products',
        4 => 'Direct Child Products + Grand Child Products + Great Grand Child Products + Great Great Grand Child Products',
    ];

    /**
     * variable to speed up methods in this class.
     *
     * @var array
     */
    protected $configOptionsCache = [];

    /**
     * cache variable for default preference key.
     *
     * @var array
     */
    protected $myUserPreferencesDefaultCache = [];

    /**
     * count before limit.
     *
     * @var int
     */
    protected $rawCount = 0;

    /**
     * count after limit.
     *
     * @var int
     */
    protected $totalCount = 0;

    /**
     * Can product list (and related) be cached at all?
     * Set this to FALSE if the product details can be changed
     * for an individual user.
     *
     * @var bool
     */
    protected $allowCaching = true;

    /*****************************************************
     *
     *
     *
     *
     * FINAL PRODUCTS
     *
     *
     *
     *
     *****************************************************/

    /**
     * This is the dataList that contains all the products.
     *
     * @var DataList
     */
    protected $allProducts = null;

    /**
     * a list of relevant buyables that can
     * be purchased.  We keep this so that
     * that we can save to session, etc... for future use.
     * Should be set to NULL to start with so we know if it has been
     * set yet.
     *
     * @var array|null (like so: array(1,2,4,5,99))
     */
    protected $canBePurchasedArray = null;

    /**
     * @var Zend_Cache_Core
     */
    protected $silverstripeCoreCache = null;

    /**
     * @var string
     */
    private static $base_buyable_class = Product::class;

    /**
     * @var bool
     */
    private static $actively_check_for_can_purchase = false;

    /**
     * @var int
     */
    private static $maximum_number_of_products_to_list = 300;

    /**
     * @var array
     */
    private static $sort_options = [
        'default' => [
            'Title' => 'Default Order',
            'SQL' => '"Sort" ASC, "Title" ASC',
        ],
        'price' => [
            'Title' => 'Lowest Price',
            'SQL' => '"Price" ASC, "Sort" ASC, "Title" ASC',
        ],
    ];

    /**
     * @var array
     */
    private static $filter_options = [
        'default' => [
            'Title' => 'All Products (default)',
            'SQL' => [
                'ShowInSearch' => 1,
            ],
        ],
        'featuredonly' => [
            'Title' => 'Featured Only',
            'SQL' => [
                'ShowInSearch' => 1,
                'FeaturedProduct' => 1,
            ],
        ],
    ];

    /**
     * @var array
     */
    private static $display_styles = [
        'default' => [
            'Title' => 'default',
        ],
    ];

    /**
     * @var string
     */
    private static $session_name_for_product_array = 'ProductGroupProductIDs';

    /**
     * standard SS variable.
     *
     * @static Array
     */
    private static $table_name = 'ProductGroup';

    private static $db = [
        'NumberOfProductsPerPage' => 'Int',
        'LevelOfProductsToShow' => 'Int',
        'DefaultSortOrder' => 'Varchar(20)',
        'DefaultFilter' => 'Varchar(20)',
        'DisplayStyle' => 'Varchar(20)',
    ];

    /**
     * standard SS variable.
     *
     * @static Array
     */
    private static $has_one = [
        'Image' => Image::class,
    ];

    /**
     * standard SS variable.
     *
     * @static Array
     */
    private static $owns = [
        'Image',
    ];

    /**
     * standard SS variable.
     *
     * @static Array
     */
    private static $belongs_many_many = [
        'AlsoShowProducts' => Product::class,
    ];

    /**
     * standard SS variable.
     *
     * @static Array
     */
    private static $defaults = [
        'DefaultSortOrder' => 'default',
        'DefaultFilter' => 'default',
        'DisplayStyle' => 'default',
        'LevelOfProductsToShow' => 99,
    ];

    /**
     * standard SS variable.
     *
     * @static Array
     */
    private static $indexes = [
        'LevelOfProductsToShow' => true,
        'DefaultSortOrder' => true,
        'DefaultFilter' => true,
        'DisplayStyle' => true,
    ];

    private static $summary_fields = [
        'Image.CMSThumbnail' => 'Image',
        'Title' => 'Category',
        'NumberOfProducts' => 'Direct Product Count',
    ];

    private static $casting = [
        'NumberOfProducts' => 'Int',
    ];

    /**
     * standard SS variable.
     *
     * @static String
     */
    private static $default_child = Product::class;

    /**
     * standard SS variable.
     *
     * @static String | Array
     */
    private static $icon = 'sunnysideup/ecommerce: client/images/icons/productgroup-file.gif';

    /**
     * Standard SS variable.
     */
    private static $singular_name = 'Product Category';

    /**
     * Standard SS variable.
     */
    private static $plural_name = 'Product Categories';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A page the shows a bunch of products, based on your selection. By default it shows products linked to it (children)';

    private $_numberOfProductsPerPage = null;

    /**
     * a list of relevant buyables that can
     * not be purchased and therefore should be excluded.
     * Should be set to NULL to start with so we know if it has been
     * set yet.
     *
     * @var array|null (like so: array(1,2,4,5,99))
     */
    private $canNOTbePurchasedArray = null;

    /**
     * keeps a cache of the common caching key element
     * @var string
     */
    private static $_product_group_cache_key_cache = null;

    /**
     * cache for result array.
     *
     * @var array
     */
    private static $_result_array = [];

    public function i18n_singular_name()
    {
        return _t('ProductGroup.SINGULARNAME', 'Product Category');
    }

    public function i18n_plural_name()
    {
        return _t('ProductGroup.PLURALNAME', 'Product Categories');
    }

    public function canCreate($member = null, $context = [])
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Shop Admins can edit.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canDelete($member = null, $context = [])
    {
        if (is_a(Controller::curr(), EcommerceConfigClassNames::getName(ProductsAndGroupsModelAdmin::class))) {
            return false;
        }
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canPublish($member = null)
    {
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Setter for all products
     * @param DataList $dataList List of products
     */
    public function setAllProducts(DataList $dataList)
    {
        $this->allProducts = $dataList;

        return $this;
    }

    /*********************
     * SETTINGS: Title
     *********************/

    /**
     * Returns the Title for a type key.
     * If no key is provided then the default key is used.
     *
     * @param string $type - FILTER | SORT | DISPLAY
     * @param string $key
     *
     * @return string
     */
    public function getUserPreferencesTitle($type, $key = '')
    {
        $value = $this->getBestKeyAndValidateKey($type, $key, 'Title');
        if ($value) {
            return $value;
        }
        return _t('ProductGroup.UNKNOWN', 'UNKNOWN USER SETTING');
    }

    /*********************
     * SETTINGS: products per page
     *********************/

    /**
     * @return int
     **/
    public function ProductsPerPage()
    {
        return $this->MyNumberOfProductsPerPage();
    }

    /**
     * @return int
     **/
    public function MyNumberOfProductsPerPage()
    {
        if ($this->_numberOfProductsPerPage === null) {
            $productsPagePage = 0;
            if ($this->NumberOfProductsPerPage) {
                $productsPagePage = $this->NumberOfProductsPerPage;
            } else {
                if ($parent = $this->ParentGroup()) {
                    $productsPagePage = $parent->MyNumberOfProductsPerPage();
                } else {
                    $productsPagePage = EcommerceDBConfig::current_ecommerce_db_config()->NumberOfProductsPerPage;
                }
            }
            $this->_numberOfProductsPerPage = $productsPagePage;
        }
        return $this->_numberOfProductsPerPage;
    }

    /*********************
     * SETTINGS: level of products to show
     *********************/

    /**
     * returns the number of product groups (children)
     * to show in the current product list
     * based on the user setting for this page.
     *
     * @return int
     */
    public function MyLevelOfProductsToShow()
    {
        if ($this->LevelOfProductsToShow === 0) {
            if ($parent = $this->ParentGroup()) {
                $this->LevelOfProductsToShow = $parent->MyLevelOfProductsToShow();
            }
        }
        //reset to default
        if ($this->LevelOfProductsToShow === 0) {
            $defaults = Config::inst()->get(ProductGroup::class, 'defaults');

            return isset($defaults['LevelOfProductsToShow']) ? $defaults['LevelOfProductsToShow'] : 99;
        }

        return $this->LevelOfProductsToShow;
    }

    /*********************
     * CMS Fields
     *********************/

    /**
     * standard SS method.
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        //dirty hack to show images!
        $fields->addFieldToTab('Root.Images', ProductProductImageUploadField::create('Image', _t('Product.IMAGE', 'Product Group Image')));
        //number of products
        $calculatedNumberOfProductsPerPage = $this->MyNumberOfProductsPerPage();
        $numberOfProductsPerPageExplanation = $calculatedNumberOfProductsPerPage !== $this->NumberOfProductsPerPage ? _t('ProductGroup.CURRENTLVALUE', 'Current value: ') . $calculatedNumberOfProductsPerPage . ' ' . _t('ProductGroup.INHERITEDFROMPARENTSPAGE', ' (inherited from parent page because the current page is set to zero)') : '';
        $fields->addFieldToTab(
            'Root',
            Tab::create(
                'ProductDisplay',
                _t('ProductGroup.DISPLAY', 'Display'),
                $productsToShowField = DropdownField::create('LevelOfProductsToShow', _t('ProductGroup.PRODUCTSTOSHOW', 'Products to show'), $this->showProductLevels),
                HeaderField::create('WhatProductsAreShown', _t('ProductGroup.WHATPRODUCTSSHOWN', _t('ProductGroup.OPTIONSSELECTEDBELOWAPPLYTOCHILDGROUPS', 'Inherited options'))),
                $numberOfProductsPerPageField = NumericField::create('NumberOfProductsPerPage', _t('ProductGroup.PRODUCTSPERPAGE', 'Number of products per page'))
            )
        );
        $numberOfProductsPerPageField->setRightTitle($numberOfProductsPerPageExplanation);
        if ($calculatedNumberOfProductsPerPage && ! $this->NumberOfProductsPerPage) {
            $this->NumberOfProductsPerPage = 0;
            $numberOfProductsPerPageField->setAttribute('placeholder', $calculatedNumberOfProductsPerPage);
        }
        //sort
        $sortDropdownList = $this->getUserPreferencesOptionsForDropdown('SORT');
        if (count($sortDropdownList) > 1) {
            $sortOrderKey = $this->getMyUserPreferencesDefault('SORT');
            if ($this->DefaultSortOrder === 'inherit') {
                $actualValue = ' (' . (isset($sortDropdownList[$sortOrderKey]) ? $sortDropdownList[$sortOrderKey] : _t('ProductGroup.ERROR', 'ERROR')) . ')';
                $sortDropdownList['inherit'] = _t('ProductGroup.INHERIT', 'Inherit') . $actualValue;
            }
            $fields->addFieldToTab(
                'Root.ProductDisplay',
                $defaultSortOrderField = DropdownField::create('DefaultSortOrder', _t('ProductGroup.DEFAULTSORTORDER', 'Default Sort Order'), $sortDropdownList)
            );
            $defaultSortOrderField->setRightTitle(_t('ProductGroup.INHERIT_RIGHT_TITLE', "Inherit means that the parent page value is used - and if there is no relevant parent page then the site's default value is used."));
        }
        //filter
        $filterDropdownList = $this->getUserPreferencesOptionsForDropdown('FILTER');
        if (count($filterDropdownList) > 1) {
            $filterKey = $this->getMyUserPreferencesDefault('FILTER');
            if ($this->DefaultFilter === 'inherit') {
                $actualValue = ' (' . (isset($filterDropdownList[$filterKey]) ? $filterDropdownList[$filterKey] : _t('ProductGroup.ERROR', 'ERROR')) . ')';
                $filterDropdownList['inherit'] = _t('ProductGroup.INHERIT', 'Inherit') . $actualValue;
            }
            $fields->addFieldToTab(
                'Root.ProductDisplay',
                $defaultFilterField = DropdownField::create('DefaultFilter', _t('ProductGroup.DEFAULTFILTER', 'Default Filter'), $filterDropdownList)
            );
            $defaultFilterField->setRightTitle(_t('ProductGroup.INHERIT_RIGHT_TITLE', "Inherit means that the parent page value is used - and if there is no relevant parent page then the site's default value is used."));
        }
        //display style
        $displayStyleDropdownList = $this->getUserPreferencesOptionsForDropdown('DISPLAY');
        if (count($displayStyleDropdownList) > 2) {
            $displayStyleKey = $this->getMyUserPreferencesDefault('DISPLAY');
            if ($this->DisplayStyle === 'inherit') {
                $actualValue = ' (' . (isset($displayStyleDropdownList[$displayStyleKey]) ? $displayStyleDropdownList[$displayStyleKey] : _t('ProductGroup.ERROR', 'ERROR')) . ')';
                $displayStyleDropdownList['inherit'] = _t('ProductGroup.INHERIT', 'Inherit') . $actualValue;
            }
            $fields->addFieldToTab(
                'Root.ProductDisplay',
                DropdownField::create('DisplayStyle', _t('ProductGroup.DEFAULTDISPLAYSTYLE', 'Default Display Style'), $displayStyleDropdownList)
            );
        }
        if (EcommerceDBConfig::current_ecommerce_db_config()->ProductsAlsoInOtherGroups) {
            if (! $this instanceof ProductGroupSearchPage) {
                $fields->addFieldsToTab(
                    'Root.OtherProductsShown',
                    [
                        HeaderField::create('ProductGroupsHeader', _t('ProductGroup.OTHERPRODUCTSTOSHOW', 'Other products to show ...')),
                        $this->getProductGroupsTable(),
                    ]
                );
            }
        }

        return $fields;
    }

    /**
     * used if you install lumberjack
     * @return string
     */
    public function getLumberjackTitle()
    {
        return _t('ProductGroup.BUYABLES', 'Products');
    }

    /**
     * add this segment to the end of a Product Group
     * link to create a cross-filter between the two categories.
     *
     * @return string
     */
    public function FilterForGroupLinkSegment()
    {
        return 'filterforgroup/' . $this->URLSegment . '/';
    }

    /*****************************************************
     *
     *
     *
     * PRODUCTS THAT BELONG WITH THIS PRODUCT GROUP
     *
     *
     *
     *****************************************************/

    /**
     * returns the inital (all) products, based on the all the eligible products
     * for the page.
     *
     * This is THE pivotal method that probably changes for classes that
     * extend ProductGroup as here you can determine what products or other buyables are shown.
     *
     * The return from this method will then be sorted to produce the final product list.
     *
     * There is no sort for the initial retrieval
     *
     * This method is public so that you can retrieve a list of products for a product group page.
     *
     * @param array | string $extraFilter          Additional SQL filters to apply to the Product retrieval
     * @param string         $alternativeFilterKey Alternative standard filter to be used.
     *
     * @return \SilverStripe\ORM\DataList
     **/
    public function currentInitialProducts($extraFilter = null, $alternativeFilterKey = '')
    {

        //INIT ALLPRODUCTS
        $this->setProductBase();

        // GROUP FILTER (PRODUCTS FOR THIS GROUP)
        $this->allProducts = $this->getGroupFilter();

        // STANDARD FILTER (INCLUDES USER PREFERENCE)
        $filterStatement = $this->allowPurchaseWhereStatement();
        if ($filterStatement) {
            if (is_array($filterStatement)) {
                $this->allProducts = $this->allProducts->filter($filterStatement);
            } elseif (is_string($filterStatement)) {
                $this->allProducts = $this->allProducts->where($filterStatement);
            }
        }
        $this->allProducts = $this->getStandardFilter($alternativeFilterKey);

        // EXTRA FILTER (ON THE FLY FROM CONTROLLER)
        if (is_array($extraFilter) && count($extraFilter)) {
            $this->allProducts = $this->allProducts->filter($extraFilter);
        } elseif (is_string($extraFilter) && strlen($extraFilter) > 2) {
            $this->allProducts = $this->allProducts->where($extraFilter);
        }

        //JOINS
        $this->allProducts = $this->getGroupJoin();

        return $this->allProducts;
    }

    /**
     * this method can be used quickly get current initial products
     * whenever you write:
     *  ```php
     *   currentInitialProducts->(null, $key)->map("ID", "ID")->toArray();
     *  ```
     * this is the better replacement.
     *
     * @param string $filterKey
     *
     * @return array
     */
    public function currentInitialProductsAsCachedArray($filterKey)
    {
        //no need to add ID here ...
        $cacheKey = 'CurrentInitialProductsArray' . $filterKey;
        if ($array = $this->retrieveObjectStore($cacheKey)) {
            //do nothing
        } else {
            $array = $this->currentInitialProducts(null, $filterKey)->map('ID', 'ID')->toArray();
            $this->saveObjectStore($array, $cacheKey);
        }

        return $array;
    }

    /**
     * returns the total numer of products
     * (before pagination AND before MAX is applie).
     *
     * @return int
     **/
    public function RawCount()
    {
        return $this->rawCount ?: 0;
    }

    /**
     * returns the total numer of products
     * (before pagination but after MAX is applied).
     *
     * @return int
     **/
    public function TotalCount()
    {
        return $this->totalCount ?: 0;
    }

    /**
     * this is used to save a list of sorted products
     * so that you can find a previous and a next button, etc...
     *
     * @return array
     */
    public function getProductsThatCanBePurchasedArray()
    {
        return $this->canBePurchasedArray;
    }

    /**
     * Retrieve a set of products, based on the given parameters.
     * This method is usually called by the various controller methods.
     * The extraFilter helps you to select different products,
     * depending on the method used in the controller.
     *
     * Furthermore, extrafilter can take all sorts of variables.
     * This is basically setup like this so that in ProductGroup extensions you
     * can setup all sorts of filters, while still using the ProductsShowable method.
     *
     * The extra filter can be supplied as array (e.g. array("ID" => 12) or array("ID" => array(12,13,45)))
     * or as string. Arrays are used like this $productDataList->filter($array) and
     * strings are used with the where commands $productDataList->where($string).
     *
     * @param array | string $extraFilter          Additional SQL filters to apply to the Product retrieval
     * @param array | string $alternativeSort      Additional SQL for sorting
     * @param string         $alternativeFilterKey alternative filter key to be used
     *
     * @return \SilverStripe\ORM\DataList | Null
     */
    public function ProductsShowable($extraFilter = null, $alternativeSort = null, $alternativeFilterKey = '')
    {

        //get original products without sort
        $this->allProducts = $this->currentInitialProducts($extraFilter, $alternativeFilterKey);

        //sort products
        $this->allProducts = $this->currentFinalProducts($alternativeSort);

        return $this->allProducts;
    }

    /*****************************************************
     * Children and Parents
     *****************************************************/

    /**
     * Returns children ProductGroup pages of this group.
     *
     * @param int            $maxRecursiveLevel  - maximum depth , e.g. 1 = one level down - so no Child Groups are returned...
     * @param string | Array $filter             - additional filter to be added
     * @param int            $numberOfRecursions - current level of depth
     *
     * @return \SilverStripe\ORM\ArrayList (ProductGroups)
     */
    public function ChildGroups($maxRecursiveLevel, $filter = null, $numberOfRecursions = 0)
    {
        $arrayList = ArrayList::create();
        ++$numberOfRecursions;
        if ($numberOfRecursions < $maxRecursiveLevel) {
            if ($filter && is_string($filter)) {
                $filterWithAND = " AND ${filter}";
                $where = "\"ParentID\" = '{$this->ID}' ${filterWithAND}";
                $children = ProductGroup::get()->where($where);
            } elseif (is_array($filter) && count($filter)) {
                $filter += ['ParentID' => $this->ID];
                $children = ProductGroup::get()->filter($filter);
            } else {
                $children = ProductGroup::get()->filter(['ParentID' => $this->ID]);
            }

            if ($children->count()) {
                foreach ($children as $child) {
                    $arrayList->push($child);
                    $arrayList->merge($child->ChildGroups($maxRecursiveLevel, $filter, $numberOfRecursions));
                }
            }
        }
        if (! ($arrayList instanceof SS_List)) {
            user_error('We expect an SS_List as output');
        }

        return $arrayList;
    }

    /**
     * Deprecated method.
     */
    public function ChildGroupsBackup($maxRecursiveLevel, $filter = '')
    {
        Deprecation::notice('3.1', 'No longer in use');
        if ($maxRecursiveLevel > 24) {
            $maxRecursiveLevel = 24;
        }

        $stage = $this->getStage();
        $select = 'P1.ID as ID1 ';
        $from = "ProductGroup${stage} as P1 ";
        $join = " INNER JOIN SiteTree${stage} AS S1 ON P1.ID = S1.ID";
        $where = '1 = 1';
        $ids = [-1];
        for ($i = 1; $i < $maxRecursiveLevel; ++$i) {
            $j = $i + 1;
            $select .= ", P${j}.ID AS ID${j}, S${j}.ParentID";
            $join .= "
                LEFT JOIN ProductGroup${stage} AS P${j} ON P${j}.ID = S${i}.ParentID
                LEFT JOIN SiteTree${stage} AS S${j} ON P${j}.ID = S${j}.ID
            ";
        }
        $rows = DB::Query(' SELECT ' . $select . ' FROM ' . $from . $join . ' WHERE ' . $where);
        if ($rows) {
            foreach ($rows as $row) {
                for ($i = 1; $i < $maxRecursiveLevel; ++$i) {
                    if ($row['ID' . $i]) {
                        $ids[$row['ID' . $i]] = $row['ID' . $i];
                    }
                }
            }
        }

        return ProductGroup::get()->where("\"ProductGroup${stage}\".\"ID\" IN (" . implode(',', $ids) . ')');
    }

    /**
     * returns the parent page, but only if it is an instance of Product Group.
     *
     * @return \SilverStripe\ORM\DataObject | Null (ProductGroup)
     **/
    public function ParentGroup()
    {
        if ($this->ParentID) {
            return ProductGroup::get()->byID($this->ParentID);
        }
    }

    /*****************************************************
     * Other Stuff
     *****************************************************/

    /**
     * Recursively generate a product menu.
     *
     * @param string $filter
     *
     * @return \SilverStripe\ORM\ArrayList (ProductGroups)
     */
    public function GroupsMenu($filter = 'ShowInMenus = 1')
    {
        if ($parent = $this->ParentGroup()) {
            return is_a($parent, EcommerceConfigClassNames::getName(ProductGroup::class)) ? $parent->GroupsMenu() : $this->ChildGroups($filter);
        }
        return $this->ChildGroups($filter);
    }

    /**
     * returns a "BestAvailable" image if the current one is not available
     * In some cases this is appropriate and in some cases this is not.
     * For example, consider the following setup
     * - product A with three variations
     * - Product A has an image, but the variations have no images
     * With this scenario, you want to show ONLY the product image
     * on the product page, but if one of the variations is added to the
     * cart, then you want to show the product image.
     * This can be achieved bu using the BestAvailable image.
     *
     * @return Image | Null
     */
    public function BestAvailableImage()
    {
        $image = $this->Image();
        if ($image && $image->exists() && file_exists(ASSETS_PATH . '/' . $image->getFilename())) {
            return $image;
        } elseif ($parent = $this->ParentGroup()) {
            return $parent->BestAvailableImage();
        }
    }

    /*****************************************************
     * Other related products
     *****************************************************/

    /**
     * returns a list of Product Groups that have the products for
     * the CURRENT product group listed as part of their AlsoShowProducts list.
     *
     * EXAMPLE:
     * You can use the AlsoShowProducts to list products by Brand.
     * In general, they are listed under type product groups (e.g. socks, sweaters, t-shirts),
     * and you create a list of separate ProductGroups (brands) that do not have ANY products as children,
     * but link to products using the AlsoShowProducts many_many relation.
     *
     * With the method below you can work out a list of brands that apply to the
     * current product group (e.g. socks come in three brands - namely A, B and C)
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function ProductGroupsFromAlsoShowProducts()
    {
        $parentIDs = [];
        //we need to add the last array to make sure we have some products...
        $myProductsArray = $this->currentInitialProductsAsCachedArray($this->getMyUserPreferencesDefault('FILTER'));
        $rows = [];
        if (count($myProductsArray)) {
            $rows = DB::query('
                SELECT "ProductGroupID"
                FROM "Product_ProductGroups"
                WHERE "ProductID" IN (' . implode(',', $myProductsArray) . ')
                GROUP BY "ProductGroupID";
            ');
        }
        foreach ($rows as $row) {
            $parentIDs[$row['ProductGroupID']] = $row['ProductGroupID'];
        }
        //just in case
        unset($parentIDs[$this->ID]);
        if (! count($parentIDs)) {
            $parentIDs = [0 => 0];
        }

        return ProductGroup::get()->filter(['ID' => $parentIDs, 'ShowInSearch' => 1]);
    }

    /**
     * This is the inverse of ProductGroupsFromAlsoShowProducts
     * That is, it list the product groups that a product is primarily listed under (exact parents only)
     * from a "AlsoShow" product List.
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function ProductGroupsFromAlsoShowProductsInverse()
    {
        $alsoShowProductsArray = $this->AlsoShowProducts()
            ->filter($this->getUserSettingsOptionSQL('FILTER', $this->getMyUserPreferencesDefault('FILTER')))
            ->map('ID', 'ID')->toArray();
        $alsoShowProductsArray[0] = 0;
        $parentIDs = Product::get()->filter(['ID' => $alsoShowProductsArray])->map('ParentID', 'ParentID')->toArray();
        //just in case
        unset($parentIDs[$this->ID]);
        if (! count($parentIDs)) {
            $parentIDs = [0 => 0];
        }

        return ProductGroup::get()->filter(['ID' => $parentIDs, 'ShowInMenus' => 1]);
    }

    /**
     * given the products for this page,
     * retrieve the parent groups excluding the current one.
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function ProductGroupsParentGroups()
    {
        $arrayOfIDs = $this->currentInitialProductsAsCachedArray($this->getMyUserPreferencesDefault('FILTER')) + [0 => 0];
        $parentIDs = Product::get()->filter(['ID' => $arrayOfIDs])->map('ParentID', 'ParentID')->toArray();
        //just in case
        unset($parentIDs[$this->ID]);
        if (! count($parentIDs)) {
            $parentIDs = [0 => 0];
        }

        return ProductGroup::get()->filter(['ID' => $parentIDs, 'ShowInSearch' => 1]);
    }

    /*****************************************************
     * STANDARD SS METHODS
     *****************************************************/

    /**
     * tells us if the current page is part of e-commerce.
     *
     * @return bool
     */
    public function IsEcommercePage()
    {
        return true;
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $urlSegments = ProductGroup::get()->column('URLSegment');
        foreach ($urlSegments as $urlSegment) {
            $counts = array_count_values($urlSegments);
            $hasDuplicates = $counts[$urlSegment] > 1 ? true : false;
            if ($hasDuplicates) {
                DB::alteration_message('found duplicates for ' . $urlSegment, 'deleted');
                $checkForDuplicatesURLSegments = ProductGroup::get()
                    ->filter(['URLSegment' => $urlSegment]);
                if ($checkForDuplicatesURLSegments->count()) {
                    $count = 0;
                    foreach ($checkForDuplicatesURLSegments as $productGroup) {
                        if ($count > 0) {
                            $oldURLSegment = $productGroup->URLSegment;
                            DB::alteration_message(' ... Correcting URLSegment for ' . $productGroup->Title . ' with ID: ' . $productGroup->ID, 'deleted');
                            $productGroup->writeToStage('Stage');
                            $productGroup->publish('Stage', 'Live');
                            $newURLSegment = $productGroup->URLSegment;
                            DB::alteration_message(' ... .... from ' . $oldURLSegment . ' to ' . $newURLSegment, 'created');
                        }
                        $count++;
                    }
                }
            }
        }
    }

    /*****************************************************
     * CACHING
     *****************************************************/

    /**
     * @return bool
     */
    public function AllowCaching()
    {
        return $this->allowCaching;
    }

    /**
     * @param string $cacheKey
     * @param string $filterKey
     *
     * @return string
     */
    public function cacheKey($cacheKey)
    {
        $cacheKey .= '_' . $this->ID;
        if (self::$_product_group_cache_key_cache === null) {
            self::$_product_group_cache_key_cache = '_PR_'
                . strtotime(Product::get()->max('LastEdited')) . '_'
                . Product::get()->count();
            self::$_product_group_cache_key_cache .= 'PG_'
                . strtotime(ProductGroup::get()->max('LastEdited')) . '_'
                . ProductGroup::get()->count();
            if (class_exists('ProductVariation')) {
                self::$_product_group_cache_key_cache .= 'PV_'
                  . strtotime(ProductVariation::get()->max('LastEdited')) . '_'
                  . ProductVariation::get()->count();
            }
        }

        return $cacheKey . self::$_product_group_cache_key_cache;
    }

    /**
     * Set the cache object to use when storing / retrieving partial cache blocks.
     *
     * @param $silverstripeCoreCache
     */
    public function setSilverstripeCoreCache($silverstripeCoreCache)
    {
        $this->silverstripeCoreCache = $silverstripeCoreCache;
    }

    public function getNumberOfProducts()
    {
        return Product::get()->filter(['ParentID' => $this->ID])->count();
    }

    /**
     * return the options for one type.
     * This method solely exists to speed up processing.
     *
     * @param string $type - options are FILTER | SORT | DISPLAY
     *
     * @return array
     */
    protected function getConfigOptions($type)
    {
        if (! isset($this->configOptionsCache[$type])) {
            $configName = $this->sortFilterDisplayNames[$type]['configName'];
            $this->configOptionsCache[$type] = EcommerceConfig::get($this->ClassName, $configName);
        }

        return $this->configOptionsCache[$type];
    }

    /**
     * returns the full sortFilterDisplayNames set, a subset, or one value
     * by either type (e.g. FILER) or variable (e.g dbFieldName)
     * or both.
     *
     * @param string $typeOrVariable FILTER | SORT | DISPLAY or sessionName, getVariable, etc...
     * @param string $variable:          sessionName, getVariable, etc...
     *
     * @return array | String
     */
    protected function getSortFilterDisplayNames($typeOrVariable = '', $variable = '')
    {
        //return a string ...
        if ($variable) {
            return $this->sortFilterDisplayNames[$typeOrVariable][$variable];
        }
        //return an array ...
        $data = [];
        if (isset($this->sortFilterDisplayNames[$typeOrVariable])) {
            $data = $this->sortFilterDisplayNames[$typeOrVariable];
        } elseif ($typeOrVariable) {
            foreach ($this->sortFilterDisplayNames as $group) {
                $data[] = $group[$typeOrVariable];
            }
        } else {
            $data = $this->sortFilterDisplayNames;
        }

        return $data;
    }

    /**
     * sets a user preference.  This is typically used by the controller
     * to set filter and sort.
     *
     * @param string $type  SORT | FILTER | DISPLAY
     * @param string $value
     */
    protected function setCurrentUserPreference($type, $value)
    {
        $this->sortFilterDisplayNames[$type]['value'] = $value;
    }

    /**
     * Get a user preference.
     * This value can be updated by the controller
     * For example, the filter can be changed, based on a session value.
     *
     * @param string $type SORT | FILTER | DISPLAY
     *
     * @return string
     */
    protected function getCurrentUserPreferences($type)
    {
        return $this->sortFilterDisplayNames[$type]['value'];
    }

    /*********************
     * SETTINGS: Default Key
     *********************/

    /**
     * Checks for the most applicable user preferences for this page:
     * 1. what is saved in Database for this page.
     * 2. what the parent product group has saved in the database
     * 3. what the standard default is.
     *
     * @param string $type - FILTER | SORT | DISPLAY
     *
     * @return string - returns the key
     */
    protected function getMyUserPreferencesDefault($type)
    {
        if (! isset($this->myUserPreferencesDefaultCache[$type]) || ! $this->myUserPreferencesDefaultCache[$type]) {
            $options = $this->getConfigOptions($type);
            $dbVariableName = $this->sortFilterDisplayNames[$type]['dbFieldName'];
            $defaultOption = '';
            if ($defaultOption === 'inherit' && $parent = $this->ParentGroup()) {
                $defaultOption = $parent->getMyUserPreferencesDefault($type);
            } elseif ($this->{$dbVariableName} && array_key_exists($this->{$dbVariableName}, $options)) {
                $defaultOption = $this->{$dbVariableName};
            }
            if (! $defaultOption) {
                if (isset($options['default'])) {
                    $defaultOption = 'default';
                } else {
                    user_error("It is recommended that you have a default (key) option for ${type}", E_USER_NOTICE);
                    $keys = array_keys($options);
                    $defaultOption = $keys[0];
                }
            }
            $this->myUserPreferencesDefaultCache[$type] = $defaultOption;
        }

        return $this->myUserPreferencesDefaultCache[$type];
    }

    /*********************
     * SETTINGS: Dropdowns
     *********************/

    /**
     * SORT:
     * returns an array of Key => Title for sort options.
     *
     * FILTER:
     * Returns options for the dropdown of filter options.
     *
     * DISPLAY:
     * Returns the options for product display styles.
     * In the configuration you can set which ones are available.
     * If one is available then you must make sure that the corresponding template is available.
     * For example, if the display style is
     * MyTemplate => "All Details"
     * Then you must make sure MyTemplate.ss exists.
     *
     * @param string $type - FILTER | SORT | DISPLAY
     *
     * @return array
     */
    protected function getUserPreferencesOptionsForDropdown($type)
    {
        $options = $this->getConfigOptions($type);
        $inheritTitle = _t('ProductGroup.INHERIT', 'Inherit');
        $array = ['inherit' => $inheritTitle];
        if (is_array($options) && count($options)) {
            foreach ($options as $key => $option) {
                if (is_array($option)) {
                    $array[$key] = $option['Title'];
                } else {
                    $array[$key] = $option;
                }
            }
        }

        return $array;
    }

    /*********************
     * SETTINGS
     *********************/

    /**
     * check if the key is valid.
     *
     * @param  string $type     e.g. SORT | FILTER
     * @param  string $key      e.g. best_match | price | lastest
     * @param  string $variable e.g. SQL | Title

     * @return string - empty if not found
     */
    protected function getBestKeyAndValidateKey($type, $key = '', $variable = '')
    {
        $options = $this->getConfigOptions($type);
        //check !!!
        if ($key && isset($options[$key])) {
            //all good
        } else {
            //reset
            // TODO: what is this for?
            $key = $this->getMyUserPreferencesDefault($type);
            $this->getSortFilterDisplayNames($type, 'getVariable');
            //clear bogus value from session ...
            $sessionName = $this->getSortFilterDisplayNames($type, 'sessionName');
            Controller::curr()->getRequest()->getSession()->set('ProductGroup_' . $sessionName, '');
        }
        if ($key) {
            if ($variable) {
                return $options[$key][$variable];
            }
        }

        return $key;
    }

    /**
     * SORT:
     * Returns the sort sql for a particular sorting key.
     * If no key is provided then the default key will be returned.
     *
     * @param string $key
     *
     * @return array (e.g. Array(MyField => "ASC", "MyOtherField" => "DESC")
     *
     * FILTER:
     * Returns the sql associated with a filter option.
     *
     * @param string $type - FILTER | SORT | DISPLAY
     * @param string $key  - the options selected
     *
     * @return array | String (e.g. array("MyField" => 1, "MyOtherField" => 0)) OR STRING
     */
    protected function getUserSettingsOptionSQL($type, $key = '')
    {
        $value = $this->getBestKeyAndValidateKey($type, $key, 'SQL');
        if ($value) {
            return $value;
        }
        if ($type === 'FILTER') {
            return ['Sort' => 'ASC'];
        } elseif ($type === 'SORT') {
            return ['ShowInSearch' => 1];
        }
    }

    // /**
    //  * used if you install lumberjack
    //  * @return string
    //  */
    // public function getLumberjackGridFieldConfig()
    // {
    //     return GridFieldConfig_RelationEditor::create();
    // }

    /**
     * Used in getCSMFields.
     *
     * @return GridField
     **/
    protected function getProductGroupsTable()
    {
        return GridField::create(
            'AlsoShowProducts',
            _t('ProductGroup.OTHER_PRODUCTS_SHOWN_IN_THIS_GROUP', 'Other products shown in this group ...'),
            $this->AlsoShowProducts(),
            GridFieldBasicPageRelationConfig::create()
        );
    }

    protected function setProductBase()
    {
        unset($this->allProducts);
        $className = $this->getBuyableClassName();

        $this->allProducts = $className::get();
    }

    /*****************************************************
     * DATALIST: adjusters
     * these are the methods you want to override in
     * any clases that extend ProductGroup
     *****************************************************/

    /**
     * Do products occur in more than one group.
     *
     * @return bool
     */
    protected function getProductsAlsoInOtherGroups()
    {
        return EcommerceDBConfig::current_ecommerce_db_config()->ProductsAlsoInOtherGroups;
    }

    /**
     * Returns the class we are working with.
     *
     * @return string
     */
    protected function getBuyableClassName()
    {
        return EcommerceConfig::get(ProductGroup::class, 'base_buyable_class');
    }

    /**
     * @SEE: important notes at the top of this file / class
     *
     * IMPORTANT: Adjusts allProducts and returns it...
     *
     * @return \SilverStripe\ORM\DataList
     */
    protected function getGroupFilter()
    {
        $levelToShow = $this->MyLevelOfProductsToShow();
        $cacheKey = 'GroupFilter_' . abs(intval($levelToShow + 999));
        if ($groupFilter = $this->retrieveObjectStore($cacheKey)) {
            $this->allProducts = $this->allProducts->where($groupFilter);
        } else {
            $groupFilter = '';
            $productFilterArray = [];
            //special cases
            if ($levelToShow < 0) {
                //no produts but if LevelOfProductsToShow = -1 then show all
                $groupFilter = ' (' . $levelToShow . ' = -1) ';
            } elseif ($levelToShow > 0) {
                $groupIDs = [$this->ID => $this->ID];
                $productFilterTemp = $this->getProductsToBeIncludedFromOtherGroups();
                $productFilterArray[$productFilterTemp] = $productFilterTemp;
                $childGroups = $this->ChildGroups($levelToShow);
                if ($childGroups && $childGroups->count()) {
                    foreach ($childGroups as $childGroup) {
                        $groupIDs[$childGroup->ID] = $childGroup->ID;
                        $productFilterTemp = $childGroup->getProductsToBeIncludedFromOtherGroups();
                        $productFilterArray[$productFilterTemp] = $productFilterTemp;
                    }
                }
                $groupFilter = ' ( "ParentID" IN (' . implode(',', $groupIDs) . ') ) ' . implode($productFilterArray) . ' ';
            } else {
                //fall-back
                $groupFilter = '"ParentID" < 0';
            }
            $this->allProducts = $this->allProducts->where($groupFilter);
            $this->saveObjectStore($groupFilter, $cacheKey);
        }

        return $this->allProducts;
    }

    /**
     * If products are show in more than one group
     * Then this returns a where phrase for any products that are linked to this
     * product group.
     *
     * @return string
     */
    protected function getProductsToBeIncludedFromOtherGroups()
    {
        //TO DO: this should actually return
        //Product.ID = IN ARRAY(bla bla)
        $array = [];
        if ($this->getProductsAlsoInOtherGroups()) {
            $array = $this->AlsoShowProducts()->map('ID', 'ID')->toArray();
        }
        if (count($array)) {
            return ' OR ("Product"."ID" IN (' . implode(',', $array) . ')) ';
        }

        return '';
    }

    /**
     * @SEE: important notes at the top of this class / file for more information!
     *
     * IMPORTANT: Adjusts allProducts and returns it...
     *
     * @param string $alternativeFilterKey - filter key to be used... if none is specified then we use the current one.
     *
     * @return \SilverStripe\ORM\DataList
     */
    protected function getStandardFilter($alternativeFilterKey = '')
    {
        if ($alternativeFilterKey) {
            $filterKey = $alternativeFilterKey;
        } else {
            $filterKey = $this->getCurrentUserPreferences('FILTER');
        }
        $filter = $this->getUserSettingsOptionSQL('FILTER', $filterKey);
        if (is_array($filter)) {
            $this->allProducts = $this->allProducts->Filter($filter);
        } elseif (is_string($filter) && strlen($filter) > 2) {
            $this->allProducts = $this->allProducts->Where($filter);
        }

        return $this->allProducts;
    }

    /**
     * Join statement for the product groups.
     *
     * IMPORTANT: Adjusts allProducts and returns it...
     *
     * @return \SilverStripe\ORM\DataList
     */
    protected function getGroupJoin()
    {
        return $this->allProducts;
    }

    /**
     * Quick - dirty hack - filter to
     * only show relevant products.
     *
     * @param bool   $asArray
     * @param string $classNameOrTableName
     */
    protected function allowPurchaseWhereStatement($asArray = true, $classNameOrTableName = Product::class)
    {
        if (class_exists($classNameOrTableName)) {
            $tableName = DataObject::getSchema()->tableName($classNameOrTableName);
        } else {
            $tableName = $classNameOrTableName;
        }
        if (EcommerceDBConfig::current_ecommerce_db_config()->OnlyShowProductsThatCanBePurchased) {
            if ($asArray) {
                $allowPurchaseWhereStatement = ['AllowPurchase' => 1];
            } else {
                $allowPurchaseWhereStatement = "\"${$tableName}\".\"AllowPurchase\" = 1  ";
            }

            return $allowPurchaseWhereStatement;
        }
    }

    /**
     * returns the final products, based on the all the eligile products
     * for the page.
     *
     * In the process we also save a list of included products
     * and we sort them.  We also keep a record of the total count.
     *
     * All of the 'current' methods are to support the currentFinalProducts Method.
     *
     * @TODO: cache data for faster access.
     *
     * @param array | string $alternativeSort = Alternative Sort String or array
     *
     * @return \SilverStripe\ORM\DataList
     **/
    protected function currentFinalProducts($alternativeSort = null)
    {
        if ($this->allProducts) {
            //limit to maximum number of products for speed's sake
            $this->allProducts = $this->sortCurrentFinalProducts($alternativeSort);
            $this->allProducts = $this->limitCurrentFinalProducts();
            $this->allProducts = $this->removeExcludedProductsAndSaveIncludedProducts();

            return $this->allProducts;
        }
    }

    /**
     * returns the SORT part of the final selection of products.
     *
     * @return \SilverStripe\ORM\DataList (allProducts)
     */
    protected function sortCurrentFinalProducts($alternativeSort)
    {
        if ($alternativeSort) {
            if ($this->IsIDarray($alternativeSort)) {
                $sort = $this->createSortStatementFromIDArray($alternativeSort);
            } else {
                $sort = $alternativeSort;
            }
        } else {
            $sort = $this->currentSortSQL();
        }
        $this->allProducts = $this->allProducts->Sort($sort);

        return $this->allProducts;
    }

    /**
     * is the variable provided is an array
     * that can be used as a list of IDs?
     *
     * @param mixed $variable
     *
     * @return bool
     */
    protected function IsIDarray($variable)
    {
        return $variable && is_array($variable) && count($variable) && intval(current($variable)) === current($variable);
    }

    /**
     * returns the SORT part of the final selection of products.
     *
     * @return string | Array
     */
    protected function currentSortSQL()
    {
        $sortKey = $this->getCurrentUserPreferences('SORT');

        return $this->getUserSettingsOptionSQL('SORT', $sortKey);
    }

    /**
     * creates a sort string from a list of ID arrays...
     *
     * @param array $IDarray - list of product IDs
     * @param string $classNameOrTableName
     *
     * @return string
     */
    protected function createSortStatementFromIDArray($IDarray, $classNameOrTableName = Product::class)
    {
        if (class_exists($classNameOrTableName)) {
            $tableName = DataObject::getSchema()->tableName($classNameOrTableName);
        } else {
            $tableName = $classNameOrTableName;
        }
        $ifStatement = 'CASE ';
        // $sortStatement = '';
        $stage = $this->getStage();
        $count = 0;
        foreach ($IDarray as $productID) {
            $ifStatement .= ' WHEN "' . $tableName . $stage . "\".\"ID\" = ${productID} THEN ${count}";
            ++$count;
        }
        return $ifStatement . ' END';
    }

    /**
     * limits the products to a maximum number (for speed's sake).
     *
     * @return \SilverStripe\ORM\DataList (this->allProducts adjusted!)
     */
    protected function limitCurrentFinalProducts()
    {
        $this->rawCount = $this->allProducts->count();
        $max = EcommerceConfig::get(ProductGroup::class, 'maximum_number_of_products_to_list');
        if ($this->rawCount > $max) {
            $this->allProducts = $this->allProducts->limit($max - 1);
            $this->totalCount = $max;
        } else {
            $this->totalCount = $this->rawCount;
        }

        return $this->allProducts;
    }

    /**
     * Excluded products that can not be purchased
     * We all make a record of all the products that are in the current list
     * For efficiency sake, we do both these things at the same time.
     * IMPORTANT: Adjusts allProducts and returns it...
     *
     * @todo: cache data per user ....
     *
     * @return \SilverStripe\ORM\DataList
     */
    protected function removeExcludedProductsAndSaveIncludedProducts()
    {
        if (is_array($this->canBePurchasedArray) && is_array($this->canNOTbePurchasedArray)) {
            //already done!
        } else {
            $this->canNOTbePurchasedArray = [];
            $this->canBePurchasedArray = [];
            if ($this->config()->get('actively_check_for_can_purchase')) {
                foreach ($this->allProducts as $buyable) {
                    if ($buyable->canPurchase()) {
                        $this->canBePurchasedArray[$buyable->ID] = $buyable->ID;
                    } else {
                        $this->canNOTbePurchasedArray[$buyable->ID] = $buyable->ID;
                    }
                }
            } else {
                if ($this->rawCount > 0) {
                    $this->canBePurchasedArray = $this->allProducts->map('ID', 'ID')->toArray();
                } else {
                    $this->canBePurchasedArray = [];
                }
            }
            if (count($this->canNOTbePurchasedArray)) {
                $this->allProducts = $this->allProducts->exclude(['ID' => $this->canNOTbePurchasedArray]);
            }
        }

        return $this->allProducts;
    }

    /**
     * returns stage as "" or "_Live".
     *
     * @return string
     */
    protected function getStage()
    {
        $stage = '';
        if (Versioned::get_stage() === 'Live') {
            $stage = '_Live';
        }

        return $stage;
    }

    /**
     * Get the cache object to use when storing / retrieving stuff in the Silverstripe Cache
     */
    protected function getSilverstripeCoreCache()
    {
        return $this->silverstripeCoreCache ?: Injector::inst()->get(CacheInterface::class . '.EcomPG');
    }

    /**
     * saving an object to the.
     *
     * @param string $cacheKey
     *
     * @return mixed
     */
    protected function retrieveObjectStore($cacheKey)
    {
        $cacheKey = $this->cacheKey($cacheKey);
        if ($this->AllowCaching()) {
            $cache = $this->getSilverstripeCoreCache();

            $data = $cache->get($cacheKey);
            if (! $cache->has($cacheKey)) {
                return;
            }
            /**
             * UPGRADE TO DO: 'automatic_serialization' no longer exists, what do we replace it with
             */
            // if (! $cache->getOption('automatic_serialization')) {
            //     $data = @unserialize($data);
            // }

            return $data;
        }

        return;
    }

    /**
     * returns true when the data is saved...
     *
     * @param mixed  $data
     * @param string $cacheKey - key under which the data is saved...
     *
     * @return bool
     */
    protected function saveObjectStore($data, $cacheKey)
    {
        $cacheKey = $this->cacheKey($cacheKey);
        if ($this->AllowCaching()) {
            $cache = $this->getSilverstripeCoreCache();
            /**
             * UPGRADE TO DO: 'automatic_serialization' no longer exists, what do we replace it with
             */
            // if (! $cache->getOption('automatic_serialization')) {
            //     $data = serialize($data);
            // }
            $cache->set($cacheKey, $data);
            return true;
        }

        return false;
    }
}
