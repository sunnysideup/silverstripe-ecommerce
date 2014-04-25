<?php
/**
 * @description: Allows user to specifically search products
 **/

class ProductSearchForm extends Form {


	/**
	 * Additional fields array is formatted as follows:
	 * array(
	 *  "FormField" => Field
	 *  "DBField" => Acts On / Searches,
	 *  "FilterUsed" => SearchFilter
	 * );
	 * e.g.
	 * array(
	 *  [1] =>
	 *  "FormField" => new TextField("MyDatabaseField", "Keyword");
	 *  "DBField" => "MyDatabaseField",
	 *  "FilterUsed" => "PartialMatchFilter"
	 * )
	 *
	 * The Additional Fields can be left blank to search all products
	 *
	 * @param String $name - name of form
	 * @param Controller $controller - associated controller
	 * @param Array $additionalFields - additional list of fields (see comments above)
	 * @param DataList | Null $productsToSearch  (see comments above)
	 */
	function __construct($name, $controller, $additionalFields, $productsToSearch = null) {
		$objectBrandDefault = null;
		$objectTypeDefault = null;
		$typeField = null;
		$brandField = null;
		$literalFieldSearchAllLink = null;
		if("PhotographicProductGroup" != $this->ClassName) {
			$typeDefault = Session::get("ProductFilter.Type")+0;} else {$typeDefault = $this->ID;
		}
		if("BrandPage" != $this->ClassName) {
			$brandDefault = Session::get("ProductFilter.Brand")+0;} else {$brandDefault = $this->ID;
		}
		if($typeDefault) {
			$objectTypeDefault = PhotographicProductGroup::get()->byId($typeDefault);
			if($objectTypeDefault) {
				$typeField = new HiddenField("Type","Type", $typeDefault);
			}
		}
		if(!$typeField) {
			$typeField = new DropdownField("Type", "Type",$this->MakeDoubleDropDown("PhotographicProductGroup", "BrandPage", $brandDefault));
		}
		if($brandDefault) {
			$objectBrandDefault = BrandPage::get()->byId($brandDefault);
			if($objectBrandDefault) {
				$brandField = new HiddenField("Brand", "Brand", $brandDefault);
			}
		}
		if(!$brandField) {
			$brandField = new DropdownField("Brand", "Brand", $this->MakeDoubleDropDown("BrandPage", "PhotographicProductGroup", $typeDefault));
		}

		if($objectBrandDefault || $objectTypeDefault) {
			if($objectBrandDefault && $objectTypeDefault) {
				$string = 'both the <i>'.$objectBrandDefault->Title.'</i> and <i>'.$objectTypeDefault->Title.'</i>';
			}
			elseif($objectBrandDefault) {
				$string = 'the <i>'.$objectBrandDefault->Title.'</i> ';
			}
			elseif($objectTypeDefault) {
				$string = 'the <i>'.$objectTypeDefault->Title.'</i>';
			}
			$productSearchPage = $this->ProductSearchPage();
			if($productSearchPage) {
				$literalFieldSearchAllLink = new LiteralField(
					"literalFieldSearchAllLink",
					'<p class="filterExplanationParagraph">You are currently searching products that are in '.$string.' section. You can also widen this search to <a href="'.$productSearchPage->Link().'">all products</a>.'
				);
			}
		}

		$fields = new FieldList(
			new HeaderField("Header", "Make your selection below - only enter relevant fields", 5),
			$typeField,
			$brandField,
			new DropdownField("MinimumPrice", "Price Range", $this->MakePriceDropwDown(), Session::get("ProductFilter.MinimumPrice")),
			//new CheckboxField("Professional", "Professional Products Only", Session::get("ProductFilter.Professional")),
			new CheckboxField("Specials", "Specials Only", Session::get("ProductFilter.Specials")),
			new TextField("Keyword", "Keyword or product code", Session::get("ProductFilter.Keyword"))
		);
		if($literalFieldSearchAllLink) {
			$fields->push($literalFieldSearchAllLink);
		}
		$actions = new FieldList(
			new FormAction('doFilterForm', 'Go')
		);
		$form = new Form($this, "FilterForm", $fields, $actions);
		$form->setLegend("Advanced Filter Form Fields");
		return $form;
	}

