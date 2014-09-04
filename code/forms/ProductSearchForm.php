<?php

/**
 * @description: Allows user to specifically search products
 **/

class ProductSearchForm extends Form {

	/**
	 * set to TRUE to show the search logic
	 * @var Boolean
	 */
	protected $debug = false;

	/**
	 * list of additional fields to add to search
	 *
	 * Additional fields array is formatted as follows:
	 * array(
	 *  "FormField" => Field,
	 *  "DBField" => Acts On / Searches,
	 *  "FilterUsed" => SearchFilter
	 * );
	 * e.g.
	 * array(
	 *  [1] => array(
	 *    "FormField" => TextField::create("MyDatabaseField", "Keyword"),
	 *    "DBField" => "MyDatabaseField",
	 *    "FilterUsed" => "PartialMatchFilter"
	 *   )
	 * );
	 * @var Array
	 */
	protected $additionalFields = array();

	/**
	 * list of products that need to be searched
	 * @var NULL | Array | Datalist
	 */
	protected $productsToSearch = null;

	/**
	 * class name of the buyables to search
	 * at this stage, you can only search one type of buyable at any one time
	 * e.g. only products or only mydataobject
	 *
	 * @var string
	 */
	protected $baseClassNameForBuyables = "";

	/**
	 * this is mysql specific, see: https://dev.mysql.com/doc/refman/5.0/en/fulltext-boolean.html
	 *
	 * @var Boolean
	 */
	protected $useBooleanSearch = true;

	/**
	 * get parameters added to the link
	 * you dont need to start them with & or ?
	 * e.g.
	 * a=23&b=234
	 *
	 * @var String
	 */
	protected $additionalGetParameters = "";

	/**
	 * List of additional fields that should be searched full text.
	 * We are matching this against the buyable class name.
	 * @var Array
	 */
	protected $extraBuyableFieldsToSearchFullText = array(
		"Product" => array("Title","MenuTitle","Content","MetaDescription"),
		"ProductVariation" => array("FullTitle", "Description")
	);

	/**
	 * Maximum number of results to return
	 * we limit this because otherwise the system will choke
	 * the assumption is that no user is really interested in looking at
	 * tons of results.
	 * It defaults to: EcommerceConfig::get("ProductGroup", "maximum_number_of_products_to_list")
	 * @var Int
	 */
	protected $maximumNumberOfResults = 0;

	/**
	 * The method on the parent controller that can display the results of the
	 * search results
	 * @var String
	 */
	protected $controllerSearchResultDisplayMethod = "searchresults";

	/**
	 * array of IDs of the results found so far
	 * @var Array
	 */
	protected $resultArray = array();

	/**
	 * product groups found
	 * @var Array
	 */
	protected $productGroupIDs = array();

	/**
	 * Number of results found so far
	 * @var Int
	 */
	protected $resultArrayPos = 0;

	/**
	 * Is the extended or the short form?
	 * @var Boolean
	 */
	protected $isShortForm = 0;


	public function setControllerSearchResultDisplayMethod($s) {
		$this->controllerSearchResultDisplayMethod = $s;
	}

	public function setExtraBuyableFieldsToSearchFullText($a) {
		$this->extraBuyableFieldsToSearchFullText = $a;
	}

	public function setBaseClassNameForBuyables($s) {
		$this->baseClassNameForBuyables = $s;
	}

	public function setUseBooleanSearch($b) {
		$this->useBooleanSearch = $b;
	}

	public function setMaximumNumberOfResults($i) {
		$this->maximumNumberOfResults = $i;
	}

	public function setAdditionalGetParameters($s) {
		$this->additionalGetParameters = $s;
	}

	public function addAdditionalField($formField, $dbField, $filterUsed) {
		$this->additionalFields[$dbField] = array(
			"FormField" => $formField,
			"DBField" => $dbField,
			"FilterUsed" => $filterUsed
		);
		$this->fields->push($formField);
	}

