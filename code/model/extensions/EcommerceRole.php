<?php
/**
 * @description EcommerceRole provides specific customisations to the {@link Member}
 * class for the ecommerce module.
 *
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: extensions
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceRole extends DataExtension {


	static $api_access = array(
		'view' => array(
			'ID',
			'Orders',
			'PreferredCurrency'
		)
	);

	/**
	 * standard SS method
	 */
	static $db = array(
		'Notes' => 'Text'
	);

	static $has_one = array(
		'PreferredCurrency' => 'EcommerceCurrency'
	);

	static $has_many = array(
		'Orders' => 'Order'
	);

	/**
	 *@return DataObject (Group) | NULL
	 **/
	public static function get_customer_group() {
		$customerCode = EcommerceConfig::get("EcommerceRole", "customer_group_code");
		$customerName = EcommerceConfig::get("EcommerceRole", "customer_group_name");
		return Group::get()
			->Filter(array("Code" => $customerCode))->First();
	}

	/**
	 * returns an aray of customers
	 * The unselect option shows an extra line, basically allowing you to deselect the
	 * current option.
	 *
	 * @param Boolean $showUnselectedOption
	 * @return Array ( ID => Email (member.title) )
	 */
	public static function list_of_customers($showUnselectedOption = false){
		//start array
		$array = Array();
		if($showUnselectedOption) {
			$array[0] = _t("Member.SELECTCUSTOMER", " --- SELECT CUSTOMER ---");
		}
		//get customer group
		$customerCode = EcommerceConfig::get("EcommerceRole", "customer_group_code");
		$group = Group::get()
			->Filter(array("Code" => $customerCode))
			->First();
		//fill array
		if($group) {
			$members = $group->Members();
			if($members) {
				foreach($members as $member) {
					if($member->Email) {
						$array[$member->ID] = $member->Email." (".$member->getTitle().")";
					}
				}
			}
		}
		//sort in a natural order
		natcasesort($array);
		return $array;
	}


	/**
	 * tells us if the current member is in the Shop Administrators Group.
	 *
	 * @param Member | Null $member
	 * @return Boolean
	 */
	public static function current_member_is_shop_admin(Member $member = null) {
		if(!$member) {
			$member = Member::currentUser();
		}
		if($member) {
			return $member->IsShopAdmin();
		}
		return false;
	}

	/**
	 * @return DataObject (Group) | NULL
	 **/
	public static function get_admin_group() {
		$adminCode = EcommerceConfig::get("EcommerceRole", "admin_group_code");
		$adminName = EcommerceConfig::get("EcommerceRole", "admin_group_name");
		return Group::get()->Filter(array("Code" => $adminCode))->First();
	}

	/**
	 * Update the CMS Fields
	 * for /admin/security
	 *
	 * @param FieldList $fields
	 * @return FieldList
	 */
	public function updateCMSFields(FieldList $fields) {
		//$orderField = $fields->dataFieldByName("Orders");
		$preferredCurrencyField = $fields->dataFieldByName("PreferredCurrencyID");
		$notesFields = $fields->dataFieldByName("Notes");
		$link = Shoppingcart_Controller::get_url_segment()."/loginas/".$this->owner->ID."/";
		$loginAsField = new LiteralField("LoginAsThisCustomer", "<a href=\"$link\" target=\"_blank\">Login as this customer</a>");
		$fields->addFieldsToTab(
			"Root.Orders",
			array(
				//$orderField,
				$preferredCurrencyField,
				$notesFields,
				$loginAsField
			)
		);

		return $fields;
	}

	/**
	 * Save a preferred currency for a member.
	 * @param EcommerceCurrency $currency - object for the currency
	 */
	public function SetPreferredCurrency(EcommerceCurrency $currency){
		if($this->owner->exists()) {
			if($currency && $currency->exists()) {
				$this->owner->PreferredCurrencyID = $currency->ID;
				$this->owner->write();
			}
		}
	}

	/**
	 * get CMS fields describing the member in the CMS when viewing the order.
	 *
	 * @return CompositeField
	 **/
	public function getEcommerceFieldsForCMS() {
		$fields = new CompositeField();
		$memberTitle = new ReadonlyField("MemberTitle", _t("Member.TITLE", "Name"), "<p>"._t("Member.TITLE", "Name").": ".$this->owner->getTitle()."</p>");
		$memberTitle->dontEscape = true;
		$fields->push($memberTitle);
		$memberEmail = new ReadonlyField("MemberEmail",_t("Member.EMAIL", "Email"), "<p>"._t("Member.EMAIL", "Email").": ".$this->owner->Email."</p>");
		$memberEmail->dontEscape = true;
		$fields->push($memberEmail);
		$lastLogin = new ReadonlyField("MemberLastLogin",_t("Member.LASTLOGIN", "Last Login"),"<p>"._t("Member.LASTLOGIN", "Last Login").": ".$this->owner->dbObject('LastVisited')->Nice()."</p>");
		$lastLogin->dontEscape = true;
		$fields->push($lastLogin);
		$group = EcommerceRole::get_customer_group();
		if(!$group){$group = new Group();}
		$linkField = new LiteralField(
			"MemberLinkField",
			"
			<h3>"._t("Member.EDIT_CUSTOMER", "Edit Customer")."</h3>
			<ul>
				<li><a href=\"/admin/security/EditForm/field/Members/item/".$this->owner->ID."/edit\" target=\"_blank\">"._t("Member.EDIT", "Edit")." <i>".$this->owner->getTitle()."</i></a></li>
				<li><a href=\"/admin/security/show/".$group->ID."/\" target=\"_blank\">"._t("Member.EDIT_ALL_CUSTOMERS", "Edit All Customers")."</a></li>
			</ul>
			"
		);
		$fields->push($linkField);
		return $fields;
	}

	/**
	 * @param Boolean $additionalFields: add extra fields.
	 * @return FieldList
	 */
	function getEcommerceFields() {
		if(!EcommerceConfig::get("EcommerceRole", "allow_customers_to_setup_accounts")) {
			$fields = new FieldList(
				new HeaderField('PersonalInformation', _t('EcommerceRole.PERSONALINFORMATION','Personal Information'), 3),
				new TextField('FirstName', _t('EcommerceRole.FIRSTNAME','First Name')),
				new TextField('Surname', _t('EcommerceRole.SURNAME','Surname'))
			);
			$this->owner->extend('augmentEcommerceFields', $fields);
			return $fields;
		}
		Requirements::javascript('ecommerce/javascript/EcomPasswordField.js');
		if($this->owner->exists()) {
			if($this->owner->Password) {
				$passwordField = new PasswordField('Password', _t('Account.NEW_PASSWORD','New Password'));
				$passwordDoubleCheckField = new PasswordField('PasswordDoubleCheck', _t('Account.CONFIRM_NEW_PASSWORD','Confirm New Password'));
				$updatePasswordLinkField = new LiteralField('UpdatePasswordLink', "<a href=\"#Password\" class=\"updatePasswordLink\" rel=\"Password\">"._t('Account.UPDATE_PASSWORD','Update Password')."</a>");
			}
			$loginDetailsHeader = new HeaderField('LoginDetails',_t('Account.LOGINDETAILS','Login Details'), 3);
			$loginDetailsDescription = new LiteralField(
				'AccountInfo',
				'<p>'.
				_t('OrderForm.PLEASE_REVIEW','Please review your log in details below.')
				.'</p>'
			);
		}
		else {
			//login invite right on the top
			if(EcommerceConfig::get("EcommerceRole", "automatic_membership")) {
				$loginDetailsHeader = new HeaderField('CreateAnAccount',_t('OrderForm.CREATEANACCONTOPTIONAL','Create an account (optional)'), 3);
				//allow people to purchase without creating a password
				$loginDetailsDescription = new LiteralField(
					'AccountInfo',
					'<p>'.
					_t('OrderForm.ACCOUNTINFO','Please <a href="#Password" class="choosePassword">choose a password</a>; this will allow you to check your order history in the future.')
					.'</p>'
				);
				//close by default
			}
			else {
				$loginDetailsHeader = new HeaderField('CreateAnAccount', _t('OrderForm.SETUPYOURACCOUNT','Create an account'), 3);
				//dont allow people to purchase without creating a password
				$loginDetailsDescription = new LiteralField(
					'AccountInfo',
					'<p>'.
					_t('OrderForm.MUSTCREATEPASSWORD','Please choose a password to create your account.')
					.'</p>'
				);
			}	
		}
	
		if(empty($passwordField)) {
			$passwordField = new PasswordField('Password', _t('Account.CREATE_PASSWORD','Create Account (enter password)'));
			$passwordDoubleCheckField = new PasswordField('PasswordDoubleCheck', _t('Account.CONFIRM_PASSWORD','Confirm Password'));
		}
		if(empty($updatePasswordLinkField)) {
			$updatePasswordLinkField = new LiteralField('UpdatePasswordLink', "");
		}
		$fields = new FieldList(
			new HeaderField('PersonalInformation', _t('EcommerceRole.PERSONALINFORMATION','Personal Information'), 3),
			new TextField('FirstName', _t('EcommerceRole.FIRSTNAME','First Name')),
			new TextField('Surname', _t('EcommerceRole.SURNAME','Surname')),
			$loginDetailsHeader,
			$loginDetailsDescription,
			new EmailField('Email', _t('EcommerceRole.EMAIL','Email')),
			$updatePasswordLinkField,
			$passwordField,
			$passwordDoubleCheckField
		);
		$this->owner->extend('augmentEcommerceFields', $fields);
		return $fields;
	}

	/**
	 * Return which member fields should be required on {@link OrderForm}
	 * and {@link ShopAccountForm}.
	 *
	 * @return array
	 */
	function getEcommerceRequiredFields() {
		$fields = array(
			'FirstName',
			'Surname',
			'Email',
		);
		if(EcommerceConfig::get("EcommerceRole", "automatic_membership")) {
			$passwordFieldIsRequired = false;
		}
		else {
			$passwordFieldIsRequired = true;
			if($this->owner->exists()) {
				if($this->owner->Password) {
					$passwordFieldIsRequired = false;
				}
			}			
		}
		if($passwordFieldIsRequired) {
			$fields[] = "Password";
			$fields[] = "PasswordDoubleCheck";
		}
		$this->owner->extend('augmentEcommerceRequiredFields', $fields);
		return $fields;
	}

	/**
	 *
	 * can be run $form->loadDataFrom($member);
	 * @param Fieldlist
	 * @return FieldList
	 */
	function afterLoadDataFrom(FieldList $fieldList) {
		if($passwordField = $fieldList->dataFieldByName("Password")) {
			$passwordField->setValue("");
		}
	}

	/**
	 * Is the member a member of the ShopAdmin Group
	 * @return Boolean
	 **/
	public function IsShopAdmin() {
		if(Permission::checkMember($this->owner, 'ADMIN')) {
			return true;
		}
		else{
			return Permission::checkMember($this->owner, EcommerceConfig::get("EcommerceRole", "admin_permission_code"));
		}
	}

	/**
	 * returns the last (submitted) order  by the member
	 * @param Boolean $includeUnsubmittedOrders - set to TRUE to include unsubmitted orders
	 * @return Null | Order
	 */
	function LastOrder($includeUnsubmittedOrders = false){
		//limit to 10
		if($includeUnsubmittedOrders) {
			$orders = Order::get_datalist_of_orders_with_submit_record(false);
		}
		else {
			$orders = Order::get_datalist_of_orders_with_submit_record(true);
		}
		$lastOrder = $orders
			->Filter(array("MemberID" => $this->owner->ID))
			->First();
		return $lastOrder;
	}

	/**
	 * standard SS method
	 * Make sure the member is added as a customer
	 */
	public function onAfterWrite() {
		$customerGroup = EcommerceRole::get_customer_group();
		if($customerGroup){
			$existingMembers = $customerGroup->Members();
			if($existingMembers){
				$existingMembers->add($this->owner);
			}
		}
	}

	/**
	 * Finds previous addresses from the member of the current address
	 *
	 * @param String $type
	 * @param Int $excludeID - the ID of the record to exlcude (if any)
	 * @param Boolean $onlyLastRecord - only select one
	 * @param Boolean $keepDoubles - keep addresses that are the same (if set to false, only unique addresses are returned)
	 * @return ArrayList (BillingAddresses | ShippingAddresses)
	 **/
	public function previousOrderAddresses($type = "BillingAddress", $excludeID = 0, $onlyLastRecord = false, $keepDoubles = false) {
		$returnArrayList = new ArrayList();
		if($this->owner->exists()) {
			$fieldName = $type."ID";
			$limit = 999;
			if($onlyLastRecord) {
				$limit = 1;
			}
			$addresses = $type::get()
				->where(
					"\"Obsolete\" = 0 AND \"Order\".\"MemberID\" = ".$this->owner->ID
				)
				->sort("LastEdited", "DESC")
				->exclude(array("ID" => $excludeID))
				//->limit($limit)
				->innerJoin("Order", "\"Order\".\"".$fieldName."\" = \"OrderAddress\".\"ID\"");
			if($addresses->count()){
				if($keepDoubles) {
					foreach($addresses as $address) {
						$returnArrayList->push($address);
					}
				}
				else {
					$addressCompare = array();
					foreach($addresses as $address) {
						$comparisonString = $address->comparisonString();
						if(in_array($comparisonString, $addressCompare)) {
							//do nothing
						}
						else {
							$addressCompare[$address->ID] = $comparisonString;
							$returnArrayList->push($address);
						}
					}
				}
			}
		}
		return $returnArrayList;
	}

	/**
	 * Finds the last address used by this member
	 * @param String $type
	 * @param Int $excludeID - the ID of the record to exlcude (if any)
	 * @return Null | ShippingAddress | BillingAddress | another type
	 **/
	public function previousOrderAddress($type = "BillingAddress", $excludeID = 0) {
		$addresses = $this->previousOrderAddresses($type, $excludeID, true, false);
		if($addresses->count()) {
			return $addresses->First();
		}
	}


}



