<?php

/**
 * @description:
 * The Order Confirmation page shows order history.
 * It also serves as the end point for the current order...
 * once submitted, the Order Confirmation page shows the
 * finalised detail of the order.
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: Pages
 * @inspiration: Silverstripe Ltd, Jeremy
 **/


class OrderConfirmationPage extends CartPage{

	/**
	 * Standard SS variable
	 * @var String
	 */
	private static $icon = 'ecommerce/images/icons/OrderConfirmationPage';

	/**
	 * Standard SS variable
	 * @var Array
	 */
	private static $db = array(
		'StartNewOrderLinkLabel' => 'Varchar(100)',
		'CopyOrderLinkLabel' => 'Varchar(100)',
		'OrderCancelledHeader' => 'Varchar(255)',
		'PaymentSuccessfulHeader' => 'Varchar(255)',
		'PaymentNotSuccessfulHeader' => 'Varchar(255)',
		'PaymentPendingHeader' => 'Varchar(255)',
		'OrderCancelledMessage' => 'HTMLText',
		'PaymentSuccessfulMessage' => 'HTMLText',
		'PaymentNotSuccessfulMessage' => 'HTMLText',
		'PaymentPendingMessage' => 'HTMLText',
		'EnableGoogleAnalytics' => 'Boolean'
	);

	/**
	 * Standard SS variable
	 * @var Array
	 */
	private static $defaults = array(
		"ShowInMenus" => false,
		"ShowInSearch" => false,
		"StartNewOrderLinkLabel" => "start new order",
		"CopyOrderLinkLabel" => "copy order items into a new order",
		'OrderCancelledHeader' => 'Order has been cancelled',
		'PaymentSuccessfulHeader' => 'Payment Successful',
		'PaymentNotSuccessfulHeader' => 'Payment not Completed',
		'PaymentPendingHeader' => 'Payment Pending',
		'OrderCancelledMessage' => '<p>This order is no longer valid.</p>',
		'PaymentSuccessfulMessage' => '<p>Your order will be processed.</p>',
		'PaymentNotSuccessfulMessage' => '<p>Your order will not be processed until your payment has been completed.</p>',
		'PaymentPendingMessage' => '<p>Please complete your payment before the order can be processed.</p>'
	);


	/**
	 * standard SS variable
	 * @Var String
	 */
	private static $singular_name = "Order Confirmation Page";
		function i18n_singular_name() { return _t("OrderConfirmationpage.SINGULARNAME", "Order Confirmation Page");}

	/**
	 * standard SS variable
	 * @Var String
	 */
	private static $plural_name = "Order Confirmation Pages";
		function i18n_plural_name() { return _t("OrderConfirmationpage.PLURALNAME", "Order Confirmation Pages");}

	/**
	 * Standard SS variable.
	 * @var String
	 */
	private static $description = "A page where the customer can view her or his submitted order. Every e-commerce site needs an Order Confirmation Page.";

	/**
	 * Standard SS function, we only allow for one OrderConfirmation Page to exist
	 * but we do allow for extensions to exist at the same time.
	 * @param Member $member
	 * @return Boolean
	 */
	function canCreate($member = null) {
		return OrderConfirmationPage::get()->filter(array("ClassName" => "OrderConfirmationPage"))->Count() ? false : $this->canEdit($member);
	}

	function caView($member = null) {
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
	}