	/**

	 *
	 * ProductsToSearch can be left blank to search all products
	 *
	 * @param Controller $controller - associated controller
	 * @param String $name - name of form
	 * @param String $nameOfProductsBeingSearched - name of the products being search (also see productsToSearch below)
	 * @param DataList | Array | Null $productsToSearch  (see comments above)
	 */
	function __construct($controller, $name, $nameOfProductsBeingSearched = "", $productsToSearch = null) {

		//set basics
		$productsToSearchCount = 0;
		if($productsToSearch) {
			if(is_array($productsToSearch)) {
				$productsToSearchCount = count($productsToSearch);
			}
			elseif($productsToSearch instanceof DataList) {
				$productsToSearchCount = $productsToSearch->count();
			}
		}
		$this->productsToSearch = $productsToSearch;
		if($this->isShortForm) {
			$fields = FieldList::create(
				TextField::create("ShortKeyword", "")
			);
			$actions = FieldList::create(
				FormAction::create('doProductSearchForm', 'Go')
			);
		}
		else {
			if(Config::inst()->get("ProductSearchForm", "include_price_filters")) {
				$fields = FieldList::create(
					TextField::create("Keyword",  _t("ProductSearchForm.KEYWORDS", "Keywords")),
					NumericField::create("MinimumPrice", _t("ProductSearchForm.MINIMUM_PRICE", "Minimum Price")),
					NumericField::create("MaximumPrice", _t("ProductSearchForm.MAXIMUM_PRICE", "Maximum Price"))
				);
			}
			else {
				$fields = FieldList::create(
					TextField::create("Keyword",  _t("ProductSearchForm.KEYWORDS", "Keywords"))
				);
			}
			$actions = FieldList::create(
				FormAction::create('doProductSearchForm', 'Search')
			);
			if($productsToSearchCount) {
				$fields->push(
					CheckboxField::create("SearchOnlyFieldsInThisSection", _t("ProductSearchForm.ONLY_SHOW", "Only search in")." <i>".$nameOfProductsBeingSearched."</i> ", true)
				);
			}
			if(Director::isDev() || Permission::check("ADMIN")) {
				$fields->push(CheckboxField::create("DebugSearch", "Debug Search"));
			}
		}
		$requiredFields = array();
		$validator = ProductSearchForm_Validator::create($requiredFields);
		parent::__construct($controller, $name, $fields, $actions, $validator);
		//extensions need to be set after __construct
		if($this->extend('updateFields',$fields) !== null) {$this->setFields($fields);}
		if($this->extend('updateActions',$actions) !== null) {$this->setActions($actions);}
		if($this->extend('updateValidator',$requiredFields) !== null) {$this->setValidator($requiredFields);}
		$oldData = Session::get($this->Config()->get("form_data_session_variable"));
		if($oldData && (is_array($oldData) || is_object($oldData))) {
			$this->loadDataFrom($oldData);
		}
		$this->extend('updateProductSearchForm',$this);
		return $this;
	}

