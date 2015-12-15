<?php

class EcommercePaymentFormSetupAndValidation extends Object {

	/**
	 * you can set specific EcommercePayment payment fields here, like this:
	 *     MyEcommercePaymentClass
	 *         CardNumber: MyCardNumberDBField
	 *         NameOnCard: MyNameOnCardDBField
	 *         CVVNumber: MyCVVNumberDBField
	 *         ExpiryDate: MyExpiryDateDBField
	 * 
	 * @var array
	 *
	 */ 
	private static $db_field_map = array();

	/**
	 *
	 * @var EcommercePayment
	 */ 
	private static $_payment_object = null;

	/**
	 *
	 * @return EcommercePayment
	 */ 
	public static function get_payment_object(){
		return self::$_payment_object;
	}
	
	/**
	 * Return the payment form fields that should
	 * be shown on the checkout order form for the
	 * payment type. Example: for {@link DPSPayment},
	 * this would be a set of fields to enter your
	 * credit card details.
	 *
	 * @param EcommercePayment self::$_payment_object
	 * 
	 * @return FieldList
	 */
	public static function get_credit_card_payment_form_fields($paymentObject = null){
		if(!self::$_payment_object) {
			self::$_payment_object = $paymentObject;
		}
		$paymentClassName = self::$_payment_object->ClassName;
		$fieldList = new FieldList(
			array(
				$CardNumberField = new EcommerceCreditCardField(
					$paymentClassName."_CardNumber",
					_t("EcommercePaymentFormSetupAndValidation.CardNumber", "Card Number")
				),
				$nameOnCardField = new TextField(
					$paymentClassName."_NameOnCard",
					_t("EcommercePaymentFormSetupAndValidation.NAMEONCARD", "Name on Card")
				),
				$expiryDateField = new ExpiryDateField(
					$paymentClassName."_ExpiryDate",
					_t("EcommercePaymentFormSetupAndValidation.EXPIRYDATE", "Expiry Date")
				),
				$cvvNumberField = new TextField(
					$paymentClassName."_CVVNumber",
					_t("EcommercePaymentFormSetupAndValidation.CVVNumber", "Security Number")
				)
			)
		);
		$nameOnCardField->setAttribute("maxlength", "40");
		$cvvNumberField->setAttribute("maxlength", "4");
		$cvvNumberField->setAttribute("size", "4");
		$cvvNumberField->setAttribute("autocomplete", "off");
		return $fieldList;
	}
	
	/*
	 *
	 * @param EcommercePayment $paymentObject
	 * 
	 * @return array
	 */
	public static function get_credit_card_payment_form_fields_required($paymentObject = null){
		if(!self::$_payment_object) {
			self::$_payment_object = $paymentObject;
		}
		$paymentClassName = self::$_payment_object->ClassName;
		return array(
			$paymentClassName."_CardNumber",
			$paymentClassName."_NameOnCard",
			$paymentClassName."_ExpiryDate",
			$paymentClassName."_CVVNumber"
		);
	} 


