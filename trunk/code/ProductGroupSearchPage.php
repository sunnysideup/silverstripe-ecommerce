<?php
/**
 *
 * This page manages searching for products
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: Pages
 **/


class ProductGroupSearchPage extends ProductGroup {

	/**
	 * standard SS variable
	 * @static String | Array
	 *
	 */
	private static $icon = 'ecommerce/images/icons/productgroupsearchpage';

	/**
	 * Standard SS variable.
	 * @var String
	 */
	private static $description = "This page allowing the user to search for products.";

	/**
	 * Standard SS variable.
	 */
	private static $singular_name = "Product Search Page";
		function i18n_singular_name() { return _t("ProductGroupSearchPage.SINGULARNAME", "Product Search Page");}

	/**
	 * Standard SS variable.
	 */
	private static $plural_name = "Product Search Pages";
		function i18n_plural_name() { return _t("ProductGroupSearchPage.PLURALNAME", "Product Search Pages");}

	/**
	 * Standard SS function, we only allow for one Product Search Page to exist
	 * but we do allow for extensions to exist at the same time.
	 * @param Member $member
	 * @return Boolean
	 */
	function canCreate($member = null) {
		return ProductGroupSearchPage::get()->filter(array("ClassName" => "ProductGroupSearchPage"))->Count() ? false : $this->canEdit($member);
	}


	/**
	 * Can product list (and related) be cached at all?
	 * @var Boolean
	 */
	protected $allowCaching = false;

	function getGroupFilter(){
		$resultArray = explode(",",Session::get($this->SearchResultsSessionVariable(false)));
		if(!is_array($resultArray) || !count($resultArray)) {
			$resultArray = array(0 => 0);
		}
		$this->allProducts = $this->allProducts->filter(array("ID" => $resultArray));
		return $this->allProducts;
	}

	/**
	 * returns the SORT part of the final selection of products.
	 * @return String | Array
	 */
	protected function currentSortSQL() {
		$sortKey = $this->getCurrentUserPreferences("SORT");
		$defaultSortKey = $this->getMyUserPreferencesDefault("FILTER");
		if($sortKey == $defaultSortKey) {
			$resultArray = explode(",",Session::get($this->SearchResultsSessionVariable(false)));
			if(!$resultArray || !count($resultArray)) {
				$resultArray = array(0 => 0);
			}
			return $this->createSortStatementFromIDArray($resultArray);
		}
		return $this->getUserSettingsOptionSQL("SORT", $sortKey);
	}

	function childGroups($maxRecursiveLevel, $filter = null, $numberOfRecursions = 0){
		return ArrayList::create();
	}




}

class ProductGroupSearchPage_Controller extends ProductGroup_Controller {

	/**
	 * standard SS variable
	 * @var Array
	 */
	private static $allowed_actions = array(
		"debug" => "ADMIN",
		"filterforgroup" => true,
		"ProductSearchForm" => true,
		"searchresults" => true,
		"resetfilter" => true
	);

	/**
	 * Is this a product search?
	 * @var Boolean
	 */
	protected $isSearchResults = true;


	/**
	 * get the search results
	 * @param HTTPRequest
	 */
	public function searchresults($request){
		//set the filter and the sort...
		$this->addSecondaryTitle();
		$this->products = $this->paginateList($this->ProductsShowable(null));
		return array();
	}

	/**
	 * returns child product groups for use in
	 * 'in this section'. For example the vegetable Product Group
	 * May have listed here: Carrot, Cabbage, etc...
	 * @return ArrayList (ProductGroups)
	 */
	public function MenuChildGroups() {
		return null;
	}


}
