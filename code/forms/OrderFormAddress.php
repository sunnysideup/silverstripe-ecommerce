<?php

/**
 * This class is the form for editing the Order Addresses.
 * It is also used to link the order to a member.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/



class OrderFormAddress extends Form {


	/**
	 *
	 * @var Object (Member)
	 */
	protected $orderMember = null;

	/**
	 *
	 * @var Object (Member)
	 */
	protected $loggedInMember = null;

	/**
	 * ID of the member that has just been created.
	 * @var Int
	 */
	protected $newlyCreatedMemberID = 0;

	function __construct($controller, $name) {


		//requirements
		Requirements::javascript('ecommerce/javascript/EcomOrderFormAddress.js'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		if(EcommerceConfig::get("OrderAddress", "use_separate_shipping_address")) {
			Requirements::javascript('ecommerce/javascript/EcomOrderFormShipping.js'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		}

		//set basics
		$order = ShoppingCart::current_order();
		$requiredFields = array();


		//  ________________ 1) Member + Address fields

		//find member
		$this->orderMember = $order->CreateOrReturnExistingMember(false);
		$this->loggedInMember = Member::currentUser();

		//strange security situation...
		if($this->orderMember->exists() && $this->loggedInMember) {
			if($this->orderMember->ID != $this->loggedInMember->ID) {
				if(!$this->loggedInMember->IsShopAdmin()) {
					$this->loggedInMember->logOut();
				}
			}
		}

		$addressFieldsBilling = new FieldSet();

		//member fields
		if($this->orderMember) {
			$memberFields = $this->orderMember->getEcommerceFields();
			$requiredFields = array_merge($requiredFields, $this->orderMember->getEcommerceRequiredFields());
			$addressFieldsBilling->merge($memberFields);
		}

		//billing address field
		$billingAddress = $order->CreateOrReturnExistingAddress("BillingAddress");
		$billingAddressFields = $billingAddress->getFields($this->orderMember);
		$requiredFields = array_merge($requiredFields, $billingAddress->getRequiredFields());
		$addressFieldsBilling->merge($billingAddressFields);

		//shipping address field
		$addressFieldsShipping = null;
		if(EcommerceConfig::get("OrderAddress", "use_separate_shipping_address")) {
			$addressFieldsShipping = new FieldSet();
			//add the important CHECKBOX
			$useShippingAddressField = new FieldSet(new CheckboxField("UseShippingAddress", _t("OrderForm.USESHIPPINGADDRESS", "Use an alternative shipping address")));
			$addressFieldsShipping->merge($useShippingAddressField);
			//now we can add the shipping fields
			$shippingAddress = $order->CreateOrReturnExistingAddress("ShippingAddress");
			$shippingAddressFields = $shippingAddress->getFields($this->orderMember);
			//we have left this out for now as it was giving a lot of grief...
			//$requiredFields = array_merge($requiredFields, $shippingAddress->getRequiredFields());
			//finalise left fields
			$addressFieldsShipping->merge($shippingAddressFields);
		}
		$leftFields = new CompositeField($addressFieldsBilling);
		$leftFields->setID('LeftOrderBilling');
		$allLeftFields = new CompositeField($leftFields);
		$allLeftFields->setID('LeftOrder');
		if($addressFieldsShipping) {
			$leftFieldsShipping = new CompositeField($addressFieldsShipping);
			$leftFieldsShipping->setID('LeftOrderShipping');
			$allLeftFields->push($leftFieldsShipping);
		}


		//  ________________  2) Log in / vs Create Account fields - RIGHT-HAND-SIDE fields


		$rightFields = new CompositeField();
		$rightFields->setID('RightOrder');
		//to do: simplify
		if(EcommerceConfig::get("EcommerceRole", "allow_customers_to_setup_accounts")) {
			if($this->orderDoesNotHaveFullyOperationalMember()) {
				//general header
				if(!$this->loggedInMember) {
					$rightFields->push(
						//TODO: check EXACT link!!!
						new LiteralField('MemberInfo', '<p class="message good">'._t('OrderForm.MEMBERINFO','If you already have an account then please')." <a href=\"Security/login/?BackURL=/" . urlencode(implode("/", $controller->getURLParams())) . "\">"._t('OrderForm.LOGIN','log in').'</a>.</p>')
					);
				}
				$passwordField = new ConfirmedPasswordField('Password', _t('OrderForm.PASSWORD','Password'));
				$passwordField->setValue("");
				//login invite right on the top
				if(EcommerceConfig::get("EcommerceRole", "automatic_membership")) {
					$rightFields->push(new HeaderField('CreateAnAccount',_t('OrderForm.CREATEANACCONTOPTIONAL','Create an account (optional)'), 3));
					//allow people to purchase without creating a password
					$passwordField->setCanBeEmpty(true);
					$rightFields->push(
						new LiteralField(
							'AccountInfo',
							'<p>'.
							_t('OrderForm.ACCOUNTINFO','Please <a href="#Password" class="choosePassword">choose a password</a>; this will allow you to check your order history in the future.')
							.'</p>'
						)
					);
					//close by default
				}
				else {
					$rightFields->push(new HeaderField('CreateAnAccount', _t('OrderForm.SETUPYOURACCOUNT','Create an account'), 3));
					//dont allow people to purchase without creating a password
					$passwordField->setCanBeEmpty(false);
					$rightFields->push(
						new LiteralField(
							'AccountInfo',
							'<p>'.
							_t('OrderForm.MUSTCREATEPASSWORD','Please choose a password to create your account.')
							.'</p>'
						)
					);
				}
				$requiredFields[] = 'Password[_Password]';
				$requiredFields[] = 'Password[_ConfirmPassword]';
				$rightFields->push(new FieldGroup($passwordField));
			}
			else {
				if($this->loggedInMember) {
					$rightFields->push(
						new LiteralField(
							'LoginNote',
							"<p class=\"message good\">" . _t("Account.LOGGEDIN","You are logged in as ") .
							Convert::raw2xml($this->loggedInMember->FirstName) . ' ' .
							Convert::raw2xml($this->loggedInMember->Surname) .
							' ('.Convert::raw2xml($this->loggedInMember->Email).').</p>'
						)
					);
				}
			}
			if($this->orderMember->exists()) {
				if($this->loggedInMember) {
					if($this->loggedInMember->ID !=  $this->orderMember->ID) {
						$rightFields->push(
							new LiteralField(
								'OrderAddedTo',
								"<p class=\"message good\">" .
								_t("Account.ORDERADDEDTO","Order will be added to ") .
								Convert::raw2xml($this->orderMember->FirstName) . ' ' .
								Convert::raw2xml($this->orderMember->Surname) . ' ('.
								Convert::raw2xml($this->orderMember->Email).
								').</p>'
							)
						);
					}
				}
			}
		}


		//  ________________  5) Put all the fields in one FieldSet


		$fields = new FieldSet($rightFields, $allLeftFields);



		//  ________________  6) Actions and required fields creation + Final Form construction

		$nextButton = new FormAction('saveAddress', _t('OrderForm.NEXT','Next'));
		$nextButton->addExtraClass("next");
		$actions = new FieldSet($nextButton);
		$validator = new OrderFormAddress_Validator($requiredFields);
		//TODO: do we need this here?
		//$validator->setJavascriptValidationHandler("prototype");
		foreach($requiredFields as $requiredField) {
			$field = $fields->dataFieldByName($requiredField);
			if($field) {
				$field->addExtraClass("required");
			}
		}
		parent::__construct($controller, $name, $fields, $actions, $validator);
		//extensions need to be set after __construct
		if($this->extend('updateFields', $fields) !== null) {$this->setFields($fields);}
		if($this->extend('updateActions', $actions) !== null) {$this->setActions($actions);}
		if($this->extend('updateValidator', $validator) !== null) {$this->setValidator($validator);}

		//  ________________  7)  Load saved data

		//we do this first so that Billing and Shipping Address can override this...
		if ($this->orderMember) {
			$this->loadDataFrom($this->orderMember);
		}

		if($order) {
			$this->loadDataFrom($order);
			if($billingAddress) {
				$this->loadDataFrom($billingAddress);
			}
			if(EcommerceConfig::get("OrderAddress", "use_separate_shipping_address")) {
				if ($shippingAddress) {
					$this->loadDataFrom($shippingAddress);
				}
			}
		}


		//allow updating via decoration
		$this->extend('updateOrderFormAddress',$this);


	}



	/**
	 * Is there a member that is fully operational?
	 * - saved
	 * - has password
	 * @return Boolean
	 */
	protected function orderHasFullyOperationalMember(){
		if($this->orderMember) {
			if($this->orderMember->exists()) {
				if($this->orderMember->Password) {
					return true;
				}
			}
		}
	}

	/**
	 * Opposite of orderHasFullyOperationalMember method.
	 * @return Boolean
	 */
	protected function orderDoesNotHaveFullyOperationalMember(){
		return $this->orderHasFullyOperationalMember() ? false : true;
	}


	/**
	 * Process the items in the shopping cart from session,
	 * creating a new {@link Order} record, and updating the
	 * customer's details {@link Member} record.
	 *
	 * {@link Payment} instance is created, linked to the order,
	 * and payment is processed {@link Payment::processPayment()}
	 *
	 * @param array $data Form request data submitted from OrderForm
	 * @param Form $form Form object for this action
	 * @param HTTPRequest $request Request object for this action
	 */
	function saveAddress($data, $form, $request) {
		$this->saveDataToSession($data); //save for later if necessary
		$order = ShoppingCart::current_order();
		//check for cart items
		if(!$order) {
			$form->sessionMessage(_t('OrderForm.ORDERNOTFOUND','Your order could not be found.'), 'bad');
			Director::redirectBack();
			return false;
		}
		if($order && ($order->TotalItems($recalculate = true) < 1) ) {
			// WE DO NOT NEED THE THING BELOW BECAUSE IT IS ALREADY IN THE TEMPLATE AND IT CAN LEAD TO SHOWING ORDER WITH ITEMS AND MESSAGE
			$form->sessionMessage(_t('OrderForm.NOITEMSINCART','Please add some items to your cart.'), 'bad');
			Director::redirectBack();
			return false;
		}

		//PASSWORD HACK ... TO DO: test that you can actually update a password as the method below
		//does NOT change the FORM only DATA, but we save to the new details using $form->saveInto($member)
		//and NOT $data->saveInto($member)
		$password = $this->validPassword($data);

		//----------- START BY SAVING INTO ORDER
		$form->saveInto($order);
		//----------- --------------------------------

		//MEMBER
		$member = $this->createOrFindMember($data);

		if($member && is_object($member)) {
			if($this->memberShouldBeSaved($data)) {
				$form->saveInto($member);
				if($password) {
					$member->changePassword($password);
				}
				$member->write();
			}
			if($this->memberShouldBeLoggedIn($data)) {
				$member->LogIn();
			}
		}

		//BILLING ADDRESS
		if($billingAddress = $order->CreateOrReturnExistingAddress("BillingAddress")) {
			$form->saveInto($billingAddress);
			// NOTE: write should return the new ID of the object
			$order->BillingAddressID = $billingAddress->write();
		}

		// SHIPPING ADDRESS
		if(isset($data['UseShippingAddress'])){
			if($data['UseShippingAddress']) {
				if($shippingAddress = $order->CreateOrReturnExistingAddress("ShippingAddress")) {
					$form->saveInto($shippingAddress);
					// NOTE: write should return the new ID of the object
					$order->ShippingAddressID = $shippingAddress->write();
				}
			}
		}

		//SAVE ORDER
		$order->write();

		//----------------- CLEAR OLD DATA ------------------------------
		$this->clearSessionData(); //clears the stored session form data that might have been needed if validation failed
		//-----------------------------------------------

		$nextStepLink = CheckoutPage::find_next_step_link("orderformaddress");
		Director::redirect($nextStepLink);
		return true;
	}

	/**
	 * saves the form into session
	 * @param Array $data - data from form.
	 */
	function saveDataToSession($data){
		Session::set("FormInfo.{$this->FormName()}.data", $data);
	}

	/**
	 * loads the form data from the session
	 * @return Array
	 */
	function loadDataFromSession(){
		if($data = Session::get("FormInfo.{$this->FormName()}.data")){
			$this->loadDataFrom($data);
		}
	}


	/**
	 * clear the form data (after the form has been submitted and processed)
	 */
	function clearSessionData(){
		$this->clearMessage();
		Session::set("FormInfo.{$this->FormName()}.data", null);
	}


	/**
	 * works out the most likely member for the order after submission of the form.
	 * It returns a member if appropriate.
	 * 1. does the order already have a member?
	 * 2. can we create a member
	 * 3. can the entered data be used?
	 * 4. is there no member yet?
	 * 5. find member from data entered (even if not logged in)
	 * At this stage, if we dont have a member, we will create one!
	 * @param Array - form data - should include $data[uniqueField....] - e.g. $data["Email"]
	 * @return DataObject| Null
	 **/
	protected function createOrFindMember($data) {
		$order = ShoppingCart::current_order();
		$member = $order->CreateOrReturnExistingMember(false);
		if($member->exists()) {
			//do nothing
		}
		elseif(EcommerceConfig::get("EcommerceRole", "allow_customers_to_setup_accounts")) {
			$member = null;
			//member that will be added does not exist somewhere else.
			if($this->uniqueMemberFieldCanBeUsed($data)) {
				//no logged in member
				if(!$this->loggedInMember) {
					//another member with the same email?
					$member = $this->anotherExistingMemberWithSameUniqueFieldValue($data);
					//in case we still dont have a member AND we should create a member for every customer, then we do this below...
					if(!$member) {
						//are we allowed to create a member?
						if($this->memberShouldBeCreated($data)) {
							$member = $order->CreateOrReturnExistingMember(false);
							$member->write($forceCreation = true);
							$this->newlyCreatedMemberID = $member->ID;
						}
					}
				}
			}
		}
		return $member;
	}

	/**
	 *returns TRUE if
	 * - the member is not logged in
	 * - AND non-members are automatically created OR password has been provided
	 * - AND unique field does not exist already (someone else has used that email)
	 *
	 * @Todo: explain why password needs to be more than three characters...
	 * @todo: create class that checks if password is good enough
	 * @param Array - form data - should include $data[uniqueField....] - e.g. $data["Email"]
	 * @return Boolean
	 **/
	protected function memberShouldBeCreated($data) {
		if(!$this->loggedInMember && !$this->newlyCreatedMemberID) {
			$automaticMembership = EcommerceConfig::get("EcommerceRole", "automatic_membership");
			$validPassword = $this->validPassword($data);
			if( $automaticMembership || $validPassword) {
				if(!$this->anotherExistingMemberWithSameUniqueFieldValue($data)){
				 return true;
				}
			}
		}
		return false;
	}


	/**
	 * returns TRUE if
	 * - the member is not logged in
	 * - AND non-members are automatically created OR password has been provided
	 * - AND unique field does not exist already (someone else has used that email)
	 *
	 * @param Array - form data - should include $data[uniqueField....] - e.g. $data["Email"]
	 * @return Boolean
	 **/
	protected function memberShouldBeLoggedIn($data) {
		if(!$this->loggedInMember) {
			if($this->newlyCreatedMemberID && $this->validPassword($data)) {
				return true;
			}
		}
		return false;
	}


	/**
	 * returns TRUE
	 * - if member should be logged-in OR
	 * - member is logged and the unique field matches and member data is automatically updated.
	 * @param Array - form data - should include $data[uniqueField....] - e.g. $data["Email"]
	 * @return Boolean
	 **/
	protected function memberShouldBeSaved($data) {
		$a = $this->memberShouldBeCreated($data) ? true : false;
		$b = ($this->loggedInMember && !$this->anotherExistingMemberWithSameUniqueFieldValue($data) && EcommerceConfig::get("EcommerceRole", "automatically_update_member_details")) ? true : false;
		$c = $this->newlyCreatedMemberID ? true : false;
		if( ($a) || ($b) || ($c) ){
			return true;
		}
		return false;
	}


	/**
	 * returns TRUE if
	 * - there is no existing member with the unique field
	 * - OR the member is not logged in.
	 * returns FALSE if
	 * - the unique field already exists in another member
	 * - AND the member being "tested" is already logged in...
	 * in that case the logged in member tries to take on another identity.	 * If you are not logged BUT the the unique field is used by an existing member then we can still
	 * use the field - we just CAN NOT log in the member.
	 * This method needs to be public because it is used by the OrderForm_Validator (see below).
	 * @param Array - form data - should include $data[uniqueField....] - e.g. $data["Email"]
	 * @return Boolean
	 **/
	public function uniqueMemberFieldCanBeUsed($data) {
		if($this->anotherExistingMemberWithSameUniqueFieldValue($data) && $this->loggedInMember) {
			if($this->loggedInMember->IsShopAdmin()) {
				if($this->orderMember->ID == $this->loggedInMember->ID) {
					return false;
				}
			}
			else {
				return false;
			}
		}
		return true;
	}

	/**
	 * returns existing member if it already exists and it is not the logged-in one.
	 * Based on the unique field (email)).
	 * @param Array - form data - should include $data[uniqueField....] - e.g. $data["Email"]
	 * @return  Null | DataObject (Member)
	 **/
	protected function anotherExistingMemberWithSameUniqueFieldValue($data) {
		$uniqueField = Member::get_unique_identifier_field();
		//The check below covers both Scenario 3 and 4....
		if(isset($data[$uniqueField])) {
			if($this->loggedInMember) {
				$currentUserID = $this->loggedInMember->ID;
			}
			else {
				$currentUserID = 0;
			}
			$uniqueFieldValue = Convert::raw2xml($data[$uniqueField]);
			return DataObject::get_one('Member', "\"$uniqueField\" = '{$uniqueFieldValue}' AND \"Member\".\"ID\" <> ".$currentUserID);
		}
		return null;
	}

	/**
	 * Check if the password is good enough
	 * @param data
	 * @return String
	 */
	protected function validPassword($data){
		if(isset($data['Password']) && is_array($data['Password'])) {
			if(isset($data['Password']['_Password']) && isset($data['Password']['_ConfirmPassword'])) {
				if($data['Password']['_Password'] == $data['Password']['_ConfirmPassword']) {
					if(strlen($data["Password"]['_Password']) > 3) {
						return $data["Password"]['_Password'];
					}
				}
			}
		}
		return "";
	}

}



/**
 * @Description: allows customer to make additional payments for their order
 *
 * @package: ecommerce
 * @authors: Silverstripe, Jeremy, Nicolaas
 **/

class OrderFormAddress_Validator extends ShopAccountForm_Validator{

	/**
	 * Ensures member unique id stays unique and other basic stuff...
	 * @param array $data = Form Data
	 * @return Boolean
	 */
	function php($data){
		$valid = parent::php($data);
		//Note the exclamation Mark - only applies if it return FALSE.
		if(!$this->form->uniqueMemberFieldCanBeUsed($data)) {
			$uniqueField = Member::get_unique_identifier_field();
			$this->validationError(
				$uniqueField,
				_t("OrderForm.EMAILFROMOTHERUSER", 'Sorry, an account with that email is already in use by another customer. If this is your email address then please log in first before placing your order.'),
				"required"
			);
			$valid = false;
		}
		if(!$valid) {
			$this->form->sessionMessage(_t("OrderForm.ERRORINFORM", "We could not proceed with your order, please check your errors below."), "bad");
			$this->form->messageForForm("OrderForm", _t("OrderForm.ERRORINFORM", "We could not proceed with your order, please check your errors below."), "bad");
		}
		return $valid;
	}

}


