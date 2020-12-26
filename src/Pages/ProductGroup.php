<?php

namespace Sunnysideup\Ecommerce\Pages;

use Page;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\Tab;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DB;

use SilverStripe\ORM\Connect\MySQLSchemaManager;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Cms\ProductsAndGroupsModelAdmin;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Forms\Fields\ProductProductImageUploadField;
use Sunnysideup\Ecommerce\Forms\Gridfield\Configs\GridFieldBasicPageRelationConfig;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\ORM\ProductList;
use Sunnysideup\Ecommerce\ORM\ProductListOptions;

/**
 * Product Group is a 'holder' for Products within the CMS
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @subpackage: Pages
 */
class ProductGroup extends Page
{


    /**
    * @var string
    */
    private static $base_buyable_class = Product::class;

    /**
     * @var string
     */
    private static $product_list_class = ProductList::class;

    /**
     * @var string
     */
    private static $product_group_list_class = ProductGroupList::class;

    /**
     * @var string
     */
    private static $product_list_options_class = ProductListOptions::class;

    private static $table_name = 'ProductGroup';

    private static $db = [
        'NumberOfProductsPerPage' => 'Int',
        'LevelOfProductsToShow' => 'Int',
        'DefaultSortOrder' => 'Varchar(20)',
        'DefaultFilter' => 'Varchar(20)',
        'DisplayStyle' => 'Varchar(20)',
    ];

    private static $has_one = [
        'Image' => Image::class,
    ];

    private static $owns = [
        'Image',
    ];

    private static $belongs_many_many = [
        'AlsoShowProducts' => Product::class,
    ];

    private static $defaults = [
        'DefaultSortOrder' => 'default',
        'DefaultFilter' => 'default',
        'DisplayStyle' => 'default',
        'LevelOfProductsToShow' => 99,
    ];

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

    public function SummaryFields()
    {
        return Config::inst()->get(ProductGroup::class, 'summary_fields', Config::UNINHERITED);
    }

    private static $casting = [
        'NumberOfProducts' => 'Int',
    ];

    private static $default_child = Product::class;

    private static $icon = 'sunnysideup/ecommerce:client/images/icons/productgroup-file.gif';

    private static $singular_name = 'Product Category';

    private static $plural_name = 'Product Categories';

    private static $description = 'A page the shows a bunch of products, based on your selection. By default it shows products linked to it (children)';

    /**
     * @var array
     */
    protected $recursiveValues = null;

