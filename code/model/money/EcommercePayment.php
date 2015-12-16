<?php
/**
 * "Abstract" class for a number of different payment
 * types allowing a user to pay for something on a site.
 *
 *
 * This can't be an abstract class because sapphire doesn't
 * support abstract DataObject classes.
 *
 * @package payment
 */
class EcommercePayment extends DataObject implements EditableEcommerceObject {

	/**
	 * standard SS Variable
	 * @var Array
	 */
	private static $dependencies = array(
		'supportedMethodsProvider' => '%$EcommercePaymentSupportedMethodsProvider'
	);

	/**
	 * automatically populated by the dependency manager
	 * @var EcommercePaymentSupportedMethodsProvider
	 */
	public $supportedMethodsProvider = null;

	/**
	 * Incomplete (default): Payment created but nothing confirmed as successful
	 * Success: Payment successful
	 * Failure: Payment failed during process
	 * Pending: Payment awaiting receipt/bank transfer etc
	 */
	private static $db = array(
		'Status' => "Enum('Incomplete,Success,Failure,Pending','Incomplete')",
		'Amount' => 'Money',
		'Message' => 'Text',
		'IP' => 'Varchar',
		'ProxyIP' => 'Varchar',
		'ExceptionError' => 'Text'
	);

	private static $has_one = array(
		'PaidBy' => 'Member',
		'Order' => 'Order'
	);

	private static $summary_fields = array(
		"Order.Title",
		"Type" => "ClassName",
		"Date" => "Created",
		"AmountValue" => "Amount",
		"Status" => "Status"
	);

	private static $casting = array(
		'AmountValue' => 'Currency',
		'AmountCurrency' => 'Varchar'
	);

