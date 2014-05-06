<?php
 /**
  * Product Group is a 'holder' for Products within the CMS
  * It contains functions for versioning child products
  *
  * The way the products are selected:
  *
  * Controller calls:
  * ProductGroup::ProductsShowable($extraFilter = "")
  *
  * ProductsShowable runs currentInitialProducts.  This selects ALL the applicable products
  * but it does NOT PAGINATE (limit) or SORT them.
  * After that, it calls currentFinalProducts, this sorts the products and notes the total
  * count of products.
  *
  *
  *
  * For each product page, there is a default:
  *  - filter
  *  - sort
  *  - number of levels to show (e.g. children, grand-children, etc...)
  * and these settings can be changed in the CMS, depending on what the
  * developer makes available to the content editor.
  *
  * In extending the ProductGroup class, it is recommended
  * that you override the following methods:
  * - getBuyableClassName
  * - getStandardFilter
  * - getGroupFilter
  * - getGroupJoin
  * - getExcludedProducts
  *
  *
  *
  * @authors: Nicolaas [at] Sunny Side Up .co.nz
  * @package: ecommerce
  * @sub-package: Pages
  * @inspiration: Silverstripe Ltd, Jeremy
  **/


class ProductGroup extends Page {

	/**
	 * standard SS variable
	 * @static Array
	 *
	 */
	private static $db = array(
		"NumberOfProductsPerPage" => "Int",
		"LevelOfProductsToShow" => "Int",
		"DefaultSortOrder" => "Varchar(20)",
		"DefaultFilter" => "Varchar(20)",
		"DisplayStyle" => "Varchar(20)"
	);

	/**
	 * standard SS variable
	 * @static Array
	 *
	 */
	private static $has_one = array(
		'Image' => 'Product_Image'
	);

	/**
	 * standard SS variable
	 * @static Array
	 *
	 */
	private static $belongs_many_many = array(
		'AlsoShowProducts' => 'Product'
	);

	/**
	 * standard SS variable
	 * @static Array
	 *
	 */
	private static $defaults = array(
		"DefaultSortOrder" => "default",
		"DefaultFilter" => "default",
		"DisplayStyle" => "default",
		"LevelOfProductsToShow" => 99
	);

	/**
	 * standard SS variable
	 * @static Array
	 *
	 */
	private static $indexes = array(
		"LevelOfProductsToShow" => true,
		"DefaultSortOrder" => true,
		"DefaultFilter" => true,
		"DisplayStyle" => true
	);

	/**
	 * standard SS variable
	 * @static String
	 */
	private static $default_child = 'Product';

	/**
	 * standard SS variable
	 * @static String | Array
	 *
	 */
	private static $icon = 'ecommerce/images/icons/productgroup';

	/**
	 * Standard SS variable.
	 */
	private static $singular_name = "Product Category";
		function i18n_singular_name() { return _t("ProductGroup.SINGULARNAME", "Product Category");}

	/**
	 * Standard SS variable.
	 */
	private static $plural_name = "Product Categories";
		function i18n_plural_name() { return _t("ProductGroup.PLURALNAME", "Product Categories");}

	/**
	 * Standard SS variable.
	 * @var String
	 */
	private static $description = "A page the shows a bunch of products, based on your selection. By default it shows products linked to it (children)";

	/**
	 * Shop Admins can edit
	 * @param Member $member
	 * @return Boolean
	 */
	function canEdit($member = null) {
		if(is_a(Controller::curr(), Object::getCustomClass("ProductsAndGroupsModelAdmin"))) {
			return false;
		}
		if(!$member) {
			$member = Member::currentUser();
		}
		if($member && $member->IsShopAdmin()) {
			return true;
		}
		return parent::canEdit($member);
	}

