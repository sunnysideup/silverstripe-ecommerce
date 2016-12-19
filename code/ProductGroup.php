<?php

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
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: Pages
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class ProductGroup extends Page
{
    /**
     * standard SS variable.
     *
     * @static Array
     */
    private static $db = array(
        'NumberOfProductsPerPage' => 'Int',
        'LevelOfProductsToShow' => 'Int',
        'DefaultSortOrder' => 'Varchar(20)',
        'DefaultFilter' => 'Varchar(20)',
        'DisplayStyle' => 'Varchar(20)',
    );

    /**
     * standard SS variable.
     *
     * @static Array
     */
    private static $has_one = array(
        'Image' => 'Product_Image',
    );

    /**
     * standard SS variable.
     *
     * @static Array
     */
    private static $belongs_many_many = array(
        'AlsoShowProducts' => 'Product',
    );

    /**
     * standard SS variable.
     *
     * @static Array
     */
    private static $defaults = array(
        'DefaultSortOrder' => 'default',
        'DefaultFilter' => 'default',
        'DisplayStyle' => 'default',
        'LevelOfProductsToShow' => 99,
    );

    /**
     * standard SS variable.
     *
     * @static Array
     */
    private static $indexes = array(
        'LevelOfProductsToShow' => true,
        'DefaultSortOrder' => true,
        'DefaultFilter' => true,
        'DisplayStyle' => true,
    );

    private static $summary_fields = array(
        'Image.CMSThumbnail' => 'Image',
        'Title' => 'Category',
        'NumberOfProducts' => 'Direct Product Count'
    );

    private static $casting = array(
        'NumberOfProducts' => 'Int'
    );

    /**
     * standard SS variable.
     *
     * @static String
     */
    private static $default_child = 'Product';

    /**
     * standard SS variable.
     *
     * @static String | Array
     */
    private static $icon = 'ecommerce/images/icons/productgroup';

    /**
     * Standard SS variable.
     */
    private static $singular_name = 'Product Category';
    public function i18n_singular_name()
    {
        return _t('ProductGroup.SINGULARNAME', 'Product Category');
    }

    /**
     * Standard SS variable.
     */
    private static $plural_name = 'Product Categories';
    public function i18n_plural_name()
    {
        return _t('ProductGroup.PLURALNAME', 'Product Categories');
    }

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A page the shows a bunch of products, based on your selection. By default it shows products linked to it (children)';

    public function canCreate($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Shop Admins can edit.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canEdit($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        if (is_a(Controller::curr(), Object::getCustomClass('ProductsAndGroupsModelAdmin'))) {
            return false;
        }
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canPublish($member = null)
    {
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * list of sort / filter / display variables.
     *
     * @var array
     */
    protected $sortFilterDisplayNames = array(
        'SORT' => array(
            'value' => 'default',
            'configName' => 'sort_options',
            'sessionName' => 'session_name_for_sort_preference',
            'getVariable' => 'sort',
            'dbFieldName' => 'DefaultSortOrder',
            'translationCode' => 'SORT_BY',
        ),
        'FILTER' => array(
            'value' => 'default',
            'configName' => 'filter_options',
            'sessionName' => 'session_name_for_filter_preference',
            'getVariable' => 'filter',
            'dbFieldName' => 'DefaultFilter',
            'translationCode' => 'FILTER_FOR',
        ),
        'DISPLAY' => array(
            'value' => 'default',
            'configName' => 'display_styles',
            'sessionName' => 'session_name_for_display_style_preference',
            'getVariable' => 'display',
            'dbFieldName' => 'DisplayStyle',
            'translationCode' => 'DISPLAY_STYLE',
        ),
    );

    /**
     * @var array
     *            List of options to show products.
     *            With it, we provide a bunch of methods to access and edit the options.
     *            NOTE: we can not have an option that has a zero key ( 0 => "none"), as this does not work
     *            (as it is equal to not completed yet - not yet entered in the Database).
     */
    protected $showProductLevels = array(
        99 => 'All Child Products (default)',
        -2 => 'None',
        -1 => 'All products',
        1 => 'Direct Child Products',
        2 => 'Direct Child Products + Grand Child Products',
        3 => 'Direct Child Products + Grand Child Products + Great Grand Child Products',
        4 => 'Direct Child Products + Grand Child Products + Great Grand Child Products + Great Great Grand Child Products',
    );

    /**
     * variable to speed up methods in this class.
     *
     * @var array
     */
    protected $configOptionsCache = array();

    /**
     * cache variable for default preference key.
     *
     * @var array
     */
    protected $myUserPreferencesDefaultCache = array();

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
        if (!isset($this->configOptionsCache[$type])) {
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
     * @param string $typeOfVariableName FILTER | SORT | DISPLAY or sessionName, getVariable, etc...
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
        $data = array();
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
        if (!isset($this->myUserPreferencesDefaultCache[$type]) || !$this->myUserPreferencesDefaultCache[$type]) {
            $options = $this->getConfigOptions($type);
            $dbVariableName = $this->sortFilterDisplayNames[$type]['dbFieldName'];
            $defaultOption = '';
            if ($defaultOption == 'inherit' && $parent = $this->ParentGroup()) {
                $defaultOption = $parent->getMyUserPreferencesDefault($type);
            } elseif ($this->$dbVariableName && array_key_exists($this->$dbVariableName, $options)) {
                $defaultOption = $this->$dbVariableName;
            }
            if (!$defaultOption) {
                if (isset($options['default'])) {
                    $defaultOption = 'default';
                } else {
                    user_error("It is recommended that you have a default (key) option for $type", E_USER_NOTICE);
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
        $array = array('inherit' => $inheritTitle);
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
     * SETTINGS: SQL
     *********************/

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
        $options = $this->getConfigOptions($type);
            //if we cant find the current one, use the default
        if (!$key || (!isset($options[$key]))) {
            $key = $this->getMyUserPreferencesDefault($type);
        }
        if ($key) {
            return $options[$key]['SQL'];
        } else {
            if ($type == 'FILTER') {
                return array('Sort' => 'ASC');
            } elseif ($type == 'SORT') {
                return array('ShowInSearch' => 1);
            }
        }
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
        $options = $this->getConfigOptions($type);
        if (!$key || (!isset($options[$key]))) {
            $key = $this->getMyUserPreferencesDefault($type);
        }
        if ($key && isset($options[$key]['Title'])) {
            return $options[$key]['Title'];
        } else {
            return _t('ProductGroup.UNKNOWN', 'UNKNOWN USER SETTING');
        }
    }

    /*********************
     * SETTINGS: products per page
     *********************/

    /**
     *@return int
     **/
    public function ProductsPerPage()
    {
        return $this->MyNumberOfProductsPerPage();
    }
    public function MyNumberOfProductsPerPage()
    {
        $productsPagePage = 0;
        if ($this->NumberOfProductsPerPage) {
            $productsPagePage = $this->NumberOfProductsPerPage;
        } else {
            if ($parent = $this->ParentGroup()) {
                $productsPagePage = $parent->MyNumberOfProductsPerPage();
            } else {
                $productsPagePage = $this->EcomConfig()->NumberOfProductsPerPage;
            }
        }

        return $productsPagePage;
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
        if ($this->LevelOfProductsToShow == 0) {
            if ($parent = $this->ParentGroup()) {
                $this->LevelOfProductsToShow = $parent->MyLevelOfProductsToShow();
            }
        }
        //reset to default
        if ($this->LevelOfProductsToShow     == 0) {
            $defaults = Config::inst()->get('ProductGroup', 'defaults');

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
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        //dirty hack to show images!
        $fields->addFieldToTab('Root.Images', Product_ProductImageUploadField::create('Image', _t('Product.IMAGE', 'Product Group Image')));
        //number of products
        $calculatedNumberOfProductsPerPage = $this->MyNumberOfProductsPerPage();
        $numberOfProductsPerPageExplanation = $calculatedNumberOfProductsPerPage != $this->NumberOfProductsPerPage ? _t('ProductGroup.CURRENTLVALUE', 'Current value: ').$calculatedNumberOfProductsPerPage.' '._t('ProductGroup.INHERITEDFROMPARENTSPAGE', ' (inherited from parent page because the current page is set to zero)') : '';
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
        if ($calculatedNumberOfProductsPerPage && !$this->NumberOfProductsPerPage) {
            $this->NumberOfProductsPerPage = null;
            $numberOfProductsPerPageField->setAttribute('placeholder', $calculatedNumberOfProductsPerPage);
        }
        //sort
        $sortDropdownList = $this->getUserPreferencesOptionsForDropdown('SORT');
        if (count($sortDropdownList) > 1) {
            $sortOrderKey = $this->getMyUserPreferencesDefault('SORT');
            if ($this->DefaultSortOrder == 'inherit') {
                $actualValue = ' ('.(isset($sortDropdownList[$sortOrderKey]) ? $sortDropdownList[$sortOrderKey] : _t('ProductGroup.ERROR', 'ERROR')).')';
                $sortDropdownList['inherit'] = _t('ProductGroup.INHERIT', 'Inherit').$actualValue;
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
            if ($this->DefaultFilter == 'inherit') {
                $actualValue = ' ('.(isset($filterDropdownList[$filterKey]) ? $filterDropdownList[$filterKey] : _t('ProductGroup.ERROR', 'ERROR')).')';
                $filterDropdownList['inherit'] = _t('ProductGroup.INHERIT', 'Inherit').$actualValue;
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
            if ($this->DisplayStyle == 'inherit') {
                $actualValue = ' ('.(isset($displayStyleDropdownList[$displayStyleKey]) ? $displayStyleDropdownList[$displayStyleKey] : _t('ProductGroup.ERROR', 'ERROR')).')';
                $displayStyleDropdownList['inherit'] = _t('ProductGroup.INHERIT', 'Inherit').$actualValue;
            }
            $fields->addFieldToTab(
                'Root.ProductDisplay',
                DropdownField::create('DisplayStyle', _t('ProductGroup.DEFAULTDISPLAYSTYLE', 'Default Display Style'), $displayStyleDropdownList)
            );
        }
        if ($this->EcomConfig()->ProductsAlsoInOtherGroups) {
            if (!$this instanceof ProductGroupSearchPage) {
                $fields->addFieldsToTab(
                    'Root.OtherProductsShown',
                    array(
                        HeaderField::create('ProductGroupsHeader', _t('ProductGroup.OTHERPRODUCTSTOSHOW', 'Other products to show ...')),
                        $this->getProductGroupsTable(),
                    )
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
        $gridField = GridField::create(
            'AlsoShowProducts',
            _t('ProductGroup.OTHER_PRODUCTS_SHOWN_IN_THIS_GROUP', 'Other products shown in this group ...'),
            $this->AlsoShowProducts(),
            GridFieldBasicPageRelationConfig::create()
        );
        //make sure edits are done in the right place ...
        return $gridField;
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
     * @return DataList
     **/
    public function currentInitialProducts($extraFilter = null, $alternativeFilterKey = '')
    {

        //INIT ALLPRODUCTS
        unset($this->allProducts);
        $className = $this->getBuyableClassName();
        $this->allProducts = $className::get();

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
     * this method can be used quickly current initial products
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
        $cacheKey = 'CurrentInitialProductsArray'.$filterKey;
        if ($array = $this->retrieveObjectStore($cacheKey)) {
            //do nothing
        } else {
            $array = $this->currentInitialProducts(null, $filterKey)->map('ID', 'ID')->toArray();
            $this->saveObjectStore($array, $cacheKey);
        }

        return $array;
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
        return $this->EcomConfig()->ProductsAlsoInOtherGroups;
    }

    /**
     * Returns the class we are working with.
     *
     * @return string
     */
    protected function getBuyableClassName()
    {
        return EcommerceConfig::get('ProductGroup', 'base_buyable_class');
    }

    /**
     * @SEE: important notes at the top of this file / class
     *
     * IMPORTANT: Adjusts allProducts and returns it...
     *
     * @return DataList
     */
    protected function getGroupFilter()
    {
        $levelToShow = $this->MyLevelOfProductsToShow();
        $cacheKey = 'GroupFilter_'.abs(intval($levelToShow + 999));
        if ($groupFilter = $this->retrieveObjectStore($cacheKey)) {
            $this->allProducts = $this->allProducts->where($groupFilter);
        } else {
            $groupFilter = '';
            $productFilterArray = array();
            //special cases
            if ($levelToShow < 0) {
                //no produts but if LevelOfProductsToShow = -1 then show all
                $groupFilter = ' ('.$levelToShow.' = -1) ';
            } elseif ($levelToShow > 0) {
                $groupIDs = array($this->ID => $this->ID);
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
                $groupFilter = ' ( "ParentID" IN ('.implode(',', $groupIDs).') ) '.implode($productFilterArray).' ';
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
        $array = array();
        if ($this->getProductsAlsoInOtherGroups()) {
            $array = $this->AlsoShowProducts()->map('ID', 'ID')->toArray();
        }
        if (count($array)) {
            $stage = $this->getStage();

            return " OR (\"Product$stage\".\"ID\" IN (".implode(',', $array).')) ';
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
     * @return DataList
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
     * @return DataList
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
     * @param string $table
     */
    protected function allowPurchaseWhereStatement($asArray = true, $table = 'Product')
    {
        if ($this->EcomConfig()->OnlyShowProductsThatCanBePurchased) {
            if ($asArray) {
                $allowPurchaseWhereStatement = array('AllowPurchase' => 1);
            } else {
                $allowPurchaseWhereStatement = "\"$table\".\"AllowPurchase\" = 1  ";
            }

            return $allowPurchaseWhereStatement;
        }
    }

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
     * not be purchased and therefore should be excluded.
     * Should be set to NULL to start with so we know if it has been
     * set yet.
     *
     * @var null | Array (like so: array(1,2,4,5,99))
     */
    private $canNOTbePurchasedArray = null;

    /**
     * a list of relevant buyables that can
     * be purchased.  We keep this so that
     * that we can save to session, etc... for future use.
     * Should be set to NULL to start with so we know if it has been
     * set yet.
     *
     * @var null | Array (like so: array(1,2,4,5,99))
     */
    protected $canBePurchasedArray = null;

    /**
     * returns the total numer of products (before pagination).
     *
     * @return int
     **/
    public function RawCount()
    {
        return $this->rawCount ? $this->rawCount : 0;
    }

    /**
     * returns the total numer of products (before pagination).
     *
     * @return int
     **/
    public function TotalCount()
    {
        return $this->totalCount ? $this->totalCount : 0;
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
     * @return DataList | Null
     */
    public function ProductsShowable($extraFilter = null, $alternativeSort = null, $alternativeFilterKey = '')
    {

        //get original products without sort
        $this->allProducts = $this->currentInitialProducts($extraFilter, $alternativeFilterKey);

        //sort products
        $this->allProducts = $this->currentFinalProducts($alternativeSort);

        return $this->allProducts;
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
     * @return DataList
     **/
    protected function currentFinalProducts($alternativeSort = null)
    {
        if ($this->allProducts) {

            //limit to maximum number of products for speed's sake
            $this->allProducts = $this->sortCurrentFinalProducts($alternativeSort);
            $this->allProducts = $this->limitCurrentFinalProducts();
            $this->allProducts = $this->removeExcludedProductsAndSaveIncludedProducts($this->allProducts);

            return $this->allProducts;
        }
    }

    /**
     * returns the SORT part of the final selection of products.
     *
     * @return DataList (allProducts)
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
     * @param mixed
     *
     * @return bool
     */
    protected function IsIDarray($variable)
    {
        return $variable && is_array($variable) && count($variable) && intval(current($variable)) == current($variable);
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
     *
     * @return string
     */
    protected function createSortStatementFromIDArray($IDarray, $table = 'Product')
    {
        $ifStatement = 'CASE ';
        $sortStatement = '';
        $stage = $this->getStage();
        $count = 0;
        foreach ($IDarray as $productID) {
            $ifStatement .= ' WHEN "'.$table.$stage."\".\"ID\" = $productID THEN $count";
            ++$count;
        }
        $sortStatement = $ifStatement.' END';

        return $sortStatement;
    }

    /**
     * limits the products to a maximum number (for speed's sake).
     *
     * @return DataList (this->allProducts adjusted!)
     */
    protected function limitCurrentFinalProducts()
    {
        $this->rawCount = $this->allProducts->count();
        $max = EcommerceConfig::get('ProductGroup', 'maximum_number_of_products_to_list');
        if ($this->rawCount > $max) {
            $this->allProducts = $this->allProducts->limit($max);
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
     * @return DataList
     */
    protected function removeExcludedProductsAndSaveIncludedProducts()
    {
        if (is_array($this->canBePurchasedArray) && is_array($this->canNOTbePurchasedArray)) {
            //already done!
        } else {
            $this->canNOTbePurchasedArray = array();
            $this->canBePurchasedArray = array();
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
                    $this->canBePurchasedArray = array();
                }
            }
            if (count($this->canNOTbePurchasedArray)) {
                $this->allProducts = $this->allProducts->Exclude(array('ID' => $this->canNOTbePurchasedArray));
            }
        }

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
     * @return ArrayList (ProductGroups)
     */
    public function ChildGroups($maxRecursiveLevel, $filter = null, $numberOfRecursions = 0)
    {
        $arrayList = ArrayList::create();
        ++$numberOfRecursions;
        if ($numberOfRecursions < $maxRecursiveLevel) {
            if ($filter && is_string($filter)) {
                $filterWithAND = " AND $filter";
                $where = "\"ParentID\" = '$this->ID' $filterWithAND";
                $children = ProductGroup::get()->where($where);
            } elseif (is_array($filter) && count($filter)) {
                $filter = $filter + array('ParentID' => $this->ID);
                $children = ProductGroup::get()->filter($filter);
            } else {
                $children = ProductGroup::get()->filter(array('ParentID' => $this->ID));
            }

            if ($children->count()) {
                foreach ($children as $child) {
                    $arrayList->push($child);
                    $arrayList->merge($child->ChildGroups($maxRecursiveLevel, $filter, $numberOfRecursions));
                }
            }
        }
        if (!$arrayList instanceof ArrayList) {
            user_error('We expect an array list as output');
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
        $from = "ProductGroup$stage as P1 ";
        $join = " INNER JOIN SiteTree$stage AS S1 ON P1.ID = S1.ID";
        $where = '1 = 1';
        $ids = array(-1);
        for ($i = 1; $i < $maxRecursiveLevel; ++$i) {
            $j = $i + 1;
            $select .= ", P$j.ID AS ID$j, S$j.ParentID";
            $join .= "
                LEFT JOIN ProductGroup$stage AS P$j ON P$j.ID = S$i.ParentID
                LEFT JOIN SiteTree$stage AS S$j ON P$j.ID = S$j.ID
            ";
        }
        $rows = DB::Query(' SELECT '.$select.' FROM '.$from.$join.' WHERE '.$where);
        if ($rows) {
            foreach ($rows as $row) {
                for ($i = 1; $i < $maxRecursiveLevel; ++$i) {
                    if ($row['ID'.$i]) {
                        $ids[$row['ID'.$i]] = $row['ID'.$i];
                    }
                }
            }
        }

        return ProductGroup::get()->where("\"ProductGroup$stage\".\"ID\" IN (".implode(',', $ids).')'.$filterWithAND);
    }

    /**
     * returns the parent page, but only if it is an instance of Product Group.
     *
     * @return DataObject | Null (ProductGroup)
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
     * @return ArrayList (ProductGroups)
     */
    public function GroupsMenu($filter = 'ShowInMenus = 1')
    {
        if ($parent = $this->ParentGroup()) {
            return is_a($parent, Object::getCustomClass('ProductGroup')) ? $parent->GroupsMenu() : $this->ChildGroups($filter);
        } else {
            return $this->ChildGroups($filter);
        }
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
        if ($image && $image->exists() && file_exists($image->getFullPath())) {
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
     * @return DataList
     */
    public function ProductGroupsFromAlsoShowProducts()
    {
        $parentIDs = array();
        //we need to add the last array to make sure we have some products...
        $myProductsArray = $this->currentInitialProductsAsCachedArray($this->getMyUserPreferencesDefault('FILTER'));
        $rows = array();
        if (count($myProductsArray)) {
            $rows = DB::query('
                SELECT "ProductGroupID"
                FROM "Product_ProductGroups"
                WHERE "ProductID" IN ('.implode(',', $myProductsArray).')
                GROUP BY "ProductGroupID";
            ');
        }
        foreach ($rows as $row) {
            $parentIDs[$row['ProductGroupID']] = $row['ProductGroupID'];
        }
        //just in case
        unset($parentIDs[$this->ID]);
        if (!count($parentIDs)) {
            $parentIDs = array(0 => 0);
        }

        return ProductGroup::get()->filter(array('ID' => $parentIDs, 'ShowInSearch' => 1));
    }

    /**
     * This is the inverse of ProductGroupsFromAlsoShowProducts
     * That is, it list the product groups that a product is primarily listed under (exact parents only)
     * from a "AlsoShow" product List.
     *
     * @return DataList
     */
    public function ProductGroupsFromAlsoShowProductsInverse()
    {
        $alsoShowProductsArray = $this->AlsoShowProducts()
            ->filter($this->getUserSettingsOptionSQL('FILTER', $this->getMyUserPreferencesDefault('FILTER')))
            ->map('ID', 'ID')->toArray();
        $alsoShowProductsArray[0] = 0;
        $parentIDs = Product::get()->filter(array('ID' => $alsoShowProductsArray))->map('ParentID', 'ParentID')->toArray();
        //just in case
        unset($parentIDs[$this->ID]);
        if (! count($parentIDs)) {
            $parentIDs = array(0 => 0);
        }

        return ProductGroup::get()->filter(array('ID' => $parentIDs, 'ShowInMenus' => 1));
    }

    /**
     * given the products for this page,
     * retrieve the parent groups excluding the current one.
     *
     * @return DataList
     */
    public function ProductGroupsParentGroups()
    {
        $arrayOfIDs = $this->currentInitialProductsAsCachedArray($this->getMyUserPreferencesDefault('FILTER')) + array(0 => 0);
        $parentIDs = Product::get()->filter(array('ID' => $arrayOfIDs))->map('ParentID', 'ParentID')->toArray();
        //just in case
        unset($parentIDs[$this->ID]);
        if (! count($parentIDs)) {
            $parentIDs = array(0 => 0);
        }

        return ProductGroup::get()->filter(array('ID' => $parentIDs, 'ShowInSearch' => 1));
    }

    /**
     * returns stage as "" or "_Live".
     *
     * @return string
     */
    protected function getStage()
    {
        $stage = '';
        if (Versioned::current_stage() == 'Live') {
            $stage = '_Live';
        }

        return $stage;
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
        if ($this->ImageID) {
            if ($normalImage = Image::get()->exclude(array('ClassName' => 'Product_Image'))->byID($this->ImageID)) {
                $normalImage = $normalImage->newClassInstance('Product_Image');
                $normalImage->write();
            }
        }
    }

    /*****************************************************
     * CACHING
     *****************************************************/
    /**
     *
     * @return bool
     */
    public function AllowCaching()
    {
        return $this->allowCaching;
    }

    /**
     * keeps a cache of the common caching key element
     * @var string
     */
    private static $_product_group_cache_key_cache = null;

    /**
     *
     * @param string $name
     * @param string $filterKey
     *
     * @return string
     */
    public function cacheKey($cacheKey)
    {
        // $cacheKey = $key.'_'.$this->ID;
        // if (self::$_product_group_cache_key_cache === null) {
        //     self::$_product_group_cache_key_cache = "PR_"
        //         .strtotime(Product::get()->max('LastEdited')). "_"
        //         .Product::get()->count();
        //     self::$_product_group_cache_key_cache .= "PG_"
        //         .strtotime(ProductGroup::get()->max('LastEdited')). "_"
        //         .ProductGroup::get()->count();
        //     if (class_exists('ProductVariation')) {
        //         self::$_product_group_cache_key_cache .= "PV_"
        //           .strtotime(ProductVariation::get()->max('LastEdited')). "_"
        //           .ProductVariation::get()->count();
        //     }
        // }
        // $cacheKey .= self::$_product_group_cache_key_cache;

        return $cacheKey;
    }

    protected function cacheFactoryName()
    {
        return 'EcomPG_'.$this->ID;
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
            $cache = SS_Cache::factory($this->cacheFactoryName());
            $data = $cache->load($cacheKey);
            if (!$data) {
                return;
            }
            if( ! $cache->getOption('automatic_serialization')) {
                $data = @unserialize($data);
            }
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
            $cache = SS_Cache::factory($this->cacheFactoryName());
            if( ! $cache->getOption('automatic_serialization')) {
                $data = serialize($data);
            }
            $cache->save($data, $cacheKey);
            return true;
        }

        return false;
    }

    public function SearchResultsSessionVariable($isForGroups = false)
    {
        $idString = '_'.$this->ID;
        if ($isForGroups) {
            return Config::inst()->get('ProductSearchForm', 'product_session_variable').$idString;
        } else {
            return Config::inst()->get('ProductSearchForm', 'product_group_session_variable').$idString;
        }
    }

    /**
     * cache for result array.
     *
     * @var array
     */
    private static $_result_array = array();

    /**
     * @return array
     */
    public function searchResultsArrayFromSession()
    {
        if (! isset(self::$_result_array[$this->ID]) || self::$_result_array[$this->ID] === null) {
            self::$_result_array[$this->ID] = explode(',', Session::get($this->SearchResultsSessionVariable(false)));
        }
        if (! is_array(self::$_result_array[$this->ID]) || ! count(self::$_result_array[$this->ID])) {
            self::$_result_array[$this->ID] = array(0 => 0);
        }

        return self::$_result_array[$this->ID];
    }

    public function getNumberOfProducts()
    {
        return Product::get()->filter(array('ParentID' => $this->ID))->count();
    }
}

class ProductGroup_Controller extends Page_Controller
{
    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $allowed_actions = array(
        'debug' => 'ADMIN',
        'filterforgroup' => true,
        'ProductSearchForm' => true,
        'searchresults' => true,
        'resetfilter' => true,
    );

    /**
     * The original Title of this page before filters, etc...
     *
     * @var string
     */
    protected $originalTitle = '';

    /**
     * list of products that are going to be shown.
     *
     * @var DataList
     */
    protected $products = null;

    /**
     * Show all products on one page?
     *
     * @var bool
     */
    protected $showFullList = false;

    /**
     * The group filter that is applied to this page.
     *
     * @var ProductGroup
     */
    protected $filterForGroupObject = null;

    /**
     * Is this a product search?
     *
     * @var bool
     */
    protected $isSearchResults = false;

    /**
     * standard SS method.
     */
    public function init()
    {
        parent::init();
        $this->originalTitle = $this->Title;
        Requirements::themedCSS('ProductGroup', 'ecommerce');
        Requirements::themedCSS('ProductGroupPopUp', 'ecommerce');
        Requirements::javascript('ecommerce/javascript/EcomProducts.js');
        //we save data from get variables...
        $this->saveUserPreferences();
    }

    /****************************************************
     *  ACTIONS
    /****************************************************/

    /**
     * standard selection of products.
     */
    public function index()
    {
        //set the filter and the sort...
        $this->addSecondaryTitle();
        $this->products = $this->paginateList($this->ProductsShowable(null));
        if ($this->returnAjaxifiedProductList()) {
            return $this->renderWith('AjaxProductList');
        }

        return array();
    }

    /**
     * cross filter with another product group..
     *
     * e.g. socks (current product group) for brand A or B (the secondary product group)
     *
     * @param HTTPRequest
     */
    public function filterforgroup($request)
    {
        $this->resetfilter();
        $otherGroupURLSegment = Convert::raw2sql($request->param('ID'));
        $arrayOfIDs = array(0 => 0);
        if ($otherGroupURLSegment) {
            $otherProductGroup = ProductGroup::get()->filter(array('URLSegment' => $otherGroupURLSegment))->first();
            if ($otherProductGroup) {
                $this->filterForGroupObject = $otherProductGroup;
                $arrayOfIDs = $otherProductGroup->currentInitialProductsAsCachedArray($this->getMyUserPreferencesDefault('FILTER'));
            }
        }
        $this->addSecondaryTitle();
        $this->products = $this->paginateList($this->ProductsShowable(array('ID' => $arrayOfIDs)));
        if ($this->returnAjaxifiedProductList()) {
            return $this->renderWith('AjaxProductList');
        }

        return array();
    }

    /**
     * get the search results.
     *
     * @param HTTPRequest
     */
    public function searchresults($request)
    {
        $this->resetfilter();
        $this->isSearchResults = true;
        //reset filter and sort
        $resultArray = $this->searchResultsArrayFromSession();
        if (!$resultArray || !count($resultArray)) {
            $resultArray = array(0 => 0);
        }
        $defaultKeySort = $this->getMyUserPreferencesDefault('SORT');
        $myKeySort = $this->getCurrentUserPreferences('SORT');
        $searchArray = null;
        if ($defaultKeySort == $myKeySort) {
            $searchArray = $resultArray;
        }
        $this->addSecondaryTitle();
        $this->products = $this->paginateList($this->ProductsShowable(array('ID' => $resultArray), $searchArray));

        return array();
    }

    /**
     * resets the filter only.
     */
    public function resetfilter()
    {
        $defaultKey = $this->getMyUserPreferencesDefault('FILTER');
        $filterGetVariable = $this->getSortFilterDisplayNames('FILTER', 'getVariable');
        $this->saveUserPreferences(
            array(
                $filterGetVariable => $defaultKey,
            )
        );

        return array();
    }

    /****************************************************
     *  TEMPLATE METHODS PRODUCTS
    /****************************************************/

    /**
     * Return the products for this group.
     * This is the call that is made from the template...
     * The actual final products being shown.
     *
     * @return PaginatedList
     **/
    public function Products()
    {
        //IMPORTANT!
        //two universal actions!
        $this->addSecondaryTitle();
        $this->cachingRelatedJavascript();

        //save products to session for later use
        $stringOfIDs = '';
        $array = $this->getProductsThatCanBePurchasedArray();
        if (is_array($array)) {
            $stringOfIDs = implode(',', $array);
        }
        //save list for future use
        Session::set(EcommerceConfig::get('ProductGroup', 'session_name_for_product_array'), $stringOfIDs);

        return $this->products;
    }

    /**
     * you can overload this function of ProductGroup Extensions.
     *
     * @return bool
     */
    protected function returnAjaxifiedProductList()
    {
        return Director::is_ajax() ? true : false;
    }

    /**
     * is the product list cache-able?
     *
     * @return bool
     */
    public function ProductGroupListAreCacheable()
    {
        if ($this->productListsHTMLCanBeCached()) {
            //exception 1
            if ($this->IsSearchResults()) {
                return false;
            }
            //exception 2
            $currentOrder = ShoppingCart::current_order();
            if ($currentOrder->getHasAlternativeCurrency()) {
                return false;
            }
            //can be cached...
            return true;
        }

        return false;
    }

    /**
     * is the product list ajaxified.
     *
     * @return bool
     */
    public function ProductGroupListAreAjaxified()
    {
        return $this->IsSearchResults() ? false : true;
    }

    /**
     * Unique caching key for the product list...
     *
     * @return string | Null
     */
    public function ProductGroupListCachingKey()
    {
        if ($this->ProductGroupListAreCacheable()) {
            $displayKey = $this->getCurrentUserPreferences('DISPLAY');
            $filterKey = $this->getCurrentUserPreferences('FILTER');
            $filterForGroupKey = $this->filterForGroupObject ? $this->filterForGroupObject->ID : 0;
            $sortKey = $this->getCurrentUserPreferences('SORT');
            $pageStart = isset($_GET['start']) ? intval($_GET['start']) : 0;
            $isFullList = $this->IsShowFullList() ? 'Y' : 'N';

            $this->cacheKey(
                implode(
                    '_',
                    array(
                        $displayKey,
                        $filterKey,
                        $filterForGroupKey,
                        $sortKey,
                        $pageStart,
                        $isFullList,
                    )
                )
            );
        }

        return;
    }

    /**
     * adds Javascript to the page to make it work when products are cached.
     */
    public function CachingRelatedJavascript()
    {
        if ($this->ProductGroupListAreAjaxified()) {
            Requirements::customScript("
                    if(typeof EcomCartOptions === 'undefined') {
                        var EcomCartOptions = {};
                    }
                    EcomCartOptions.ajaxifyProductList = true;
                    EcomCartOptions.ajaxifiedListHolderSelector = '#".$this->AjaxDefinitions()->ProductListHolderID()."';
                    EcomCartOptions.ajaxifiedListAdjusterSelectors = '.".$this->AjaxDefinitions()->ProductListAjaxifiedLinkClassName()."';
                    EcomCartOptions.hiddenPageTitleID = '#".$this->AjaxDefinitions()->HiddenPageTitleID()."';
                ",
                'cachingRelatedJavascript_AJAXlist'
            );
        } else {
            Requirements::customScript("
                    if(typeof EcomCartOptions === 'undefined') {
                        var EcomCartOptions = {};
                    }
                    EcomCartOptions.ajaxifyProductList = false;
                ",
                'cachingRelatedJavascript_AJAXlist'
            );
        }
        $currentOrder = ShoppingCart::current_order();
        if ($currentOrder->TotalItems(true)) {
            $responseClass = EcommerceConfig::get('ShoppingCart', 'response_class');
            $obj = new $responseClass();
            $obj->setIncludeHeaders(false);
            $json = $obj->ReturnCartData();
            Requirements::customScript("
                    if(typeof EcomCartOptions === 'undefined') {
                        var EcomCartOptions = {};
                    }
                    EcomCartOptions.initialData= ".$json.";
                ",
                'cachingRelatedJavascript_JSON'
            );
        }
    }

    /**
     * you can overload this function of ProductGroup Extensions.
     *
     * @return bool
     */
    protected function productListsHTMLCanBeCached()
    {
        return Config::inst()->get('ProductGroup', 'actively_check_for_can_purchase') ? false : true;
    }

    /*****************************************************
     * DATALIST: totals, number per page, etc..
     *****************************************************/

    /**
     * returns the total numer of products (before pagination).
     *
     * @return bool
     **/
    public function TotalCountGreaterThanOne($greaterThan = 1)
    {
        return $this->TotalCount() > $greaterThan;
    }

    /**
     * have the ProductsShowable been limited.
     *
     * @return bool
     **/
    public function TotalCountGreaterThanMax()
    {
        return $this->RawCount() >  $this->TotalCount();
    }

    /****************************************************
     *  TEMPLATE METHODS MENUS AND SIDEBARS
    /****************************************************/

    /**
     * title without additions.
     *
     * @return string
     */
    public function OriginalTitle()
    {
        return $this->originalTitle;
    }
    /**
     * This method can be extended to show products in the side bar.
     */
    public function SidebarProducts()
    {
        return;
    }

    /**
     * returns child product groups for use in
     * 'in this section'. For example the vegetable Product Group
     * May have listed here: Carrot, Cabbage, etc...
     *
     * @return ArrayList (ProductGroups)
     */
    public function MenuChildGroups()
    {
        return $this->ChildGroups(2, '"ShowInMenus" = 1');
    }

    /**
     * After a search is conducted you may end up with a bunch
     * of recommended product groups. They will be returned here...
     * We sort the list in the order that it is provided.
     *
     * @return DataList | Null (ProductGroups)
     */
    public function SearchResultsChildGroups()
    {
        $groupArray = explode(',', Session::get($this->SearchResultsSessionVariable($isForGroup = true)));
        if (is_array($groupArray) && count($groupArray)) {
            $sortStatement = $this->createSortStatementFromIDArray($groupArray, 'ProductGroup');

            return ProductGroup::get()->filter(array('ID' => $groupArray, 'ShowInSearch' => 1))->sort($sortStatement);
        }

        return;
    }

    /****************************************************
     *  Search Form Related controllers
    /****************************************************/

    /**
     * returns a search form to search current products.
     *
     * @return ProductSearchForm object
     */
    public function ProductSearchForm()
    {
        $onlySearchTitle = $this->originalTitle;
        if ($this->dataRecord instanceof ProductGroupSearchPage) {
            if ($this->HasSearchResults()) {
                $onlySearchTitle = 'Last Search Results';
            }
        }
        $form = ProductSearchForm::create(
            $this,
            'ProductSearchForm',
            $onlySearchTitle,
            $this->currentInitialProducts(null, $this->getMyUserPreferencesDefault('FILTER'))
        );
        $filterGetVariable = $this->getSortFilterDisplayNames('FILTER', 'getVariable');
        $sortGetVariable = $this->getSortFilterDisplayNames('SORT', 'getVariable');
        $additionalGetParameters = $filterGetVariable.'='.$this->getMyUserPreferencesDefault('FILTER').'&'.
                                   $sortGetVariable.'='.$this->getMyUserPreferencesDefault('SORT');
        $form->setAdditionalGetParameters($additionalGetParameters);

        return $form;
    }

    /**
     * Does this page have any search results?
     * If search was carried out without returns
     * then it returns zero (false).
     *
     * @return int | false
     */
    public function HasSearchResults()
    {
        $resultArray = $this->searchResultsArrayFromSession();
        if ($resultArray) {
            $count = count($resultArray) - 1;

            return $count ? $count : 0;
        }

        return 0;
    }

    /**
     * Should the product search form be shown immediately?
     *
     * @return bool
     */
    public function ShowSearchFormImmediately()
    {
        if ($this->IsSearchResults()) {
            return true;
        }
        if ((!$this->products) || ($this->products && $this->products->count())) {
            return false;
        }

        return true;
    }

    /**
     * Show a search form on this page?
     *
     * @return bool
     */
    public function ShowSearchFormAtAll()
    {
        return true;
    }

    /**
     * Is the current page a display of search results.
     *
     * This does not mean that something is actively being search for,
     * it could also be just "showing the search results"
     *
     * @return bool
     */
    public function IsSearchResults()
    {
        return $this->isSearchResults;
    }

    /**
     * Is there something actively being searched for?
     *
     * This is different from IsSearchResults.
     *
     * @return bool
     */
    public function ActiveSearchTerm()
    {
        $data = Session::get(Config::inst()->get('ProductSearchForm', 'form_data_session_variable'));
        if (!empty($data['Keyword'])) {
            return $this->IsSearchResults();
        }
    }

    /****************************************************
     *  Filter / Sort / Display related controllers
    /****************************************************/

    /**
     * Do we show all products on one page?
     *
     * @return bool
     */
    public function ShowFiltersAndDisplayLinks()
    {
        if ($this->TotalCountGreaterThanOne()) {
            if ($this->HasFilters()) {
                return true;
            }
            if ($this->DisplayLinks()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Do we show the sort links.
     *
     * A bit arbitrary to say three,
     * but there is not much point to sort three or less products
     *
     * @return bool
     */
    public function ShowSortLinks($minimumCount = 3)
    {
        if ($this->TotalCountGreaterThanOne($minimumCount)) {
            return true;
        }

        return false;
    }

    /**
     * Is there a special filter operating at the moment?
     * Is the current filter the default one (return inverse!)?
     *
     * @return bool
     */
    public function HasFilter()
    {
        return $this->getCurrentUserPreferences('FILTER') != $this->getMyUserPreferencesDefault('FILTER')
        || $this->filterForGroupObject;
    }

    /**
     * Is there a special sort operating at the moment?
     * Is the current sort the default one (return inverse!)?
     *
     * @return bool
     */
    public function HasSort()
    {
        $sort = $this->getCurrentUserPreferences('SORT');
        if ($sort != $this->getMyUserPreferencesDefault('SORT')) {
            return true;
        }
    }

    /**
     * @return boolean
     */
    public function HasFilterOrSort()
    {
        return $this->HasFilter() || $this->HasSort();
    }

    /**
     * @return boolean
     */
    public function HasFilterOrSortFullList()
    {
        return $this->HasFilterOrSort() || $this->IsShowFullList();
    }

    /**
     * are filters available?
     * we check one at the time so that we do the least
     * amount of DB queries.
     *
     * @return bool
     */
    public function HasFilters()
    {
        $countFilters = $this->FilterLinks()->count();
        if ($countFilters > 1) {
            return true;
        }
        $countGroupFilters = $this->ProductGroupFilterLinks()->count();
        if ($countGroupFilters > 1) {
            return true;
        }
        if ($countFilters + $countGroupFilters > 1) {
            return true;
        }

        return false;
    }

    /**
     * Do we show all products on one page?
     *
     * @return bool
     */
    public function IsShowFullList()
    {
        return $this->showFullList;
    }

    /**
     * returns the current filter applied to the list
     * in a human readable string.
     *
     * @return string
     */
    public function CurrentDisplayTitle()
    {
        $displayKey = $this->getCurrentUserPreferences('DISPLAY');
        if ($displayKey != $this->getMyUserPreferencesDefault('DISPLAY')) {
            return $this->getUserPreferencesTitle('DISPLAY', $displayKey);
        }
    }

    /**
     * returns the current filter applied to the list
     * in a human readable string.
     *
     * @return string
     */
    public function CurrentFilterTitle()
    {
        $filterKey = $this->getCurrentUserPreferences('FILTER');
        $filters = array();
        if ($filterKey != $this->getMyUserPreferencesDefault('FILTER')) {
            $filters[] = $this->getUserPreferencesTitle('FILTER', $filterKey);
        }
        if ($this->filterForGroupObject) {
            $filters[] = $this->filterForGroupObject->MenuTitle;
        }
        if (count($filters)) {
            return implode(', ', $filters);
        }
    }

    /**
     * returns the current sort applied to the list
     * in a human readable string.
     *
     * @return string
     */
    public function CurrentSortTitle()
    {
        $sortKey = $this->getCurrentUserPreferences('SORT');
        if ($sortKey != $this->getMyUserPreferencesDefault('SORT')) {
            return $this->getUserPreferencesTitle('SORT', $sortKey);
        }
    }

    /**
     * short-cut for getMyUserPreferencesDefault("DISPLAY")
     * for use in templtes.
     *
     * @return string - key
     */
    public function MyDefaultDisplayStyle()
    {
        return $this->getMyUserPreferencesDefault('DISPLAY');
    }

    /**
     * Number of entries per page limited by total number of pages available...
     *
     * @return int
     */
    public function MaxNumberOfProductsPerPage()
    {
        return $this->MyNumberOfProductsPerPage() > $this->TotalCount() ? $this->TotalCount() : $this->MyNumberOfProductsPerPage();
    }

    /****************************************************
     *  TEMPLATE METHODS FILTER LINK
    /****************************************************/

    /**
     * Provides a ArrayList of links for filters products.
     *
     * @return ArrayList( ArrayData(Name, Link, SelectKey, Current (boolean), LinkingMode))
     */
    public function FilterLinks()
    {
        $cacheKey = 'FilterLinks_'.($this->filterForGroupObject ? $this->filterForGroupObject->ID : 0);
        if ($list = $this->retrieveObjectStore($cacheKey)) {
            //do nothing
        } else {
            $list = $this->userPreferencesLinks('FILTER');
            foreach ($list as $obj) {
                $key = $obj->SelectKey;
                if ($key != $this->getMyUserPreferencesDefault('FILTER')) {
                    $count = count($this->currentInitialProductsAsCachedArray($key));
                    if ($count == 0) {
                        $list->remove($obj);
                    } else {
                        $obj->Count = $count;
                    }
                }
            }
            $this->saveObjectStore($list, $cacheKey);
        }
        $selectedItem = $this->getCurrentUserPreferences('FILTER');
        foreach ($list as $obj) {
            $canHaveCurrent = true;
            if ($this->filterForGroupObject) {
                $canHaveCurrent = false;
            }
            $obj->Current = $selectedItem == $obj->SelectKey && $canHaveCurrent ? true : false;
            $obj->LinkingMode = $obj->Current ? 'current' : 'link';
            $obj->Ajaxify = true;
        }

        return $list;
    }

    /**
     * returns a list of items (with links).
     *
     * @return ArrayList( ArrayData(Name, FilterLink,  SelectKey, Current (boolean), LinkingMode))
     */
    public function ProductGroupFilterLinks()
    {

        if ($array = $this->retrieveObjectStore('ProductGroupFilterLinks')) {
            //do nothing
        } else {
            $arrayOfItems = array();

            $baseArray = $this->currentInitialProductsAsCachedArray($this->getMyUserPreferencesDefault('FILTER'));

            //also show
            $items = $this->ProductGroupsFromAlsoShowProducts();
            $arrayOfItems = array_merge($arrayOfItems, $this->productGroupFilterLinksCount($items, $baseArray, true));
            //also show inverse
            $items = $this->ProductGroupsFromAlsoShowProductsInverse();
            $arrayOfItems = array_merge($arrayOfItems, $this->productGroupFilterLinksCount($items, $baseArray, true));

            //parent groups
            $items = $this->ProductGroupsParentGroups();
            $arrayOfItems = array_merge($arrayOfItems, $this->productGroupFilterLinksCount($items, $baseArray, true));

            //child groups
            $items = $this->MenuChildGroups();
            $arrayOfItems = array_merge($arrayOfItems,  $this->productGroupFilterLinksCount($items, $baseArray, true));

            ksort($arrayOfItems);
            $array = array();
            foreach ($arrayOfItems as $arrayOfItem) {
                $array[] = $this->makeArrayItem($arrayOfItem);
            }
            $this->saveObjectStore($array, 'ProductGroupFilterLinks');
        }
        $arrayList = ArrayList::create();
        foreach($array as $item) {
            $arrayList->push(ArrayData::create($item));
        }
        return $arrayList;
    }

    /**
     * counts the total number in the combination....
     *
     * @param DataList $items     - list of
     * @param Arary    $baseArray - list of products on the current page
     *
     * @return array
     */
    protected function productGroupFilterLinksCount($items, $baseArray, $ajaxify = true)
    {
        $array = array();
        if ($items && $items->count()) {
            foreach ($items as $item) {
                $arrayOfIDs = $item->currentInitialProductsAsCachedArray($this->getMyUserPreferencesDefault('FILTER'));
                $newArray = array_intersect_key(
                    $arrayOfIDs,
                    $baseArray
                );
                $count = count($newArray);
                if ($count) {
                    $array[$item->Title] = array(
                        'Item' => $item,
                        'Count' => $count,
                        'Ajaxify' => $ajaxify,
                    );
                }
            }
        }

        return $array;
    }

    /**
     * @param array itemInArray (Item, Count, UserFilterAction)
     *
     * @return ArrayData
     */
    protected function makeArrayItem($itemInArray)
    {
        $item = $itemInArray['Item'];
        $count = $itemInArray['Count'];
        $ajaxify = $itemInArray['Ajaxify'];
        $filterForGroupObjectID = $this->filterForGroupObject ? $this->filterForGroupObject->ID : 0;
        $isCurrent = $item->ID == $filterForGroupObjectID;
        if ($ajaxify) {
            $link = $this->Link('filterforgroup/'.$item->URLSegment);
        } else {
            $link = $item->Link();
        }
        return array(
            'Title' => $item->Title,
            'Count' => $count,
            'SelectKey' => $item->URLSegment,
            'Current' => $isCurrent ? true : false,
            'MyLinkingMode' => $isCurrent ? 'current' : 'link',
            'FilterLink' => $link,
            'Ajaxify' => $ajaxify ? true : false,
        );
    }

    /**
     * Provides a ArrayList of links for sorting products.
     */
    public function SortLinks()
    {
        $list = $this->userPreferencesLinks('SORT');
        $selectedItem = $this->getCurrentUserPreferences('SORT');
        if ($list) {
            foreach ($list as $obj) {
                $obj->Current = $selectedItem == $obj->SelectKey ? true : false;
                $obj->LinkingMode = $obj->Current ? 'current' : 'link';
                $obj->Ajaxify = true;
            }

            return $list;
        }
    }

    /**
     * Provides a ArrayList for displaying display links.
     */
    public function DisplayLinks()
    {
        $list = $this->userPreferencesLinks('DISPLAY');
        $selectedItem = $this->getCurrentUserPreferences('DISPLAY');
        if ($list) {
            foreach ($list as $obj) {
                $obj->Current = $selectedItem == $obj->SelectKey ? true : false;
                $obj->LinkingMode = $obj->Current ? 'current' : 'link';
                $obj->Ajaxify = true;
            }

            return $list;
        }
    }

    /**
     * Link that returns a list of all the products
     * for this product group as a simple list.
     *
     * @return string
     */
    public function ListAllLink()
    {
        if ($this->filterForGroupObject) {
            return $this->Link('filterforgroup/'.$this->filterForGroupObject->URLSegment).'?showfulllist=1';
        } else {
            return $this->Link().'?showfulllist=1';
        }
    }

    /**
     * Link that returns a list of all the products
     * for this product group as a simple list.
     *
     * @return string
     */
    public function ListAFewLink()
    {
        return str_replace('?showfulllist=1', '', $this->ListAllLink());
    }

    /**
     * Link that returns a list of all the products
     * for this product group as a simple list.
     *
     * It resets everything - not just filter....
     *
     * @return string
     */
    public function ResetPreferencesLink($escapedAmpersands = true)
    {
        $ampersand = '&';
        if ($escapedAmpersands) {
            $ampersand = '&amp;';
        }
        $getVariableNameFilter = $this->getSortFilterDisplayNames('FILTER', 'getVariable');
        $getVariableNameSort = $this->getSortFilterDisplayNames('SORT', 'getVariable');

        return $this->Link().'?'.
            $getVariableNameFilter.'='.$this->getMyUserPreferencesDefault('FILTER').$ampersand.
            $getVariableNameSort.'='.$this->getMyUserPreferencesDefault('SORT').$ampersand.
            'reload=1';
    }

    /**
     * Link to the search results.
     *
     * @return string
     */
    public function SearchResultLink()
    {
        if ($this->HasSearchResults() && !$this->isSearchResults) {
            return $this->Link('searchresults');
        }
    }

    /****************************************************
     *  INTERNAL PROCESSING: PRODUCT LIST
    /****************************************************/

    /**
     * turns full list into paginated list.
     *
     * @param SS_List
     *
     * @return PaginatedList
     */
    protected function paginateList(SS_List $list)
    {
        if ($list && $list->count()) {
            if ($this->IsShowFullList()) {
                $obj = PaginatedList::create($list, $this->request);
                $obj->setPageLength(EcommerceConfig::get('ProductGroup', 'maximum_number_of_products_to_list') + 1);

                return $obj;
            } else {
                $obj = PaginatedList::create($list, $this->request);
                $obj->setPageLength($this->MyNumberOfProductsPerPage());

                return $obj;
            }
        }
    }

    /****************************************************
     *  INTERNAL PROCESSING: USER PREFERENCES
    /****************************************************/

    /**
     * Checks out a bunch of $_GET variables
     * that are used to work out user preferences
     * Some of these are saved to session.
     *
     * @param array $overrideArray - override $_GET variable settings
     */
    protected function saveUserPreferences($overrideArray = array())
    {

        //save sort - filter - display
        $sortFilterDisplayNames = $this->getSortFilterDisplayNames();
        foreach ($sortFilterDisplayNames as $type => $oneTypeArray) {
            $getVariableName = $oneTypeArray['getVariable'];
            $sessionName = $oneTypeArray['sessionName'];
            if (isset($overrideArray[$getVariableName])) {
                $newPreference = $overrideArray[$getVariableName];
            } else {
                $newPreference = $this->request->getVar($getVariableName);
            }
            if ($newPreference) {
                $optionsVariableName = $oneTypeArray['configName'];
                $options = EcommerceConfig::get($this->ClassName, $optionsVariableName);
                if (isset($options[$newPreference])) {
                    Session::set('ProductGroup_'.$sessionName, $newPreference);
                    //save in model as well...
                }
            } else {
                $newPreference = Session::get('ProductGroup_'.$sessionName);
            }
            //save data in model...
            $this->setCurrentUserPreference($type, $newPreference);
        }
        /* save URLSegments in model
        $this->setCurrentUserPreference(
            "URLSegments",
            array(
                "Action" => $this->request->param("Action"),
                "ID" => $this->request->param("ID")
            )
        );
        */

        //clearing data..
        if ($this->request->getVar('reload')) {
            //reset other session variables...
            Session::set($this->SearchResultsSessionVariable(false), '');
            Session::set($this->SearchResultsSessionVariable(true), '');

            return $this->redirect($this->Link());
        }

        //full list ....
        if ($this->request->getVar('showfulllist')) {
            $this->showFullList = true;
        }
    }

    /**
     * Checks for the most applicable user preferences for this user:
     * 1. session value
     * 2. getMyUserPreferencesDefault.
     *
     * @param string $type - FILTER | SORT | DISPLAY
     *
     * @return string
     *
     * @todo: move to controller?
     */
    protected function getCurrentUserPreferences($type)
    {
        $sessionName = $this->getSortFilterDisplayNames($type, 'sessionName');
        if ($sessionValue = Session::get('ProductGroup_'.$sessionName)) {
            $key = Convert::raw2sql($sessionValue);
        } else {
            $key = $this->getMyUserPreferencesDefault($type);
        }

        return $key;
    }

    /**
     * Provides a dataset of links for a particular user preference.
     *
     * @param string $type SORT | FILTER | DISPLAY - e.g. sort_options
     *
     * @return ArrayList( ArrayData(Name, Link,  SelectKey, Current (boolean), LinkingMode))
     */
    protected function userPreferencesLinks($type)
    {
        //get basics
        $sortFilterDisplayNames = $this->getSortFilterDisplayNames();
        $options = $this->getConfigOptions($type);

        //if there is only one option then do not bother
        if (count($options) < 2) {
            return;
        }

        //get more config names
        $translationCode = $sortFilterDisplayNames[$type]['translationCode'];
        $getVariableName = $sortFilterDisplayNames[$type]['getVariable'];
        $arrayList = ArrayList::create();
        if (count($options)) {
            foreach ($options as $key => $array) {
                //$isCurrent = ($key == $selectedItem) ? true : false;

                $link = '?'.$getVariableName."=$key";
                if ($type == 'FILTER') {
                    $link = $this->Link().$link;
                } else {
                    $link = $this->request->getVar('url').$link;
                }
                $arrayList->push(ArrayData::create(array(
                    'Name' => _t('ProductGroup.'.$translationCode.strtoupper(str_replace(' ', '', $array['Title'])), $array['Title']),
                    'Link' => $link,
                    'SelectKey' => $key,
                    //we add current at runtime, so we can store the object without current set...
                    //'Current' => $isCurrent,
                    //'LinkingMode' => $isCurrent ? "current" : "link"
                )));
            }
        }

        return $arrayList;
    }

    /****************************************************
     *  INTERNAL PROCESSING: TITLES
    /****************************************************/

    /**
     * variable to make sure secondary title only gets
     * added once.
     *
     * @var bool
     */
    protected $secondaryTitleHasBeenAdded = false;

    /**
     * add a secondary title to the main title
     * in case there is, for example, a filter applied
     * e.g. Socks | MyBrand.
     *
     * @param string
     */
    protected function addSecondaryTitle($secondaryTitle = '')
    {
        $pipe = _t('ProductGroup.TITLE_SEPARATOR', ' | ');
        if (! $this->secondaryTitleHasBeenAdded) {
            if (trim($secondaryTitle)) {
                $secondaryTitle = $pipe.$secondaryTitle;
            }
            if ($this->IsSearchResults()) {
                if ($array = $this->searchResultsArrayFromSession()) {
                    //we remove 1 item here, because the array starts with 0 => 0
                    $count = count($array) - 1;
                    if ($count > 3) {
                        $toAdd = $count. ' '._t('ProductGroup.PRODUCTS_FOUND', 'Products Found');
                        $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
                    }
                } else {
                    $toAdd = _t('ProductGroup.SEARCH_RESULTS', 'Search Results');
                    $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
                }
            }
            if (is_object($this->filterForGroupObject)) {
                $toAdd = $this->filterForGroupObject->Title;
                $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
            }
            if ($this->IsShowFullList()) {
                $toAdd = _t('ProductGroup.LIST_VIEW', 'List View');
                $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
            }
            $filter = $this->getCurrentUserPreferences('FILTER');
            if ($filter != $this->getMyUserPreferencesDefault('FILTER')) {
                $toAdd = $this->getUserPreferencesTitle('FILTER', $this->getCurrentUserPreferences('FILTER'));
                $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
            }
            if ($this->HasSort()) {
                $toAdd = $this->getUserPreferencesTitle('SORT', $this->getCurrentUserPreferences('SORT'));
                $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
            }
            if ($secondaryTitle) {
                $this->Title .= $secondaryTitle;
                if (isset($this->MetaTitle)) {
                    $this->MetaTitle .= $secondaryTitle;
                }
            }
            //dont update menu title, because the entry in the menu
            //should stay the same as it links back to the unfiltered
            //page (in some cases).
            $this->secondaryTitleHasBeenAdded = true;
        }
    }

    /**
     * removes any spaces from the 'toAdd' bit and adds the pipe if there is
     * anything to add at all.  Through the lang files, you can change the pipe
     * symbol to anything you like.
     *
     * @param  string $pipe
     * @param  string $toAdd
     * @return string
     */
    protected function cleanSecondaryTitleForAddition($pipe, $toAdd)
    {
        $toAdd = trim($toAdd);
        $length = strlen($toAdd);
        if ($length > 0) {
            $toAdd = $pipe.$toAdd;
        }
        return $toAdd;
    }

    /****************************************************
     *  DEBUG
    /****************************************************/

    public function debug()
    {
        $member = Member::currentUser();
        if (!$member || !$member->IsShopAdmin()) {
            $messages = array(
                'default' => 'You must login as an admin to use debug functions.',
            );
            Security::permissionFailure($this, $messages);
        }
        $this->ProductsShowable();
        $html = EcommerceTaskDebugCart::debug_object($this->dataRecord);
        $html .= '<ul>';

        $html .= '<li><hr /><h3>Available options</h3><hr /></li>';
        $html .= '<li><b>Sort Options for Dropdown:</b><pre> '.print_r($this->getUserPreferencesOptionsForDropdown('SORT'), 1).'</pre> </li>';
        $html .= '<li><b>Filter Options for Dropdown:</b><pre> '.print_r($this->getUserPreferencesOptionsForDropdown('FILTER'), 1).'</pre></li>';
        $html .= '<li><b>Display Styles for Dropdown:</b><pre> '.print_r($this->getUserPreferencesOptionsForDropdown('DISPLAY'), 1).'</pre> </li>';

        $html .= '<li><hr /><h3>Selection Setting (what is set as default for this page)</h3><hr /></li>';
        $html .= '<li><b>MyDefaultFilter:</b> '.$this->getMyUserPreferencesDefault('FILTER').' </li>';
        $html .= '<li><b>MyDefaultSortOrder:</b> '.$this->getMyUserPreferencesDefault('SORT').' </li>';
        $html .= '<li><b>MyDefaultDisplayStyle:</b> '.$this->getMyUserPreferencesDefault('DISPLAY').' </li>';
        $html .= '<li><b>MyNumberOfProductsPerPage:</b> '.$this->MyNumberOfProductsPerPage().' </li>';
        $html .= '<li><b>MyLevelOfProductsToshow:</b> '.$this->MyLevelOfProductsToShow().' = '.(isset($this->showProductLevels[$this->MyLevelOfProductsToShow()]) ? $this->showProductLevels[$this->MyLevelOfProductsToShow()] : 'ERROR!!!! $this->showProductLevels not set for '.$this->MyLevelOfProductsToShow()).' </li>';

        $html .= '<li><hr /><h3>Current Settings</h3><hr /></li>';
        $html .= '<li><b>Current Sort Order:</b> '.$this->getCurrentUserPreferences('SORT').' </li>';
        $html .= '<li><b>Current Filter:</b> '.$this->getCurrentUserPreferences('FILTER').' </li>';
        $html .= '<li><b>Current display style:</b> '.$this->getCurrentUserPreferences('DISPLAY').' </li>';

        $html .= '<li><hr /><h3>DATALIST: totals, numbers per page etc</h3><hr /></li>';
        $html .= '<li><b>Total number of products:</b> '.$this->TotalCount().' </li>';
        $html .= '<li><b>Is there more than one product:</b> '.($this->TotalCountGreaterThanOne() ? 'YES' : 'NO').' </li>';
        $html .= '<li><b>Number of products per page:</b> '.$this->MyNumberOfProductsPerPage().' </li>';

        $html .= '<li><hr /><h3>SQL Factors</h3><hr /></li>';
        $html .= '<li><b>Default sort SQL:</b> '.print_r($this->getUserSettingsOptionSQL('SORT'), 1).' </li>';
        $html .= '<li><b>User sort SQL:</b> '.print_r($this->getUserSettingsOptionSQL('SORT',  $this->getCurrentUserPreferences('SORT')), 1).' </li>';
        $html .= '<li><b>Default Filter SQL:</b> <pre>'.print_r($this->getUserSettingsOptionSQL('FILTER'), 1).'</pre> </li>';
        $html .= '<li><b>User Filter SQL:</b> <pre>'.print_r($this->getUserSettingsOptionSQL('FILTER',  $this->getCurrentUserPreferences('FILTER')), 1).'</pre> </li>';
        $html .= '<li><b>Buyable Class name:</b> '.$this->getBuyableClassName().' </li>';
        $html .= '<li><b>allProducts:</b> '.print_r(str_replace('"', '`', $this->allProducts->sql()), 1).' </li>';

        $html .= '<li><hr /><h3>Search</h3><hr /></li>';
        $resultArray = $this->searchResultsArrayFromSession();
        $productGroupArray = explode(',', Session::get($this->SearchResultsSessionVariable(true)));
        $html .= '<li><b>Is Search Results:</b> '.($this->IsSearchResults() ? 'YES' : 'NO').' </li>';
        $html .= '<li><b>Products In Search (session variable : '.$this->SearchResultsSessionVariable(false).'):</b> '.print_r($resultArray, 1).' </li>';
        $html .= '<li><b>Product Groups In Search (session variable : '.$this->SearchResultsSessionVariable(true).'):</b> '.print_r($productGroupArray, 1).' </li>';

        $html .= '<li><hr /><h3>Other</h3><hr /></li>';
        if ($image = $this->BestAvailableImage()) {
            $html .= '<li><b>Best Available Image:</b> <img src="'.$image->Link.'" /> </li>';
        }
        $html .= '<li><b>BestAvailableImage:</b> '.($this->BestAvailableImage() ? $this->BestAvailableImage()->Link : 'no image available').' </li>';
        $html .= '<li><b>Is this an ecommerce page:</b> '.($this->IsEcommercePage() ? 'YES' : 'NO').' </li>';
        $html .= '<li><hr /><h3>Related Groups</h3><hr /></li>';
        $html .= '<li><b>Parent product group:</b> '.($this->ParentGroup() ? $this->ParentGroup()->Title : '[NO PARENT GROUP]').'</li>';

        $childGroups = $this->ChildGroups(99);
        if ($childGroups->count()) {
            $childGroups = $childGroups->map('ID', 'MenuTitle');
            $html .= '<li><b>Child Groups (all):</b><pre> '.print_r($childGroups, 1).' </pre></li>';
        } else {
            $html .= '<li><b>Child Groups (full tree): </b>NONE</li>';
        }
        $html .= '<li><b>a list of Product Groups that have the products for the CURRENT product group listed as part of their AlsoShowProducts list:</b><pre>'.print_r($this->ProductGroupsFromAlsoShowProducts()->map('ID', 'Title')->toArray(), 1).' </pre></li>';
        $html .= '<li><b>the inverse of ProductGroupsFromAlsoShowProducts:</b><pre> '.print_r($this->ProductGroupsFromAlsoShowProductsInverse()->map('ID', 'Title')->toArray(), 1).' </pre></li>';
        $html .= '<li><b>all product parent groups:</b><pre> '.print_r($this->ProductGroupsParentGroups()->map('ID', 'Title')->toArray(), 1).' </pre></li>';

        $html .= '<li><hr /><h3>Product Example and Links</h3><hr /></li>';
        $product = Product::get()->filter(array('ParentID' => $this->ID))->first();
        if ($product) {
            $html .= '<li><b>Product View:</b> <a href="'.$product->Link().'">'.$product->Title.'</a> </li>';
            $html .= '<li><b>Product Debug:</b> <a href="'.$product->Link('debug').'">'.$product->Title.'</a> </li>';
            $html .= '<li><b>Product Admin Page:</b> <a href="'.'/admin/pages/edit/show/'.$product->ID.'">'.$product->Title.'</a> </li>';
            $html .= '<li><b>ProductGroup Admin Page:</b> <a href="'.'/admin/pages/edit/show/'.$this->ID.'">'.$this->Title.'</a> </li>';
        } else {
            $html .= '<li>this page has no products of its own</li>';
        }
        $html .= '</ul>';

        return $html;
    }
}
