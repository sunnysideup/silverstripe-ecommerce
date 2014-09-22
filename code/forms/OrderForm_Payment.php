<?php

class OrderForm_Payment extends Form {

	/**
	 * @param Controller $controller
	 * @param String $name
	 * @param Order $order
	 * @param String
	 */
	function __construct(Controller $controller, $name, Order $order, $returnToLink = '') {
		$fields = new FieldList(
			new HiddenField('OrderID', '', $order->ID)
		);
		if($returnToLink) {
			$fields->push(new HiddenField("returntolink", "", convert::raw2att($returnToLink)));
		}

		$bottomFields = new CompositeField();
		$bottomFields->addExtraClass('bottomOrder');
		if($order->Total() > 0) {
			$paymentFields = EcommercePayment::combined_form_fields($order->getTotalAsMoney()->NiceWithCurrencyCode(false), $order);
			foreach($paymentFields as $paymentField) {
				$bottomFields->push($paymentField);
			}
			if($paymentRequiredFields = EcommercePayment::combined_form_requirements($order)) {
				$requiredFields = array_merge($requiredFields, $paymentRequiredFields);
			}
		}
		else {
			$bottomFields->push(new HiddenField("PaymentMethod", "", ""));
		}
		$fields->push($bottomFields);

		$actions = new FieldList(
			new FormAction('dopayment', _t('OrderForm.PAYORDER','Pay balance'))
		);
		$requiredFields = array();
		$validator = OrderForm_Payment_Validator::create($requiredFields);
		$form = parent::__construct($controller, $name, $fields, $actions, $validator);
		if($this->extend('updateFields', $fields) !== null) {$this->setFields($fields);}
		if($this->extend('updateActions', $actions) !== null) {$this->setActions($actions);}
		if($this->extend('updateValidator', $validator) !== null) {$this->setValidator($validator);}
		$this->setFormAction($controller->Link($name));
		$oldData = Session::get("FormInfo.{$this->FormName()}.data");
		if($oldData && (is_array($oldData) || is_object($oldData))) {
			$this->loadDataFrom($oldData);
		}
		$this->extend('updateOrderForm_Payment',$this);
	}

	function dopayment($data, $form) {
		$SQLData = Convert::raw2sql($data);
		if(isset($SQLData['OrderID'])) {
			if($orderID = intval($SQLData['OrderID'])) {
				$order = Order::get_by_id_if_can_view($orderID);
				if($order && $order->canPay()) {
					return EcommercePayment::process_payment_form_and_return_next_step($order, $form, $data);
				}
			}
		}
		$form->sessionMessage(_t('OrderForm.COULDNOTPROCESSPAYMENT','Sorry, we could not process your payment.'),'bad');
		$this->controller->redirectBack();
		return false;
	}

	/**
	 * saves the form into session
	 * @param Array $data - data from form.
	 */
	public function saveDataToSession(){
		$data = $this->getData();
		unset($data["LoggedInAsNote"]);
		Session::set("FormInfo.{$this->FormName()}.data", $data);
	}


}



class OrderForm_Payment_Validator extends RequiredFields{

	function php($data){
		$this->form->saveDataToSession();
		return parent::php($data);
	}

}


