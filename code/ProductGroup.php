<?php
 /**
  * Product Group is a 'holder' for Products within the CMS
  * It contains functions for versioning child products
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
	public static $db = array(
		"NumberOfProductsPerPage" => "Int",
		"LevelOfProductsToShow" => "Int",
		"DefaultSortOrder" => "Varchar(50)",
		"DefaultFilter" => "Varchar(50)",
		"DisplayStyle" => "Varchar(50)"
	);

	/**
	 * standard SS variable
	 * @static Array
	 *
	 */
	public static $has_one = array(
		'Image' => 'Product_Image'
	);

	/**
	 * standard SS variable
	 * @static Array
	 *
	 */
	public static $belongs_many_many = array(
		'AlsoShowProducts' => 'Product'
	);

	/**
	 * standard SS variable
	 * @static Array
	 *
	 */
	public static $defaults = array(
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
	public static $indexes = array(
		"LevelOfProductsToShow" => true,
		"DefaultSortOrder" => true,
		"DefaultFilter" => true,
		"DisplayStyle" => true
	);

	/**
	 * standard SS variable
	 * @static String
	 */
	public static $default_child = 'Product';

	/**
	 * standard SS variable
	 * @static String | Array
	 *
	 */
	public static $icon = 'ecommerce/images/icons/productgroup';

	/**
	 * Standard SS variable.
	 */
	public static $singular_name = "Product Category";
		function i18n_singular_name() { return _t("ProductGroup.PRODUCTCATEGORY", "Product Category");}

	/**
	 * Standard SS variable.
	 */
	public static $plural_name = "Product Categories";
		function i18n_plural_name() { return _t("ProductGroup.PRODUCTCATEGORIES", "Product Categories");}

	/**
	 * returns the default Sort key.
	 * @return String
	 */
	protected function getDefaultSortKey(){
		$sortOptions = EcommerceConfig::get("ProductGroup", "sort_options");
		if(isset($sortOptions["default"])) {
			return "default";
		}
		$keys = array_keys($sortOptions);
		return $keys[0];
	}

	/**
	 * returns an array of Key => Title for sort options
	 * @todo: why is this public?
	 * @return Array
	 */
	public function getSortOptionsForDropdown(){
		$inheritTitle = _t("ProductGroup.INHERIT", "Inherit");
		$array = array("inherit" => $inheritTitle);
		$sortOptions = EcommerceConfig::get("ProductGroup", "sort_options");
		if(is_array($sortOptions) && count($sortOptions)) {
			foreach($sortOptions as $key => $sortOption) {
				$array[$key] = $sortOption["Title"];
			}
		}
		return $array;
	}

	/**
	 * Returns the sort sql for a particular sorting key.
	 * If no key is provided then the default key will be returned.
	 * @param String
	 * @return String
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
			return "\"Sort\" ASC";
		}
	}

	/**
	 * Returns the Title for a sorting key.
	 * If no key is provided then the default key is used.
	 * @param String
	 * @return String
	 */
	protected function getSortOptionTitle($key = ""){ // NOT STATIC
		$sortOptions = EcommerceConfig::get("ProductGroup", "sort_options");
		if(!$key || (!isset($sortOptions[$key]))) {
			$key = $this->getDefaultSortKey();
		}
		if($key) {
			return $sortOptions[$key]["Title"];
		}
		else {
			return _t("ProductGroup.UNKNOWN", "UNKNOWN");
		}
	}

	/**
	 * Returns the default filter key. Carefully making sure it exists
	 * @return String
	 */
	protected function getDefaultFilterKey(){
		$filterOptions = EcommerceConfig::get("ProductGroup", "filter_options");
		if(isset($filterOptions["default"])) {
			return "default";
		}
		$keys = array_keys($filterOptions);
		return $keys[0];
	}

	/**
	 * Returns options for the dropdown of filter options.
	 * This is public because it can be used in a template???
	 * @todo: check if this needs to be public
	 * @return String
	 */
	public function getFilterOptionsForDropdown(){
		$filterOptions = EcommerceConfig::get("ProductGroup", "filter_options");
		$inheritTitle = _t("ProductGroup.INHERIT", "Inherit");
		$array = array("inherit" => $inheritTitle);
		if(is_array($filterOptions) && count($filterOptions)) {
			foreach($filterOptions as $key => $filter_option) {
				$array[$key] = $filter_option["Title"];
			}
		}
		return $array;
	}

	/**
	 * Returns the sql associated with a filter option.
	 * @param String $key - the option selected
	 * @return String
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
			return " \"ShowInSearch\" = 1";
		}
	}

	/**
	 * The title for the selected filter option.
	 * @param String $key - the key for the selected filter option
	 * @return String
	 */
	protected function getFilterOptionTitle($key = ""){ // NOT STATIC
		$filterOptions = EcommerceConfig::get("ProductGroup", "filter_options");
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

	/**
	 * Returns the options for product display styles.
	 * These can include: Default, Short and MoreDetail
	 * In the configuration you can set which ones are available.
	 * This is important because if one is available then you must make sure that the template is available.
	 * @return Array
	 */
	public function getDisplayStyleForDropdown(){
		//inherit
		$array = array(
			"inherit" => _t("ProductGroup.INHERIT", "Inherit"),
		);
		//short
		if(EcommerceConfig::get("ProductGroup", "allow_short_display_style")) {
			$array["Short"] = _t("ProductGroup.SHORT", "Short (compact)");
		}
		//standard / default
		$array["default"] = _t("ProductGroup.DEFAULT", "Standard");
		//more details
		if(EcommerceConfig::get("ProductGroup", "allow_more_detail_display_style")) {
			$array["MoreDetail"] = _t("ProductGroup.MOREDETAIL", "More Detail (extended)");
		}
		return $array;
	}

	/**
	 * Returns the default Display style: Default....
	 * @return String
	 */
	protected function getDefaultDisplayStyle(){
		return "default";
	}

	/**
	 * @var Array
	 * List of options to show products.
	 * With it, we provide a bunch of methods to access and edit the options.
	 * NOTE: we can not have an option that has a zero key ( 0 => "none"), as this does not work
	 * (as it is equal to not completed yet).
	 */
	protected $showProductLevels = array(
	 -2 => "None",
	 -1 => "All products",
		1 => "Direct Child Products",
		2 => "Direct Child Products + Grand Child Products",
		3 => "Direct Child Products + Grand Child Products + Great Grand Child Products",
		4 => "Direct Child Products + Grand Child Products + Great Grand Child Products + Great Great Grand Child Products",
		99 => "All Child Products (default)"
	);
		public function SetShowProductLevels($a) {$this->showProductLevels = $a;}
		public function RemoveShowProductLevel($i) {unset($this->showProductLevels[$i]);}
		public function AddShowProductLevel($key, $value) {$this->showProductLevels[$key] = $value; ksort($this->showProductLevels);}

	/**
	 * standard SS method
	 * @return FieldSet
	 */
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab('Root.Content.Images', new ImageField('Image', _t('Product.IMAGE', 'Product Group Image')));
		//number of products
		$numberOfProductsPerPageExplanation = $this->MyNumberOfProductsPerPage() != $this->NumberOfProductsPerPage ? _t("ProductGroup.CURRENTLVALUE", " - current value: ").$this->MyNumberOfProductsPerPage()." "._t("ProductGroup.INHERITEDFROMPARENTSPAGE", " (inherited from parent page because the current page is set to zero)") : "";
		$fields->addFieldToTab(
			'Root.Content',
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
				"Root.Content.ProductDisplay",
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
				"Root.Content.ProductDisplay",
				new DropdownField("DefaultFilter", _t("ProductGroup.DEFAULTFILTER", "Default Filter"), $filterDropdownList)
			);
		}
		//displa style
		$displayStyleDropdownList = $this->getDisplayStyleForDropdown();
		if(count($displayStyleDropdownList) > 1) {
			$displayStyleKey = $this->MyDefaultDisplayStyle();
			if($this->DisplayStyle == "inherit") {
				$actualValue = " (".(isset($displayStyleDropdownList[$displayStyleKey]) ? $displayStyleDropdownList[$displayStyleKey] : _t("ProductGroup.ERROR", "ERROR")).")";
				$displayStyleDropdownList["inherit"] = _t("ProductGroup.INHERIT", "Inherit").$actualValue;
			}
			$fields->addFieldToTab(
				"Root.Content.ProductDisplay",
				new DropdownField("DisplayStyle", _t("ProductGroup.DEFAULTDISPLAYSTYLE", "Default Display Style"), $displayStyleDropdownList)
			);
		}
		if($this->EcomConfig()->ProductsAlsoInOtherGroups) {
			$fields->addFieldsToTab(
				'Root.Content.OtherProductsShown',
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
	 * @return TreeMultiselectField
	 **/
	protected function getProductGroupsTable() {
		$field = new TreeMultiselectField(
			$name = "AlsoShowProducts",
			$title = _t("ProductGroup.OTHERPRODUCTSSHOWINTHISGROUP", "Other products shown in this group ..."),
			$sourceObject = "SiteTree",
			$keyField = "ID",
			$labelField = "MenuTitle"
		);
		$filter = create_function('$obj', 'return ( ( $obj InstanceOf ProductGroup || $obj InstanceOf Product) && ($obj->ParentID != '.$this->ID.'));');
		$field->setFilterFunction($filter);
		return $field;
	}

	/**
	 * Retrieve a set of products, based on the given parameters.
	 * This method is usually called by the various controller methods.
	 * The extraFilter and recursive help you to select different products,
	 * depending on the method used in the controller.
	 *
	 * We do not use the recursive here.
	 * Furthermore, extrafilter can take onl all sorts of variables.
	 * This is basically setup like this so that in ProductGroup extensions you
	 * can setup all sorts of filters, while still using the ProductsShowable method.
	 *
	 * @param mixed $extraFilter Additional SQL filters to apply to the Product retrieval
	 * @param boolean $recursive
	 * @return DataObjectSet | Null
	 */
	public function ProductsShowable($extraFilter = ''){
		$allProducts = $this->currentInitialProducts($extraFilter);
		return $this->currentFinalProducts($allProducts);
	}

	/**
	 * returns the inital (all) products, based on the all the eligile products
	 * for the page.
	 *
	 * This is THE pivotal method that probably changes for classes that
	 * extend ProductGroup as here you can determine what products or other buyables are shown.
	 *
	 * The return from this method will then be sorted and limited to produce the final product list.
	 *
	 * NOTE: there is no sort and limit for the initial retrieval
	 *
	 * @param string $extraFilter Additional SQL filters to apply to the Product retrieval
	 * @return DataObjectSet | Null
	 **/
	protected function currentInitialProducts($extraFilter = ''){
		$className = $this->getClassNameSQL();
		//WHERE
			// STANDARD FILTER
		$finalFilterArray = array();
		$filter = $this->getStandardFilter();
		if(strlen(trim($filter)) > 2) {
			$finalFilterArray[] = $filter;
		}
			// EXTRA FILTER
		if(strlen(trim($extraFilter)) > 2) {
			$finalFilterArray[] = $extraFilter;
		}
			// GROUP FILTER
		$groupFilter = $this->getGroupFilter();
		if(strlen(trim($groupFilter)) > 2) {
			$finalFilterArray[] = $groupFilter;
		}
		$where = " ( ".implode(" ) AND ( ", $finalFilterArray)." ) ";
		//REST OF THE VARIABLES
		$sort = $this->currentSortSQL; //NOTE: we sort here already to get some idea of the order of the products.
		$join = $this->getGroupJoin();
		$limit = null;
		$allProducts = DataObject::get($className,$where, $sort, $join, $limit);
		return $allProducts;
	}

	/**
	 * Returns the class we are working with
	 * @return String
	 */
	protected function getClassNameSQL(){
		return "Product";
	}

	/**
	 * Do products occur in more than one group
	 * @return Boolean
	 */
	protected function getProductsAlsoInOtherGroups(){
		return $this->EcomConfig()->ProductsAlsoInOtherGroups;
	}

	/**
	 * returns the filter SQL, based on the $_GET or default entry.
	 * The standard filter excludes the product group filter.
	 * The default would be something like "ShowInSearch = 1"
	 * @return String
	 */
	protected function getStandardFilter(){
		if(isset($_GET['filterfor'])) {
			$filterKey = Convert::raw2sqL($_GET['filterfor']);
		}
		else {
			$filterKey = $this->MyDefaultFilter();
		}
		$filter = $this->getFilterOptionSQL($filterKey);
		return $filter;
	}

	/**
	 * works out the group filter baswed on the LevelOfProductsToShow value
	 * it also considers the other group many-many relationship
	 * this filter ALWAYS returns something: 1 = 1 if nothing else.
	 * @return String
	 */
	protected function getGroupFilter(){
		$groupFilter = "";
		if($this->LevelOfProductsToShow < 0) {
			//no produts but if LevelOfProductsToShow = -1 then show all
			$groupFilter = " (".$this->LevelOfProductsToShow." = -1) " ;
		}
		elseif($this->LevelOfProductsToShow > 0) {
			$groupIDs = array($this->ID => $this->ID);
			$groupFilter .= $this->getProductsToBeIncludedFromOtherGroups();
			$childGroups = $this->ChildGroups($this->LevelOfProductsToShow);
			if($childGroups) {
				foreach($childGroups as $childGroup) {
					$groupIDs[$childGroup->ID] = $childGroup->ID;
					$groupFilter .= $childGroup->getProductsToBeIncludedFromOtherGroups();
				}
			}
			$groupFilter = " ( \"ParentID\" IN (".implode(",", $groupIDs).") ) ".$groupFilter;
		}
		return $groupFilter;
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
			$array = $this->AlsoShowProducts()->map("ID", "ID");
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
	 * @return Null | String
	 */
	protected function getGroupJoin() {
		if($this->getProductsAlsoInOtherGroups()) {
			return $this->getManyManyJoin('AlsoShowProducts','Product');
		}
		return null;
	}

	/**
	 * returns the final products, based on the all the eligile products
	 * for the page.
	 *
	 * All of the 'current' methods are to support the currentFinalProducts Method.
	 *
	 * @param Object $allProducts DataObjectSet of all eligile products before sorting and limiting
	 * @returns Object DataObjectSet of products
	 **/
	protected function currentFinalProducts($buyables){
		if($buyables && $buyables instanceOf DataObjectSet) {
			$buyables->removeDuplicates();
			if($this->EcomConfig()->OnlyShowProductsThatCanBePurchased) {
				foreach($buyables as $buyable) {
					if(!$buyable->canPurchase()) {
						$buyables->remove($buyable);
					}
				}
			}
		}
		if($buyables) {
			$this->totalCount = $buyables->Count();
			if($this->totalCount) {
				return DataObject::get(
					$this->currentClassNameSQL(),
					$this->currentWhereSQL($buyables),
					$this->currentSortSQL(),
					$this->currentJoinSQL(),
					$this->currentLimitSQL()
				);
			}
		}
	}

	/**
	 * returns the CLASSNAME part of the final selection of products.
	 * @return String
	 */
	protected function currentClassNameSQL() {
		return "Product";
	}

	/**
	 * returns the WHERE part of the final selection of products.
	 * @param Object | Array $buyables - list of ALL products showable (without the applied LIMIT)
	 * @return String
	 */
	protected function currentWhereSQL($buyables) {
		if($buyables instanceOf DataObjectSet) {
			$buyablesIDArray = $buyables->map("ID", "ID");
		}
		else {
			$buyablesIDArray = $buyables;
		}
		$className = $this->currentClassNameSQL();
		$stage = '';
		//@to do - make sure products are versioned!
		if(Versioned::current_stage() == "Live") {
			$stage = "_Live";
		}
		$listOfIDs = implode(",", $buyablesIDArray);
		Session::set(EcommerceConfig::get("ProductGroup", "session_name_for_product_array"), $listOfIDs);
		$where = "\"{$className}{$stage}\".\"ID\" IN (". $listOfIDs .")";
		return $where;
	}

	/**
	 * returns the SORT part of the final selection of products.
	 * @return String
	 */
	protected function currentSortSQL() {
		if(isset($_GET['sortby'])) {
			$sortKey = Convert::raw2sqL($_GET['sortby']);
		}
		else {
			$sortKey = $this->MyDefaultSortOrder();
		}
		$sort = $this->getSortOptionSQL($sortKey);
		return $sort;
	}

	/**
	 * returns the JOIN part of the final selection of products.
	 * @return String
	 */
	protected function currentJoinSQL() {
		return null;
	}

	/**
	 * returns the LIMIT part of the final selection of products.
	 * @return String
	 */
	protected function currentLimitSQL() {
		$limit = (isset($_GET['start']) && (int)$_GET['start'] > 0) ? (int)$_GET['start'] : "0";
		$limit .= ", ".$this->MyNumberOfProductsPerPage();
		return $limit;
	}

	/**
	 * returns the total numer of products (before pagination)
	 * @return Integer
	 **/
	public function TotalCount() {
		return $this->totalCount ? $this->totalCount : 0;
	}

	/**
	 *@return Integer
	 **/
	function ProductsPerPage() {return $this->MyNumberOfProductsPerPage();}
	function MyNumberOfProductsPerPage() {
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

	/**
	 * returns the code of the default sort order.
	 * @param $field "", "Title", "SQL"
	 * @return String
	 **/
	function MyDefaultSortOrder() {
		$defaultSortOrder = "";
		$sortOptions = EcommerceConfig::get("ProductGroup", "sort_options");
		if($this->DefaultSortOrder && array_key_exists($this->DefaultSortOrder, $sortOptions)) {
			$defaultSortOrder = $this->DefaultSortOrder;
		}
		if(!$defaultSortOrder && $parent = $this->ParentGroup()) {
			$defaultSortOrder = $parent->MyDefaultSortOrder();
		}
		elseif(!$defaultSortOrder) {
			$defaultSortOrder = $this->getDefaultSortKey();
		}
		return $defaultSortOrder;
	}

	/**
	 * returns the code of the default sort order.
	 * @return String
	 **/
	function MyDefaultFilter() {
		$defaultFilter = "";
		$filterOptions = EcommerceConfig::get("ProductGroup", "filter_options");
		if($this->DefaultFilter && array_key_exists($this->DefaultFilter, $filterOptions)) {
			$defaultFilter = $this->DefaultFilter;
		}
		if(!$defaultFilter && $parent = $this->ParentGroup()) {
			$defaultFilter = $parent->MyDefaultFilter();
		}
		elseif(!$defaultFilter) {
			$defaultFilter = $this->getDefaultFilterKey();
		}
		return $defaultFilter;
	}

	/**
	 * returns the code of the default style for template.
	 * @return String
	 **/
	function MyDefaultDisplayStyle() {
		$displayStyle = "";
		if($this->DisplayStyle != "inherit") {
			$displayStyle = $this->DisplayStyle;
		}
		if($displayStyle == "inherit" && $parent = $this->ParentGroup()) {
			$displayStyle = $parent->MyDefaultDisplayStyle();
		}
		if(!$displayStyle) {
			$displayStyle = $this->getDefaultDisplayStyle();
		}
		return $displayStyle;
	}

	/**
	 * Returns children ProductGroup pages of this group.
	 * @param Int $maxRecursiveLevel - maximum depth , e.g. 1 = one level down - so no Child Groups are returned...
	 * @param String $filter - additional filter to be added
	 * @param Int $numberOfRecursions - current level of depth
	 * @return DataObjectSet | null
	 */
	function ChildGroups($maxRecursiveLevel, $filter = "", $numberOfRecursions = 0) {
		$output = null;
		$numberOfRecursions++;
		if($numberOfRecursions < $maxRecursiveLevel){
			$filterWithAND = '';
			if($filter) {
				$filterWithAND = " AND $filter";
			}
			$where = "\"ParentID\" = '$this->ID' $filterWithAND";
			if($children = DataObject::get('ProductGroup', $where)){
				if($output == null) {
					$output = $children;
				}
				foreach($children as $child){
					$output->merge($child->ChildGroups($maxRecursiveLevel, $filter, $numberOfRecursions, $output));
				}
			}
		}
		return $output;
	}

	/**
	 * Deprecated method
	 */
	function ChildGroupsBackup($maxRecursiveLevel, $filter = "") {
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
		return DataObject::get("ProductGroup", "ProductGroup$stage.ID IN (".implode(",", $ids).")".$filterWithAND);
	}

	/**
	 * returns the parent page, but only if it is an instance of Product Group.
	 * @return DataObject | Null (ProductGroup)
	 **/
	function ParentGroup() {
		if($this->ParentID) {
			return DataObject::get_by_id("ProductGroup", $this->ParentID);
		}
	}

	/**
	 * Recursively generate a product menu.
	 * @return DataObjectSet
	 */
	function GroupsMenu($filter = "ShowInMenus = 1") {
		if($parent = $this->ParentGroup()) {
			return $parent instanceof ProductGroup ? $parent->GroupsMenu() : $this->ChildGroups($filter);
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
	 * tells us if the current page is part of e-commerce.
	 * @return Boolean
	 */
	function IsEcommercePage() {
		return true;
	}

}
class ProductGroup_Controller extends Page_Controller {

	/**
	 * standard SS method
	 */
	function init() {
		parent::init();
		Requirements::themedCSS('Products');
		Requirements::themedCSS('ProductGroup');
		Requirements::themedCSS('ProductGroupPopUp');
		Requirements::javascript('ecommerce/javascript/EcomProducts.js');
		Requirements::javascript('ecommerce/javascript/EcomQuantityField.js');
	}

	/**
	 * Return the products for this group.
	 *
	 *@return DataObjectSet(Products)
	 **/
	public function Products($recursive = true){
	//	return $this->ProductsShowable("\"FeaturedProduct\" = 1",$recursive);
		return $this->ProductsShowable('');
	}

	/**
	 * Return products that are featured, that is products that have "FeaturedProduct = 1"
	 *
	 *@return DataObjectSet(Products)
	 */
	function FeaturedProducts($recursive = true) {
		return $this->ProductsShowable("\"FeaturedProduct\" = 1",$recursive);
	}

	/**
	 * Return products that are not featured, that is products that have "FeaturedProduct = 0"
	 *
	 *@return DataObjectSet(Products)
	 */
	function NonFeaturedProducts($recursive = true) {
		return $this->ProductsShowable("\"FeaturedProduct\" = 0",$recursive);
	}

	/**
	 * Provides a dataset of links for sorting products.
	 *
	 *@return DataObjectSet(Name, Link, Current (boolean), LinkingMode)
	 */
	function SortLinks(){
		$sortOptions = EcommerceConfig::get("ProductGroup", "sort_options");
		if(count($sortOptions) <= 0) return null;
		if($this->totalCount < 3) return null;
		$sort = (isset($_GET['sortby'])) ? Convert::raw2sql($_GET['sortby']) : $this->MyDefaultSortOrder();
		$dos = new DataObjectSet();
		$sortOptions = EcommerceConfig::get("ProductGroup", "sort_options");
		foreach($sortOptions as $key => $array){
			$current = ($key == $sort) ? 'current' : false;
			$dos->push(new ArrayData(array(
				'Name' => _t('ProductGroup.SORTBY'.strtoupper(str_replace(' ','',$array['Title'])),$array['Title']),
				'Link' => $this->Link()."?sortby=$key",
				'SelectKey' => $key,
				'Current' => $current,
				'LinkingMode' => $current ? "current" : "link"
			)));
		}
		return $dos;
	}

	/**
	 * returns child product groups for use in
	 * 'in this section'
	 * @return NULL | DataObjectSet
	 */
	function MenuChildGroups() {
		return $this->ChildGroups(2, "\"ShowInMenus\" = 1");
	}

	/**
	 *
	 * This method can be extended to show products in the side bar.
	 * @return Object DataObjectSet
	 */
	function SidebarProducts(){
		return null;
	}

}