	function doProductSearchForm($data, $form){
		if(!$this->maximumNumberOfResults) {
			$this->maximumNumberOfResults = EcommerceConfig::get("ProductGroup", "maximum_number_of_products_to_list");
		}
		if(isset($data["DebugSearch"])) {
			$this->debug = $data["DebugSearch"] ? true : false;
		}
		if($this->debug) { $this->debugOutput("<hr /><hr /><hr /><h2>Debugging Search Results</h2>");}

		//what is the baseclass?
		$baseClassName = $this->baseClassForBuyables;
		if(!$baseClassName) {
			$baseClassName = EcommerceConfig::get("ProductGroup", "base_buyable_class");
		}
		if(!$baseClassName) {
			user_error("Can not find $baseClassName (baseClassName)");
		}
		//basic get
		$searchableFields = ($baseClassName::create()->stat('searchable_fields'));
		$baseList = $baseClassName::get()->filter(array("ShowInSearch" => 1));
		$ecomConfig = EcommerceDBConfig::current_ecommerce_db_config();
		if($ecomConfig->OnlyShowProductsThatCanBePurchased) {
			$baseList->filter(array("AllowPurchase" => 1));
		}
		$limitToCurrentSection = false;
		if(isset($data["SearchOnlyFieldsInThisSection"]) && $data["SearchOnlyFieldsInThisSection"]) {
			$limitToCurrentSection = true;
			if($this->productsToSearch instanceof DataList) {
				$this->productsToSearch = $this->productsToSearch->map("ID", "ID")->toArray();
			}
			$baseList = $baseList->filter(array("ID" => $this->productsToSearch));
		}
		if(isset($data["MinimumPrice"]) && $data["MinimumPrice"]) {
			$baseList = $baseList->filter(array("Price:GreaterThanOrEqual" => floatval($data["MinimumPrice"])));
		}
		if(isset($data["MaximumPrice"]) && $data["MaximumPrice"]) {
			$baseList = $baseList->filter(array("Price:LessThanOrEqual" => floatval($data["MaximumPrice"])));
		}
		//defining some variables
		$isKeywordSearch = false;
		if($this->debug) { $this->debugOutput("<hr /><h3>BASE LIST</h3><pre>".str_replace($this->sqlWords, array_flip($this->sqlWords), $baseList->sql())."</pre>");}
		//KEYWORD SEARCH - only bother if we have any keywords and results at all ...
		if(isset($data["ShortKeyword"]) && !isset($data["Keyword"])) {
			$data["Keyword"] = $data["ShortKeyword"];
		}
		if(isset($data["Keyword"]) && $keywordPhrase = $data["Keyword"]) {
			if($baseList->count()) {
				if(strlen($keywordPhrase) > 1){
					$isKeywordSearch = true;
					$this->resultArrayPos = 0;
					$this->resultArray = Array();

					$keywordPhrase = Convert::raw2sql($keywordPhrase);
					$keywordPhrase = strtolower($keywordPhrase);

					SearchHistory::add_entry($keywordPhrase);

					// 1) Exact search by code
					$count = 0;
					if($this->debug) { $this->debugOutput("<hr /><h2>SEARCH BY CODE</h2>");}
					if($code = intval($keywordPhrase) ) {
						$list1 = $baseList->filter(array("InternalItemID" => $code));
						$count = $list1->count();
						if($count == 1) {
							if(!$this->debug) {
								return $this->controller->redirect($list1->First()->Link());
							}
						}
						elseif($count > 1) {
							if($this->addToResults($list1)) {
								break;
							}
						}
					}
					if($this->debug) { $this->debugOutput("<h3>SEARCH BY CODE RESULT: $count</h3>");}


					// 2) Search of the entire keyword phrase and its replacements
					$count = 0;
					if($this->debug) { $this->debugOutput("<hr /><h3>FULL KEYWORD SEARCH</h3>");}
					if($this->resultArrayPos <= $this->maximumNumberOfResults) {
						//now we are going to look for synonyms
						$words = explode(' ', trim(preg_replace('!\s+!', ' ', $keywordPhrase)));
						foreach($words as $wordKey => $word) {
							if($this->debug) { $this->debugOutput("checking for aliases of $word");}
							$replacements = SearchReplacement::get()
								->where("
									LOWER(\"Search\") = '$word' OR
									LOWER(\"Search\") LIKE '%,$word' OR
									LOWER(\"Search\") LIKE '$word,%' OR
									LOWER(\"Search\") LIKE '%,$word,%'"
								);
							if($replacements->count()) {
								$replacementsArray = $replacements->map('ID', 'Replace')->toArray();
								if($this->debug) { $this->debugOutput("found alias for $word");}
								foreach($replacementsArray as $replacementWord) {
									$keywordPhrase = str_replace($word, $replacementWord, $keywordPhrase);
								}
							}
						}
						if($this->debug) { $this->debugOutput("<pre>WORD ARRAY: ".print_r($keywordPhrase, 1)."</pre>");}

						//work out searches
						$singleton = $baseClassName::create();
						foreach($this->extraBuyableFieldsToSearchFullText as $tempClassName => $fieldArrayTemp) {
							if($singleton instanceof $tempClassName) {
								$fieldArray = $fieldArrayTemp;
								break;
							}
						}
						if($this->debug) { $this->debugOutput("<pre>FIELD ARRAY: ".print_r($fieldArray, 1)."</pre>");}

						$searches = $this->getSearchArrays($keywordPhrase, $fieldArray);
						//if($this->debug) { $this->debugOutput("<pre>SEARCH ARRAY: ".print_r($searches, 1)."</pre>");}

						//we search exact matches first then other matches ...
						foreach($searches as $search) {
							$list2 = $baseList->where($search);
							$count = $list2->count();
							if($this->debug) { $this->debugOutput("<p>$search: $count</p>");}
							if($count == 1) {
								if(!$this->debug) {
									return $this->controller->redirect($list2->First()->Link());
								}
							}
							elseif($count > 1) {
								if($this->addToResults($list2)) {
									break;
								}
							}
							if($this->resultArrayPos > $this->maximumNumberOfResults) {
								break;
							}
						}
					}
					if($this->debug) { $this->debugOutput("<h3>FULL KEYWORD SEARCH: $count</h3>");}

					if($this->debug) { $this->debugOutput("<hr /><h3>PRODUCT GROUP SEARCH</h3>");}
					// 3) Do the same search for Product Group names
					$count = 0;
					if($limitToCurrentSection) {
						//cant search other sections in this case...
					}
					else {
						$searches = $this->getSearchArrays($keywordPhrase);
						if($this->debug) { $this->debugOutput("<pre>SEARCH ARRAY: ".print_r($searches, 1)."</pre>");}

						foreach($searches as $search) {
							$productGroups = ProductGroup::get()->where($search)->filter(array("ShowInSearch" => 1));
							$count = $productGroups->count();
							//redirect if we find exactly one match and we have no matches so far...
							if($count == 1 && !$this->resultArrayPos) {
								if(!$this->debug) {
									return $this->controller->redirect($productGroups->First()->Link());
								}
							}
							elseif($count) {
								foreach($productGroups as $productGroup) {
									//we add them like this because we like to keep them in order!
									if(!in_array($productGroup->ID, $this->productGroupIDs)) {
										$this->productGroupIDs[] = $productGroup->ID;
									}
								}
							}
						}
						if($this->debug) { $this->debugOutput("<h3>PRODUCT GROUP SEARCH: $count</h3>");}
					}
				}
			}
		}
		if(!$isKeywordSearch) {
			$this->addToResults($baseList);
		}
		$redirectToPage = null;
		//if no specific section is being searched then we redirect to search page:
		if(!$limitToCurrentSection) {
			$redirectToPage = ProductGroupSearchPage::get()->first();
		}
		if(!$redirectToPage) {
			// for section specific search,
			// redirect to the specific section (basically where we came from)
			$redirectToPage = $this->controller->dataRecord;
		}
		if($this->debug) {
			$this->debugOutput("<hr />".
				"<h3>Previous Search Products: ".$redirectToPage->SearchResultsSessionVariable(false)."</h3><p>".print_r(Session::get($redirectToPage->SearchResultsSessionVariable(false)), 1)."</p>".
				"<h3>Previous Search Groups: ".$redirectToPage->SearchResultsSessionVariable(true)."</h3><p>".print_r(Session::get($redirectToPage->SearchResultsSessionVariable(true)), 1)."</p>"
			);
		}
		Session::set($redirectToPage->SearchResultsSessionVariable(false), implode(",", $this->resultArray));
		Session::set($redirectToPage->SearchResultsSessionVariable(true), implode(",", $this->productGroupIDs));
		Session::save();
		if($this->debug) {
			$this->debugOutput("<hr />".
				"<h3>SAVING Products to session: ".$redirectToPage->SearchResultsSessionVariable(false)."</h3><p>".print_r(explode(",", Session::get($redirectToPage->SearchResultsSessionVariable(false))), 1)."</p>".
				"<h3>SAVING Groups to session: ".$redirectToPage->SearchResultsSessionVariable(true)."</h3><p>".print_r(explode(",", Session::get($redirectToPage->SearchResultsSessionVariable(true))), 1)."</p>"
			);
		}
		$link = $redirectToPage->Link($this->controllerSearchResultDisplayMethod);
		if($this->additionalGetParameters) {
			$link .= "?".$this->additionalGetParameters;
		}
		if($this->debug) {
			die($link);
		}
		$this->controller->redirect($link);
	}

	/**
	 * creates three levels of searches that
	 * can be executed one after the other, each
	 * being less specific than the last...
	 *
	 * @return Array
	 */
	private function addToResults($listToAdd){
		$listToAdd = $listToAdd->limit($this->maximumNumberOfResults - $this->resultArrayPos);
		foreach($listToAdd as $page) {
			if(!in_array($page->ID, $this->resultArray)) {
				$this->resultArrayPos++;
				$this->resultArray[$this->resultArrayPos] = $page->ID;
				if($this->resultArrayPos > $this->maximumNumberOfResults) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * creates three levels of searches that
	 * can be executed one after the other, each
	 * being less specific than the last...
	 *
	 * @param Array $words - words being search
	 * @param Array $fields - fields being searched
	 *
	 * @return Array
	 */
	protected function getSearchArrays($keywordPhrase, $fields = array("Title", "MenuTitle")){
		//make three levels of search
		$searches = array();
		$wordsAsString = preg_replace('!\s+!', ' ', $keywordPhrase);
		$wordAsArray = explode(" ", $wordsAsString);
		$wordsAsLikeString = trim(implode("%",$wordAsArray));
		if(in_array("Title", $fields)) {
			$searches[0][] = "LOWER(\"Title\") = '$wordsAsString'"; // a) Exact match
			$searches[1][] = "LOWER(\"Title\") LIKE '%$wordsAsString%'"; // b) Full match within a bigger string
			$searches[2][] = "LOWER(\"Title\") LIKE '%$wordsAsLikeString%'"; // c) Words matched individually
		}
		foreach($fields as $field) {
			$searches[3][] = "LOWER(\"$field\") = '$wordsAsString'"; // a) Exact match
			$searches[4][] = "LOWER(\"$field\") LIKE '%$wordsAsString%'"; // b) Full match within a bigger string
			$searches[5][] = "LOWER(\"$field\") LIKE '%$wordsAsLikeString%'"; // c) Words matched individually
			/*
			 * OR WORD SEARCH
			 * OFTEN leads to too many results, so we keep it simple...
			foreach($wordArray as $word) {
				$searches[6][] = "LOWER(\"$field\") LIKE '%$word%'"; // d) One word match within a bigger string
			}
			*/
		}
		//$searches[3][] = DB::getconn()->fullTextSearchSQL($fields, $wordsAsString, true);
		$returnArray = array();
		foreach($searches as $key => $search) {
			$returnArray[$key] = implode(" OR ", $search);
		}
		return $returnArray;
	}

	/**
	 * saves the form into session
	 * @param Array $data - data from form.
	 */
	public function saveDataToSession(){
		$data = $this->getData();
		if(isset($data["MinimumPrice"]) && !$data["MinimumPrice"]) {
			unset($data["MinimumPrice"]);
		}
		if(isset($data["MaximumPrice"]) && !$data["MaximumPrice"]) {
			unset($data["MaximumPrice"]);
		}
		if(isset($data["ShortKeyword"]) && $data["ShortKeyword"]) {
			$data["Keyword"] = $data["ShortKeyword"];
		}
		if(isset($data["Keyword"]) && $data["Keyword"]) {
			$data["ShortKeyword"] = $data["Keyword"];
		}
		Session::set($this->Config()->get("form_data_session_variable"), $data);
	}

	private function debugOutput($string) {
		echo "<br />$string";
	}

	/**
	 * @var array
	 * List of words to be replaced.
	 */
	private $sqlWords = array(
		"\r\n SELECT" => "SELECT",
		"\r\n FROM" => "FROM",
		"\r\n WHERE" => "WHERE",
		"\r\n HAVING" => "HAVING",
		"\r\n GROUP" => "GROUP",
		"\r\n ORDER BY" => "ORDER BY",
		"\r\n INNER JOIN" => "INNER JOIN",
		"\r\n LEFT JOIN" => "LEFT JOIN",
	);

}

class ProductSearchForm_Short extends ProductSearchForm {

	function __construct($controller, $name, $nameOfProductsBeingSearched = "", $productsToSearch = null) {
		$this->isShortForm = true;
		parent::__construct($controller, $name, $nameOfProductsBeingSearched, $productsToSearch);
		$oldData = Session::get(Config::inst()->get("ProductSearchForm", "form_data_session_variable"));
		if($oldData && (is_array($oldData) || is_object($oldData))) {
			$this->loadDataFrom($oldData);
		}
	}

}

class ProductSearchForm_Validator extends RequiredFields{

	function php($data){
		$this->form->saveDataToSession();
		return parent::php($data);
	}

}