	/**
	 * @param Order $order - the order that is being paid
	 * @param array $data - Array of data that is submittted
	 * @param Form $form - the form that is being submitted
	 * 
	 * @return boolean - true if the data is valid
	 */
	public static function validate_payment($order, $data, $form) {
		if(!$order){
			$form->sessionMessage(_t('EcommercePayment.NOORDER','Order not found.'), 'bad');
			return false;
		}

		//nothing to pay, always valid
		if($order->TotalOutstanding() == 0) {
			return true;
		}
		if(!self::$_payment_object) {
			$paymentClass = (!empty($data['PaymentMethod'])) ? $data['PaymentMethod'] : null;
			if($paymentClass) {
				if(class_exists($paymentClass)) {
					self::$_payment_object = $paymentClass::create();
				}
			}
		}
		if(!self::$_payment_object || !self::$_payment_object instanceof EcommercePayment) {
			$form->sessionMessage(_t('EcommercePaymentFormSetupAndValidation.NOPAYMENTOPTION','No Payment option selected.'), 'bad');
			return false;
		}
		// Check payment, get the result back
		return self::$_payment_object->validatePayment($data, $form);
	}


	
	/**
	 * Process payment form and return next step in the payment process.
	 * Steps taken are:
	 * 1. create new payment
	 * 2. save form into payment
	 * 3. return payment result
	 *
	 * @param Order $order - the order that is being paid
	 * @param Form $form - the form that is being submitted
	 * @param array $data - Array of data that is submittted
	 * 
	 * @return Boolean - if successful, this method will return TRUE
	 */
	public static function process_payment_form_and_return_next_step($order, $data, $form) {
		if(!self::$_payment_object) {
			$paymentClass = (!empty($data['PaymentMethod'])) ? $data['PaymentMethod'] : null;
			if($paymentClass) {
				if(class_exists($paymentClass)) {
					self::$_payment_object = $paymentClass::create();
				}
			}
		}
		if(!self::$_payment_object) {
			return false;
		}
		// Save payment data from form and process payment
		$form->saveInto(self::$_payment_object);
		self::$_payment_object->OrderID = $order->ID;
		//important to set the amount and currency.
		self::$_payment_object->Amount = $order->getTotalOutstandingAsMoney();
		self::$_payment_object->write();
		// Process payment, get the result back
		$result = self::$_payment_object->processPayment($data, $form);
		if(!(is_a($result, Object::getCustomClass("EcommercePayment_Result")))) {
			$form->controller->redirectBack();
			return false;
		}
		else {
			if($result->isProcessing()) {
				//IMPORTANT!!!
				// isProcessing(): Long payment process redirected to another website (PayPal, Worldpay)
				//redirection is taken care of by payment processor
				return $result->getValue();
			}
			else {
				//payment is done, redirect to either returntolink
				//OR to the link of the order ....
				if(isset($data["returntolink"])) {
					$form->controller->redirect($data["returntolink"]);
				}
				else {
					$form->controller->redirect($order->Link());
				}
			}
			return true;
		}
	}

	/**
	 * return false if there is an error and
	 * returns true if all is well.
	 * If there are no errors, then the payment object will also be written...
	 * If there are errors, the errors will be added to the form.
	 * 
	 * @param Form $form
	 * @param array $data
	 * @param EcommercePayment $paymentObject
	 *
	 * @return boolean
	 *
	 */
	public static function validate_and_save_credit_card_information($data, $form, $paymentObject = null) {
		$errors = false;
		if(!self::$_payment_object) {
			self::$_payment_object = $paymentObject;
		}
		$paymentClassName = self::$_payment_object->ClassName;
		$dbFieldMap = Config::inst()->get("EcommercePaymentFormSetupAndValidation","db_field_map");
		$cardNumberFormFields = array(
			"CardNumber", 
			"ExpiryDate" ,
			"CVVNumber" ,
			"NameOnCard"
		);
		foreach($cardNumberFormFields as $dbFieldName) {
			$formFieldName = $paymentClassName."_".$dbFieldName;
			switch ($dbFieldName) {
				case "CardNumber":
					if(isset($dbFieldMap[$paymentClassName]["CardNumber"])) {
						$dbFieldName = $dbFieldMap[$paymentClassName]["CardNumber"];
					}
					self::$_payment_object->$dbFieldName = trim(
						$data[$formFieldName][0].
						$data[$formFieldName][1].
						$data[$formFieldName][2].
						$data[$formFieldName][3]
					);
					$cardNumber = self::$_payment_object->$dbFieldName;
					if(!self::validate_card_number(self::$_payment_object->$dbFieldName)) {
						$form->addErrorMessage(
							$formFieldName,
							_t('EcommercePaymentFormSetupAndValidation.INVALID_CREDIT_CARD','Invalid credit card number.'),
							'bad'
						);
						$errors = true;
					}
					break;
				case "ExpiryDate":
					self::$_payment_object->$dbFieldName =
						$data[$formFieldName]["month"].
						$data[$formFieldName]["year"];
					if(!self::validate_expiry_month(self::$_payment_object->$dbFieldName)) {
						$form->addErrorMessage(
							$formFieldName,
							_t('EcommercePaymentFormSetupAndValidation.INVALID_EXPIRY_DATE','Expiry date not valid.'),
							'bad'
						);
						$errors = true;
					}
					break;
				case "CVVNumber":
					self::$_payment_object->$dbFieldName = trim($data[$formFieldName]);
					if(!self::validate_CVV($cardNumber, self::$_payment_object->$dbFieldName)) {
						$form->addErrorMessage(
							$formFieldName,
							_t('EcommercePaymentFormSetupAndValidation.INVALID_CVV_NUMBER','Invalid security number.'),
							'bad'
						);
					}
					break;
				case "NameOnCard":
					self::$_payment_object->$dbFieldName = trim($data[$formFieldName]);
					if(strlen(self::$_payment_object->$dbFieldName) < 3) {
						$form->addErrorMessage(
							$formFieldName,
							_t("EcommercePaymentFormSetupAndValidation.NO_CARD_NAME",'No card name provided.'),
							'bad'
						);
						$errors = true;
					}
					break;
				default:
					user_error("Type must be one of four options: CardNumber, NameOnCard, CVV, ExpiryDate");
			}
		}
		if($errors) {
			$form->sessionMessage(_t('EcommercePaymentFormSetupAndValidation.PLEASE_REVIEW_CARD_DETAILS','Please review your card details.'),'bad');
			return false;
		}
		else {
			self::$_payment_object->write();
			return true;
		}
	}

