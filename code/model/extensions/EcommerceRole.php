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

class EcommerceRole extends DataObjectDecorator {

	/**
	 * standard SS method
	 * defines additional statistics
	 */
	function extraStatics() {
		return array(
			'db' => array(
				'Notes' => 'Text'
			),
			'has_one' => array(
				'PreferredCurrency' => 'EcommerceCurrency'
			),
			'has_many' => array(
				'Orders' => 'Order'
			),
			'api_access' => array(
				'view' =>
					array(
						'ID',
						'Orders',
						'PreferredCurrency'
					)
				)
		);
	}

	/**
	 *@return DataObject (Group)
	 **/
	public static function get_customer_group() {
		$customerCode = EcommerceConfig::get("EcommerceRole", "customer_group_code");
		$customerName = EcommerceConfig::get("EcommerceRole", "customer_group_name");
		return DataObject::get_one("Group", "\"Code\" = '".$customerCode."' OR \"Title\" = '".$customerName."'");
	}


	/**
	 * returns an aray of members
	 * @return Array
	 */
	public static function list_of_customers($showUnselectedOption = false){
		$customerCode = EcommerceConfig::get("EcommerceRole", "customer_group_code");
		$group = DataObject::get_one("Group", "\"Code\" = '".$customerCode."'");
		$array = Array();
		if($showUnselectedOption) {
			$array[0] = _t("Member.SELECTCUSTOMER", " --- SELECT CUSTOMER ---");
		}
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
		natcasesort($array);
		return $array;
	}


	/**
	 * tells us if the current member is in the Shop Administrators Group.
	 * @param Member | Null $member
	 * @return Boolean
	 */
	public static function current_member_is_shop_admin($member = null) {
		if(!$member) {
			$member = Member::currentUser();
		}
		if($member) {
			return $member->IsShopAdmin();
		}
		return false;
	}

	/**
	 *@return DataObject (Group)
	 **/
	public static function get_admin_group() {
		$adminCode = EcommerceConfig::get("EcommerceRole", "admin_group_code");
		$adminName = EcommerceConfig::get("EcommerceRole", "admin_group_name");
		return DataObject::get_one("Group", "\"Code\" = '".$adminCode."' OR \"Title\" = '".$adminName."'");
	}

	/**
	 * Standard SS method
	 * @return FieldList
	 */
	public function updateCMSFields(&$fields) {
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
	public function SetPreferredCurrency($currency){
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
	 * @return Field / ComponentSet
	 **/
	public function getEcommerceFieldsForCMS() {
		$fields = new CompositeField();
		$memberTitle = new TextField("MemberTitle", "Name", $this->owner->getTitle());
		$fields->push($memberTitle->performReadonlyTransformation());
		$memberEmail = new TextField("MemberEmail","Email", $this->owner->Email);
		$fields->push($memberEmail->performReadonlyTransformation());
		$lastLogin = new TextField("MemberLastLogin","Last login",$this->owner->dbObject('LastVisited')->Nice());
		$fields->push($lastLogin->performReadonlyTransformation());
		return $fields;
	}

	/**
	 * returns content for a literal field for the CMS that links through to the member.
	 * @return String
	 * @author: nicolaas
	 **/
	function getEcommerceFieldsForCMSAsString() {
		$v = $this->owner->renderWith("Order_Member");
		if($group = EcommerceRole::get_customer_group()) {
			$v .= '<p><a href="/admin/security/show/'.$group->ID.'/" target="_blank">view (and edit) all customers</a></p>';
		}
		$this->owner->extend('augmentEcommerceFieldsForCMSAsString', $v);
		return $v;
	}

	/**
	 * @param Boolean $additionalFields: extra fields to be added.
	 * @return FieldSet
	 */
	function getEcommerceFields($additionalFields = false) {
		if($additionalFields) {
			$fields = new FieldSet(
				new HeaderField('PersonalInformation', _t('EcommerceRole.PERSONALINFORMATION','Personal Information'), 3),
				new TextField('FirstName', _t('EcommerceRole.FIRSTNAME','First Name')),
				new TextField('Surname', _t('EcommerceRole.SURNAME','Surname')),
				new EmailField('Email', _t('EcommerceRole.EMAIL','Email'))
			);
		}
		else {
			$fields = new FieldSet();
		}
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
			'Email',
			'FirstName',
			'Surname'
		);
		$this->owner->extend('augmentEcommerceRequiredFields', $fields);
		return $fields;
	}

	/**
	 * Is the member a member of the ShopAdmin Group
	 * @return Boolean
	 **/
	public function IsShopAdmin() {
		if($this->owner->IsAdmin()) {
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
		//limit to 30
		$orders = DataObject::get("Order", "MemberID =".$this->owner->ID, null, null, 30);
		if($orders) {
			foreach($orders as $order) {
				if($order->IsSubmitted() || $includeUnsubmittedOrders) {
					return $order;
				}
			}
		}
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

}



