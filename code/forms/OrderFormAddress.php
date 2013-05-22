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

	/**
	 * ID of the member that has just been created.
	 * @var Order
	 */
	protected $order = null;

	/**
	 *
	 * @param Controller
	 * @param String
	 */
	function __construct(Controller $controller, $name) {

		//set basics
		$requiredFields = array();

		//requirements
		Requirements::javascript('ecommerce/javascript/EcomOrderFormAddress.js'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		if(EcommerceConfig::get("OrderAddress", "use_separate_shipping_address")) {
			Requirements::javascript('ecommerce/javascript/EcomOrderFormShipping.js'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		}

		//  ________________ 1) Order + Member + Address fields

		//find member
		$this->order = ShoppingCart::current_order();
		$this->orderMember = $this->order->CreateOrReturnExistingMember(false);
		$this->loggedInMember = Member::currentUser();

		//strange security situation...
		if($this->orderMember->exists() && $this->loggedInMember) {
			if($this->orderMember->ID != $this->loggedInMember->ID) {
				if(!$this->loggedInMember->IsShopAdmin()) {
					$this->loggedInMember->logOut();
				}
			}
		}

		$addressFieldsBilling = new FieldList();

		//member fields
		if($this->orderMember) {
			$memberFields = $this->orderMember->getEcommerceFields();
			$requiredFields = array_merge($requiredFields, $this->orderMember->getEcommerceRequiredFields());
			$addressFieldsBilling->merge($memberFields);
		}

		//billing address field
		$billingAddress = $this->order->CreateOrReturnExistingAddress("BillingAddress");
		$billingAddressFields = $billingAddress->getFields($this->orderMember);
		$requiredFields = array_merge($requiredFields, $billingAddress->getRequiredFields());
		$addressFieldsBilling->merge($billingAddressFields);

		//shipping address field
		$addressFieldsShipping = null;
		if(EcommerceConfig::get("OrderAddress", "use_separate_shipping_address")) {
			$addressFieldsShipping = new FieldList();
			//add the important CHECKBOX
			$useShippingAddressField = new FieldList(new CheckboxField("UseShippingAddress", _t("OrderForm.USESHIPPINGADDRESS", "Use an alternative shipping address")));
			$addressFieldsShipping->merge($useShippingAddressField);
			//now we can add the shipping fields
			$shippingAddress = $this->order->CreateOrReturnExistingAddress("ShippingAddress");
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

			}
			else {
				if($this->loggedInMember) {
					$rightFields->push(
						new LiteralField(
							'LoginNote',
							"<p class=\"message good\">" . _t("Account.LOGGEDIN","You are logged in as ") .
							Convert::raw2xml($this->loggedInMember->FirstName) . ' ' .
							Convert::raw2xml($this->loggedInMember->Surname) .
							' ('.Convert::raw2xml($this->loggedInMember->Email).').'.
							' <a href="/Security/logout/">'.
							_t("Account.LOGOUTNOW","Log out?").
							'</a>'.
							'</p>'
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


		//  ________________  5) Put all the fields in one FieldList


		$fields = new FieldList($rightFields, $allLeftFields);



		//  ________________  6) Actions and required fields creation + Final Form construction

		$nextButton = new FormAction('saveAddress', _t('OrderForm.NEXT','Next'));
		$nextButton->addExtraClass("next");
		$actions = new FieldList($nextButton);
		$validator = new OrderFormAddress_Validator($requiredFields);
		foreach($requiredFields as $requiredField) {
			$field = $fields->dataFieldByName($requiredField);
			if($field) {
				$field->addExtraClass("required");
			}
		}
		parent::__construct($controller, $name, $fields, $actions, $validator);
		$this->setAttribute("autocomplete", "off");
		//extensions need to be set after __construct
		if($this->extend('updateFields', $fields) !== null) {$this->setFields($fields);}
		if($this->extend('updateActions', $actions) !== null) {$this->setActions($actions);}
		if($this->extend('updateValidator', $validator) !== null) {$this->setValidator($validator);}

		//  ________________  7)  Load saved data

		//we do this first so that Billing and Shipping Address can override this...
		if ($this->orderMember) {
			$this->loadDataFrom($this->orderMember);
		}
		$this->orderMember->afterLoadDataFrom($this->Fields());

		if($this->order) {
			$this->loadDataFrom($this->order);
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
		//orderMember is Created in __CONSTRUCT
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
	function saveAddress(Array $data, Form $form, SS_HTTPRequest $request) {
		$data = Convert::raw2sql($data);
		$this->saveDataToSession($data); //save for later if necessary
		//check for cart items
		if(!$this->order) {
			$form->sessionMessage(_t('OrderForm.ORDERNOTFOUND','Your order could not be found.'), 'bad');
			$this->controller->redirectBack();
			return false;
		}
		if($this->order && ($this->order->TotalItems($recalculate = true) < 1) ) {
			// WE DO NOT NEED THE THING BELOW BECAUSE IT IS ALREADY IN THE TEMPLATE AND IT CAN LEAD TO SHOWING ORDER WITH ITEMS AND MESSAGE
			$form->sessionMessage(_t('OrderForm.NOITEMSINCART','Please add some items to your cart.'), 'bad');
			$this->controller->redirectBack();
			return false;
		}

		//PASSWORD HACK ... TO DO: test that you can actually update a password as the method below
		//does NOT change the FORM only DATA, but we save to the new details using $form->saveInto($member)
		//and NOT $data->saveInto($member)
		$password = $this->validPassword($data);

		//----------- START BY SAVING INTO ORDER
		$form->saveInto($this->order);
		//----------- --------------------------------

		//MEMBER
		$this->orderMember = $this->createOrFindMember($data);

		if($this->orderMember && is_object($this->orderMember)) {
			if($this->memberShouldBeSaved($data)) {
				$form->saveInto($this->orderMember);
				if($password) {
					$this->orderMember->changePassword($password);
				}
				$this->orderMember->write();
			}
			if($this->memberShouldBeLoggedIn($data)) {
				$this->orderMember->LogIn();
			}
		}

		//BILLING ADDRESS
		if($billingAddress = $this->order->CreateOrReturnExistingAddress("BillingAddress")) {
			$form->saveInto($billingAddress);
			// NOTE: write should return the new ID of the object
			$this->order->BillingAddressID = $billingAddress->write();
		}

		// SHIPPING ADDRESS
		if(isset($data['UseShippingAddress'])){
			if($data['UseShippingAddress']) {
				if($shippingAddress = $this->order->CreateOrReturnExistingAddress("ShippingAddress")) {
					$form->saveInto($shippingAddress);
					// NOTE: write should return the new ID of the object
					$this->order->ShippingAddressID = $shippingAddress->write();
				}
			}
		}

		//SAVE ORDER
		$this->order->write();

		//----------------- CLEAR OLD DATA ------------------------------
		$this->clearSessionData(); //clears the stored session form data that might have been needed if validation failed
		//-----------------------------------------------

		$nextStepLink = CheckoutPage::find_next_step_link("orderformaddress");
		$this->controller->redirect($nextStepLink);
		return true;
	}

	/**
	 * saves the form into session
	 * @param Array $data - data from form.
	 */
	function saveDataToSession(Array $data){
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
	 * 2. shop allows creation of member
	 * 3. can the entered data be used?
	 * 4. is there no member logged in yet?
	 * 5. find member from data entered (even if not logged in)
	 * 6. At this stage, if we dont have a member, we will create one!
	 * 7. We do one last check to see if we are allowed to create one
	 *
	 * @param Array - form data - should include $data[uniqueField....] - e.g. $data["Email"]
	 * @return Member | Null
	 **/
	protected function createOrFindMember(Array $data) {
		$this->orderMember = $this->order->CreateOrReturnExistingMember(false);

		// 1. does the order already have a member
		if($this->orderMember->exists()) {
			//do nothing
		}

		// 2. shop allows creation of member
		elseif(EcommerceConfig::get("EcommerceRole", "allow_customers_to_setup_accounts")) {
			$this->orderMember = null;

			//3. can the entered data be used?
			//member that will be added does not exist somewhere else.
			if($this->uniqueMemberFieldCanBeUsed($data)) {

				// 4. is there no member logged in yet?
				//no logged in member
				if(!$this->loggedInMember) {

					//5. find member from data entered (even if not logged in)
					//another member with the same email?
					$this->orderMember = $this->anotherExistingMemberWithSameUniqueFieldValue($data);

					//6. At this stage, if we dont have a member, we will create one!
					//in case we still dont have a member AND we should create a member for every customer, then we do this below...
					if(!$this->orderMember) {

						// 7. We do one last check to see if we are allowed to create one
						//are we allowed to create a member?
						if($this->memberShouldBeCreated($data)) {
							$this->orderMember = $this->order->CreateOrReturnExistingMember(false);
							$this->orderMember->write($forceCreation = true);
							$this->newlyCreatedMemberID = $this->orderMember->ID;
						}
					}
				}
			}
		}
		return $this->orderMember;
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
	protected function memberShouldBeCreated(Array $data) {
		if($this->loggedInMember || $this->newlyCreatedMemberID) {
			return false;
		}
		else {
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
	protected function memberShouldBeLoggedIn(Array $data) {
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
	protected function memberShouldBeSaved(Array $data) {
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
	 * - there is no existing member with the same value in the unique field
	 * - OR the member is not logged in.
	 * returns FALSE if
	 * - the unique field already exists in another member
	 * - AND the member being "tested" is already logged in...
	 * in that case the logged in member tries to take on another identity.
	 * If you are not logged BUT the the unique field is used by an existing member then we can still
	 * use the field - we just CAN NOT log in the member.
	 * This method needs to be public because it is used by the OrderForm_Validator (see below).
	 * @param Array - form data - should include $data[uniqueField....] - e.g. $data["Email"]
	 * @return Boolean
	 **/
	public function uniqueMemberFieldCanBeUsed(Array $data) {
		if($this->anotherExistingMemberWithSameUniqueFieldValue($data) && $this->loggedInMember) {
			//there is an exception for shop admins
			//who can place an order on behalve of a customer.
			if($this->loggedInMember->IsShopAdmin()) {
				//but NOT when the member placing the Order is the ShopAdmin
				//AND there is another member with the same credentials.
				//because in that case the ShopAdmin is not placing an order
				//on behalf of someone else.
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
	protected function anotherExistingMemberWithSameUniqueFieldValue(Array $data) {
		$uniqueFieldName = Member::get_unique_identifier_field();
		//The check below covers both Scenario 3 and 4....
		if(isset($data[$uniqueFieldName])) {
			if($this->loggedInMember) {
				$currentUserID = $this->loggedInMember->ID;
			}
			else {
				$currentUserID = 0;
			}
			$uniqueFieldValue = $data[$uniqueFieldName];
			//no need to convert raw2sql as this has already been done.
			return Member::get()
				->filter(
					array(
						$uniqueFieldName => $uniqueFieldValue,
					)
				)
				->exclude(
					array(
						"ID" => $currentUserID
					)
				)
				->First();
		}
		user_error("No email data was set, suspicious transaction", E_USER_WARNING);
		return null;
	}

	/**
	 * Check if the password is good enough
	 * @param data (from form)
	 * @return String
	 */
	protected function validPassword($data){
		if(isset($data['Password']) && isset($data['PasswordDoubleCheck'])) {
			if(isset($data['Password']) && isset($data['PasswordDoubleCheck'])) {
				if($data['Password'] == $data['PasswordDoubleCheck']) {
					if(strlen($data["Password"]) >= 7) {
						return Convert::raw2sql($data["Password"]);
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
		if(Member::currentUserID()) {
			$allowExistingEmail = false;
		}
		else {
			$allowExistingEmail = true;
		}
		$valid = parent::php($data, $allowExistingEmail);
		//Note the exclamation Mark - only applies if it return FALSE.
		if($this->form->uniqueMemberFieldCanBeUsed($data)) {
			//do nothing
		}
		else {
			$uniqueFieldName = Member::get_unique_identifier_field();
			$this->validationError(
				$uniqueFieldName,
				_t(
					"OrderForm.EMAILFROMOTHERUSER",
					'Sorry, an account with that email is already in use by another customer. If this is your email address then please log in first before placing your order.'
				),
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