	/**
	 * checks if a credit card is a real credit card number
	 * @reference: http://en.wikipedia.org/wiki/Luhn_algorithm
	 *
	 * @param String | Int $number
	 * @return Boolean
	 */
	public static function validate_card_number($cardNumber) {
		if(!$cardNumber) {
			return false;
		}
		for ($sum = 0, $i = strlen($cardNumber) - 1; $i >= 0; $i--) {
			$digit = (int) $cardNumber[$i];
			$sum += (($i % 2) === 0) ? array_sum(str_split($digit * 2)) : $digit;
		}
		return (($sum % 10) === 0);
	}

	/**
	 * @todo: finish!
	 * valid expiry date
	 * @param String $monthYear - e.g. 0218
	 * @return Boolean
	 */
	public static function validate_expiry_month($monthYear) {
		$month = intval(substr($monthYear, 0, 2));
		$year = intval("20".substr($monthYear, 2));
		$currentYear = intval(Date("Y"));
		$currentMonth = intval(Date("m"));
		if(($month > 0 || $month < 13) && $year > 0 ) {
			if($year > $currentYear) {
				return true;
			}
			elseif($year == $currentYear) {
				if($currentMonth <= $month) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @todo: TEST
	 * valid CVC/CVV number?
	 *
	 * @param int $cardNumber
	 * @param int $cvv
	 * @return Boolean
	 */
	protected static function validate_CVV($cardNumber, $cvv) {
		$cardNumber = preg_replace('/\D/', '', $cardNumber);
		$cvv = preg_replace('/\D/', '', $cvv);

		//Checks to see whether the submitted value is numeric (After spaces and hyphens have been removed).
		if(is_numeric($cardNumber)) {
			//Checks to see whether the submitted value is numeric (After spaces and hyphens have been removed).
			if(is_numeric($cvv)) {
				//Splits up the card number into various identifying lengths.
				$firstOne = substr($cardNumber, 0, 1);
				$firstTwo = substr($cardNumber, 0, 2);

				//If the card is an American Express
				if($firstTwo == "34" || $firstTwo == "37") {
					if (!preg_match("/^\d{4}$/", $cvv)) {
						// The credit card is an American Express card
						// but does not have a four digit CVV code
						return false;
					}
				}
				else if (!preg_match("/^\d{3}$/", $cvv)) {
					// The credit card is a Visa, MasterCard, or Discover Card card
					// but does not have a three digit CVV code
					return false;
				}
				//passed all checks
				return true;
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}


}
