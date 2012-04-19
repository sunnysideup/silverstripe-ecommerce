<?php
/**
 * @description EcommerceRole provides customisations to the {@link Member}
 * class specifically for this ecommerce module.
 *
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package ecommerce
 * @sub-package member
 *
 **/

class EcommerceRole extends DataObjectDecorator {



	/**
	 * standard SS method
	 * defines additional statistics
	 */
	function extraStatics() {
		return array(
			'db' => array(
				'Notes' => 'HTMLText'
			),
			'has_one' => array(
				'PreferredCurrency' => 'EcommerceCurrency'
			),
			'has_many' => array(
				'Orders' => 'Order'
			),
			'api_access' => array(
				'view' =>
					array('ID', 'Orders')
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


/*******************************************************
   * SHOP ADMIN
*******************************************************/

	public static function current_member_is_shop_admin($member = null) {
		if(!$member) {
			$member = Member::currentMember();
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
	 **/

	function getEcommerceFieldsForCMSAsString() {
		$v = $this->owner->renderWith("Order_Member");
		if($group = EcommerceRole::get_customer_group()) {
			$v .= '<p><a href="/admin/security/show/'.$group->ID.'/" target="_blank">view (and edit) all customers</a></p>';
		}
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
			//'FirstName',
			//'Surname',
			//'Email'
		);
		$this->owner->extend('augmentEcommerceRequiredFields', $fields);
		return $fields;
	}


	/**
	 * standard SS method
	 * Make sure the member is added as a customer
	 */
	public function onAfterWrite() {
		parent::onAfterWrite();
		//...
		$customerGroup = EcommerceRole::get_customer_group();
		if($customerGroup){
			$existingMembers = $customerGroup->Members();
			if($existingMembers){
				$existingMembers->add($this->owner);
			}
		}
	}

	/**
	 * Is the member a member of the ShopAdmin Group
	 * @return Boolean
	 **/
	function IsShopAdmin() {
		if($this->owner->IsAdmin()) {
			return true;
		}
		else{
			return Permission::checkMember($this->owner, EcommerceConfig::get("EcommerceRole", "admin_permission_code"));
		}
	}

	/**
	 * Save a preferred currency for a member.
	 * @param String $code - code for the currency
	 */
	function SetPreferredCurrency($code){
		$preferredCurrency = DataObject::get_one("EcommerceCurrency", "\"Code\" = '$code'");
		$this->owner->PreferredCurrencyID = $preferredCurrency->ID;
		$this->owner->write();
	}

}



