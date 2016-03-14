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
	 * @var boolean
	 */
	protected $debug = false;


	/**
	 * @var array
	 */
	protected $debugArray = array();

	/**
	 * the member attached to the order
	 * this is not always the same as the loggedInMember
	 * @var Object (Member)
	 */
	protected $orderMember = null;

	/**
	 * the logged in member, if any
	 * this is not always the same as the orderMember
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
		$leftFields->addExtraClass('leftOrderBilling');
		$allLeftFields = new CompositeField($leftFields);
		$allLeftFields->addExtraClass('leftOrder');
		if($addressFieldsShipping) {
			$leftFieldsShipping = new CompositeField($addressFieldsShipping);
			$leftFieldsShipping->addExtraClass('leftOrderShipping');
			$allLeftFields->push($leftFieldsShipping);
		}


		//  ________________  2) Log in / vs Create Account fields - RIGHT-HAND-SIDE fields


		$rightFields = new CompositeField();
		$rightFields->addExtraClass('rightOrder');
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

		$validator = OrderFormAddress_Validator::create($requiredFields);

		parent::__construct($controller, $name, $fields, $actions, $validator);
		$this->setAttribute("autocomplete", "off");
		//extensions need to be set after __construct
		//extension point
		$this->extend('updateFields', $fields); $this->setFields($fields);
		$this->extend('updateActions', $actions); $this->setActions($actions);
		$this->extend('updateValidator', $validator); $this->setValidator($validator);

		//this needs to come after the extension calls
		foreach($validator->getRequired() as $requiredField) {
			$field = $fields->dataFieldByName($requiredField);
			if($field) {
				$field->addExtraClass("required");
			}
		}

		//  ________________  7)  Load saved data

		//we do this first so that Billing and Shipping Address can override this...
		if ($this->orderMember) {
			$this->loadDataFrom($this->orderMember);
		}

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
		$oldData = Session::get("FormInfo.{$this->FormName()}.data");
		if($oldData && (is_array($oldData) || is_object($oldData))) {
			$this->loadDataFrom($oldData);
		}

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
		Session::set("BillingEcommerceGeocodingFieldValue", empty($data["BillingEcommerceGeocodingField"]) ? null: $data["BillingEcommerceGeocodingField"]);
		Session::set("ShippingEcommerceGeocodingFieldValue", empty($data["ShippingEcommerceGeocodingField"]) ? null : $data["ShippingEcommerceGeocodingField"]);

		$data = Convert::raw2sql($data);
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

		//----------- START BY SAVING INTO ORDER
		$form->saveInto($this->order);
		//----------- --------------------------------

		//MEMBER
		$this->orderMember = $this->createOrFindMember($data);
		if($this->debug) {debug::log(implode("\r\n<hr />", $this->debugArray));}

		if($this->orderMember && is_object($this->orderMember)) {
			if($this->memberShouldBeSaved($data)) {
				$form->saveInto($this->orderMember);
				$password = $this->validPasswordHasBeenEntered($data);
				if($password) {
					$this->orderMember->changePassword($password);
				}
				$this->orderMember->write();
			}
			if($this->memberShouldBeLoggedIn($data)) {
				$this->orderMember->LogIn();
			}
			//this causes ERRORS ....
			$this->order->MemberID = $this->orderMember->ID;
			Session::set("Ecommerce_Member_For_Order", $this->orderMember->ID);
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

		$this->extend("saveAddressExtension", $data, $form, $order, $this->orderMember);

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
	function saveDataToSession(){
		$data = $this->getData();
		unset($data["AccountInfo"]);
		unset($data["LoginDetails"]);
		unset($data["LoggedInAsNote"]);
		unset($data["PasswordCheck1"]);
		unset($data["PasswordCheck2"]);
		Session::set("FormInfo.{$this->FormName()}.data", $data);
	}

	/**
	 * clear the form data (after the form has been submitted and processed)
	 */
	function clearSessionData(){
		$this->clearMessage();
		Session::set("FormInfo.{$this->FormName()}.data", null);
	}

	/**
	 * Works out the most likely member for the order after submission of the form.
	 * It returns a member if appropriate.
	 * 1. does the order already have a member that is not a shop-admin - if so - DONE.
	 * 2. shop allows creation of member? - if NOT return NULL
	 * A. is the logged in member the shop admin placing an order on behalf of someone else?
	 * A1. is the email entered different from the admin email?
	 * A2. attach to other member as new one or existing one.
	 * 3. can the entered data be used? - if
	 * 4. is there no member logged in yet? - If there is one return null, member is already linked to order.
	 * 5. find member from data entered (even if not logged in)
	 * 6. At this stage, if we dont have a member, we will create one!
	 * 7. We do one last check to see if we are allowed to create one
	 *
	 * @param Array - form data - should include $data[uniqueField....] - e.g. $data["Email"]
	 * @return Member | Null
	 **/
	protected function createOrFindMember(Array $data) {
		//get the best available from order.
		$this->orderMember = $this->order->CreateOrReturnExistingMember(false);
		$orderPlacedByShopAdmin = ($this->loggedInMember && $this->loggedInMember->IsShopAdmin()) ? true : false;
		//1. does the order already have a member
		if($this->orderMember->exists() && !$orderPlacedByShopAdmin) {
			if($this->debug) {$this->debugArray[] = "1. the order already has a member";}
		}
		else {
			//special shop admin situation:
			if($orderPlacedByShopAdmin) {
				if($this->debug) {$this->debugArray[] = "A1. shop admin places order ";}
				//2. does email match shopadmin email
				if($newEmail = $this->enteredEmailAddressDoesNotMatchLoggedInUser($data)) {
					$this->orderMember = null;
					if($this->debug) {$this->debugArray[] = "A2. email does not match shopadmin email - reset orderMember";}
					$this->orderMember = $this->anotherExistingMemberWithSameUniqueFieldValue($data);
					if($this->orderMember) {
						if($this->debug) {$this->debugArray[] = "A3. the other member already exists";}
					}
					elseif($this->memberShouldBeCreated($data)) {
						if($this->debug) {$this->debugArray[] = "A4. No other member found - creating new one";}
						$this->orderMember = Member::create();
						$this->orderMember->Email = Convert::raw2sql($newEmail);
						$this->orderMember->write($forceCreation = true);
						$this->newlyCreatedMemberID = $this->orderMember->ID;
					}
				}
			}
			else {
				if($this->debug) {$this->debugArray[] = "2. shop allows creation of member";}
				$this->orderMember = null;

				//3. can the entered data be used?
				//member that will be added does not exist somewhere else.
				if($this->uniqueMemberFieldCanBeUsed($data)) {

					if($this->debug) {$this->debugArray[] = "3. can the entered data be used?";}
					// 4. is there no member logged in yet?
					//no logged in member
					if(!$this->loggedInMember) {

						if($this->debug) {$this->debugArray[] = "4. is there no member logged in yet?";}
						//5. find member from data entered (even if not logged in)
						//another member with the same email?

						if($this->debug) {$this->debugArray[] = "5. find member from data entered (even if not logged in)";}
						$this->orderMember = $this->anotherExistingMemberWithSameUniqueFieldValue($data);

						//6. At this stage, if we dont have a member, we will create one!
						//in case we still dont have a member AND we should create a member for every customer, then we do this below...
						if(!$this->orderMember) {
							if($this->debug) {$this->debugArray[] = "6. No other member found";}
							// 7. We do one last check to see if we are allowed to create one
							//are we allowed to create a member?
							if($this->memberShouldBeCreated($data)) {
								if($this->debug) {$this->debugArray[] = "7. We do one last check to see if we are allowed to create one. CREATE NEW MEMBER";}
								$this->orderMember = $this->order->CreateOrReturnExistingMember(false);
								$this->orderMember->write($forceCreation = true);
								//this is safe because it is memberShouldBeCreated ... 
								$this->newlyCreatedMemberID = $this->orderMember->ID;
							}
						}
					}
				}
			}
		}
		return $this->orderMember;
	}

	/**
	 * Should a new member be created?
	 *
	 * @Todo: explain why password needs to be more than three characters...
	 * @todo: create class that checks if password is good enough
	 * @param Array - form data - should include $data[uniqueField....] - e.g. $data["Email"]
	 * @return Boolean
	 **/
	protected function memberShouldBeCreated(Array $data) {
		//shop admin and
		//data entered does not match shop admin and
		//data entered does not match existing member...
		//TRUE!
		if($this->loggedInMember && $this->loggedInMember->IsShopAdmin()) {
			if($this->enteredEmailAddressDoesNotMatchLoggedInUser($data)) {
				if($this->anotherExistingMemberWithSameUniqueFieldValue($data)) {
					return false;
				}
				else {
					return true;
				}
			}
		}
		// already logged in or already created...
		// FALSE!
		elseif($this->loggedInMember || $this->newlyCreatedMemberID) {
			return false;
		}
		// no other user exists with the email...
		// TRUE!
		else {
			if($this->anotherExistingMemberWithSameUniqueFieldValue($data)){
				return false;
			}
			else {
				return true;
			}
		}
		//defaults to FALSE...
		return false;
	}

	/**
	 * @param Array - form data - should include $data[uniqueField....] - e.g. $data["Email"]
	 * @return Boolean
	 **/
	protected function memberShouldBeSaved(Array $data) {

		//new members always need to be saved
		$newMember = (
			$this->memberShouldBeCreated($data) ||
			$this->newlyCreatedMemberID
		) ? true : false;

		// existing logged in members need to be saved if they are updateable
		// AND do not match someone else...
		$updateableMember = (
			$this->loggedInMember &&
			!$this->anotherExistingMemberWithSameUniqueFieldValue($data) &&
			EcommerceConfig::get("EcommerceRole", "automatically_update_member_details")
		) ? true : false;

		// logged in member is shop admin and members are updateable...
		$memberIsShopAdmin = (
			$this->loggedInMember &&
			$this->loggedInMember->IsShopAdmin() &&
			EcommerceConfig::get("EcommerceRole", "automatically_update_member_details")
		) ? true : false;
		if( $newMember || $updateableMember || $memberIsShopAdmin){
			return true;
		}
		return false;
	}

	/**
	 * returns TRUE if
	 * - the member is not logged in
	 * - the member is new AND
	 * - the password is valid
	 *
	 * @param Array - form data - should include $data[uniqueField....] - e.g. $data["Email"]
	 * @return Boolean
	 **/
	protected function memberShouldBeLoggedIn(Array $data) {
		if(!$this->loggedInMember) {
			if($this->newlyCreatedMemberID && $this->validPasswordHasBeenEntered($data)) {
				return true;
			}
		}
		return false;
	}



	/**
	 * returns TRUE if
	 * - there is no existing member with the same value in the unique field
	 * - OR the member is not logged in.
	 * - OR the member is a Shop Admin (we assume they are placing an order on behalf of someone else).
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
		if($this->loggedInMember && $this->anotherExistingMemberWithSameUniqueFieldValue($data)) {
			//there is an exception for shop admins
			//who can place an order on behalve of a customer.
			if($this->loggedInMember->IsShopAdmin()) {
				//REMOVED PART:
				//but NOT when the member placing the Order is the ShopAdmin
				//AND there is another member with the same credentials.
				//because in that case the ShopAdmin is not placing an order
				//on behalf of someone else.
				//that is,
				//if($this->orderMember->ID == $this->loggedInMember->ID) {
				//	return false;
				//}
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
	 * returns the email if
	 * - user is logged in already
	 * - user's email in DB does not match email entered.
	 *
	 * @param Array
	 * @return String | false
	 */
	protected function enteredEmailAddressDoesNotMatchLoggedInUser($data){
		if($this->loggedInMember) {
			$DBUniqueFieldName = $this->loggedInMember->Email;
			if($DBUniqueFieldName) {
				$uniqueFieldName = Member::get_unique_identifier_field();
				if(isset($data[$uniqueFieldName])) {
					$enteredUniqueFieldName = $data[$uniqueFieldName];
					if($enteredUniqueFieldName) {
						if($DBUniqueFieldName != $enteredUniqueFieldName) {
							return $enteredUniqueFieldName;
						}
					}
				}
			}
		}
		return false;
	}

	/**
	 * Check if the password is good enough
	 * @param data (from form)
	 * @return String
	 */
	protected function validPasswordHasBeenEntered($data){
		return ShopAccountForm_PasswordValidator::clean_password($data);
	}


}


