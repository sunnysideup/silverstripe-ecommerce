<?php



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
		$this->form->saveDataToSession();
		if(Member::currentUserID()) {
			$allowExistingEmail = false;
		}
		else {
			$allowExistingEmail = true;
		}
		$valid = parent::php($data, $allowExistingEmail);
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