	private function MakePriceDropwDown() {
		$array = array();
		foreach(self::$price_range_array as $min => $max) {
			if($min > 0) {
				$currencyMin = "$".$min;
				$currencyMax = "$".$max;
				$array[$min] = "{$currencyMin} - {$currencyMax}";
			}
			else {
			 $array[$min] = $max;
			}
		}
		return $array;
	}

	private function MakeDoubleDropDown($className, $alternativeClassName = "", $filter = 0 ) {
		if(!isset(self::$double_drop_down[$className])) {
			self::$double_drop_down[$className] = array();
			self::$double_drop_down[$className][0] = "* Any";
			if($filter && $alternativeClassName) {
				$base = $alternativeClassName::get()->byID($filter);
				if($base) {
					$objects = $base->AlternativeChildren();
					if($objects && $objects->count()) {
						foreach($objects as $page) {
							self::$double_drop_down[$className][$page->ID] = convert::raw2xml($page->Title);
						}
					}
				}
			}
			if(count(self::$double_drop_down[$className]) < 2) {
			//high usage
				$objects = $className::get()
					->where("IsHighUsageGroup = 1 AND LENGTH(Title) > 3")
					->sort("MenuTitle", "ASC") ;
				if($objects) {
					self::$double_drop_down[$className][-1] = self::$main_options_list_heading;
					foreach($objects as $page) {
						self::$double_drop_down[$className][$page->ID] = convert::raw2xml($page->Title);
					}
				}
				//low usage
				$objects = $objects = $className::get()
					->where("IsHighUsageGroup = 0 AND LENGTH(Title) > 3")
					->sort("MenuTitle", "ASC") ;
				if($objects) {
					self::$double_drop_down[$className][-2] = self::$other_options_list_heading;
					foreach($objects as $page) {
						self::$double_drop_down[$className][$page->ID] = convert::raw2xml($page->Title);
					}
				}
			}
		}
		return self::$double_drop_down[$className];
	}

