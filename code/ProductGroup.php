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
  * - getClassNameSQL
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




	/*********************
	 * SETTINGS: Default Key
	 *********************/

	/**
	 * returns the default Sort key.
	 * @return String
	 */
	protected function getDefaultSortKey(){
		return $this->getUserPreferencesDefault("sort_options");
	}


	/**
	 * Returns the default filter key. Carefully making sure it exists
	 * @return String
	 */
	protected function getDefaultFilterKey(){
		return $this->getUserPreferencesDefault("filter_options");
	}

	/**
	 * Returns the default Display style:
	 * @return String
	 */
	protected function getDefaultDisplayStyleKey(){
		return $this->getUserPreferencesDefault("display_styles");
	}

	/**
	 * returns the default value
	 *
	 * @return String
	 */
	private function getUserPreferencesDefault($configName) {
		$options = EcommerceConfig::get("ProductGroup", $configName);
		if(isset($options["default"])) {
			return "default";
		}
		$keys = array_keys($options);
		return $keys[0];
	}

	/*********************
	 * SETTINGS: Dropdowns
	 *********************/


	/**
	 * returns an array of Key => Title for sort options
	 *
	 * This list is also used in the controller so it must be public.
	 *
	 * @return Array
	 */
	protected function getSortOptionsForDropdown(){
		return $this->getUserPreferencesOptionsForDropdown("sort_options");
	}

	/**
	 * Returns options for the dropdown of filter options.
	 *
	 * This list is also used in the controller so it must be public.
	 *
	 * @return Array
	 */
	protected function getFilterOptionsForDropdown(){
		return $this->getUserPreferencesOptionsForDropdown("filter_options");
	}

	/**
	 * Returns the options for product display styles.
	 * In the configuration you can set which ones are available.
	 * If one is available then you must make sure that the corresponding template is available.
	 * For example, if the display style is
	 * MyTempalte => "All Details"
	 * Then you must make sure MyTemplate.ss exists.
	 *
	 * This list is also used in the controller so it must be public.
	 *
	 * @return Array
	 */
	public function getDisplayStylesForDropdown(){
		return $this->getUserPreferencesOptionsForDropdown("display_styles");
	}

	/**
	 *
	 * @return Array
	 */
	protected function getUserPreferencesOptionsForDropdown($configName){
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
	 * Returns the sort sql for a particular sorting key.
	 * If no key is provided then the default key will be returned.
	 * @param String $key
	 * @return Array (e.g. Array(MyField => "ASC", "MyOtherField" => "DESC")
	 */
	protected function getSortOptionSQL($key = ""){
		$sortOptions = EcommerceConfig::get("ProductGroup", "sort_options");
		if(!$key || (!isset($sortOptions[$key]))) {
			$key = $this->getDefaultSortKey();
		}
		if($key) {
			return $sortOptions[$key]["SQL"];
		}
		else {
			return array("Sort" => "ASC");
		}
	}

	/**
	 * Returns the sql associated with a filter option.
	 * @param String $key - the option selected
	 * @return Array (e.g. array("MyField" => 1, "MyOtherField" => 0)) OR STRING!!!!
	 */
	protected function getFilterOptionSQL($key = ""){
		$filterOptions = EcommerceConfig::get("ProductGroup", "filter_options");
		if(!$key || (!isset($filterOptions[$key]))){
			$key = $this->getDefaultFilterKey();
		}
		if($key) {
			return $filterOptions[$key]["SQL"];
		}
		else {
			return array("ShowInSearch" => 1);
		}
	}


	/*********************
	 * SETTINGS: Title
	 *********************/

	/**
	 * Returns the Title for a sorting key.
	 * If no key is provided then the default key is used.
	 * @param String $key
	 * @return String
	 */
	protected function getSortOptionTitle($key = ""){
		return $this->getUserPreferencesTitle("sort_options", $key);
	}


	/**
	 * The title for the selected filter option.
	 * @param String $key - the key for the selected filter option
	 * @return String
	 */
	protected function getFilterOptionTitle($key = ""){
		return $this->getUserPreferencesTitle("filter_options", $key);
	}


	/**
	 * The title for the selected display style option.
	 * @param String $key - the key for the selected filter option
	 * @return String
	 */
	protected function getDisplayStyleTitle($key = ""){
		return $this->getUserPreferencesTitle("filter_options", $key);
	}


	/**
	 *
	 * @return String
	 */
	private function getUserPreferencesTitle($configName, $key = "") {
		$filterOptions = EcommerceConfig::get("ProductGroup", $configName);
		if(!$key || (!isset($filterOptions[$key]))){
			$key = $this->getDefaultFilterKey();
		}
		if($key) {
			return $filterOptions[$key]["Title"];
		}
		else {
			return _t("ProductGroup.UNKNOWN", "UNKNOWN");
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
			return isset($defaults["LevelOfProductsToShow"]) && $defaults["LevelOfProductsToShow"] ? $defaults["LevelOfProductsToShow"] : 99;
		}
		return $this->LevelOfProductsToShow;
	}

	/**
	 * returns the CODE of the default sort order.
	 * @return String
	 **/
	public function MyDefaultSortOrder() {
		return $this->getMyUserPreferencesDefault("sort_options", "DefaultSort", "getDefaultSortKey");
	}

	/**
	 * returns the CODE of the default filter.
	 * @return String
	 **/
	function MyDefaultFilter() {
		return $this->getMyUserPreferencesDefault("filter_options", "DefaultFilter", "getDefaultFilterKey");
	}

	/**
	 * returns the CODE of the style for the display template for this page
	 * @return String
	 **/
	function MyDefaultDisplayStyle() {
		return $this->getMyUserPreferencesDefault("display_styles", "DisplayStyle", "getDefaultDisplayStyleKey");
	}

	/**
	 * Checks for the most applicable user preferences for this page:
	 * 1. what is saved in Database for this page.
	 * 2. what the parent product group has saved in the database
	 * 3. what the standard default is
	 *
	 * @return String
	 */
	protected function getMyUserPreferencesDefault($configName, $dbVariableName, $methodName){
		$defaultOption = "";
		$options = EcommerceConfig::get("ProductGroup", $configName);
		if($this->$dbVariableName && array_key_exists($this->$dbVariableName, $options)) {
			$defaultOption = $this->$dbVariableName;
		}
		if(!$defaultOption && $parent = $this->ParentGroup()) {
			$defaultOption = $parent->getMyUserPreferencesDefault($configName, $dbVariableName, $methodName);
		}
		if(!$defaultOption || $defaultOption == "inherit") {
			$defaultOption = $this->$methodName();
		}
		return $defaultOption;
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
		$sortDropdownList = $this->getSortOptionsForDropdown();
		if(count($sortDropdownList) > 1) {
			$sortOrderKey = $this->MyDefaultSortOrder();
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
		$filterDropdownList = $this->getFilterOptionsForDropdown();
		if(count($filterDropdownList) > 1) {
			$filterKey = $this->MyDefaultFilter();
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
		$displayStyleDropdownList = $this->getDisplayStylesForDropdown();
		if(count($displayStyleDropdownList) > 1) {
			$displayStyleKey = $this->MyDefaultDisplayStyle();
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
	 * This is the dataList that
	 * contains all the products: sorted
	 * @var DataList $sortedProducts
	 */
	protected $sortedProducts = null;


	/**
	 * a list of relevant buyables that can
	 * not be purchased and therefore should be excluded.
	 * @var Array (like so: array(1,2,4,5,99))
	 */
	private static $negative_can_purchase_array = null;


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
	public function ProductsShowable($extraFilter = ''){
		//get original products without sort / limit
		$this->allProducts = $this->currentInitialProducts($extraFilter);
		//sort products
		$this->currentFinalProducts();
		$stringOfIDs = "0";
		//save products to session for later use
		if($this->sortedProducts && $this->sortedProducts->count()) {
			$buyablesIDArray = $this->sortedProducts->map("ID", "ID")->toArray();
			if(is_array($buyablesIDArray) && count($buyablesIDArray)) {
				$stringOfIDs = implode(",", $buyablesIDArray);
			}
		}
		Session::set(EcommerceConfig::get("ProductGroup", "session_name_for_product_array"), $stringOfIDs);
		return $this->sortedProducts;
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
	 * NOTE: there is no sort for the initial retrieval
	 *
	 * @param array | string $extraFilter Additional SQL filters to apply to the Product retrieval
	 * @return DataList
	 **/
	protected function currentInitialProducts($extraFilter = ''){
		$className = $this->getClassNameSQL();

		//init allProducts
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
		$this->allProducts = $this->getExcludedProducts();

		return $this->allProducts;
	}


	/**
	 * returns the final products, based on the all the eligile products
	 * for the page.
	 *
	 * All of the 'current' methods are to support the currentFinalProducts Method.
	 *
	 * @return DataList
	 **/
	protected function currentFinalProducts(){
		if($this->allProducts) {
			if($this->totalCount = $this->allProducts->count()) {
				if($this->totalCount) {
					$this->sortedProducts = $this->allProducts->Sort($this->currentSortSQL());
					return $this->sortedProducts;
				}
			}
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
	protected function getClassNameSQL(){
		return "Product";
	}


	/**
	 * adjust the allProducts, based on the $_GET or default entry.
	 * The standard filter excludes the product group filter.
	 * The default would be something like "ShowInSearch = 1"
	 * IMPORTANT: Adjusts allProducts and returns it...
	 * @return DataList
	 */
	protected function getStandardFilter(){
		if($sessionFilters = Session::get("ProductGroup_".EcommerceConfig::get("ProductGroup", "session_name_for_filter_preference"))) {
			$filterKey = Convert::raw2sqL($sessionFilters);
		}
		else {
			$filterKey = $this->MyDefaultFilter();
		}
		$filter = $this->getFilterOptionSQL($filterKey);
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
	 * @return String
	 */
	protected function currentSortSQL() {
		if($sessionSort = Session::get("ProductGroup_".EcommerceConfig::get("ProductGroup", "session_name_for_sort_preference"))) {
			$sortKey = Convert::raw2sqL($sessionSort);
		}
		else {
			$sortKey = $this->MyDefaultSortOrder();
		}
		$sort = $this->getSortOptionSQL($sortKey);
		return $sort;
	}

	/**
	 * Excluded products that can not be purchased
	 * IMPORTANT: Adjusts allProducts and returns it...
	 * @return DataList
	 */
	protected function getExcludedProducts() {
		if($this->EcomConfig()->OnlyShowProductsThatCanBePurchased && empty(self::$negative_can_purchase_array)) {
			self::$negative_can_purchase_array = array();
			$rawCount = $this->allProducts->count();
			if($rawCount && $rawCount < 9999) {
				foreach($this->allProducts as $buyable) {
					if(!$buyable->canPurchase()) {
						//NOTE: the ->remove we had here would have removed
						//the product from the DB.
						self::$negative_can_purchase_array[$buyable->ID] = $buyable->ID;
					}
				}
			}
			if(count(self::$negative_can_purchase_array)) {
				$this->allProducts = $this->allProducts->Exclude(array("ID" => self::$negative_can_purchase_array));
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
		Deprecation::notice('3.1', 'Use the "ProductGroup.getClassNameSQL" instead');
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
		$myProductsArray = $this->currentInitialProducts()->map("ID", "ID")->toArray();
		$rows = DB::query("
			SELECT \"ProductGroupID\" FROM \"Product_ProductGroups\" WHERE \"ProductID\" IN (".implode(",", $myProductsArray).") GROUP BY \"ProductGroupID\";
		");
		$selectArray = array(0);
		foreach($rows as $row) {
			$selectArray[$row["ProductGroupID"]] = $row["ProductGroupID"];
		}
		//just in case
		unset($selectArray[$this->ID]);
		return ProductGroup::get()->filter(array("ID" => $selectArray));
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
		$parentIDs = Product::get()->filter(array("ID" => $alsoShowProductsArray))->map("ID", "ID")->toArray();
		return ProductGroup::get()->filter(array("ID" => $parentIDs));
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

	/**
	 * Debug helper method.
	 * Can be called from /shoppingcart/debug/
	 * @return String
	 */
	public function debug() {
		$this->ProductsShowable();
		$html = EcommerceTaskDebugCart::debug_object($this);
		$html .= "<ul>";

		$html .= "<li><hr />Default Keys<hr /></li>";
		$html .= "<li><b>Default Sort Key:</b> ".$this->getDefaultSortKey()." </li>";
		$html .= "<li><b>Default Filter Key:</b> ".$this->getDefaultFilterKey()." </li>";
		$html .= "<li><b>Default Display Style Key:</b> ".$this->getDefaultDisplayStyleKey()." </li>";

		$html .= "<li><hr />Dropdowns<hr /></li>";
		$html .= "<li><b>Sort Options for Dropdown:</b> ".$this->getSortOptionsForDropdown()." </li>";
		$html .= "<li><b>Filter Options for Dropdown:</b> ".$this->getFilterOptionsForDropdown()." </li>";
		$html .= "<li><b>Display Styles for Dropdown:</b> ".$this->getDisplayStylesForDropdown()." </li>";


		$html .= "<li><hr />Selection Settings<hr /></li>";
		$html .= "<li><b>MyDefaultFilter:</b> ".$this->MyDefaultFilter()." </li>";
		$html .= "<li><b>MyDefaultSortOrder:</b> ".$this->MyDefaultSortOrder()." </li>";
		$html .= "<li><b>MyDefaultDisplayStyle:</b> ".$this->MyDefaultDisplayStyle()." </li>";
		$html .= "<li><b>MyNumberOfProductsPerPage:</b> ".$this->MyNumberOfProductsPerPage()." </li>";
		$html .= "<li><b>MyLevelOfProductsToshow:</b> ".$this->MyLevelOfProductsToShow()." </li>";

		$html .= "<li><hr />Filters<hr /></li>";
		$html .= "<li><b>currentClassNameSQL:</b> ".$this->getClassNameSQL()." </li>";
		$html .= "<li><b>getFilterOptionSQL:</b> ".print_r($this->getFilterOptionSQL(), 1)." </li>";
		$html .= "<li><b>getGroupFilter:</b> ".$this->getGroupFilter()." </li>";
		$html .= "<li><b>getStandardFilter:</b> ".$this->getStandardFilter()." </li>";

		$html .= "<li><hr />DATALIST: adjusters<hr /></li>";
	$html .= "<li><b>Join statement for product groups:</b> ".$this->getGroupJoin()." </li>";
		$html .= "<li><b>Excluded products that can not be searched:</b> ".$this->getExcludedProducts()." </li>";

		$html .= "<li><hr />DATALIST: totals, numbers per page etc<hr /></li>";
		$html .= "<li><b>Total number of products:</b> ".$this->TotalCount()." </li>";
		$html .= "<li><b>Is there more than one product:</b> ".$this->TotalCountGreaterThanOne()." </li>";
		$html .= "<li><b>Number of products per page:</b> ".$this->MyNumberOfProductsPerPage()." </li>";
		$html .= "<li><b>Parent page if it is an instance of product group:</b> ".$this->ParentGroup()." </li>";


		$html .= "<li><hr />SQL Factors<hr /></li>";
		$html .= "<li><b>Sort options for SQL:</b> ".$this->getSortOptionSQL()." </li>";
		$html .= "<li><b>Filter options for SQL:</b> ".$this->getFilterOptionSQL()." </li>";
		$html .= "<li><b>currentClassNameSQL:</b> ".$this->getClassNameSQL()." </li>";
		$html .= "<li><b>currentSortSQL:</b> ".$this->currentSortSQL()." </li>";

		$html .= "<li><hr />Outcome<hr /></li>";
		$html .= "<li><b>TotalCount:</b> ".$this->TotalCount()." </li>";
		$html .= "<li><b>allProducts:</b> ".print_r($this->allProducts->sql(), 1)." </li>";

		$html .= "<li><hr />Other<hr /></li>";
		$html .= "<li><b>BestAvailableImage:</b> ".$this->BestAvailableImage()." </li>";
		$html .= "<li><b>a list of Product Groups that have the products for the CURRENT product group listed as part of their AlsoShowProducts list:</b> ".$this->ProductGroupsFromAlsoShowProducts()." </li>";
		$html .= "<li><b>the inverse of ProductGroupsFromAlsoShowProducts:</b> ".$this->ProductGroupsFromAlsoShowProductsInverse()." </li>";
		$html .= "<li><b>Is this an ecommerce page:</b> ".$this->IsEcommercePage()." </li>";

		$html .= "<li><hr />Final Products<hr /></li>";
		$html .= "<li><b>currentFinalProducts:</b> ".$this->currentFinalProducts()." </li>";



		$html .= "</ul>";
		return $html;
	}

}



class ProductGroup_Controller extends Page_Controller {

	private static $allowed_actions = array(
		"debug" => "ADMIN",
		"filterforgroup" => true
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
	 * @var Int
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
		return $dos;
	}

	/**
	 * Provides a dataset of links for sorting products.
	 *
	 * @return ArrayList( ArrayData(Name, Link, SelectKey, Current (boolean), LinkingMode))
	 */
	public function SortLinks(){
		return $this->userPreferencesLinks("sort_options", "MyDefaultSortOrder", "SORTBY", "session_name_for_sort_preference");
	}

	/**
	 * Provides a dataset of links for filters products.
	 *
	 * @return ArrayList( ArrayData(Name, Link, SelectKey, Current (boolean), LinkingMode))
	 */
	public function FilterLinks(){
		return $this->userPreferencesLinks("filter_options", "MyDefaultFilter", "FILTERFOR", "session_name_for_filter_preference");
	}

	/**
	 * Provides a dataset of links for displaying prodicts products.
	 *
	 * @return ArrayList( ArrayData(Name, Link,  SelectKey, Current (boolean), LinkingMode))
	 */
	public function DisplayLinks(){
		return $this->userPreferencesLinks("display_styles", "MyDefaultDisplayStyle", "DISPLAYBY", "session_name_for_display_style_preference");
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

	/****************************************************
	 *  INTERNAL PROCESSING
	/****************************************************/


	/**
	 * Provides a dataset of links for a particular user preference
	 *
	 * @return ArrayList( ArrayData(Name, Link,  SelectKey, Current (boolean), LinkingMode))
	 */
	protected function userPreferencesLinks($configName, $method, $configTranslationCode, $sessionVariableName){
		$options = EcommerceConfig::get("ProductGroup", $configName);
		if(count($options) < 2) return null;
		$selectedItem = Session::get("ProductGroup_".EcommerceConfig::get("ProductGroup", $sessionVariableName));
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
					'Name' => _t('ProductGroup.'.$configTranslationCode.strtoupper(str_replace(' ','',$array['Title'])),$array['Title']),
					'Link' => $this->Link().$linkGetVariable,
					'SelectKey' => $key,
					'Current' => $isCurrent,
					'LinkingMode' => $isCurrent ? "current" : "link"
				)));
			}
		}
		return $dos;
	}

	/**
	 * turns full list into paginated list
	 * @param SS_List
	 * @return PaginatedList
	 */
	protected function paginateList(SS_List $list){
		if($list && $list->count()) {
			if($this->showFullList) {
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

	/**
	 * Checks out a bunch of $_GET variables
	 * that are used to work out user preferences
	 * Some of these are saved to session.
	 *
	 */
	protected function saveUserPreferences(){
		$preferencesArray = array(
			"sort_options" => "session_name_for_sort_preference",
			"filter_options" => "session_name_for_filter_preference",
			"display_options" => "session_name_for_display_style_preference"
		);
		foreach($preferencesArray as $optionsVariableName => $preferenceVariableName) {
			$myPreferenceVariableName = EcommerceConfig::get("ProductGroup", $preferenceVariableName);
			if(isset($_GET[$myPreferenceVariableName])) {
				$newPreference = $_GET[$myPreferenceVariableName];
				if($newPreference) {
					$options = EcommerceConfig::get("ProductGroup", $optionsVariableName);
					if(isset($options[$newPreference])) {
						Session::set("ProductGroup_".$myPreferenceVariableName, $newPreference);
					}
				}
			}
		}
		//make sure to save the new settings first!
		if(isset($_GET["reload"])) {
			return $this->redirect($this->Link());
		}
		if(isset($_GET["showfulllist"])) {
			$this->showFullList = true;
		}
	}

	/**
	 * add a secondary title to the main title
	 * in case there is, for example, a filter applied
	 * e.g. Socks | MyBrand
	 *
	 *
	 */
	private $secondaryTitleHasBeenAdded = false;

	protected function addSecondaryTitle($secondaryTitle = "") {
		$pipe = _t("ProductGroup.TITLE_SEPERATOR", " | ");
		if(!$this->secondaryTitleHasBeenAdded) {
			if($this->showFullList) {
				$secondaryTitle .= $pipe._t("ProductGroup.LIST_VIEW", "List View");
			}
			if($secondaryTitle) {
				$this->Title .= $pipe.$secondaryTitle;
				if(isset($this->MetaTitle)) {
					$this->MetaTitle .= $pipe.$secondaryTitle;
				}
			}
			//dont update menu title, because the entry in the menu
			//should stay the same as it links back to the unfiltered
			//page (in some cases).
			//$this->MenuTitle .= " | ".$otherProductGroup->MenuTitle;
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
		return $this->dataRecord->debug();
	}

}


