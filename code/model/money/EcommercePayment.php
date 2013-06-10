<?php
/**
 * "Abstract" class for a number of different payment
 * types allowing a user to pay for something on a site.
 *
 * @see DPSPayment
 * @see WorldPayPayment
 * @see ChequePayment
 *
 * This can't be an abstract class because sapphire doesn't
 * support abstract DataObject classes.
 *
 * @package payment
 */
class EcommercePayment extends DataObject {

	/**
	 * Incomplete (default): Payment created but nothing confirmed as successful
	 * Success: Payment successful
	 * Failure: Payment failed during process
	 * Pending: Payment awaiting receipt/bank transfer etc
	 */
	public static $db = array(
		'Status' => "Enum('Incomplete,Success,Failure,Pending','Incomplete')",
		'Amount' => 'Money',
		'Message' => 'Text',
		'IP' => 'Varchar',
		'ProxyIP' => 'Varchar',
		'PaymentDate' => "Date",
		'ExceptionError' => 'Text'
	);

	public static $has_one = array(
		'PaidBy' => 'Member',
		'Order' => 'Order'
	);

	public static $summary_fields = array(
		"Order.Title",
		"ClassName" => "Type",
		"AmountValue" => "Amount",
		"Status" => "Status"
	);

	static $casting = array(
		'AmountValue' => 'Currency',
		'AmountCurrency' => 'Varchar'
	);

	static $searchable_fields = array(
		'OrderID' => array(
			'field' => 'NumericField',
			'title' => 'Order Number'
		),
		'Created' => array(
			'title' => 'Date (e.g. today)',
			'field' => 'TextField',
			'filter' => 'PaymentFilters_AroundDateFilter', //TODO: this breaks the sales section of the CMS
		),
		'IP' => array(
			'title' => 'IP Address',
			'filter' => 'PartialMatchFilter'
		),
		'Status'
	);

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $default_sort = '"Created" DESC';