	private static $searchable_fields = array(
		'OrderID' => array(
			'field' => 'NumericField',
			'title' => 'Order Number'
		),
		'Created' => array(
			'title' => 'Date (e.g. today)',
			'field' => 'TextField',
			'filter' => 'EcommercePaymentFilters_AroundDateFilter'
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
	private static $default_sort = '"Created" DESC';


	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->replaceField("OrderID", new ReadonlyField("OrderID", "Order ID"));
		return $fields;
	}

	/**
	 * link to edit the record
	 * @param String | Null $action - e.g. edit
	 * @return String
	 */
	function CMSEditLink($action = null) {
		return Controller::join_links(
			Director::baseURL(),
			"/admin/sales/".$this->ClassName."/EditForm/field/".$this->ClassName."/item/".$this->ID."/",
			$action
		);
	}


	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	function canCreate($member = null) {
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canCreate($member);
	}


	function canView($member = null){
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canCreate($member);
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	function canEdit($member = null) {
		if($this->Status == "Pending" || $this->Status == "Incomplete") {
			if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
			return parent::canCreate($member);
		}
		return false;
	}

	/**
	 * Standard SS method
	 * set to false as a security measure...
	 * @param Member $member
	 * @return Boolean
	 */
	function canDelete($member = null) {
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
		if($order && is_a($order, Object::getCustomClass("Order")) && $order->IsSubmitted()) {
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
		$currency = EcommerceConfig::get("EcommerceCurrency", "default_currency");
		if(!$currency) {
			user_error("It is highly recommended that you set a default currency using the config files (EcommerceCurrency.default_currency)", E_USER_NOTICE);
		}
		return $currency;
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

		if(isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif(isset($_SERVER['REMOTE_ADDR'])) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		else {
			$ip = null;
		}

		if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			//swapsies
			$proxy = $ip;
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		// Only set the IP and ProxyIP if none currently set
		if(!$this->IP) $this->IP = $ip;
		if(!$this->ProxyIP) $this->ProxyIP = $proxy;
	}

	/**
	 * Returns the Payment type currently in use.
	 * @return string | null
	 */
	function PaymentMethod() {
		$supportedMethods = self::get_supported_methods($this->Order());
		if(isset($supportedMethods[$this->ClassName])) {
			return $supportedMethods[$this->ClassName];
		}
	}


	/**
	 * Static method to quickly update the payment method on runtime
	 * associative array that goes like ClassName => Description ...
	 *
	 * e.g. MyPaymentClass => Best Payment Method Ever	 * @param array $array -
	 * @param Array $array
	 */
	public static function set_supported_methods($array) {
		Config::inst()->update("EcommercePayment", "supported_methods", null);
		Config::inst()->update("EcommercePayment", "supported_methods", $array);
	}


	/**
	 * returns the list of supported methods
	 * test methods are included if the site is in DEV mode OR
	 * the current user is a ShopAdmin.
	 * @return Array
	 */
	public static function get_supported_methods($order = null){
		$obj = EcommercePayment::create();
		return $obj->supportedMethodsProvider->SupportedMethods($order);
	}

	/**
	 * Return the form requirements for all the payment methods.
	 *
	 * @param NULL | Array
	 * @return An array suitable for passing to CustomRequiredFields
	 */
	public static function combined_form_requirements($order = null) {
		return null;
	}

	/**
	 * Return a set of payment fields from all enabled
	 * payment methods for this site, given the .
	 * is used to define which methods are available.
	 *
	 * @param String $amount formatted amount (e.g. 12.30) without the currency
	 * @param Null | Order $order
	 *
	 * @return FieldList
	 */
	public static function combined_form_fields($amount, $order = null) {
		// Create the initial form fields, which defines an OptionsetField
		// allowing the user to choose which payment method to use.
		$supportedMethods = self::get_supported_methods($order);
		$fields = new FieldList(
			new OptionsetField(
				'PaymentMethod',
				'',
				$supportedMethods
			)
		);
		foreach($supportedMethods as $methodClass => $methodName) {
			// Create a new CompositeField with method specific fields,
			// as defined on each payment method class using getPaymentFormFields()
			$methodFields = new CompositeField($methodClass::create()->getPaymentFormFields());
			$methodFields->addExtraClass("methodFields_$methodClass");
			$methodFields->addExtraClass('paymentfields');
			// Add those fields to the initial FieldSet we first created
			$fields->push($methodFields);
		}

		// Add the amount and subtotal fields for the payment amount
		$fields->push(new HeaderField('Amount', _t('Payment.AMOUNT_COLON', 'Amount to be charged: ').'<u class="totalAmountToBeCharged">'.$amount.'</u>', 4));
		return $fields;
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
	 * Checks if all the data for payment is correct (e.g. credit card)
	 * By default it returns true, because lots of payments gatewawys
	 * do not have any fields required here.
	 *
	 * @param array $data The form request data - see OrderForm
	 * @param OrderForm $form The form object submitted on
	 */
	function validatePayment($data, $form){return true;}

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

	protected function handleError($e){
		$this->ExceptionError = $e->getMessage();
		$this->write();
	}

	function PaidObject(){
		return $this->Order();
	}


	/**
	 * Debug helper method.
	 * Access through : /shoppingcart/debug/
	 */
	public function debug() {
		$html =  EcommerceTaskDebugCart::debug_object($this);
		return $html;
	}



	/**
	 * LEGACY METHOD
	 * Process payment form and return next step in the payment process.
	 * Steps taken are:
	 * 1. create new payment
	 * 2. save form into payment
	 * 3. return payment result
	 *
	 * @param Order $order - the order that is being paid
	 * @param Form $form - the form that is being submitted
	 * @param Array $data - Array of data that is submittted
	 *
	 * @return Boolean - if successful, this method will return TRUE
	 */
	public static function process_payment_form_and_return_next_step($order, $data, $form) {
		$formHelper = $this->ecommercePaymentFormSetupAndValidationObject();
		return $formHelper->processPaymentFormAndReturnNextStep($order, $data, $form);
	}

	/**
	 * LEGACY METHOD
	 * @param Order $order - the order that is being paid
	 * @param array $data - Array of data that is submittted
	 * @param Form $form - the form that is being submitted
	 * 
	 * @return Boolean - true if the data is valid
	 */
	public static function validate_payment($order, $data, $form) {
		$formHelper = $this->ecommercePaymentFormSetupAndValidationObject();
		return $formHelper->validatePayment($order, $data, $form);
	}

	private $ecommercePaymentFormSetupAndValidationObject = null;

	/**
	 *
	 * @return EcommercePaymentFormSetupAndValidation
	 */ 
	protected function ecommercePaymentFormSetupAndValidationObject() {
		if(!$this->ecommercePaymentFormSetupAndValidationObject) {
			$this->ecommercePaymentFormSetupAndValidationObject = Injector::inst()->create('EcommercePaymentFormSetupAndValidation');
		}
		return $this->ecommercePaymentFormSetupAndValidationObject;
	}

	/**
	 *
	 * @return EcommercePaymentFormSetupAndValidation
	 */ 
	public static function ecommerce_payment_form_setup_and_validation_object(){
		return Injector::inst()->create('EcommercePaymentFormSetupAndValidation');
	}
}







