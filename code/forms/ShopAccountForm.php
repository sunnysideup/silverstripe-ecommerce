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
			$fields->insertBefore(new HeaderField('LoginDetails',_t('Account.LOGINDETAILS','Login Details'), 3), "Email");
			$logoutLink = ShoppingCart_Controller::clear_cart_and_logout_link();
			$loginField = new ReadonlyField(
				'LoggedInAsNote',
				_t("Account.LOGGEDIN", "You are currently logged in as "),
				Convert::raw2xml($member->FirstName) . ' ' . Convert::raw2xml($member->Surname) .', '
					.'<a href="'.$logoutLink.'">'._t('Account.LOGOUT','Log out now?').
					"</a>"
			);
			$loginField->dontEscape = true;
			$fields->push($loginField);
			// PASSWORD KEPT CHANGING - SO I REMOVED IT FOR NOW - Nicolaas
			$passwordField = new ConfirmedPasswordField('Password', _t('Account.PASSWORD','Password'), "", null, true);
			$fields->push($passwordField);
			$actions = new FieldSet(
				new FormAction('submit', _t('Account.SAVE','Save Changes'))
			);
			if($order = ShoppingCart::current_order()) {
				if($order->Items()) {
					$actions->push(new FormAction('proceed', _t('Account.SAVEANDPROCEED','Save changes and proceed to checkout')));
				}
			}
		}
		else {
			$member = new Member();
			$fields = new FieldSet();
			$fields->push(new HeaderField('SignUp', _t('ShopAccountForm.CREATEACCOUNT','Create Account')));
			$fields->push(new LiteralField('MemberInfo', '<p class="message good">'._t('OrderForm.MEMBERINFO','If you already have an account then please')." <a href=\"Security/login?BackURL=" . urlencode(implode("/", $controller->getURLParams())) . "\">"._t('OrderForm.LOGIN','log in').'</a>.</p>'));
			$memberFields = $member->getEcommerceFields(true);
			if($memberFields) {
				foreach($memberFields as $memberField) {
					$fields->push($memberField);
				}
			}
			$passwordField = new PasswordField('Password', _t('Account.PASSWORD','Password'));
			$passwordFieldCheck = new PasswordField('PasswordCheck', _t('Account.PASSWORDCHECK','Password (repeat)'));
			$fields->push($passwordField);
			$fields->push($passwordFieldCheck);
			$actions = new FieldSet(
				new FormAction('creatememberandaddtoorder', _t('Account.SAVE','Create Account'))
			);
		}

		$requiredFields = new ShopAccountForm_Validator($member->getEcommerceRequiredFields());
		parent::__construct($controller, $name, $fields, $actions, $requiredFields);
		//extensions need to be set after __construct
		if($this->extend('updateFields',$fields) !== null) {$this->setFields($fields);}
		if($this->extend('updateActions',$actions) !== null) {$this->setActions($actions);}
		if($this->extend('updateValidator',$requiredFields) !== null) {$this->setValidator($requiredFields);}
		if($member && $member->Password ){
			$this->loadDataFrom($member);
			if(!isset($_REQUEST["Password"])) {
				$this->fields()->fieldByName("Password")->SetValue("");
			}
			$this->fields()->fieldByName("Password")->setCanBeEmpty(true);
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
		Director::redirectBack();
	}



	/**
	 *@return Boolean + redirection
	 **/
	protected function processForm($data, $form, $request, $link = "") {
		$member = Member::currentUser();
		if(!$member) {
			$form->sessionMessage(_t('Account.DETAILSNOTSAVED','Your details could not be saved.'), 'bad');
			Director::redirectBack();
		}
		$form->saveInto($member);
		$member->write();
		if($link) {
			Director::redirect($link);
		}
		else {
			$form->sessionMessage(_t('Account.DETAILSSAVED','Your details have been saved.'), 'good');
			Director::redirectBack();
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
	function php($data){
		$valid = parent::php($data);
		$uniqueFieldName = Member::get_unique_identifier_field();
		$loggedInMember = Member::currentUser();
		if(isset($data[$uniqueFieldName]) && $loggedInMember && $data[$uniqueFieldName]){
			if(!$loggedInMember->IsShopAdmin()) {
				$uniqueFieldValue = Convert::raw2sql($data[$uniqueFieldName]);
				//can't be taken
				if(DataObject::get_one('Member',"\"$uniqueFieldName\" = '$uniqueFieldValue' AND \"Member\".\"ID\" <> ".$loggedInMember->ID)){
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
		// check password fields are the same before saving
		if(isset($data["Password"]["_Password"]) && isset($data["Password"]["_ConfirmPassword"])) {
			if($data["Password"]["_Password"] != $data["Password"]["_ConfirmPassword"]) {
				$this->validationError(
					"Password",
					_t('Account.PASSWORDSERROR', 'Passwords do not match.'),
					"required"
				);
				$valid = false;
			}
			if(!$loggedInMember && !$data["Password"]["_Password"]) {
				$this->validationError(
					"Password",
					_t('Account.SELECTPASSWORD', 'Please select a password.'),
					"required"
				);
				$valid = false;
			}
		}
		//password for new user
		if(isset($data["PasswordCheck"]) && isset($data["Password"])) {
			if($data["PasswordCheck"] != $data["Password"]) {
				$this->validationError(
					"Password",
					_t('Account.PASSWORDSERROR', 'Passwords do not match.'),
					"required"
				);
				$valid = false;
			}
			if(strlen($data["Password"]) < 7 ) {
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
