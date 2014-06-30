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
	 *    "FormField" => new TextField("MyDatabaseField", "Keyword"),
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
	 * tons of results
	 * @var Int
	 */
	protected $maximumNumberOfResults = 100;

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
	 * Number of results found so far
	 * @var Int
	 */
	protected $resultArrayPos = 0;


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

		$fields = new FieldList(
			new TextField("Keyword",  _t("ProductSearchForm.KEYWORDS", "Keywords"), Session::get("Ecommerce_ProductSearchForm_Keyword")),
			new NumericField("MinimumPrice", _t("ProductSearchForm.MINIMUM_PRICE", "Minimum Price"),  Session::get("Ecommerce_ProductSearchForm_MinimumPrice")),
			new NumericField("MaximumPrice", _t("ProductSearchForm.MAXIMUM_PRICE", "Maximum Price"), Session::get("Ecommerce_ProductSearchForm_MaximumPrice"))
		);
		$actions = new FieldList(
			new FormAction('doProductSearchForm', 'Search')
		);
		if($productsToSearchCount) {
			$fields->push(
				new CheckboxField("SearchOnlyFieldsInThisSection", _t("ProductSearchForm.ONLY_SHOW", "Only Show Results from")." <i>".$nameOfProductsBeingSearched."</i> "._t("ProductSearchForm.SECTION", "section"), true)
			);
		}
		$requiredFields = array();
		$validator = ProductSearchForm_Validator::create($requiredFields);
		if(Director::isDev() || Permission::check("ADMIN")) {
			$fields->push(new CheckboxField("DebugSearch", "Debug Search"));
		}
		parent::__construct($controller, $name, $fields, $actions, $validator);
		//extensions need to be set after __construct
		if($this->extend('updateFields',$fields) !== null) {$this->setFields($fields);}
		if($this->extend('updateActions',$actions) !== null) {$this->setActions($actions);}
		if($this->extend('updateValidator',$requiredFields) !== null) {$this->setValidator($requiredFields);}
		$oldData = Session::get("FormInfo.".$this->FormName().".data");
		if($oldData && (is_array($oldData) || is_object($oldData))) {
			$this->loadDataFrom($oldData);
		}
		$this->extend('updateProductSearchForm',$this);
		return $this;
	}

	function doProductSearchForm($data, $form){
		foreach($data as $field => $value) {
			Session::set("Ecommerce_ProductSearchForm_$field", $value);
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
		$limitToCurrentSection = false;
		if(isset($data["SearchOnlyFieldsInThisSection"]) && $data["SearchOnlyFieldsInThisSection"]) {
			$limitToCurrentSection = true;
			$baseList = $baseList->filter(array("ID" => $this->productsToSearch));
		}
		if(isset($data["MinimumPrice"]) && $data["MinimumPrice"]) {
			$baseList = $baseList->filter(array("Price:GreaterThanOrEqual" => floatval($data["MinimumPrice"])));
		}
		if(isset($data["MaximumPrice"]) && $data["MaximumPrice"]) {
			$baseList = $baseList->filter(array("Price:LessThanOrEqual" => floatval($data["MaximumPrice"])));
		}
		$keywordResults = false;
		if($this->debug) { $this->debugOutput("<hr /><h3>BASE LIST</h3><pre>".str_replace($this->sqlWords, array_flip($this->sqlWords), $baseList->sql())."</pre>");}
		//KEYWORD SEARCH - only bother if we have any keywords and results at all ...
		if(isset($data["Keyword"]) && $keyword = $data["Keyword"]) {
			if($baseList->count()) {
				if(strlen($keyword) > 1){
					$keywordResults = true;
					$this->resultArrayPos = 0;
					$this->resultArray = Array();

					$keyword = Convert::raw2sql($keyword);
					$keyword = strtolower($keyword);

					SearchHistory::add_entry($keyword);

					// 1) Exact search by code
					$count = 0;
					if($this->debug) { $this->debugOutput("<hr /><h2>SEARCH BY CODE</h2>");}
					if($code = intval($keyword) ) {
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

						//find all keywords ...
						$wordArray = array($keyword);
						$words = explode(' ', trim(preg_replace('!\s+!', ' ', $keyword)));
						foreach($words as $word) {
							$replacements = SearchReplacement::get()
								->where("
									LOWER(\"Search\") = '$keyword' OR
									LOWER(\"Search\") LIKE '%,$keyword' OR
									LOWER(\"Search\") LIKE '$keyword,%' OR
									LOWER(\"Search\") LIKE '%,$keyword,%'"
								);
							if($replacements->count()) {
								$wordArray += array_values($replacements->map('ID', 'Replace')->toArray());
							}
						}
						$wordArray = array_unique($wordArray);
						if($this->debug) { $this->debugOutput("<pre>WORD ARRAY: ".print_r($wordArray, 1)."</pre>");}

						//work out searches
						$singleton = $baseClassName::create();
						foreach($this->extraBuyableFieldsToSearchFullText as $tempClassName => $fieldArrayTemp) {
							if($singleton instanceof $tempClassName) {
								$fieldArray = $fieldArrayTemp;
								break;
							}
						}
						if($this->debug) { $this->debugOutput("<pre>FIELD ARRAY: ".print_r($fieldArray, 1)."</pre>");}

						$searches = $this->getSearchArrays($wordArray, $fieldArray);
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
						if($this->resultArrayPos <= $this->maximumNumberOfResults) {
							$searches = $this->getSearchArrays($wordArray);
							if($this->debug) { $this->debugOutput("<pre>SEARCH ARRAY: ".print_r($searches, 1)."</pre>");}

							foreach($searches as $search) {
								$productGroups = ProductGroup::get()->where($search);
								$count = $productGroups->count();
								if($count == 1) {
									if(!$this->debug) {
										return $this->controller->redirect($productGroups->First()->Link());
									}
								}
								elseif($count > 1) {
									$productIDArray = array();
									foreach($productGroups as $productGroup) {
										$productIDArray += Product::get()->filter(array("ParentID" => $productGroup->ID))->limit(100)->map("ID", "ID")->toArray();
									}
									$productIDArray = array_unique($productIDArray);
									$list3 = $baseList->filter(array("ID" => $productIDArray));
									$count = $list3->count();
									if($count == 1) {
										if(!$this->debug) {
											return $this->controller->redirect($list3->First()->Link());
										}
									}
									elseif($count > 1) {
										if($this->addToResults($list3)) {
											break;
										}
									}
								}
								if($this->resultArrayPos > $this->maximumNumberOfResults) {
									break;
								}
							}
						}
						if($this->debug) { $this->debugOutput("<h3>PRODUCT GROUP SEARCH: $count</h3>");}
					}
				}
			}
		}
		if(!$keywordResults) {
			$this->addToResults($baseList);
		}
		$redirectToPage = null;
		//if no specific section is being searched then we redirect to search page:
		if(!$limitToCurrentSection) {
			$redirectToPage = ProductGroupSearchPage::get()->first();
		}
		if(!$redirectToPage) {
			//for section specific stuff, we redirect to the specific section (basically where we came from
			$redirectToPage = $this->controller;
		}
		$link = $redirectToPage->Link($this->controllerSearchResultDisplayMethod)."?results=".implode(",", $this->resultArray);
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
	protected function getSearchArrays($words, $fields = array("Title", "MenuTitle")){
		//make three levels of search
		$searches = array();
		$words = preg_replace('!\s+!', ' ', $words);
		$wordsAsString = trim(implode(" ", $words));
		$wordArray = explode(" ", $wordsAsString);
		$wordsAsLikeString = trim(implode("%",$wordArray));
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
		Session::set("FormInfo.".$this->FormName().".data", $data);
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
		parent::__construct($controller, $name, $nameOfProductsBeingSearched, $productsToSearch);
		$this->fields = new FieldList(
			new TextField("Keyword", "", Session::get("Ecommerce_ProductSearchForm_Keyword"))
		);
		$this->actions = new FieldList(
			new FormAction('doProductSearchForm', 'Go')
		);
	}

}


class ProductSearchForm_Validator extends RequiredFields{

	function php($data){
		$this->form->saveDataToSession();
		return parent::php($data);
	}

}