	/**
	 * Shop Admins can edit
	 * @param Member $member
	 * @return Boolean
	 */
	function canEdit($member = null) {
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {
		return false;
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canPublish($member = null) {
		return $this->canEdit($member);
	}

	function customFieldLabels(){
		$newLabels = array(
			"StartNewOrderLinkLabel" => _t("OrderConfirmationPage.STARTNEWORDERLINKLABEL", 'Label for starting new order - e.g. click here to start new order.'),
			"CopyOrderLinkLabel" => _t("OrderConfirmationPage.COPYORDERLINKLABEL", 'Label for copying order items into a new one  - e.g. click here start a new order with the current order items.'),
			"OrderCancelledHeader" => _t("OrderConfirmationPage.ORDERCANCELLEDHEADER", "Header showing when order has been cancelled."),
			"PaymentSuccessfulHeader" => _t("OrderConfirmationPage.PAYMENTSUCCESSFULHEADER", "Header showing when order has been paid in full."),
			"PaymentNotSuccessfulHeader" => _t("OrderConfirmationPage.PAYMENTNOTSUCCESSFULHEADER", "Header showing when the order has not been paid in full."),
			"PaymentPendingHeader" => _t("OrderConfirmationPage.PAYMENTPENDINGHEADER", "Header showing when the order has not been paid in full - but the payment is pending."),
			"OrderCancelledMessage" => _t("OrderConfirmationPage.ORDERCANCELLEDMESSAGE", "Message showing when order has been paid cancelled."),
			"PaymentSuccessfulMessage" => _t("OrderConfirmationPage.PAYMENTSUCCESSFULMESSAGE", "Message showing when order has been paid in full."),
			"PaymentNotSuccessfulMessage" => _t("OrderConfirmationPage.PAYMENTNOTSUCCESSFULMESSAGE", "Message showing when the order has not been paid in full."),
			"PaymentPendingMessage" => _t("OrderConfirmationPage.PAYMENTPENDINGMESSAGE", "Message showing when the order has not been paid in full - but the payment is pending."),
			"EnableGoogleAnalytics" => _t("OrderConfirmationPage.ENABLEGOOGLEANALYTICS", "Enable E-commerce Google Analytics.  Make sure it is turned on in your Google Analytics account.")
		);
		return $newLabels;
	}

	/**
	 * standard SS method for decorators.
	 * @param Boolean - $includerelations: array of fields to start with
	 * @return array
	 */
	function fieldLabels($includerelations = true) {
		$defaultLabels = parent::fieldLabels();
		$newLabels = $this->customFieldLabels();
		$labels = array_merge($defaultLabels, $newLabels);
		$extendedArray = $this->extend('updateFieldLabels', $labels);
		if($extendedArray !== null && is_array($extendedArray) && count($extendedArray)) {
			foreach($extendedArray as $extendedResult) {
				return $labels += $extendedResult;
			}
		}
		return $labels;
	}

	/**
	 *@return FieldList
	 **/
	function getCMSFields(){
		$fields = parent::getCMSFields();
		$fields->removeFieldFromTab('Root.Messages.Messages.Actions',"ProceedToCheckoutLabel");
		$fields->removeFieldFromTab('Root.Messages.Messages.Actions',"ContinueShoppingLabel");
		$fields->removeFieldFromTab('Root.Messages.Messages.Actions',"ContinuePageID");
		$fields->removeFieldFromTab('Root.Messages.Messages.Actions',"SaveOrderLinkLabel");
		$fields->removeFieldFromTab('Root.Messages.Messages.Errors',"NoItemsInOrderMessage");
		$fieldLabels = $this->fieldLabels();
		$fields->addFieldToTab('Root.Messages.Messages.Actions', new TextField('StartNewOrderLinkLabel', $fieldLabels["StartNewOrderLinkLabel"]));
		$fields->addFieldToTab('Root.Messages.Messages.Actions', new TextField('CopyOrderLinkLabel', $fieldLabels["CopyOrderLinkLabel"]));
		$fields->addFieldsToTab('Root.Messages.Messages.Payment', array(
			new HeaderField('Successful'),
			new TextField('PaymentSuccessfulHeader', $fieldLabels['PaymentSuccessfulHeader']),
			$htmlEditorField1 = new HTMLEditorField('PaymentSuccessfulMessage', $fieldLabels['PaymentSuccessfulMessage']),
			new HeaderField('Unsuccessful'),
			new TextField('PaymentNotSuccessfulHeader', $fieldLabels['PaymentNotSuccessfulHeader']),
			$htmlEditorField2 = new HTMLEditorField('PaymentNotSuccessfulMessage', $fieldLabels["PaymentNotSuccessfulMessage"]),
			new HeaderField('Pending'),
			new TextField('PaymentPendingHeader', $fieldLabels['PaymentPendingHeader']),
			$htmlEditorField3 = new HTMLEditorField('PaymentPendingMessage', $fieldLabels["PaymentPendingMessage"]),
			new HeaderField('Cancelled'),
			new TextField('OrderCancelledHeader', $fieldLabels['OrderCancelledHeader']),
			$htmlEditorField3 = new HTMLEditorField('OrderCancelledMessage', $fieldLabels["OrderCancelledMessage"])
		));
		$htmlEditorField1->setRows(3);
		$htmlEditorField2->setRows(3);
		$htmlEditorField3->setRows(3);
		$fields->addFieldToTab("Root.Analytics", new CheckboxField("EnableGoogleAnalytics", $fieldLabels["EnableGoogleAnalytics"]));
		return $fields;
	}


	/**
	 * Returns the link or the Link to the OrderConfirmationPage page on this site
	 * @return String (URLSegment)
	 */
	public static function find_link() {
		if($page = OrderConfirmationPage::get()->filter(array("ClassName" => "OrderConfirmationPage"))->First()) {
			return $page->Link();
		}
		elseif($page = OrderConfirmationPage::get()->First()) {
			return $page->Link();
		}
		return CartPage::find_link();
	}

	/**
	 * Return a link to view the order on this page.
	 * @param int|string $orderID ID of the order
	 * @return String (URLSegment)
	 */
	public static function get_order_link($orderID) {
		return self::find_link(). 'showorder/' . $orderID . '/';
	}

	/**
	 * Return a link to view the order on this page.
	 * @param int|string $orderID ID of the order
	 * @param String $type - the type of email you want to send.
	 * @param Boolean $actuallySendEmail - do we actually send the email
	 * @param Int $alternativeOrderStepID - OrderStep to use
	 *
	 * NOTE: you can not ActuallySendEmail and have an AlternativeOrderStepID
	 *
	 * @return String (URLSegment)
	 */
	public static function get_email_link($orderID, $emailClassName = "Order_StatusEmail", $actuallySendEmail = false, $alternativeOrderStepID = 0) {
		$link = self::find_link(). 'sendemail/' . $orderID . '/'.$emailClassName;
		if($actuallySendEmail) {
			$link .= "?send=1";
		}
		elseif($alternativeOrderStepID) {
			$link .= "?use=".$alternativeOrderStepID;
		}
		return $link;
	}

	/**
	 * Return a link to view the order on this page.
	 * @param int|string $orderID ID of the order
	 * @return String (URLSegment)
	 */
	public function getOrderLink($orderID) {
		return self::get_order_link($orderID);
	}

	/**
	 * returns the Checkout_StepDescription assocatiated with the final step: the order confirmation.
	 * @param Boolean $isCurrentStep
	 * @return Checkout_StepDescription
	 */
	public function CurrentCheckoutStep($isCurrentStep = false) {
		$do = new CheckoutPage_StepDescription();
		$do->Link = $this->Link;
		$do->Heading = $this->MenuTitle;
		$do->Code = $this->URLSegment;
		$do->LinkingMode = "notCompleted";
		if($isCurrentStep) {
			$do->LinkingMode .= " current";
		}
		$do->Completed = 0;
		$do->ID = 99;
		return $do;
	}


	/**
	 * standard SS method for use in templates
	 * we are overriding the code from the Cart Page here.
	 * @return String
	 */
	function LinkingMode(){
		return parent::LinkingMode();
	}

	/**
	 * standard SS method for use in templates
	 * we are overriding the code from the Cart Page here.
	 * @return String
	 */
	function LinkOrSection(){
		return parent::LinkOrSection();
	}

	/**
	 * standard SS method for use in templates
	 * we are overriding the code from the Cart Page here.
	 * @return String
	 */
	function LinkOrCurrent(){
		return parent::LinkOrCurrent();
	}

	public function requireDefaultRecords(){
		parent::requireDefaultRecords();
		$checkoutPage = CheckoutPage::get()->first();
		if($checkoutPage) {
			$orderConfirmationPage = OrderConfirmationPage::get()->first();
			if(!$orderConfirmationPage) {
				$orderConfirmationPage = OrderConfirmationPage::create();
				$orderConfirmationPage->Title = "Order Confirmation";
				$orderConfirmationPage->MenuTitle = "Order Confirmation";
				$orderConfirmationPage->URLSegment = "order-confirmation";
				$orderConfirmationPage->writeToStage("Stage");
				$orderConfirmationPage->publish("Stage", "Live");
			}
		}
	}

}

class OrderConfirmationPage_Controller extends CartPage_Controller{


	/**
	 * @static array
	 * standard SS variable
	 * it is important that we list all the options here
	 */
	private static $allowed_actions = array(
		'saveorder',
		'sendreceipt',
		'CreateAccountForm',
		'retrieveorder',
		'loadorder',
		'startneworder',
		'showorder',
		'copyorder',
		'sendemail',
		'CancelForm',
		'PaymentForm',
	);


	/**
	 * standard controller function
	 **/
	function init() {
		//we retrieve the order in the parent page
		//the parent page also takes care of the security
		if($sessionOrderID = Session::get("CheckoutPageCurrentOrderID")) {
			$this->currentOrder = Order::get()->byID($sessionOrderID);
			if($this->currentOrder) {
				$this->overrideCanView = true;
				//more than an hour has passed...
				if(strtotime($this->currentOrder->LastEdited) < (strtotime("Now") - 60 * 60)) {
					Session::clear("CheckoutPageCurrentOrderID");
					Session::clear("CheckoutPageCurrentOrderID");
					Session::set("CheckoutPageCurrentOrderID", 0);
					Session::save();
					$this->overrideCanView = false;
					$this->currentOrder = null;
				}
			}
		}
		parent::init();
		Requirements::themedCSS('Order', 'ecommerce');
		Requirements::themedCSS('Order_Print', 'ecommerce', "print");
		Requirements::themedCSS('CheckoutPage', 'ecommerce');
		Requirements::javascript('ecommerce/javascript/EcomPayment.js');
		Requirements::javascript('ecommerce/javascript/EcomPrintAndMail.js');
		$this->includeGoogleAnalyticsCode();
	}


	/**
	 * This method exists just so that template
	 * sets CurrentOrder variable
	 * @param HTTPRequest
	 * @return array
	 **/
	function showorder(SS_HTTPRequest $request) {
		isset($project) ? $themeBaseFolder = $project : $themeBaseFolder = "mysite";
		if(isset($_REQUEST["print"])) {
			Requirements::clear();
			Requirements::themedCSS("typography", $themeBaseFolder); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
			Requirements::themedCSS("OrderReport", "ecommerce"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
			Requirements::themedCSS("Order_Invoice", "ecommerce"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
			Requirements::themedCSS("Order_Invoice_Print_Only", "ecommerce", "print"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
			Config::nest();
			Config::inst()->update('SSViewer', 'theme_enabled', true);
			$html = $this->renderWith("Invoice");
			Config::unnest();
			return $html;
		}
		elseif(isset($_REQUEST["packingslip"])) {
			Requirements::clear();
			Requirements::themedCSS("typography", $themeBaseFolder); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
			Requirements::themedCSS("OrderReport", "ecommerce"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
			Requirements::themedCSS("Order_PackingSlip", "ecommerce"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
			Config::nest();
			Config::inst()->update('SSViewer', 'theme_enabled', true);
			$html = $this->renderWith("PackingSlip");
			Config::unnest();
			return $html;
		}
		return array();
	}

	/**
	 * This is an additional way to look at an order.
	 * The order is already retrieved from the init function
	 *
	 * @return Array
	 **/
	function retrieveorder(SS_HTTPRequest $request){
		return array();
	}

	/**
	 * copies either the current order into the shopping cart
	 *
	 * TO DO: untested
	 * TO DO: what to do with old order
	 * @param SS_HTTPRequest
	 * @return Array
	 */
	function copyorder(SS_HTTPRequest $request) {
		self::set_message(_t("CartPage.ORDERLOADED", "Order has been loaded."));
		ShoppingCart::singleton()->copyOrder($this->currentOrder->ID);
		$this->redirect(CheckoutPage::find_last_step_link());
		return array();
	}

	/**
	 * @param HTTPRequest
	 * @return Array - just so the template is still displayed
	 **/
	function sendreceipt(SS_HTTPRequest $request) {
		if($o = $this->currentOrder) {
			if($m = $o->Member()) {
				if($m->Email) {
					$subject = _t("Account.COPYONLY", "--- COPY ONLY ---");
					$message = _t("Account.COPYONLY", "--- COPY ONLY ---");
					$o->sendReceipt($subject, $message, true);
					$this->message = _t('OrderConfirmationPage.RECEIPTSENT', 'An order receipt has been sent to: ').$m->Email.'.';
				}
				else {
					$this->message = _t('OrderConfirmationPage.RECEIPTNOTSENTNOTSENDING', 'Email could NOT be sent.');
				}
			}
			else {
				$this->message = _t('OrderConfirmationPage.RECEIPTNOTSENTNOEMAIL', 'No email could be found for sending this receipt.');
			}
		}
		else {
			$this->message = _t('OrderConfirmationPage.RECEIPTNOTSENTNOORDER', 'Order could not be found.');
		}
		$baseFolder = Director::baseFolder() ;
		if(!class_exists('\Pelago\Emogrifier')) {
			require_once(Director::baseFolder() . '/ecommerce/thirdparty/Emogrifier.php');
		}
		Requirements::clear();
		isset($project) ? $themeBaseFolder = $project : $themeBaseFolder = "mysite";
		Requirements::themedCSS("typography", $themeBaseFolder); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		Requirements::themedCSS("OrderReport", "ecommerce"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		Requirements::themedCSS("Order_Invoice", "ecommerce", "print"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		Config::nest();
		Config::inst()->update('SSViewer', 'theme_enabled', true);
		$html = $this->renderWith("Order_ReceiptEmail");
		Config::unnest();
		// if it's an html email, filter it through emogrifier
		$cssFileLocation = $baseFolder . "/". EcommerceConfig::get("Order_Email", "css_file_location");;
		$html .= "\r\n\r\n<!-- CSS can be found here: $cssFileLocation -->";
		$cssFileHandler = fopen($cssFileLocation, 'r');
		$css = fread($cssFileHandler,  filesize($cssFileLocation));
		fclose($cssFileHandler);
		$emog = new \Pelago\Emogrifier($html, $css);
		$html = $emog->emogrify();
		return $html;
	}

	/**
	 * Returns a dataobject set of the checkout steps if
	 * the OrderConfirmationPage is shown as part of the checkout process
	 * We repeat these here so that you can show the user that (s)he has reached the last step
	 *
	 * @param Int $number - if set, it returns that one step.
	 * @return Null | DataObject (CheckoutPage_Description) | ArrayList (CheckoutPage_Description)
	 */
	function CheckoutSteps($number = 0) {
		$where = '';
		if($number) {
			$where = "\"CheckoutPage_StepDescription\".\"ID\" = $number";
		}
		if(EcommerceConfig::get("OrderConfirmationPage_Controller", "include_as_checkout_step")) {
			if($this->currentOrder->IsInSession()) {
				$dos = CheckoutPage_StepDescription::get()->where($where)->sort("ID", "ASC");
				if($number) {
					if($dos && $dos->count()) {
						return $dos->First();
					}
				}
				$arrayList = new ArrayList(array());
				foreach($dos as $do) {
					$do->LinkingMode = "link completed";
					$do->Completed = 1;
					$do->Link = "";
					$arrayList->push($do);
				}
				$do = $this->CurrentCheckoutStep(true);
				if($do) {
					$arrayList->push($do);
				}
				return $arrayList;
			}
		}
	}

	/**
	 * returns the percentage of checkout steps done (0 - 100)
	 * @return Integer
	 */
	public function PercentageDone(){
		return 100;
	}

	/**
	 *
	 * @return string
	 */
	public function PaymentHeader(){
		if($order = $this->Order()) {
			if($this->OrderIsCancelled()) {
				return $this->OrderCancelledHeader;
			}
			elseif($this->PaymentIsPending()) {
				return $this->PaymentPendingHeader;
			}
			elseif($this->IsPaid()) {
				return $this->PaymentSuccessfulHeader;
			}
			else {
				return $this->PaymentNotSuccessfulHeader;
			}
		}
	}

	public function PaymentMessage(){
		if($order = $this->Order()) {
			if($this->OrderIsCancelled()) {
				return $this->OrderCancelledMessage;
			}
			elseif($this->PaymentIsPending()) {
				return $this->PaymentPendingMessage;
			}
			elseif($this->IsPaid()) {
				return $this->PaymentSuccessfulMessage;
			}
			else {
				return $this->PaymentNotSuccessfulMessage;
			}
		}
	}

	/**
	 * @return boolean
	 */
	public function OrderIsCancelled() {
		if($order = $this->Order()) {
			return $order->getIsCancelled();
		}
	}

	/**
	 * Is the Order paid?
	 * This can be useful for choosing what header to show
	 *
	 * @return Boolean
	 */
	public function IsPaid(){
		if($order = $this->Order()) {
			return $order->IsPaid();
		}
	}

	/**
	 * Are there any order Payments Pending
	 * This can be useful for choosing what header to show
	 *
	 * @return Boolean
	 */
	public function PaymentIsPending(){
		if($order = $this->Order()) {
			return $order->PaymentIsPending();
		}
	}

	/**
	 * Returns the form to cancel the current order,
	 * checking to see if they can cancel their order
	 * first of all.
	 *
	 * @return OrderForm_Cancel
	 */
	function CancelForm() {
		if($this->Order()) {
			if($this->currentOrder->canCancel()) {
				return OrderForm_Cancel::create($this, 'CancelForm', $this->currentOrder);
			}
		}
		//once cancelled, you will be redirected to main page - hence we need this...
		if($this->orderID) {
			return array();
		}
	}

	/**
	 * show the payment form
	 *
	 * @return Form (OrderForm_Payment) or Null
	 **/
	function PaymentForm(){
		if($this->currentOrder){
			if($this->currentOrder->canPay()) {
				Requirements::javascript("ecommerce/javascript/EcomPayment.js");
				return OrderForm_Payment::create($this, 'PaymentForm', $this->currentOrder);
			}
		}
		return array();
	}


	/**
	 * Can this page only show Submitted Orders (e.g. OrderConfirmationPage) ?
	 * @return Boolean
	 */
	protected function onlyShowSubmittedOrders() {return true;}

	/**
	 * Can this page only show Unsubmitted Orders (e.g. CartPage) ?
	 * @return Boolean
	 */
	protected function onlyShowUnsubmittedOrders() {return false;}

	/**
	 * sends an order email, which can be specified in the URL
	 * and displays a sample email
	 * typically this link is opened in a new window.
	 * @param SS_HTTPRequest $request
	 * @return HTML
	 **/
	function sendemail(SS_HTTPRequest $request) {
		if($this->currentOrder) {
			$emailClassName = "Order_ReceiptEmail";
			if(class_exists($request->param("OtherID"))) {
				if(is_a(singleton($request->param("OtherID")), Object::getCustomClass("Order_Email"))) {
					$emailClassName = $request->param("OtherID");
				}
			}
			if($request->getVar("send")) {
				if($email = $this->currentOrder->getOrderEmail()) {
					$subject = _t("Account.COPYONLY", "--- COPY ONLY ---");
					$message = _t("Account.COPYONLY", "--- COPY ONLY ---");
					if($this->currentOrder->sendEmail($subject, $message, $resend = true, $adminOnly = false, $emailClassName)) {
						$this->message = _t('OrderConfirmationPage.RECEIPTSENT', 'An email has been sent to: ').$email.'.';
					}
					else {
						$this->message = _t('OrderConfirmationPage.RECEIPT_NOT_SENT', 'Email sent unsuccesfully to: ').$email.'. EMAIL NOT SENT.';
					}
				}
				else {
					$this->message = _t('OrderConfirmationPage.RECEIPTNOTSENTNOEMAIL', 'No customer details found.  EMAIL NOT SENT.');
				}
			}
			elseif($statusID = intval($request->getVar("use"))) {
				$step = OrderStep::get()->byID($statusID);
				if($step) {
					$emailClassName = $step->getEmailClassName();
				}
			}
			//display same data...
			Requirements::clear();
			return $this->currentOrder->renderOrderInEmailFormat($this->message, $emailClassName);
		}
		else {
			return _t('OrderConfirmationPage.RECEIPTNOTSENTNOORDER', 'Order could not be found.');
		}
	}

	protected function includeGoogleAnalyticsCode(){
		if($this->EnableGoogleAnalytics && $this->currentOrder && Director::isLive()) {
			$currencyUsedObject = $this->currentOrder->CurrencyUsed();
			if($currencyUsedObject) {
				$currencyUsedString = $currencyUsedObject->Code;
			}
			if(empty($currencyUsedString)) {
				$currencyUsedString = EcommerceCurrency::default_currency_code();
			}
			$js = '
				ga(\'require\', \'ecommerce\');
				ga(
					\'ecommerce:addTransaction\',
					{
						\'id\': \''.$this->currentOrder->ID.'\',
						\'revenue\': \''.$this->currentOrder->getSubTotal().'\',
						\'currency\': \''.$currencyUsedString.'\'
					}
				);
				ga(\'ecommerce:send\');';
			Requirements::customScript($js, "GoogleAnalyticsEcommerce");
		}
	}

}