    /**
     * @var ProductList
     */
    protected $productList;

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
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canCreate($member, $context);
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
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member, $context, $context);
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
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return $this->canEdit($member);
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
        return parent::canEdit($member);
    }


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Images', ProductProductImageUploadField::create('Image', _t('Product.IMAGE', 'Product Group Image')));

        $calculatedNumberOfProductsPerPage = $this->getNumberOfProductsPerPage();
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
        $numberOfProductsPerPageField->setDescription($numberOfProductsPerPageExplanation);
        if ($calculatedNumberOfProductsPerPage && ! $this->NumberOfProductsPerPage) {
            $this->NumberOfProductsPerPage = 0;
            $numberOfProductsPerPageField->setAttribute('placeholder', $calculatedNumberOfProductsPerPage);
        }

        // sort
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
            $defaultSortOrderField->setDescription(_t('ProductGroup.INHERIT_RIGHT_TITLE', "Inherit means that the parent page value is used - and if there is no relevant parent page then the site's default value is used."));
        }

        // filter
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
            $defaultFilterField->setDescription(_t('ProductGroup.INHERIT_RIGHT_TITLE', "Inherit means that the parent page value is used - and if there is no relevant parent page then the site's default value is used."));
        }

        // display style
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

        $config = EcommerceConfig::inst();

        if ($config->ProductsAlsoInOtherGroups) {
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
    protected function getUserPreferencesOptionsForDropdown(string $type)
    {
        $options = $this->getConfigOptionsObject()->getConfigOptionsCache($type);
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

    /**
     * Used in getCMCFields.
     *
     * @return GridField
     */
    protected function getProductGroupsTable()
    {
        return GridField::create(
            'AlsoShowProducts',
            _t('ProductGroup.OTHER_PRODUCTS_SHOWN_IN_THIS_GROUP', 'Other products shown in this group ...'),
            $this->AlsoShowProducts(),
            GridFieldBasicPageRelationConfig::create()
        );
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
                            $productGroup->publishRecursive();

                            $newURLSegment = $productGroup->URLSegment;
                            DB::alteration_message(' ... .... from ' . $oldURLSegment . ' to ' . $newURLSegment, 'created');
                        }

                        $count++;
                    }
                }
            }
        }
    }

    /**
     * Returns the Title for a type key.
     *
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


    /**
     * @return int
     * @alias of ProductsPerPage
     **/
    public function getProductsPerPage() : int
    {
        return $this->recursiveValue('NumberOfProductsPerPage', 0);
    }

    /**
     * Returns the number of products to show per page
     * @alias of ProductsPerPage
     *
     * @return int
     */
    public function getNumberOfProductsPerPage(): int
    {
        return $this->getProductsPerPage();
    }

    /**
     * get recursive value for Product Group and check EcommerceConfig as last resort
     * @param  string $fieldName
     * @param  mixed  $default
     * @return mixed
     */
    protected function recursiveValue(string $fieldNameOrMethod, $default = null)
    {
        if (! isset($this->recursiveValues[$fieldName])) {
            $value = $default;
            if ($this->hasMethod($fieldNameOrMethod)) {
                $value = $this->{$fieldNameOrMethod}();
                if(! $value->existes()) {
                    $value = null;
                }
            } elseif ($this->{$fieldName}) {
                $value = $this->{$fieldName};
            if (! $value || $value = 'inherit')
                if ($parent = $this->ParentGroup()) {
                    $value = $parent->recursiveValue($fieldName, $default);
                } else {
                    $value = EcommerceConfig::inst()->{$fieldName} ?? $default;
                }
            }

            $this->recursiveValues[$fieldName] = $value;
        }

        return $this->recursiveValues[$fieldName];
    }

    /**
     * Returns the number of product groups (children) to show in the current
     * product list based on the user setting for this page.
     *
     * @return int
     */
    public function getLevelOfProductsToShow(): int
    {
        return $this->recursiveValue('LevelOfProductsToShow', 99);
    }

    /**
     * used if you install lumberjack
     * @return string
     */
    public function getLumberjackTitle() : string
    {
        return _t('ProductGroup.BUYABLES', 'Products');
    }

    /**
     * add this segment to the end of a Product Group
     * link to create a cross-filter between the two categories.
     *
     * @return string
     */
    public function FilterForGroupLinkSegment() : string
    {
        return 'filterforgroup/' . $this->URLSegment . '/';
    }


    /**
     * Retrieve a list of products, based on the given parameters.
     *
     * This method is usually called by the various controller methods.
     *
     * The extraFilter helps you to select different products depending on the
     * method used in the controller.
     *
     * To paginate this
     *
     * @param array|string $extraFilter          Additional SQL filters to apply to the Product retrieval
     * @param array|string $alternativeSort      Additional SQL for sorting
     *
     * @return ProductList
     */
    public function getProductList($extraFilter = null, $alternativeSort = null)
    {
        if (! $this->productList) {
            $className = $this->Config()->get('product_list_class');
            $this->productList = $className::create($this, $this->getBuyableClassName());
        }
        $list = clone $this->productList;

        if ($extraFilter) {
            $list = $list->applyFilter($extraFilter);
        }

        if ($alternativeSort) {
            $list = $list->applySort($alternativeSort);
        }

        return $list;
    }

    /**
     * Returns children ProductGroup pages of this group.
     *
     * @param int            $maxRecursiveLevel  - maximum depth , e.g. 1 = one level down - so no Child Child Groups are returned...
     * @param string | Array $filter             - additional filter to be added
     *
     * @return \SilverStripe\ORM\ArrayList (ProductGroups)
     */
    public function ChildGroups(int $maxRecursiveLevel, $filter = null) : ArrayList
    {
        return $this->getProductList()->getGroupsRecursive($maxRecursiveLevel, $filter);
    }

    /**
     * Returns the parent page, but only if it is an instance of Product Group.
     *
     * @return ProductGroup|null
     */
    public function ParentGroup() : ?ProductGroup
    {
        return ProductGroup::get()->byID($this->ParentID);
    }

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
            return $parent->GroupsMenu($filter);
        }

        return $this->ChildGroups(1, $filter);
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
     * @return Image|null
     */
    public function BestAvailableImage()
    {
        return $this->recursiveValue('Image', null);
    }

    /**
     * Returns a list of Product Groups that have the products for the CURRENT
     * product group listed as part of their AlsoShowProducts list.
     *
     * With the method below you can work out a list of brands that apply to the
     * current product group (e.g. socks come in three brands - namely A, B and C)
     *
     * @return \SilverStripe\ORM\DataList|null
     */
    public function getProductGroupsFromAlsoShowProducts()
    {
        return $this->getProductList()->getProductGroups()->getProductGroupsFromAlsoShowProducts();
    }

    /**
     * This is the inverse of ProductGroupsFromAlsoShowProducts
     *
     * That is, it list the product groups that a product is primarily listed
     * under (exact parents only) from a "AlsoShow" product List.
     *
     * @return \SilverStripe\ORM\DataList|null
     */
    public function getProductGroupsFromAlsoShowProductsInverse()
    {
        return $this->getProductList()->getProductGroups()->getProductGroupsFromAlsoShowProducts();
    }

    /**
     * Given the products for this page, retrieve the parent groups excluding
     * the current one.
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function getProductGroupsParentGroups(): DataList
    {
        return $this->getProductList()->getProductGroups()->getProductGroupsParentGroups();
    }

    /**
     * tells us if the current page is part of e-commerce.
     *
     * @return bool
     */
    public function IsEcommercePage()
    {
        return true;
    }


    /**
     * the number of direct descendants
     * @return int
     */
    public function getNumberOfProducts() : int
    {
        return Product::get()->filter(['ParentID' => $this->ID,])->count();
    }

    protected function getConfigOptionsObject()
    {
        $this->getProductList()->getConfigOptionsObject();
    }


    /**
     * Returns the full sortFilterDisplayNames set, a subset, or one value
     * by either type (e.g. FILER) or variable (e.g dbFieldName)
     * or both.
     *
     * @param string $typeOrVariable FILTER | SORT | DISPLAY or sessionName, getVariable, etc...
     * @param string $variable:          sessionName, getVariable, etc...
     *
     * @return array | String
     */
    protected function getSortFilterDisplayNames(?string $typeOrVariable = '', ?string $variable = '')
    {
        return $this->getConfigOptionsObject()->getSortFilterDisplayNames($typeOrVariable, $variable);
    }



    /**
     * Checks for the most applicable user preferences for this page:
     *
     * 1. what is saved in Database for this page.
     * 2. what the parent product group has saved in the database
     * 3. what the standard default is.
     *
     * @param string $type - FILTER | SORT | DISPLAY
     *
     * @return string - returns the key
     */
    protected function getMyUserPreferencesDefault($type): string
    {
        return $this->getConfigOptionsObject()->getMyUserPreferencesDefault($type);
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
    protected function getBestKeyAndValidateKey(string $type, ?string $key = 'default', ?string $variable = '')
    {
        return $this->getConfigOptionsObject()->getOption($type, $key, $variable);
    }

    /**
     * Do products occur in more than one group.
     *
     * @return bool
     */
    protected function getProductsAlsoInOtherGroups() : bool
    {
        return EcommerceConfig::inst()->ProductsAlsoInOtherGroups;
    }

    /**
     * Returns the class we are working with.
     *
     * @return string
     */
    protected function getBuyableClassName() : string
    {
        return EcommerceConfig::get(ProductGroup::class, 'base_buyable_class');
    }
}