	function doFilterForm($data, $form){
		//$bt stuff was here
		$this->smartReset(true);
		$this->resetWhereFilterAndHumanArray();
		if(!isset($data["KeywordQuick"])) {$data["KeywordQuick"] = "";}
		if(!isset($data["MinimumPrice"])) {$data["MinimumPrice"] = 0;}
		if(!isset($data["Brand"])) {$data["Brand"] = 0;}
		if(!isset($data["Type"])) {$data["Type"] = 0;}
		if(!isset($data["Professional"])) {$data["Professional"] = 0;}
		if(!isset($data["Specials"])) {$data["Specials"] = 0;}
		if(!isset($data["Keyword"])) {$data["Keyword"] = "";}

		$BrandObject = null;
		$TypeObject = null;
		if(!$data["KeywordQuick"]) {

			$Keyword = Convert::raw2sql($data["Keyword"]);

			$BrandID = intval($data["Brand"])+0;
			if($BrandID < 0) {$BrandID = 0;}
			$Type = intval($data["Type"])+0;
			if($Type < 0) {$Type = 0;}
			if( $BrandID > 0){
				$BrandObject = BrandPage::get()->byID($BrandID);
				if($BrandObject) {
					$this->addWhereFilterAndHumanFilterItem("Brand",$BrandID, array("\"BrandID\" = ".$BrandID), "Brand", $BrandObject->Title);
				}
			}
			if($Type > 0){
				$TypeObject = PhotographicProductGroup::get()->byID($Type);
				if($TypeObject ) {
					$whereFilterType = $TypeObject->ProductsShowableWhereArray();
					$this->addWhereFilterAndHumanFilterItem("Type",$Type, $whereFilterType, "Type",$TypeObject->Title);
				}
			}

			$MinimumPrice = intval($data["MinimumPrice"])+0;
			if(isset(self::$price_range_array[$MinimumPrice]) && self::$price_range_array[$MinimumPrice] > 0) {
				$MaximumPrice = intval(self::$price_range_array[$MinimumPrice]);
			}
			else {
				$MaximumPrice = 0;
			}
			if( $MinimumPrice > 0){
				$this->addWhereFilterAndHumanFilterItem("MinimumPrice",$MinimumPrice, array("\"Price\" >= ".$MinimumPrice), "Over", PhotographicProduct::$default_currency_name.$MinimumPrice);
			}
			if( $MaximumPrice > 0){
				$this->addWhereFilterAndHumanFilterItem("MaximumPrice",$MaximumPrice, array("\"Price\" <= ".$MaximumPrice), "Under", PhotographicProduct::$default_currency_name.$MaximumPrice);
			}
			$Professional = intval($data["Professional"])+0;
			if($Professional) {
				$this->addWhereFilterAndHumanFilterItem("Professional",1, array("(\"PRO\" = '1' OR \"POA\" = '1')"), "Restrict to", "Professional Products Only");
			}
			$Specials = intval($data["Specials"])+0;
			if($Specials) {
				$this->addWhereFilterAndHumanFilterItem("Specials",1, array("(\"SPE\" = '1' OR \"FPS\" = '1' OR \"BON\" <> '' OR \"IOS\" = '1')"), "Only show", "Specials");
			}
		}
		else {
			$Keyword = Convert::raw2sql($data["KeywordQuick"]);
		}
		$Keyword = trim($Keyword);
		if(strlen($Keyword) > 1){
			$Keyword = strtolower($Keyword);
			SearchHistory::add_entry($Keyword);

			// 1) Exact search by code

			if($code = intval($Keyword)) {
				$where = "\"InternalItemID\" = $code";
				$page = PhotographicProduct::get()
					->where($where)
					->first();
				if($page) {
					$this->resetWhereFilterAndHumanArray();
					$this->addWhereFilterAndHumanFilterItem("Code", $code, array($where), "Product Code", $code);
					$this->saveWhereFilterAndHumanArray();
					$this->redirect($page->Link());
					return;
				}
			}

			$fields = array('Title', 'MenuTitle');
			$results = false;

			// 2) Search of the entire keyword and its replacements

			$words = array($Keyword);
			$replacements = SearchReplacement::get()
				->where("
					LOWER(\"Search\") = '$Keyword' OR
					LOWER(\"Search\") LIKE '%,$Keyword' OR
					LOWER(\"Search\") LIKE '$Keyword,%' OR
					LOWER(\"Search\") LIKE '%,$Keyword,%'"
				);
			if($replacements && $replacements->count()) {
				$words += array_values($replacements->map('ID', 'Replace')->toArray());
			}

			$searches = array();
			foreach($words as $word) {
				foreach($fields as $field) {
					$searches[0][] = "LOWER(\"$field\") = '$word'"; // a) Full match
					$searches[1][] = "LOWER(\"$field\") LIKE '%$word%'"; // b) Partial match
					$wordParts = explode(' ', trim(eregi_replace(' +', ' ', $word)));
					if(count($wordParts) > 1) {
						$wordParts = implode('%', $wordParts);
						$searches[2][] = "LOWER(\"$field\") LIKE '%$wordParts%'"; // c) Partial match of expression
					}
				}
			}

			foreach($searches as $search) {
				$filter = implode(' OR ', $search);
				echo $filter;
				$pages = SiteTree::get()->where($filter)->sort("MenuTitle", "ASC");
				if($pages->count()) {
					if($pages->Count() == 1) {
						$this->redirect($pages->First()->Link());
						return;
					}
					else {
						$this->addWhereFilterAndHumanFilterItem('Title', $Keyword, array($filter), 'Title', $Keyword);
					}
					$results = true;
					break;
				}
			}

			// 3) Partial match search of all words by title and menu title - CATEGORIES ONLY

			if(! $results) {
				$words = explode(' ', trim(eregi_replace(' +', ' ', $Keyword)));
				$replacements = SearchReplacement::get();
				if($replacements) {
					foreach($replacements as $replacement) {
						$replace = strtolower($replacement->Replace);
						if(! in_array($replace, $words)) {
							$searches = explode(',', strtolower($replacement->Search));
							$intersect = array_intersect($searches, $words);
							if(count($intersect) > 0) {
								$words[] = $replace;
							}
							else {
								foreach($searches as $search) {
									if(strpos($search, $Keyword) !== false) {
										$words[] = $replace;
										break;
									}
								}
							}
						}
					}
				}
				unset($filter);
				$filter = array();
				foreach($words as $word) {
					if(strlen($word) > 1) {
						foreach(array('Title', 'MenuTitle') as $field) {
							$filter[] = "LOWER(\"$field\") LIKE '%$word%'";
						}
					}
				}

				$filter = implode(' OR ', $filter);
				$pages = PhotographicProductGroup::get()->filter($filter);
				if($pages->count() ) {
					if($pages->Count() == 1) {
						$this->redirect($pages->First()->Link());
						return;
					}
					else {
						$parentFilter = array("ParentID < 0");
						foreach($pages as $pages) {
							$parentFilter[] = implode( " OR ", $pages->ProductsShowableWhereArray());
						}
						//print_r($parentFilter);
						$parentFilterString = "((".implode( ") OR (", $parentFilter )."))";
						$this->addWhereFilterAndHumanFilterItem('Title', $Keyword, array($parentFilterString), 'Title', $Keyword);
						$results = true;
					}
				}
			}





			// 4) Partial search by title and menu title

			if(! $results) {
				$words = explode(' ', trim(eregi_replace(' +', ' ', $Keyword)));
				$replacements = SearchReplacement::get();
				if($replacements && $replacements->count()) {
					foreach($replacements as $replacement) {
						$replace = strtolower($replacement->Replace);
						if(! in_array($replace, $words)) {
							$searches = explode(',', strtolower($replacement->Search));
							$intersect = array_intersect($searches, $words);
							if(count($intersect) > 0) {
								$words[] = $replace;
							}
							else {
								foreach($searches as $search) {
									if(strpos($search, $Keyword) !== false) {
										$words[] = $replace;
										break;
									}
								}
							}
						}
					}
				}

				unset($filter);
				$filter = array();
				foreach($words as $word) {
					if(strlen($word) > 1) {
						foreach(array('Title', 'MenuTitle') as $field) {
							$filter[] = "LOWER(\"$field\") LIKE '%$word%'";
						}
					}
				}

				$filter = implode(' OR ', $filter);
				$pages = SiteTree::get()
					->where($filter)
					->sort("MenuTitle", "ASC");
				if($pages->count()) {
					if($pages->Count() == 1) {
						$this->redirect($pages->First()->Link());
						return;
					}
					else {
						$this->addWhereFilterAndHumanFilterItem('Title', $Keyword, array($filter), 'Title', $Keyword);
					}
					$results = true;
				}
			}

			// 5) Partial search on all fields

			if(! $results) {
				$Keyword = " ".trim(eregi_replace(" +", " ", $Keyword))." ";
				$kwarray = explode(" ", $Keyword);
				$vArray = array();
				if(count($kwarray)) {
					foreach($kwarray as $key=>$value) {
						$value = trim($value);
						if($value) {
							$vArray[] = "
								(
								 \"MenuTitle\" LIKE '%$value%'
									OR \"Title\" LIKE '%$value%'
									OR \"Content\" LIKE '%$value%'
									OR \"SPC\" LIKE '%$value%'
									OR \"InternalItemID\" LIKE '%$value%'
									OR \"CUS\" LIKE '%$value%'
								)";
						}
					}
				}
				$v = "(".implode(" AND ", $vArray).")";
				$this->addWhereFilterAndHumanFilterItem(
					"Keyword",
					$Keyword,
					array($v), // IN NATURAL LANGUAGE MODE - MATCH (\"Title\", \"MenuTitle\", \"Content\", \"Title\", \"MetaDescription\", \"MetaKeywords\") AGAINST ("'.$Keyword.'") OR Title
					"Keyword(s)",
					$Keyword
				);
			}
		}
		$this->saveWhereFilterAndHumanArray();
		if($TypeObject && !$BrandObject) {
			Session::set("ProductFilter.FilteredPage", $TypeObject->ID);
			$this->redirect($TypeObject->Link());
		}
		elseif(!$TypeObject && $BrandObject) {
			Session::set("ProductFilter.FilteredPage", $BrandObject->ID);
			$this->redirect($BrandObject->Link());
		}
		else {
			if($page = $this->ProductSearchPage()) {
				Session::set("ProductFilter.FilteredPage", $page->ID);
				$this->redirect($page->Link());
			}
			else {
				$this->redirect($this->Link());
			}
		}
	}
	/**
	 *
	 * @param Controller $controller
	 * @param String $name, Name of the form
	 */
	function __construct($controller, $name) {
		$member = Member::currentUser();
		$requiredFields = null;
		if($member && $member->exists()) {
			$fields = $member->getEcommerceFields(true);
			$clearCartAndLogoutLink = ShoppingCart_Controller::clear_cart_and_logout_link();
			$loginMessage =
				Convert::raw2xml($member->FirstName) . ' ' . Convert::raw2xml($member->Surname) .', '
				.'<a href="'.$clearCartAndLogoutLink.'">'._t('Account.LOGOUT','Log out now?').
				'</a>';
			if($loginMessage){
				$loginField = new ReadonlyField(
					'LoggedInAsNote',
					_t("Account.LOGGEDIN", "You are currently logged in as "),
					$loginMessage
				);
				$loginField->dontEscape = true;
				$fields->push($loginField);
			}
			$actions = new FieldList(
				new FormAction('submit', _t('Account.SAVE','Save Changes'))
			);
			if($order = ShoppingCart::current_order()) {
				if($order->getTotalItems()) {
					$actions->push(new FormAction('proceed', _t('Account.SAVE_AND_PROCEED','Save changes and proceed to checkout')));
				}
			}
		}
		else {
			if(!$member) {
				$member = new Member();
			}
			$fields = new FieldList();
			$urlParams = $controller->getURLParams();
			$backURLLink = "";
			if($urlParams) foreach($urlParams as $urlParam) {
				if($urlParam) {
					$backURLLink .= "/".$urlParam;
				}
			}
			$backURLLink = urlencode($backURLLink);
			$fields->push(new LiteralField('MemberInfo', '<p class="message good">'._t('OrderForm.MEMBERINFO','If you already have an account then please')." <a href=\"Security/login?BackURL=" . $backURLLink . "\">"._t('OrderForm.LOGIN','log in').'</a>.</p>'));
			$memberFields = $member->getEcommerceFields();
			if($memberFields) {
				foreach($memberFields as $memberField) {
					$fields->push($memberField);
				}
			}
			$passwordField = new PasswordField('PasswordCheck1', _t('Account.PASSWORD','Password'));
			$passwordFieldCheck = new PasswordField('PasswordCheck2', _t('Account.PASSWORDCHECK','Password (repeat)'));
			$fields->push($passwordField);
			$fields->push($passwordFieldCheck);
			$actions = new FieldList(
				new FormAction('creatememberandaddtoorder', _t('Account.SAVE','Create Account'))
			);
		}


		$requiredFields = ShopAccountForm_Validator::create($member->getEcommerceRequiredFields());
		parent::__construct($controller, $name, $fields, $actions, $requiredFields);
		$this->setAttribute("autocomplete", "off");
		//extensions need to be set after __construct
		if($this->extend('updateFields',$fields) !== null) {$this->setFields($fields);}
		if($this->extend('updateActions',$actions) !== null) {$this->setActions($actions);}
		if($this->extend('updateValidator',$requiredFields) !== null) {$this->setValidator($requiredFields);}
		if($member){
			$this->loadDataFrom($member);
		}
		$oldData = Session::get("FormInfo.{$this->FormName()}.data");
		if($oldData && (is_array($oldData) || is_object($oldData))) {
			$this->loadDataFrom($oldData);
		}
		$this->extend('updateShopAccountForm',$this);
	}


	/**
	 * Save the changes to the form, and go back to the account page.
	 * @return Boolean + redirection
	 */
	function submit($data, $form, $request) {
		return $this->processForm($data, $form, $request, "");
	}

	/**
	 * Save the changes to the form, and redirect to the checkout page
	 * @return Boolean + redirection
	 */
	function proceed($data, $form, $request) {
		return $this->processForm($data, $form, $request, CheckoutPage::find_link());
	}


	function creatememberandaddtoorder($data, $form){
		$member = new Member();
		$order =  ShoppingCart::current_order();
		if($order && $order->exists()) {
			$form->saveInto($member);
			$password = ShopAccountForm_PasswordValidator::clean_password($data);
			if($password) {
				$member->changePassword($password);
				if($member->validate()){
					$member->write();
					if($member->exists()) {
						if(!$order->MemberID) {
							$order->MemberID = $member->ID;
							$order->write();
						}
						$member->login();
						$this->sessionMessage(_t("ShopAccountForm.SAVEDDETAILS", "Your details has been saved."), "good");
					}
					else {
						$this->sessionMessage(_t("ShopAccountForm.COULDNOTCREATEMEMBER", "Could not save your details."), "bad");
					}
				}
				else {
					$this->sessionMessage(_t("ShopAccountForm.COULDNOTCREATEMEMBER", "Could not save your details."), "bad");
				}
			}
		}
		else {
			$this->sessionMessage(_t("ShopAccountForm.COULDNOTFINDORDER", "Could not find order."), "bad");
		}
		$this->controller->redirectBack();
	}



	/**
	 *@return Boolean + redirection
	 **/
	protected function processForm($data, $form, $request, $link = "") {
		$member = Member::currentUser();
		if(!$member) {
			$form->sessionMessage(_t('Account.DETAILSNOTSAVED','Your details could not be saved.'), 'bad');
			$this->controller->redirectBack();
		}
		$form->saveInto($member);
		$password = ShopAccountForm_PasswordValidator::clean_password($data);
		if($password) {
			$member->changePassword($password);
		}
		elseif($data["PasswordCheck1"]) {
			$form->sessionMessage(_t('Account.NO_VALID_PASSWORD','You need to enter a valid password.'), 'bad');
			$this->controller->redirectBack();
		}
		if($member->validate()){
			if($link) {
				return $this->controller->redirect($link);
			}
			else {
				$form->sessionMessage(_t('Account.DETAILSSAVED','Your details have been saved.'), 'good');
				$this->controller->redirectBack();
			}
		}
		else {
			$form->sessionMessage(_t('Account.NO_VALID_DATA','Your details can not be updated.'), 'bad');
			$this->controller->redirectBack();
		}
	}


	/**
	 * saves the form into session
	 * @param Array $data - data from form.
	 */
	function saveDataToSession(){
		$data = $this->getData();
		unset($data["LoggedInAsNote"]);
		unset($data["PasswordCheck1"]);
		unset($data["PasswordCheck2"]);
		Session::set("FormInfo.{$this->FormName()}.data", $data);
	}

}


class ShopAccountForm_Validator extends RequiredFields{

	/**
	 * Ensures member unique id stays unique and other basic stuff...
	 * @param array $data = array Form Field Data
	 * @param Boolean $allowExistingEmail - see comment below
	 * @return Boolean
	 **/
	function php($data, $allowExistingEmail = false){
		$this->form->saveDataToSession();
		$valid = parent::php($data);
		$uniqueFieldName = Member::get_unique_identifier_field();
		$loggedInMember = Member::currentUser();
		$loggedInMemberID = 0;
		if(isset($data[$uniqueFieldName]) && $data[$uniqueFieldName]){
			$isShopAdmin = false;
			if($loggedInMember) {
				$loggedInMemberID = $loggedInMember->ID;
				if($loggedInMember->IsShopAdmin()) {
					$isShopAdmin = true;
				}
			}
			if($isShopAdmin) {
				//do nothing
			}
			else {
				$uniqueFieldValue = Convert::raw2sql($data[$uniqueFieldName]);
				//can't be taken
				$otherMembersWithSameEmail = Member::get()
					->filter(array($uniqueFieldName => $uniqueFieldValue))
					->exclude(array("ID" => $loggedInMemberID));
				if($otherMembersWithSameEmail->count()){
					//we allow existing email
					// if we are currently NOT logged in
					// in case we place an order!
					if($allowExistingEmail) {

					}
					else {
						$message = _t(
							"Account.ALREADYTAKEN",
							"{uniqueFieldValue} is already taken by another member. Please log in or use another {uniqueFieldName}",
							array("uniqueFieldValue" => $uniqueFieldValue, "uniqueFieldName" => $uniqueFieldName)
						);
						$this->validationError(
							$uniqueFieldName,
							$message,
							"required"
						);
						$valid = false;
					}
				}
				else {
					$uniqueFieldValue = Convert::raw2sql($data[$uniqueFieldName]);
					//can't be taken
					$memberExistsCheck = Member::get()
						->filter(
							array(
								$uniqueFieldName => $uniqueFieldValue,
								"ID" => $loggedInMemberID
							)
						)->exclude(
							array(
								"ID" => $loggedInMemberID
							)
						)->count();
					if($memberExistsCheck){
						$message = sprintf(
							_t("Account.ALREADYTAKEN",  '%1$s is already taken by another member. Please log in or use another %2$s'),
							$uniqueFieldValue,
							$uniqueFieldName
						);
						$this->validationError(
							$uniqueFieldName,
							$message,
							"required"
						);
						$valid = false;
					}
				}
			}
		}
		// check password fields are the same before saving
		if(isset($data["PasswordCheck1"]) && isset($data["PasswordCheck2"])) {
			if($data["PasswordCheck1"] != $data["PasswordCheck2"]) {
				$this->validationError(
					"PasswordCheck1",
					_t('Account.PASSWORDSERROR', 'Passwords do not match.'),
					"required"
				);
				$valid = false;
			}
			//if you are not logged in, you have not provided a password and the settings require you to be logged in then
			//we have a problem
			if( !$loggedInMember && !$data["PasswordCheck1"] && EcommerceConfig::get("EcommerceRole", "must_have_account_to_purchase") ) {
				$this->validationError(
					"PasswordCheck1",
					_t('Account.SELECTPASSWORD', 'Please select a password.'),
					"required"
				);
				$valid = false;
			}
			$letterCount = strlen($data["PasswordCheck1"]);
			if($letterCount > 0 && $letterCount < 7) {
				$this->validationError(
					"PasswordCheck1",
					_t('Account.PASSWORDMINIMUMLENGTH', 'Please enter a password of at least seven characters.'),
					"required"
				);
				$valid = false;
			}
		}
		//
		if(isset($data["FirstName"])) {
			if(strlen($data["FirstName"]) < 2) {
				$this->validationError(
					"FirstName",
					_t('Account.NOFIRSTNAME', 'Please enter your first name.'),
					"required"
				);
				$valid = false;
			}
		}
		if(isset($data["Surname"])) {
			if(strlen($data["Surname"]) < 2) {
				$this->validationError(
					"Surname",
					_t('Account.NOSURNAME', 'Please enter your surname.'),
					"required"
				);
				$valid = false;
			}
		}
		if(!$valid) {
			$this->form->sessionMessage(_t('Account.ERRORINFORM', 'We could not save your details, please check your errors below.'), "bad");
		}
		return $valid;
	}



}

class ProductSearchForm_Advanced extends ProductSearchForm {
	function ShortFilterForm() {
		$fields = new FieldList(
			new TextField("KeywordQuick", "", Session::get("ProductFilter.Keyword"))
		);
		$actions = new FieldList(
			new FormAction('doFilterForm', 'Go')
		);
		$form = new Form($this, "FilterFormQuick", $fields, $actions);
		return $form;
	}

}
