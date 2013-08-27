<?php
/**
 * @description: ShopAccountForm allows shop members to update their details.
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class ShopAccountForm extends Form {

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
			$loginField = new ReadonlyField(
				'LoggedInAsNote',
				_t("Account.LOGGEDIN", "You are currently logged in as "),
				Convert::raw2xml($member->FirstName) . ' ' . Convert::raw2xml($member->Surname) .', '
					.'<a href="'.$clearCartAndLogoutLink.'">'._t('Account.LOGOUT','Log out now?').
					"</a>"
			);
			$loginField->dontEscape = true;
			$fields->push($loginField);
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
			$member = new Member();
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
			$passwordField = new PasswordField('Password', _t('Account.PASSWORD','Password'));
			$passwordFieldCheck = new PasswordField('PasswordCheck', _t('Account.PASSWORDCHECK','Password (repeat)'));
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
		$member->afterLoadDataFrom($this->Fields());
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
			$member->write();
			if($member->exists()) {
				if(!$order->MemberID) {
					$order->MemberID = $member->ID;
					$order->write();
				}
				$member->login();
				$this->sessionMessage(_t("ShopAccountForm.SAVEDDETAILS", "Your order has been saved."), "good");
			}
			else {
				$this->sessionMessage(_t("ShopAccountForm.COULDNOTCREATEMEMBER", "Could not save your details."), "bad");
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
		$member->write();
		if($link) {
			$this->controller->redirect($link);
		}
		else {
			$form->sessionMessage(_t('Account.DETAILSSAVED','Your details have been saved.'), 'good');
			$this->controller->redirectBack();
		}
		return true;
	}

}


class ShopAccountForm_Validator extends RequiredFields{

	/**
	 * Ensures member unique id stays unique and other basic stuff...
	 * @param $data = array Form Field Data
	 * @return Boolean
	 **/
	function php($data, $allowExistingEmail = false){
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
		if(isset($data["Password"]) && isset($data["PasswordDoubleCheck"])) {
			if($data["Password"] != $data["PasswordDoubleCheck"]) {
				$this->validationError(
					"PasswordDoubleCheck",
					_t('Account.PASSWORDSERROR', 'Passwords do not match.'),
					"required"
				);
				$valid = false;
			}
			//if you are not logged in, you hvae not provided a password and the settings require you to be logged in then
			//we have a problem
			if( !$loggedInMember && !$data["Password"] && EcommerceConfig::get("EcommerceRole", "must_have_account_to_purchase") ) {
				$this->validationError(
					"Password",
					_t('Account.SELECTPASSWORD', 'Please select a password.'),
					"required"
				);
				$valid = false;
			}
			$letterCount = strlen($data["Password"]);
			if($letterCount > 0 && $letterCount < 7) {
				$this->validationError(
					"Password",
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