	/**
	 * Process payment form and return next step in the payment process.
	 * Steps taken are:
	 * 1. create new payment
	 * 2. save form into payment
	 * 3. return payment result
	 *
	 * @param Order $order - the order that is being paid
	 * @param Form $form - the form that is being submitted
	 * @param Array $data - Array of data that is submittted
	 * @return Boolean - if successful, this method will return TRUE
	 */
	public static function process_payment_form_and_return_next_step(Order $order, Form $form, Array $data) {
		if(!$order){
			$form->sessionMessage(_t('EcommercePayment.NOORDER','Order not found.'), 'bad');
			$form->controller->redirectBack();
			return false;
		}
		$paidBy = $order->Member();
		if(!$paidBy) {
			$paidBy = Member::currentUser();
		}
		$paymentClass = (!empty($data['PaymentMethod'])) ? $data['PaymentMethod'] : null;

		$payment = class_exists($paymentClass) ? new $paymentClass() : null;
		if(!$payment) {
			$form->sessionMessage(_t('EcommercePayment.NOPAYMENTOPTION','No Payment option selected.'), 'bad');
			$form->controller->redirectBack();
			return false;
		}
		// Save payment data from form and process payment
		$form->saveInto($payment);
		$payment->OrderID = $order->ID;
		//important to set the amount and currency.
		$payment->Amount = $order->getTotalOutstandingAsMoney();
		$payment->write();
		// Process payment, get the result back
		$result = $payment->processPayment($data, $form);
		if(!($result instanceof Payment_Result)) {
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
	 * standard SS method
	 * @return FieldList
	 */
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->replaceField("OrderID", new ReadonlyField("OrderID", "Order ID"));
		return $fields;
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	function canCreate($member = null) {
		if(!$member) {
			$member = Member::currenUser();
		}
		return EcommerceRole::current_member_is_shop_admin($member);
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	function canDelete($member = null) {
		return false;
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	function canEdit($member = null) {
		if($this->Status == "Pending" || $this->Status == "Incomplete") {
			return parent::canEdit($member);
		}
		return false;
	}

	/**
	 * redirect to order action
	 */
	function redirectToOrder() {
		$order = $this->Order();
		if($order) {
			Controller::curr()->redirect($order->Link());
		}
		else {
			user_error("No order found with this payment: ".$this->ID, E_USER_NOTICE);
		}
		return;
	}

	/**
	 * @return float
	 **/
	function AmountValue(){return $this->getAmountValue();}
	function getAmountValue() {
		return $this->Amount->getAmount();
	}

	/**
	 * @return String
	 **/
	function AmountCurrency(){return $this->getAmountCurrency();}
	function getAmountCurrency() {
		return $this->Amount->getCurrency();
	}


	/**
	 * standard SS method
	 * try to finalise order if payment has been made.
	 */
	function onAfterWrite() {
		parent::onAfterWrite();
		$order = $this->Order();
		if($order && $order instanceof Order && $order->IsSubmitted()) {
			$order->tryToFinaliseOrder();
		}
	}


	/**
	 *@return String
	 **/
	function Status() {
		return _t('Payment.'.strtoupper($this->Status),$this->Status);
	}

	/**
	 * Return the site currency in use.
	 * @return string
	 */
	public static function site_currency() {
		return EcommerceConfig::get("EcommerceCurrency", "default_currency");
	}

	/**
	 * Set currency to default one.
	 * Set IP address
	 *
	 */
	function populateDefaults() {
		parent::populateDefaults();
		$this->Amount->Currency = EcommerceConfig::get("EcommerceCurrency", "default_currency");
		$this->setClientIP();
 	}

	/**
	 * Set the IP address of the user to this payment record.
	 * This isn't perfect - IP addresses can be hidden fairly easily.
	 */
	protected function setClientIP() {
		$proxy = null;
		$ip = null;

		if(isset($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP'];
		elseif(isset($_SERVER['REMOTE_ADDR'])) $ip = $_SERVER['REMOTE_ADDR'];
		else $ip = null;

		if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$proxy = $ip;
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		// Only set the IP and ProxyIP if none currently set
		if(!$this->IP) $this->IP = $ip;
		if(!$this->ProxyIP) $this->ProxyIP = $proxy;
	}

	/**
	 * Returns the Payment type currently in use.
	 * @return string
	 */
	function PaymentMethod() {
		$supportedMethods = EcommerceConfig::get("EcommercePayment", "supported_methods");
		if(isset($supportedMethods[$this->ClassName])) {
			return $supportedMethods[$this->ClassName];
		}
	}

	/**
	 * Return a set of payment fields from all enabled
	 * payment methods for this site, given the .
	 * is used to define which methods are available.
	 *
	 * @return FieldList
	 */
	static function combined_form_fields($amount) {

		// Create the initial form fields, which defines an OptionsetField
		// allowing the user to choose which payment method to use.
		$supportedMethods = EcommerceConfig::get("EcommercePayment", "supported_methods");
		$fields = new FieldList(
			new OptionsetField(
				'PaymentMethod',
				'',
				$supportedMethods
			)
		);

		// If the user defined an numerically indexed array, throw an error
		if(ArrayLib::is_associative($supportedMethods)) {
			foreach($supportedMethods as $methodClass => $methodTitle) {
				// Create a new CompositeField with method specific fields,
				// as defined on each payment method class using getPaymentFormFields()
				$methodFields = new CompositeField(singleton($methodClass)->getPaymentFormFields());
				$methodFields->setID("MethodFields_$methodClass");
				$methodFields->addExtraClass("methodFields_$methodClass");
				$methodFields->addExtraClass('paymentfields');

				// Add those fields to the initial FieldSet we first created
				$fields->push($methodFields);
			}
		}
		else {
			user_error('EcommercePayment::set_supported_methods() requires an associative array.', E_USER_ERROR);
		}
		// Add the amount and subtotal fields for the payment amount
		$fields->push(new ReadonlyField('Amount', _t('Payment.AMOUNT', 'Amount'), $amount));
		return $fields;
	}

	/**
	 * Return the form requirements for all the payment methods.
	 *
	 * @return An array suitable for passing to CustomRequiredFields
	 */
	static function combined_form_requirements() {
		$requirements = array();
		// Loop on available methods
		$supportedMethods = EcommerceConfig::get("EcommercePayment", "supported_methods");
		if($supportedMethods) {
			foreach($supportedMethods as $method => $methodTitle) {
				$methodRequirements = singleton($method)->getPaymentFormRequirements();
				if($methodRequirements) {
					// Put limiters into the JS/PHP code to only use those requirements for this payment method
					$methodRequirements['js'] = "for(var i=0; i <= this.elements.PaymentMethod.length-1; i++) "
						. "if(this.elements.PaymentMethod[i].value == '$method' && this.elements.PaymentMethod[i].checked == true) {"
						. $methodRequirements['js'] . " } ";
					$methodRequirements['php'] = "if(\$data['PaymentMethod'] == '$method') { " .
					$methodRequirements['php'] . " } ";
					$requirements[] = $methodRequirements;
				}
			}
		}
		return $requirements;
	}

	/**
	 * Return the payment form fields that should
	 * be shown on the checkout order form for the
	 * payment type. Example: for {@link DPSPayment},
	 * this would be a set of fields to enter your
	 * credit card details.
	 *
	 * @return FieldList
	 */
	function getPaymentFormFields(){user_error("Please implement getPaymentFormFields() on $this->class", E_USER_ERROR);}

	/**
	 * Define what fields defined in {@link Order->getPaymentFormFields()}
	 * should be required.
	 *
	 * @see DPSPayment->getPaymentFormRequirements() for an example on how
	 * this is implemented.
	 *
	 * @return array
	 */
	function getPaymentFormRequirements(){user_error("Please implement getPaymentFormRequirements() on $this->class", E_USER_ERROR);}

	/**
	 * Perform payment processing for the type of
	 * payment. For example, if this was a credit card
	 * payment type, you would perform the data send
	 * off to the payment gateway on this function for
	 * your payment subclass.
	 *
	 * This is used by {@link OrderForm} when it is
	 * submitted.
	 *
	 * @param array $data The form request data - see OrderForm
	 * @param OrderForm $form The form object submitted on
	 */
	function processPayment($data, $form){user_error("Please implement processPayment() on $this->class", E_USER_ERROR);}

	function getForm($whichTest){user_error("Please implement getForm() on $this->class", E_USER_ERROR);}

	protected function handleError($e){
		$this->ExceptionError = $e->getMessage();
		$this->write();
	}

	function PaidObject(){
		return $this->Order();
	}


	/**
	 * checks if a credit card is a real credit card number
	 * @reference: http://en.wikipedia.org/wiki/Luhn_algorithm
	 *
	 * @param String | Int $number
	 * @return Boolean
	 */
	public function validCreditCard($number) {
		for ($sum = 0, $i = strlen($number) - 1; $i >= 0; $i--) {
			$digit = (int) $number[$i];
			$sum += (($i % 2) === 0) ? array_sum(str_split($digit * 2)) : $digit;
		}
		return (($sum % 10) === 0);
	}

	/**
	 * @todo: finish!
	 * valid expiry date
	 * @param String | Int $number
	 * @return Boolean
	 */
	public function validExpiryDate($number) {
		return true;
	}

	/**
	 * @todo: finish!
	 * valid CVC number
	 *
	 * @param String | Int $number
	 * @return Boolean
	 */
	public function validCVC($number) {
		return true;
	}


	/**
	 * Debug helper method.
	 * Access through : /shoppingcart/debug/
	 */
	public function debug() {
		$html =  EcommerceTaskDebugCart::debug_object($this);
		return $html;
	}

}


/**
 * Payment object representing a TEST = SUCCESS
 *
 * @package ecommerce
 * @subpackage payment
 */
class EcommercePayment_TestSuccess extends EcommercePayment {

	function processPayment($data, $form) {
		$this->Status = 'Success';
		$this->Message = '<div>PAYMENT TEST: SUCCESS</div>';
		$this->write();
		return new Payment_Success();
	}

	function getPaymentFormFields() {
		return new FieldList(
			new LiteralField("SuccessBlurb", '<div>SUCCESSFUL PAYMENT TEST</div>')
		);
	}

	function getPaymentFormRequirements() {
		return null;
	}
}

/**
 * Payment object representing a TEST = PENDING
 *
 * @package ecommerce
 * @subpackage payment
 */
class EcommercePayment_TestPending extends EcommercePayment {

	function processPayment($data, $form) {
		$this->Status = 'Pending';
		$this->Message = '<div>PAYMENT TEST: PENDING</div>';
		$this->write();
		return new Payment_Success();
	}

	function getPaymentFormFields() {
		return new FieldList(
			new LiteralField("SuccessBlurb", '<div>PENDING PAYMENT TEST</div>')
		);
	}

	function getPaymentFormRequirements() {
		return null;
	}
}

/**
 * Payment object representing a TEST = FAILURE
 *
 * @package ecommerce
 * @subpackage payment
 */
class EcommercePayment_TestFailure extends EcommercePayment {

	function processPayment($data, $form) {
		$this->Status = 'Failure';
		$this->Message = '<div>PAYMENT TEST: FAILURE</div>';
		$this->write();
		return new Payment_Success();
	}

	function getPaymentFormFields() {
		return new FieldList(
			new LiteralField("SuccessBlurb", '<div>FAILURE PAYMENT TEST</div>')
		);
	}

	function getPaymentFormRequirements() {
		return null;
	}
}

abstract class Payment_Result {

	protected $value;

	function __construct($value = null) {
		$this->value = $value;
	}

	function getValue() {
		return $this->value;
	}

	abstract function isSuccess();

	abstract function isProcessing();

}


class Payment_Success extends Payment_Result {

	function isSuccess() {
		return true;
	}

	function isProcessing() {
		return false;
	}
}

class Payment_Processing extends Payment_Result {

	function isSuccess() {
		return false;
	}

	function isProcessing() {
		return true;
	}
}

class Payment_Failure extends Payment_Result {

	function isSuccess() {
		return false;
	}

	function isProcessing() {
		return false;
	}
}