	/**
	 * Shop Admins can edit
	 * @param Member $member
	 * @return Boolean
	 */
	function canView($member = null) {
		if(is_a(Controller::curr(), Object::getCustomClass("ProductsAndGroupsModelAdmin"))) {
			return true;
		}
		return parent::canView($member);
	}


	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {
		return $this->canEdit($member);
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canPublish($member = null) {
		return $this->canEdit($member);
	}

	/**
	 * Standard SS method
	 * //check if it is in a current cart?
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDeleteFromLive($member = null) {
		return $this->canEdit($member);
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canCreate($member = null) {
		return $this->canEdit($member);
	}

	/**
	 * list of sort / filter / display variables
	 *
	 * @var Array
	 */
	protected $sortFilterDisplayNames = array(
		"SORT" => array(
			"value" => "default",
			"configName" => "sort_options",
			"sessionName" => "session_name_for_sort_preference",
			"dbFieldName" => "DefaultSortOrder",
			"translationCode" => "SORT_BY"
		),
		"FILTER" => array(
			"value" => "default",
			"configName" => "filter_options",
			"sessionName" => "session_name_for_filter_preference",
			"dbFieldName" => "DefaultFilter",
			"translationCode" => "FILTER_FOR"
		),
		"DISPLAY" => array(
			"value" => "default",
			"configName" => "display_styles",
			"sessionName" => "session_name_for_display_style_preference",
			"dbFieldName" => "DisplayStyle",
			"translationCode" => "DISPLAY_STYLE"
		)
	);

	/**
	 * returns the full sortFilterDisplayNames set or a subset
	 * by either type (e.g. FILER) or variable (e.g dbFieldName)
	 *
	 * @param String $typeOfVariableName
	 *
	 * @return Array
	 */
	public function getSortFilterDisplayNames($typeOrVariable = "") {
		$data = array();
		if(isset($this->sortFilterDisplayNames[$typeOrVariable])) {
			$data = $this->sortFilterDisplayNames[$typeOrVariable];
		}
		elseif($typeOrVariable) {
			foreach($this->sortFilterDisplayNames as $group) {
				$data[] = $group[$typeOrVariable];
			}
		}
		else {
			$data = $this->sortFilterDisplayNames;
		}
		return $data;
	}

	/**
	 * sets a user preference.  This is typically used by the controller
	 * to set filter and sort.
	 *
	 * @param String $type SORT | FILTER | DISPLAY
	 * @param String $value
	 *
	 */
	public function setCurrentUserPreference($type, $value) {
		$this->sortFilterDisplayNames[$type]["value"] = $value;
	}

	/**
	 * get a user preference.
	 *
	 * @param String $type SORT | FILTER | DISPLAY
	 *
	 * @return String
	 */
	protected function getCurrentUserPreferences($type) {
		return $this->sortFilterDisplayNames[$type]["value"];
	}


	/*********************
	 * SETTINGS: Default Key
	 *********************/

	/**
	 * returns the default value
	 *
	 * @param String $type - FILTER | SORT | DISPLAY
	 *
	 * @return String
	 */
	public function getUserPreferencesDefault($type) {
		$configName = $this->sortFilterDisplayNames[$type]["configName"];
		$options = EcommerceConfig::get("ProductGroup", $configName);
		if(isset($options["default"])) {
			return "default";
		}
		user_error("It is recommended that you have a default (key) option for $type", E_USER_NOTICE);
		$keys = array_keys($options);
		return $keys[0];
	}

	/*********************
	 * SETTINGS: Dropdowns
	 *********************/


	/**
	 * SORT:
	 * returns an array of Key => Title for sort options
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
	 * @param String $type - FILTER | SORT | DISPLAY
	 *
	 * @return Array
	 */
	public function getUserPreferencesOptionsForDropdown($type){
		$configName = $this->sortFilterDisplayNames[$type]["configName"];
		$options = EcommerceConfig::get("ProductGroup", $configName);
		$inheritTitle = _t("ProductGroup.INHERIT", "Inherit");
		$array = array("inherit" => $inheritTitle);
		if(is_array($options) && count($options)) {
			foreach($options as $key => $option) {
				if(is_array($option)) {
					$array[$key] = $option["Title"];
				}
				else {
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
	 * @param String $key
	 * @return Array (e.g. Array(MyField => "ASC", "MyOtherField" => "DESC")
	 *
	 * FILTER:
	 * Returns the sql associated with a filter option.
	 *
	 * @param String $type - FILTER | SORT | DISPLAY
	 * @param String $key - the options selected
	 *
	 * @return Array (e.g. array("MyField" => 1, "MyOtherField" => 0)) OR STRING!!!!
	 */
	protected function getUserSettingsOptionSQL($type, $key = ""){
		$configName = $this->sortFilterDisplayNames[$type]["configName"];
		$options = EcommerceConfig::get("ProductGroup", $configName);
		//if we cant find the current one, use the default
		if(!$key || (!isset($options[$key]))) {
			$key = $this->getUserPreferencesDefault($type);
		}
		if($key) {
			return $options[$key]["SQL"];
		}
		else {
			if($type == "FILTER") {
				return array("Sort" => "ASC");
			}
			elseif($type == "SORT") {
				return array("ShowInSearch" => 1);
			}
		}
	}



	/*********************
	 * SETTINGS: Title
	 *********************/


	/**
	 * Returns the Title for a type key.
	 * If no key is provided then the default key is used.
	 * @param String $type - FILTER | SORT | DISPLAY
	 * @param String $key
	 *
	 * @return String
	 */
	public function getUserPreferencesTitle($type, $key = "") {
		$configName = $this->sortFilterDisplayNames[$type]["configName"];
		$options = EcommerceConfig::get("ProductGroup", $configName);
		if(!$key || (!isset($filterOptions[$key]))){
			$key = $this->getUserPreferencesDefault($type);
		}
		if($key && isset($options[$key]["Title"])) {
			return $options[$key]["Title"];
		}
		else {
			return _t("ProductGroup.UNKNOWN", "UNKNOWN USER SETTING");
		}
	}

	/*********************
	 * SETTINGS: default codes
	 *********************/

	 /**
	  *
	  *
	  * @return Int
	  */
	public function MyLevelOfProductsToShow(){
		if($this->LevelOfProductsToShow == 0) {
			if($parent = $this->ParentGroup()) {
				$this->LevelOfProductsToShow = $parent->MyLevelOfProductsToShow();
			}
		}
		//reset to default
		if($this->LevelOfProductsToShow	 == 0) {
			$defaults = Config::inst()->get("ProductGroup", "defaults");
			return isset($defaults["LevelOfProductsToShow"]) ? $defaults["LevelOfProductsToShow"] : 99;
		}
		return $this->LevelOfProductsToShow ;
	}

	/**
	 * Checks for the most applicable user preferences for this page:
	 * 1. what is saved in Database for this page.
	 * 2. what the parent product group has saved in the database
	 * 3. what the standard default is
	 *
	 * @param String $type - FILTER | SORT | DISPLAY
	 *
	 * @return String
	 */
	public function getMyUserPreferencesDefault($type){
		$configName = $this->sortFilterDisplayNames[$type]["configName"];
		$dbVariableName = $this->sortFilterDisplayNames[$type]["dbFieldName"];
		$defaultOption = "";
		$options = EcommerceConfig::get("ProductGroup", $configName);
		if($this->$dbVariableName && array_key_exists($this->$dbVariableName, $options)) {
			$defaultOption = $this->$dbVariableName;
		}
		if(!$defaultOption && $parent = $this->ParentGroup()) {
			$defaultOption = $parent->getMyUserPreferencesDefault($type);
		}
		if(!$defaultOption || $defaultOption == "inherit") {
			$defaultOption = $this->getUserPreferencesDefault($type);
		}
		return $defaultOption;
	}

	public function MyDefaultDisplayStyle() {
		return $this->getMyUserPreferencesDefault("DISPLAY");
	}

	/*********************
	 * SETTINGS: product levels
	 *********************/

	/**
	 * @var Array
	 * List of options to show products.
	 * With it, we provide a bunch of methods to access and edit the options.
	 * NOTE: we can not have an option that has a zero key ( 0 => "none"), as this does not work
	 * (as it is equal to not completed yet - not yet entered in the Database).
	 */
	protected $showProductLevels = array(
		99 => "All Child Products (default)",
	 -2 => "None",
	 -1 => "All products",
		1 => "Direct Child Products",
		2 => "Direct Child Products + Grand Child Products",
		3 => "Direct Child Products + Grand Child Products + Great Grand Child Products",
		4 => "Direct Child Products + Grand Child Products + Great Grand Child Products + Great Great Grand Child Products",
	);



	/*********************
	 * CMS Fields
	 *********************/


	/**
	 * standard SS method
	 * @return FieldList
	 */
	public function getCMSActions() {
		$fields = parent::getCMSActions();
		if(!$this->canEdit()) {
			$editButton = FormAction::create('editinsitetree');
			$editButton->setTitle(_t("Product.EDIT", "Edit"));
			$editButton->setDescription(_t("Product.EDIT_IN_SITETREE", "Edit this record in the site tree"));
			$fields->push($editButton);
		}
		return $fields;
	}

	/**
	 * standard SS method
	 * @return FieldList
	 */
	function getCMSFields() {
		$fields = parent::getCMSFields();
		//dirty hack to show images!
		$fields->addFieldToTab('Root.Images', new Product_ProductImageUploadField('Image', _t('Product.IMAGE', 'Product Group Image')));
		//number of products
		$numberOfProductsPerPageExplanation = $this->MyNumberOfProductsPerPage() != $this->NumberOfProductsPerPage ? _t("ProductGroup.CURRENTLVALUE", " - current value: ").$this->MyNumberOfProductsPerPage()." "._t("ProductGroup.INHERITEDFROMPARENTSPAGE", " (inherited from parent page because the current page is set to zero)") : "";
		$fields->addFieldToTab(
			'Root',
			new Tab(
				'ProductDisplay',
				new DropdownField("LevelOfProductsToShow", _t("ProductGroup.PRODUCTSTOSHOW", "Products to show ..."), $this->showProductLevels),
				new HeaderField("WhatProductsAreShown", _t("ProductGroup.WHATPRODUCTSSHOWN", _t("ProductGroup.OPTIONSSELECTEDBELOWAPPLYTOCHILDGROUPS", "Inherited options"))),
				new NumericField("NumberOfProductsPerPage", _t("ProductGroup.PRODUCTSPERPAGE", "Number of products per page").$numberOfProductsPerPageExplanation)
			)
		);
		//sort
		$sortDropdownList = $this->getUserPreferencesOptionsForDropdown("SORT");
		if(count($sortDropdownList) > 1) {
			$sortOrderKey = $this->getMyUserPreferencesDefault("SORT");
			if($this->DefaultSortOrder == "inherit") {
				$actualValue = " (".(isset($sortDropdownList[$sortOrderKey]) ? $sortDropdownList[$sortOrderKey] : _t("ProductGroup.ERROR", "ERROR")).")";
				$sortDropdownList["inherit"] = _t("ProductGroup.INHERIT", "Inherit").$actualValue;
			}
			$fields->addFieldToTab(
				"Root.ProductDisplay",
				new DropdownField("DefaultSortOrder", _t("ProductGroup.DEFAULTSORTORDER", "Default Sort Order"), $sortDropdownList)
			);
		}
		//filter
		$filterDropdownList = $this->getUserPreferencesOptionsForDropdown("FILTER");
		if(count($filterDropdownList) > 1) {
			$filterKey = $this->getMyUserPreferencesDefault("FILTER");
			if($this->DefaultFilter == "inherit") {
				$actualValue = " (".(isset($filterDropdownList[$filterKey]) ? $filterDropdownList[$filterKey] : _t("ProductGroup.ERROR", "ERROR")).")";
				$filterDropdownList["inherit"] = _t("ProductGroup.INHERIT", "Inherit").$actualValue;
			}
			$fields->addFieldToTab(
				"Root.ProductDisplay",
				new DropdownField("DefaultFilter", _t("ProductGroup.DEFAULTFILTER", "Default Filter"), $filterDropdownList)
			);
		}
		//display style
		$displayStyleDropdownList = $this->getUserPreferencesOptionsForDropdown("DISPLAY");
		if(count($displayStyleDropdownList) > 1) {
			$displayStyleKey = $this->getMyUserPreferencesDefault("DISPLAY");
			if($this->DisplayStyle == "inherit") {
				$actualValue = " (".(isset($displayStyleDropdownList[$displayStyleKey]) ? $displayStyleDropdownList[$displayStyleKey] : _t("ProductGroup.ERROR", "ERROR")).")";
				$displayStyleDropdownList["inherit"] = _t("ProductGroup.INHERIT", "Inherit").$actualValue;
			}
			$fields->addFieldToTab(
				"Root.ProductDisplay",
				new DropdownField("DisplayStyle", _t("ProductGroup.DEFAULTDISPLAYSTYLE", "Default Display Style"), $displayStyleDropdownList)
			);
		}
		if($this->EcomConfig()->ProductsAlsoInOtherGroups) {
			$fields->addFieldsToTab(
				'Root.OtherProductsShown',
				array(
					new HeaderField('ProductGroupsHeader', _t('ProductGroup.OTHERPRODUCTSTOSHOW', 'Other products to show ...')),
					$this->getProductGroupsTable()
				)
			);
		}
		return $fields;
	}

	/**
	 * Used in getCSMFields
	 * @return GridField
	 **/
	protected function getProductGroupsTable() {
		$gridFieldConfig = GridFieldConfig_RelationEditor::create();
		return new GridField(
			"AlsoShowProducts",
			_t("ProductGroup.OTHERPRODUCTSSHOWINTHISGROUP", "Other products shown in this group ..."),
			$this->AlsoShowProducts(),
			$gridFieldConfig
		);
	}


	/*****************************************************
	 *
	 * START OF THE DATA LIST SYSTEM: select the products to show
	 * (see top of this file for more information)
	 *
	 *****************************************************/

	/**
	 * This is the dataList that contains all the products
	 * @var DataList $allProducts
	 */
	protected $allProducts = null;


	/**
	 * a list of relevant buyables that can
	 * not be purchased and therefore should be excluded.
	 * Should be set to NULL to start with so we know if it has been
	 * set yet.
	 * @var NULL | Array (like so: array(1,2,4,5,99))
	 */
	private $canNOTbePurchasedArray = null;


	/**
	 * a list of relevant buyables that can
	 * be purchased.  We keep this so that
	 * that we can save to session, etc... for future use.
	 * Should be set to NULL to start with so we know if it has been
	 * set yet.
	 * @var NULL | Array (like so: array(1,2,4,5,99))
	 */
	private $canBePurchasedArray = null;


	public function getProductsThatCanBePurchasedArray(){
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
	 * @param array | string $extraFilter Additional SQL filters to apply to the Product retrieval
	 * @return DataList | Null
	 */
	public function ProductsShowable($extraFilter = '', $alternativeSort = ''){

		//get original products without sort
		$this->allProducts = $this->currentInitialProducts($extraFilter);

		//sort products
		$this->allProducts = $this->currentFinalProducts($alternativeSort);

		return $this->allProducts;
	}

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
	 * @param array | string $extraFilter Additional SQL filters to apply to the Product retrieval
	 * @return DataList
	 **/
	public function currentInitialProducts($extraFilter = ''){
		if(!$this->allProducts) {
			$className = $this->getBuyableClassName();

			//INIT ALLPRODUCTS
			$this->allProducts = $className::get();

			// STANDARD FILTER
			$this->getStandardFilter();


			// EXTRA FILTER
			if(is_array($extraFilter) && count($extraFilter)) {
				$this->allProducts = $this->allProducts->filter($extraFilter);
			}
			elseif(is_string($extraFilter) && strlen($extraFilter) > 2) {
				$this->allProducts = $this->allProducts->where($extraFilter);
			}

			// GROUP FILTER
			$this->allProducts = $this->getGroupFilter();

			//JOINS
			$this->allProducts = $this->getGroupJoin();

			//EXLUDES ONE THAT ARE NOT FOR SALE
			//we just add a little safety measure here...
			//$this->allProducts = $this->getExcludedProducts();
		}
		return $this->allProducts;
	}

	/**
	 * returns the final products, based on the all the eligile products
	 * for the page.
	 *
	 * All of the 'current' methods are to support the currentFinalProducts Method.
	 *
	 * @param String $alternativeSort = Alternative Sort String
	 * @return DataList
	 **/
	protected function currentFinalProducts($alternativeSort = ""){
		if($this->allProducts) {
			if($alternativeSort) {
				$sort = $alternativeSort;
			}
			else {
				$sort = $this->currentSortSQL();
			}
			$this->allProducts = $this->allProducts->Sort($sort);
			$this->allProducts = $this->removeExcludedProductsAndSaveIncludedProducts($this->allProducts);
			$this->totalCount = $this->allProducts->count();
			return $this->allProducts;
		}
	}

	/*****************************************************
	 * DATALIST: adjusters
	 * these are the methods you want to override in
	 * any clases that extend ProductGroup
	 *****************************************************/

	/**
	 * Do products occur in more than one group
	 * @return Boolean
	 */
	protected function getProductsAlsoInOtherGroups(){
		return $this->EcomConfig()->ProductsAlsoInOtherGroups;
	}

	/**
	 * Returns the class we are working with
	 * @return String
	 */
	protected function getBuyableClassName(){
		return EcommerceConfig::get("ProductGroup", "base_buyable_class");
	}


	/**
	 * adjust the allProducts, based on the $_GET or default entry.
	 * The standard filter excludes the product group filter.
	 * The default would be something like "ShowInSearch = 1"
	 * IMPORTANT: Adjusts allProducts and returns it...
	 * @return DataList
	 */
	protected function getStandardFilter(){
		$filterKey = $this->getCurrentUserPreferences("FILTER");
		$filter = $this->getUserSettingsOptionSQL("FILTER", $filterKey);
		if(is_array($filter)) {
			$this->allProducts = $this->allProducts->Filter($filter);
		}
		elseif(is_string($filter) && strlen($filter) > 2) {
			$this->allProducts = $this->allProducts->Where($filter);
		}
		return $this->allProducts;
	}

	/**
	 * works out the group filter based on the LevelOfProductsToShow value
	 * it also considers the other group many-many relationship
	 * this filter ALWAYS returns something: 1 = 1 if nothing else.
	 * IMPORTANT: Adjusts allProducts and returns it...
	 * @return DataList
	 */
	protected function getGroupFilter(){
		$groupFilter = "";
		$levelToShow = $this->MyLevelOfProductsToShow();
		//special cases
		if($levelToShow < 0) {
			//no produts but if LevelOfProductsToShow = -1 then show all
			$groupFilter = " (".$levelToShow." = -1) " ;
		}
		elseif($levelToShow > 0) {
			$groupIDs = array($this->ID => $this->ID);
			$groupFilter .= $this->getProductsToBeIncludedFromOtherGroups();
			$childGroups = $this->ChildGroups($levelToShow);
			if($childGroups && $childGroups->count()) {
				foreach($childGroups as $childGroup) {
					$groupIDs[$childGroup->ID] = $childGroup->ID;
					$groupFilter .= $childGroup->getProductsToBeIncludedFromOtherGroups();
				}
			}
			$groupFilter = " ( \"ParentID\" IN (".implode(",", $groupIDs).") ) ".$groupFilter;
		}
		else {
			//fall-back
			$groupFilter = "ParentID < 0";
		}
		$this->allProducts = $this->allProducts->where($groupFilter);
		return $this->allProducts;
	}

	/**
	 * If products are show in more than one group
	 * Then this returns a where phrase for any products that are linked to this
	 * product group
	 *
	 * @return String
	 */
	protected function getProductsToBeIncludedFromOtherGroups() {
		//TO DO: this should actually return
		//Product.ID = IN ARRAY(bla bla)
		$array = array();
		if($this->getProductsAlsoInOtherGroups()) {
			$array = $this->AlsoShowProducts()->map("ID", "ID")->toArray();
		}
		if(count($array)) {
			$stage = '';
			//@to do - make sure products are versioned!
			if(Versioned::current_stage() == "Live") {
				$stage = "_Live";
			}
			return " OR (\"Product$stage\".\"ID\" IN (".implode(",", $array).")) ";
		}
		return "";
	}

	/**
	 * Join statement for the product groups.
	 * IMPORTANT: Adjusts allProducts and returns it...
	 * @return DataList
	 */
	protected function getGroupJoin() {
		return $this->allProducts;
	}


	/**
	 * returns the SORT part of the final selection of products.
	 * @return String | Array
	 */
	protected function currentSortSQL() {
		$sortKey = $this->getCurrentUserPreferences("SORT");
		return $this->getUserSettingsOptionSQL("SORT", $sortKey);
	}

	/**
	 * Excluded products that can not be purchased
	 * We all make a record of all the products that are in the current list
	 * For efficiency sake, we do both these things at the same time.
	 * IMPORTANT: Adjusts allProducts and returns it...
	 * @return DataList
	 */
	protected function removeExcludedProductsAndSaveIncludedProducts() {
		$onlyShowProductsThatCanBePurchased = $this->EcomConfig()->OnlyShowProductsThatCanBePurchased;
		if( is_array($this->canBePurchasedArray) && is_array($this->canNOTbePurchasedArray)) {
			//already done!
		}
		else {
			$this->canNOTbePurchasedArray = array();
			$this->canBePurchasedArray = array();
			$rawCount = $this->allProducts->count();
			if($rawCount > 500) {
				$sql = $this->allProducts->sql();
				$rows = DB::query($sql);
				foreach($rows as  $row) {
					$this->canBePurchasedArray[$row["ID"]] = $row["ID"];
				}
			}
			elseif($onlyShowProductsThatCanBePurchased) {
				foreach($this->allProducts as $buyable) {
					if(!$buyable->canPurchase()) {
						$this->canNOTbePurchasedArray[$buyable->ID] = $buyable->ID;
					}
					else {
						$this->canBePurchasedArray[$buyable->ID] = $buyable->ID;
					}
				}
			}
			else {
				$this->canBePurchasedArray = $this->allProducts->map("ID", "ID")->toArray();
			}
			if(count($this->canNOTbePurchasedArray)) {
				$this->allProducts = $this->allProducts->Exclude(array("ID" => $this->canNOTbePurchasedArray));
			}
		}
		return $this->allProducts;
	}

	/**
	 * returns the CLASSNAME part of the final selection of products.
	 * @return String
	 */
	protected function currentWhereSQL() {
		Deprecation::notice('3.1', "No longer in use");
	}

	/**
	 * returns the CLASSNAME part of the final selection of products.
	 * @return String
	 */
	protected function currentClassNameSQL() {
		Deprecation::notice('3.1', 'Use the "ProductGroup.getBuyableClassName" instead');
	}

	/**
	 * returns the JOIN part of the final selection of products.
	 * @return String
	 */
	protected function currentJoinSQL() {
		Deprecation::notice('3.1', "No longer in use");
	}

	/*****************************************************
	 * DATALIST: totals, number per page, etc..
	 *****************************************************/

	/**
	 * returns the total numer of products (before pagination)
	 * @return Integer
	 **/
	public function TotalCount() {
		return $this->totalCount ? $this->totalCount : 0;
	}

	/**
	 * returns the total numer of products (before pagination)
	 * @return Boolean
	 **/
	public function TotalCountGreaterThanOne($greaterThan = 1) {
		return $this->totalCount > $greaterThan;
	}

	/**
	 *@return Integer
	 **/
	public function ProductsPerPage() {return $this->MyNumberOfProductsPerPage();}
	public function MyNumberOfProductsPerPage() {
		$productsPagePage = 0;
		if($this->NumberOfProductsPerPage) {
			$productsPagePage = $this->NumberOfProductsPerPage;
		}
		else {
			if($parent = $this->ParentGroup()) {
				$productsPagePage = $parent->MyNumberOfProductsPerPage();
			}
			else {
				$productsPagePage = $this->EcomConfig()->NumberOfProductsPerPage;
			}
		}
		return $productsPagePage;
	}



	/*****************************************************
	 * Children and Parents
	 *****************************************************/

	/**
	 * Returns children ProductGroup pages of this group.
	 *
	 * @param Int $maxRecursiveLevel - maximum depth , e.g. 1 = one level down - so no Child Groups are returned...
	 * @param String $filter - additional filter to be added
	 * @param Int $numberOfRecursions - current level of depth
	 * @return ArrayList (ProductGroups)
	 */
	function ChildGroups($maxRecursiveLevel, $filter = "", $numberOfRecursions = 0) {
		$arrayList = new ArrayList();
		$numberOfRecursions++;
		if($numberOfRecursions < $maxRecursiveLevel){
			$filterWithAND = '';
			if($filter) {
				$filterWithAND = " AND $filter";
			}
			$where = "\"ParentID\" = '$this->ID' $filterWithAND";
			$children = ProductGroup::get()->where($where);
			if($children->count()){
				foreach($children as $child){
					$arrayList->push($child);
					$arrayList->merge($child->ChildGroups($maxRecursiveLevel, $filter, $numberOfRecursions));
				}
			}
		}
		if(!$arrayList instanceOf ArrayList) {
			user_error("We expect an array list as output");
		}
		return $arrayList;
	}

	/**
	 * Deprecated method
	 */
	function ChildGroupsBackup($maxRecursiveLevel, $filter = "") {
		Deprecation::notice('3.1', "No longer in use");
		if($maxRecursiveLevel > 24) {
			$maxRecursiveLevel = 24;
		}

		$stage = '';
		//@to do - make sure products are versioned!
		if(Versioned::current_stage() == "Live") {
			$stage = "_Live";
		}
		$select = "P1.ID as ID1 ";
		$from = "ProductGroup$stage as P1 ";
		$join = " INNER JOIN SiteTree$stage AS S1 ON P1.ID = S1.ID";
		$where = "1 = 1";
		$ids = array(-1);
		for($i = 1; $i < $maxRecursiveLevel; $i++) {
			$j = $i + 1;
			$select .= ", P$j.ID AS ID$j, S$j.ParentID";
			$join .= "
				LEFT JOIN ProductGroup$stage AS P$j ON P$j.ID = S$i.ParentID
				LEFT JOIN SiteTree$stage AS S$j ON P$j.ID = S$j.ID
			";
		}
		$rows = DB::Query(" SELECT ".$select." FROM ".$from.$join." WHERE ".$where);
		if($rows) {
			foreach($rows as $row) {
				for($i = 1; $i < $maxRecursiveLevel; $i++) {
					if($row["ID".$i]) {
						$ids[$row["ID".$i]] = $row["ID".$i];
					}
				}
			}
		}
		return ProductGroup::get()->where("\"ProductGroup$stage\".\"ID\" IN (".implode(",", $ids).")".$filterWithAND);
	}

	/**
	 * returns the parent page, but only if it is an instance of Product Group.
	 * @return DataObject | Null (ProductGroup)
	 **/
	function ParentGroup() {
		if($this->ParentID) {
			return ProductGroup::get()->byID($this->ParentID);
		}
	}



	/*****************************************************
	 * Other Stuff
	 *****************************************************/

	/**
	 * Recursively generate a product menu.
	 * @param String $filter
	 * @return ArrayList (ProductGroups)
	 */
	function GroupsMenu($filter = "ShowInMenus = 1") {
		if($parent = $this->ParentGroup()) {
			return is_a($parent, Object::getCustomClass("ProductGroup")) ? $parent->GroupsMenu() : $this->ChildGroups($filter);
		}
		else {
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
	 * @return Image | Null
	 */
	public function BestAvailableImage() {
		$image = $this->Image();
		if($image && $image->exists()) {
			return $image;
		}
		elseif($parent = $this->ParentGroup()) {
			return $parent->BestAvailableImage();
		}
	}

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
	public function ProductGroupsFromAlsoShowProducts() {
		$myProductsArray = $this->currentInitialProducts()->map("ID", "ID")->toArray()+array(0);
		$rows = DB::query("
			SELECT \"ProductGroupID\" FROM \"Product_ProductGroups\" WHERE \"ProductID\" IN (".implode(",", $myProductsArray).") GROUP BY \"ProductGroupID\";
		");
		$selectArray = array(0);
		foreach($rows as $row) {
			$selectArray[$row["ProductGroupID"]] = $row["ProductGroupID"];
		}
		//just in case
		unset($selectArray[$this->ID]);
		//HACK - we put SiteTree here because it did not seem to work to put ProductGroup
		return SiteTree::get()->filter(array("ID" => $selectArray));
	}

	/**
	 * This is the inverse of ProductGroupsFromAlsoShowProducts
	 * That is, it list the product groups that a product is usually listed under (exact parents only)
	 * from a "AlsoShow" product List.
	 *
	 * @return DataList
	 */
	public function ProductGroupsFromAlsoShowProductsInverse() {
		$alsoShowProductsArray = array(0 => 0) + $this->AlsoShowProducts()->map("ID", "ID")->toArray();
		$parentIDs = Product::get()->filter(array("ID" => $alsoShowProductsArray))->map("ParentID", "ParentID")->toArray();
		//HACK - we put SiteTree here because it did not seem to work to put ProductGroup
		return SiteTree::get()->filter(array("ID" => $parentIDs));
	}

	/**
	 * tells us if the current page is part of e-commerce.
	 * @return Boolean
	 */
	public function IsEcommercePage() {
		return true;
	}


	function onAfterWrite() {
		parent::onAfterWrite();
		if($this->ImageID) {
			if($normalImage = Image::get()->exclude(array("ClassName" => "Product_Image"))->byID($this->ImageID)) {
				$normalImage->ClassName = "Product_Image";
				$normalImage->write();
			}
		}
	}

}



class ProductGroup_Controller extends Page_Controller {

	private static $allowed_actions = array(
		"debug" => "ADMIN",
		"filterforgroup" => true,
		"ProductSearchForm" => true,
		"searchresults" => true
	);

	/**
	 *
	 * @var DataList
	 */
	protected $products = null;

	/**
	 * Show all products on one page?
	 * @var Boolean
	 */
	protected $showFullList = false;

	/**
	 * The group filter that is applied to this page
	 * @var ProductGroup
	 */
	protected $filterForGroupObject = null;

	/**
	 * standard SS method
	 */
	function init() {
		parent::init();
		Requirements::themedCSS('Products', 'ecommerce');
		Requirements::themedCSS('ProductGroup', 'ecommerce');
		Requirements::themedCSS('ProductGroupPopUp', 'ecommerce');
		Requirements::javascript('ecommerce/javascript/EcomProducts.js');
		Requirements::javascript('ecommerce/javascript/EcomQuantityField.js');
		if(isset($_GET) && is_array($_GET) && count($_GET)) {
			$this->saveUserPreferences();
		}
		$this->setCurrentUserPreference("FILTER", $this->getCurrentUserPreferences("FILTER"));
		$this->setCurrentUserPreference("SORT", $this->getCurrentUserPreferences("SORT"));
		//$this->setCurrentUserPreference("DISPLAY", $this->getCurrentUserPreferences("DISPLAY"));
	}



	/****************************************************
	 *  ACTIONS
	/****************************************************/

	/**
	 *
	 * standard selection of products
	 */
	public function index(){
		$this->products = $this->paginateList($this->ProductsShowable(""));
		return array();
	}

	/**
	 *
	 * cross filter with another product group..
	 *
	 * e.g. socks (current product group) for brand A or B (the secondary product group)
	 * @param HTTPRequest
	 */
	public function filterforgroup($request){
		$otherGroupURLSegment = Convert::raw2sql($request->param("ID"));
		$arrayOfIDs = array();
		if($otherGroupURLSegment) {
			$otherProductGroup = ProductGroup::get()->filter(array("URLSegment" => $otherGroupURLSegment))->first();
			if($otherProductGroup) {
				$this->filterForGroupObject = $otherProductGroup;
				$arrayOfIDs = $otherProductGroup->ProductsShowable()->map("ID", "ID")->toArray();
			}
		}
		$this->products = $this->paginateList($this->ProductsShowable(array("ID" => $arrayOfIDs)));
		return array();
	}

	public function searchresults($request){
		$results = $request->getVar("results");
		if(!$results) {
			$results = "0";
		}
		$resultsArray = explode(",", $results);
		$ifStatement = 'CASE ';
		$arrayOfIDs = array();
		$count = 0;
		$stage = '';
		if(Versioned::current_stage() == "Live") {
			$stage = "_Live";
		}
		foreach($resultsArray as $productID) {
			$productID = intval($productID);
			if($productID) {
				$arrayOfIDs[] = $productID;
				$ifStatement .= " WHEN \"Product".$stage."\".\"ID\" = $productID THEN $count";
				$count++;
			}
		}
		$className = $this->getBuyableClassName();
		$sortStatement = $ifStatement." END";
		$products = $className::get()->filter(array("ID" => $arrayOfIDs))->sort($sortStatement);
		$this->products = $this->paginateList(
			$products
		);
		return Array();
	}

	/****************************************************
	 *  TEMPLATE METHODS PRODUCTS
	/****************************************************/


	/**
	 * Return the products for this group.
	 *
	 * @return PaginatedList
	 **/
	public function Products(){
		$this->addSecondaryTitle();
		//save products to session for later use
		$stringOfIDs = "";
		$array = $this->getProductsThatCanBePurchasedArray();
		if(is_array($array)) {
			$stringOfIDs = implode(",", $array);
		}
		//save list for future use
		Session::set(EcommerceConfig::get("ProductGroup", "session_name_for_product_array"), $stringOfIDs);

		return $this->products;
	}


	/****************************************************
	 *  TEMPLATE METHODS MENUS AND SIDEBARS
	/****************************************************/

	/**
	 *
	 * This method can be extended to show products in the side bar.
	 * @return Null | DataList
	 */
	public function SidebarProducts(){
		return null;
	}

	/**
	 * returns child product groups for use in
	 * 'in this section'. For example the vegetable Product Group
	 * May have listed here: Carrot, Cabbage, etc...
	 * @return ArrayList (ProductGroups)
	 */
	public function MenuChildGroups() {
		return $this->ChildGroups(2, "\"ShowInMenus\" = 1");
	}



	/****************************************************
	 *  TEMPLATE METHODS DISPLAY
	/****************************************************/



	/**
	 * returns a search form to search current products
	 *
	 * @return ProductSearchForm object
	 */
	function ProductSearchForm() {
		$form = ProductSearchForm::create($this, 'ProductSearchForm', $this->Title, $this->ProductsShowable());
		return $form;
	}


	function ShowSearchFormImmediately(){
		return !$this->products->count() || $this->request->param("Action") == "searchresults";
	}

	/**
	 * Do we show all products on one page?
	 *
	 * @return Boolean
	 */
	public function HasFilter(){
		return $this->getCurrentUserPreferences("FILTER") != "default";
	}

	/**
	 * Do we show all products on one page?
	 *
	 * @return Boolean
	 */
	public function IsShowFullList(){
		return $this->showFullList;
	}

	/**
	 * Number of entries per page limited by total number of pages available...
	 *
	 * @return Int
	 */
	public function MaxNumberOfProductsPerPage(){
		return $this->MyNumberOfProductsPerPage() > $this->TotalCount() ? $this->TotalCount() : $this->MyNumberOfProductsPerPage();
	}


	/****************************************************
	 *  TEMPLATE METHODS LINKS
	/****************************************************/

	/**
	 *
	 *
	 * @return Boolean
	 */
	public function HasFilters(){
		return $this->FilterLinks()->count() || $this->ProductGroupsFromAlsoShowProductsLinks()->count();
	}

	/**
	 *
	 * returns a list of items (with links)
	 * @return ArrayList( ArrayData(Name, FilterLink,  SelectKey, Current (boolean), LinkingMode))
	 */
	public function ProductGroupsFromAlsoShowProductsLinks() {
		$dos = new ArrayList();
		$items = $this->ProductGroupsFromAlsoShowProducts();
		$filterForGroupObjectID = $this->filterForGroupObject ? $this->filterForGroupObject->ID : 0;
		if($items->count()) {
			foreach($items as $item){
				$isCurrent = $item->ID == $filterForGroupObjectID;
				$item->Name = $item->Title;
				$item->SelectKey = $item->URLSegment;
				$item->Current = $isCurrent ? true : false;
				$item->MyLinkingMode = $isCurrent ? "current" : "link";
				$item->FilterLink = $this->Link("filterforgroup/".$item->URLSegment);
				$dos->push(clone $item);
			}
		}
		$items = $this->ProductGroupsFromAlsoShowProductsInverse();
		$filterForGroupObjectID = $this->filterForGroupObject ? $this->filterForGroupObject->ID : 0;
		if($items->count()) {
			foreach($items as $item){
				$isCurrent = $item->ID == $filterForGroupObjectID;
				$item->Name = $item->Title;
				$item->SelectKey = $item->URLSegment;
				$item->Current = $isCurrent ? true : false;
				$item->MyLinkingMode = $isCurrent ? "current" : "link";
				$item->FilterLink = $this->Link("filterforgroup/".$item->URLSegment);
				$dos->push(clone $item);
			}
		}
		return $dos;
	}

	/**
	 * Provides a ArrayList of links for sorting products.
	 *
	 * @return ArrayList( ArrayData(Name, Link, SelectKey, Current (boolean), LinkingMode))
	 */
	public function SortLinks(){
		return $this->userPreferencesLinks("SORT");
	}

	/**
	 * Provides a ArrayList of links for filters products.
	 *
	 * @return ArrayList( ArrayData(Name, Link, SelectKey, Current (boolean), LinkingMode))
	 */
	public function FilterLinks(){
		return $this->userPreferencesLinks("FILTER");
	}

	/**
	 * Provides a ArrayList for displaying display links
	 *
	 * @return ArrayList( ArrayData(Name, Link,  SelectKey, Current (boolean), LinkingMode))
	 */
	public function DisplayLinks(){
		return $this->userPreferencesLinks("DISPLAY");
	}

	/**
	 * Link that returns a list of all the products
	 * for this product group as a simple list.
	 *
	 * @return String
	 */
	public function ListAllLink() {
		return $this->Link()."?showfulllist=1";
	}

	/**
	 * Link that returns a list of all the products
	 * for this product group as a simple list.
	 *
	 * @return String
	 */
	public function ResetFilterLink() {
		$myPreferenceVariableName = EcommerceConfig::get("ProductGroup", "session_name_for_filter_preference");
		return $this->Link()."?".$myPreferenceVariableName."=default&reload=1";
	}

	/****************************************************
	 *  INTERNAL PROCESSING: PRODUCT LIST
	/****************************************************/

	/**
	 * turns full list into paginated list
	 * @param SS_List
	 * @return PaginatedList
	 */
	protected function paginateList(SS_List $list){
		if($list && $list->count()) {
			if($this->IsShowFullList()) {
				$obj = new PaginatedList($list, $this->request);
				$obj->setPageLength(3000);
				return $obj;
			}
			else {
				$obj = new PaginatedList($list, $this->request);
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
	 */
	protected function saveUserPreferences(){
		$sortFilterDisplayNames = $this->getSortFilterDisplayNames();
		$preferencesArray = array(
			"sort_options" => "session_name_for_sort_preference",
			"filter_options" => "session_name_for_filter_preference",
			"display_options" => "session_name_for_display_style_preference"
		);
		foreach($sortFilterDisplayNames as $type) {
			$optionsVariableName = $type["configName"];
			$preferenceVariableName = $type["sessionName"];
			$myPreferenceVariableName = EcommerceConfig::get("ProductGroup", $preferenceVariableName);
			if($newPreference = $this->request->getVar($myPreferenceVariableName)) {
				$options = EcommerceConfig::get("ProductGroup", $optionsVariableName);
				if(isset($options[$newPreference])) {
					Session::set("ProductGroup_".$myPreferenceVariableName, $newPreference);
				}
			}
		}
		//make sure to save the new settings first!
		if($this->request->getVar("reload")) {
			return $this->redirect($this->Link());
		}
		if($this->request->getVar("showfulllist")) {
			$this->showFullList = true;
		}
	}
	/**
	 * Checks for the most applicable user preferences for this user:
	 * 1. session value
	 * 2. getMyUserPreferencesDefault
	 *
	 * @param String $type - FILTER | SORT | DISPLAY
	 *
	 * @return String
	 *
	 * @todo: move to controller?
	 */
	protected function getCurrentUserPreferences($type){
		$sessionName = $this->sortFilterDisplayNames[$type]["sessionName"];
		if($sessionValue = Session::get("ProductGroup_".EcommerceConfig::get("ProductGroup", $sessionName))) {
			$key = Convert::raw2sql($sessionValue);
		}
		else {
			$key = $this->getMyUserPreferencesDefault($type);
		}
		return $key;
	}

	/**
	 * Provides a dataset of links for a particular user preference
	 *
	 * @param String $type SORT | FILTER | DISPLAY - e.g. sort_options
	 *
	 * @return ArrayList( ArrayData(Name, Link,  SelectKey, Current (boolean), LinkingMode))
	 */
	protected function userPreferencesLinks($type){
		//get basics
		$sortFilterDisplayNames = $this->getSortFilterDisplayNames();
		$configName = $sortFilterDisplayNames[$type]["configName"];
		$options = EcommerceConfig::get("ProductGroup", $configName);

		//if there is only one option then do not bother
		if(count($options) < 2) return null;

		//get more config names
		$translationCode = $sortFilterDisplayNames[$type]["translationCode"];
		$sessionVariableName = $sortFilterDisplayNames[$type]["sessionName"];
		$selectedItem =  $this->getCurrentUserPreferences($type);
		if($this->filterForGroupObject && $configName == "filter_options") {
			$selectedItem = "";
		}
		$dos = new ArrayList();
		if(count($options)) {
			foreach($options as $key => $array){
				$isCurrent = ($key == $selectedItem) ? true : false;
				$linkGetVariable =  "?".EcommerceConfig::get("ProductGroup", $sessionVariableName)."=$key";
				if($key == "default") {
					$linkGetVariable .= "&amp;reload=1";
				}
				$dos->push(new ArrayData(array(
					'Name' => _t('ProductGroup.'.$translationCode.strtoupper(str_replace(' ','',$array['Title'])),$array['Title']),
					'Link' => $this->Link().$linkGetVariable,
					'SelectKey' => $key,
					'Current' => $isCurrent,
					'LinkingMode' => $isCurrent ? "current" : "link"
				)));
			}
		}
		return $dos;
	}



	/****************************************************
	 *  INTERNAL PROCESSING: TITLES
	/****************************************************/



	/**
	 *
	 * @var Boolean
	 */
	private $secondaryTitleHasBeenAdded = false;

	/**
	 * add a secondary title to the main title
	 * in case there is, for example, a filter applied
	 * e.g. Socks | MyBrand
	 *
	 * @param String
	 *
	 */
	protected function addSecondaryTitle($secondaryTitle = "") {
		$pipe = _t("ProductGroup.TITLE_SEPARATOR", " | ");
		if(!$this->secondaryTitleHasBeenAdded) {
			if($secondaryTitle) {
				$secondaryTitle = $pipe.$secondaryTitle;
			}
			if(is_object($this->filterForGroupObject)) {
				$secondaryTitle .= $pipe.$this->filterForGroupObject->Title;
			}
			if($this->IsShowFullList()) {
				$secondaryTitle .= $pipe._t("ProductGroup.LIST_VIEW", "List View");
			}
			$filter = $this->getCurrentUserPreferences("FILTER");
			if($filter != "default") {
				$secondaryTitle .= $pipe.$this->getUserPreferencesTitle("FILTER", $this->getCurrentUserPreferences("FILTER"));
			}
			$sort = $this->getCurrentUserPreferences("SORT");
			if($sort != "default") {
				$secondaryTitle .= $pipe.$this->getUserPreferencesTitle("SORT", $this->getCurrentUserPreferences("SORT"));
			}
			if($secondaryTitle) {
				$this->Title .= $secondaryTitle;
				if(isset($this->MetaTitle)) {
					$this->MetaTitle .= $secondaryTitle;
				}
			}
			//dont update menu title, because the entry in the menu
			//should stay the same as it links back to the unfiltered
			//page (in some cases).
			$this->secondaryTitleHasBeenAdded = true;
		}
	}



	/****************************************************
	 *  DEBUG
	/****************************************************/

	public function debug(){
		$member = Member::currentUser();
		if(!$member || !$member->IsShopAdmin()) {
			$messages = array(
				'default' => 'You must login as an admin to use debug functions.'
			);
			Security::permissionFailure($this, $messages);
		}
		$this->ProductsShowable();
		$html = EcommerceTaskDebugCart::debug_object($this->dataRecord);
		$html .= "<ul>";

		$html .= "<li><hr />Available options<hr /></li>";
		$html .= "<li><b>Sort Options for Dropdown:</b><pre> ".print_r($this->getUserPreferencesOptionsForDropdown("SORT"), 1)."</pre> </li>";
		$html .= "<li><b>Filter Options for Dropdown:</b><pre> ".print_r($this->getUserPreferencesOptionsForDropdown("FILTER"), 1)."</pre></li>";
		$html .= "<li><b>Display Styles for Dropdown:</b><pre> ".print_r($this->getUserPreferencesOptionsForDropdown("DISPLAY"), 1)."</pre> </li>";

		$html .= "<li><hr />Default Keys (what is the default for the site)<hr /></li>";
		$html .= "<li><b>Default Sort Key:</b> ".$this->getUserPreferencesDefault("SORT")." </li>";
		$html .= "<li><b>Default Filter Key:</b> ".$this->getUserPreferencesDefault("FILTER")." </li>";
		$html .= "<li><b>Default Display Style Key:</b> ".$this->getUserPreferencesDefault("DISPLAY")." </li>";

		$html .= "<li><hr />Selection Setting (what is set as default for this page)<hr /></li>";
		$html .= "<li><b>MyDefaultFilter:</b> ".$this->getMyUserPreferencesDefault("FILTER")." </li>";
		$html .= "<li><b>MyDefaultSortOrder:</b> ".$this->getMyUserPreferencesDefault("SORT")." </li>";
		$html .= "<li><b>MyDefaultDisplayStyle:</b> ".$this->getMyUserPreferencesDefault("DISPLAY")." </li>";
		$html .= "<li><b>MyNumberOfProductsPerPage:</b> ".$this->MyNumberOfProductsPerPage()." </li>";
		$html .= "<li><b>MyLevelOfProductsToshow:</b> ".$this->MyLevelOfProductsToShow()." = ".(isset($this->showProductLevels[$this->MyLevelOfProductsToShow()]) ? $this->showProductLevels[$this->MyLevelOfProductsToShow()] : "ERROR!!!! \$this->showProductLevels not set for ".$this->MyLevelOfProductsToShow())." </li>";

		$html .= "<li><hr />Current Settings<hr /></li>";
		$html .= "<li><b>Current Sort Order:</b> ".$this->getCurrentUserPreferences("SORT")." </li>";
		$html .= "<li><b>Current Filter:</b> ".$this->getCurrentUserPreferences("FILTER")." </li>";
		$html .= "<li><b>Current display style:</b> ".$this->getCurrentUserPreferences("DISPLAY")." </li>";

		$html .= "<li><hr />DATALIST: totals, numbers per page etc<hr /></li>";
		$html .= "<li><b>Total number of products:</b> ".$this->TotalCount()." </li>";
		$html .= "<li><b>Is there more than one product:</b> ".($this->TotalCountGreaterThanOne() ? "YES" : "NO")." </li>";
		$html .= "<li><b>Number of products per page:</b> ".$this->MyNumberOfProductsPerPage()." </li>";
		$html .= "<li><b>Parent product group:</b> ".($parentGroup = $this->ParentGroup() ? $parentGroup->Title : "[NO PARENT GROUP]")."</li>";


		$html .= "<li><hr />SQL Factors<hr /></li>";
		$html .= "<li><b>Default sort SQL:</b> ".print_r($this->getUserSettingsOptionSQL("SORT"), 1)." </li>";
		$html .= "<li><b>User sort SQL:</b> ".print_r($this->getUserSettingsOptionSQL("SORT",  $this->getCurrentUserPreferences("SORT")), 1)." </li>";
		$html .= "<li><b>Default Filter SQL:</b> <pre>".print_r($this->getUserSettingsOptionSQL("FILTER"), 1)."</pre> </li>";
		$html .= "<li><b>User Filter SQL:</b> <pre>".print_r($this->getUserSettingsOptionSQL("FILTER",  $this->getCurrentUserPreferences("FILTER")), 1)."</pre> </li>";
		$html .= "<li><b>Buyable Class name:</b> ".$this->getBuyableClassName()." </li>";
		$html .= "<li><b>allProducts:</b> ".print_r($this->allProducts->sql(), 1)." </li>";

		$html .= "<li><hr />Other<hr /></li>";
		if($image = $this->BestAvailableImage()) {
			$html .= "<li><b>Best Available Image:</b> <img src=\"".$image->Link."\" /> </li>";
		}
		$html .= "<li><b>a list of Product Groups that have the products for the CURRENT product group listed as part of their AlsoShowProducts list:</b> ".$this->ProductGroupsFromAlsoShowProducts()." </li>";
		$html .= "<li><b>the inverse of ProductGroupsFromAlsoShowProducts:</b> ".$this->ProductGroupsFromAlsoShowProductsInverse()." </li>";
		$html .= "<li><b>BestAvailableImage:</b> ".($this->BestAvailableImage() ? $this->BestAvailableImage()->Link : "no image available")." </li>";
		$html .= "<li><b>a list of Product Groups that have the products for the CURRENT product group listed as part of their AlsoShowProducts list:</b><pre>".print_r($this->ProductGroupsFromAlsoShowProducts()->map()->toArray(), 1)." </pre></li>";
		$html .= "<li><b>the inverse of ProductGroupsFromAlsoShowProducts:</b><pre> ".print_r($this->ProductGroupsFromAlsoShowProductsInverse()->map()->toArray(), 1)." </pre></li>";
		$html .= "<li><b>Is this an ecommerce page:</b> ".($this->IsEcommercePage() ? "YES" : "NO")." </li>";
		$product = Product::get()->filter(array("ParentID" => $this->ID))->first();
		if($product) {
			$html .= "<li><hr />Product Example<hr /></li>";
			$html .= "<li><b>Product View:</b> <a href=\"".$product->Link()."\">".$product->Title."</a> </li>";
			$html .= "<li><b>Product Debug:</b> <a href=\"".$product->Link("debug")."\">".$product->Title."</a> </li>";
			$html .= "<li><b>Product Admin Page:</b> <a href=\""."/admin/pages/edit/show/".$product->ID."\">".$product->Title."</a> </li>";
			$html .= "<li><b>ProductGroup Admin Page:</b> <a href=\""."/admin/pages/edit/show/".$this->ID."\">".$this->Title."</a> </li>";
		}
		$html .= "</ul>";
		return $html;
	}


}


