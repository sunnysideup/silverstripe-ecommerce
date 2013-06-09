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
	public static $icon = 'ecommerce/images/icons/OrderConfirmationPage';

	/**
	 * Standard SS variable
	 * @var Array
	 */
	public static $db = array(
		'StartNewOrderLinkLabel' => 'Varchar(100)',
		'CopyOrderLinkLabel' => 'Varchar(100)',
		'PaymentSuccessfulHeader' => 'Varchar(255)',
		'PaymentNotSuccessfulHeader' => 'Varchar(255)',
		'PaymentSuccessfulMessage' => 'HTMLText',
		'PaymentNotSuccessfulMessage' => 'HTMLText'
	);

	/**
	 * Standard SS variable
	 * @var Array
	 */
	public static $defaults = array(
		"ShowInMenus" => false,
		"ShowInSearch" => false,
		"StartNewOrderLinkLabel" => "start new order",
		"CopyOrderLinkLabel" => "copy order items into a new order"
	);


	/**
	 * standard SS variable
	 * @Var String
	 */
	public static $singular_name = "Order Confirmation Page";
		function i18n_singular_name() { return _t("OrderConfirmationpage.SINGULARNAME", "Order Confirmation Page");}

	/**
	 * standard SS variable
	 * @Var String
	 */
	public static $plural_name = "Order Confirmation Pages";
		function i18n_plural_name() { return _t("OrderConfirmationpage.PLURALNAME", "Order Confirmation Pages");}

	/**
	 * Standard SS variable.
	 * @var String
	 */
	public static $description = "A page where the customer can view her or his submitted order. Every e-commerce site needs an Order Confirmation Page.";


	/**
	 * Standard SS function, we only allow for one OrderConfirmation Page to exist
	 * but we do allow for extensions to exist at the same time.
	 * @param Member $member
	 * @return Boolean
	 */
	function canCreate($member = null) {
		return OrderConfirmationPage::get()->filter(array("ClassName" => "OrderConfirmationPage"))->Count() ? false : true;
	}

	function customFieldLabels(){
		$newLabels = array(
			"StartNewOrderLinkLabel" => _t("EcommerceDBConfig.STARTNEWORDERLINKLABEL", 'Label for starting new order - e.g. click here to start new order'),
			"CopyOrderLinkLabel" => _t("EcommerceDBConfig.COPYORDERLINKLABEL", 'Label for copying order items into a new one  - e.g. click here start a new order with the current order items'),
			"PaymentSuccessfulHeader" => _t("EcommerceDBConfig.PAYMENTSUCCESSFULHEADER", "Message showing when order has been paid in full (usually at the top of the page)"),
			"PaymentNotSuccessfulHeader" => _t("EcommerceDBConfig.PAYMENTNOTSUCCESSFULHEADER", "Message showing when the order has not been paid in full (usually at the top of the page)"),
			"PaymentSuccessfulMessage" => _t("EcommerceDBConfig.PAYMENTSUCCESSFULMESSAGE", "Message showing when order has been paid in full"),
			"PaymentNotSuccessfulMessage" => _t("EcommerceDBConfig.PAYMENTNOTSUCCESSFULMESSAGE", "Message showing when the order has not been paid in full")
		);
		return $newLabels;
	}

	/**
	 * standard SS method for decorators.
	 * @param Boolean - $includerelations: array of fields to start with
	 * @return null ($fields variable is automatically updated)
	 */
	function fieldLabels($includerelations = true) {
		$defaultLabels = parent::fieldLabels();
		$newLabels = $this->customFieldLabels();
		$labels = array_merge($defaultLabels, $newLabels);
		$this->extend('updateFieldLabels', $labels);
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
			$htmlEditorField2 = new HTMLEditorField('PaymentNotSuccessfulMessage', $fieldLabels["PaymentNotSuccessfulMessage"], 5)
		));
		$htmlEditorField1->setRows(3);
		$htmlEditorField2->setRows(3);
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
		$link = self::find_link(). 'sendemail/' . $orderID . '/'.$emailClassName.'/';
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

}

class OrderConfirmationPage_Controller extends CartPage_Controller{


	/**
	 * @static array
	 * standard SS variable
	 * it is important that we list all the options here
	 */
	static $allowed_actions = array(
		'saveorder',
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
		parent::init();
		Requirements::themedCSS('Order', 'ecommerce');
		Requirements::themedCSS('Order_Print', 'ecommerce', "print");
		Requirements::javascript('ecommerce/javascript/EcomPayment.js');
		Requirements::javascript('ecommerce/javascript/EcomPrintAndMail.js');
		//clear steps from checkout page otherwise in the next order
		//you go straight to the last step.
		Session::clear("CheckoutPageCurrentOrderID");
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
			Requirements::themedCSS("Order_Invoice", "ecommerce", "print"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
			return $this->renderWith("Invoice");
		}
		elseif(isset($_REQUEST["packingslip"])) {
			Requirements::clear();
			Requirements::themedCSS("typography", $themeBaseFolder); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
			Requirements::themedCSS("OrderReport", "ecommerce"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
			Requirements::themedCSS("Order_PackingSlip", "ecommerce"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
			return $this->renderWith("PackingSlip");
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
		if(!class_exists('Emogrifier')) {
			require_once(Director::baseFolder() . '/ecommerce/thirdparty/Emogrifier.php');
		}
		Requirements::clear();
		isset($project) ? $themeBaseFolder = $project : $themeBaseFolder = "mysite";
		Requirements::themedCSS("typography", $themeBaseFolder); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		Requirements::themedCSS("OrderReport", "ecommerce"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		Requirements::themedCSS("Order_Invoice", "ecommerce", "print"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		$html =  $this->renderWith("Order_ReceiptEmail");
		// if it's an html email, filter it through emogrifier
		$cssFileLocation = $baseFolder . "/". EcommerceConfig::get("Order_Email", "css_file_location");;
		$html .= "\r\n\r\n<!-- CSS can be found here: $cssFileLocation -->";
		$cssFileHandler = fopen($cssFileLocation, 'r');
		$css = fread($cssFileHandler,  filesize($cssFileLocation));
		fclose($cssFileHandler);
		$emog = new Emogrifier($html, $css);
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
			if($this->currentOrder->SessionID && $this->currentOrder->SessionID == session_id()) {
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
	 * Returns the form to cancel the current order,
	 * checking to see if they can cancel their order
	 * first of all.
	 *
	 * @return OrderForm_Cancel
	 */
	function CancelForm() {
		if($this->Order()) {
			if($this->currentOrder->canCancel()) {
				return new OrderForm_Cancel($this, 'CancelForm', $this->currentOrder);
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
		if($this->Order()){
			if($this->currentOrder->canPay()) {
				Requirements::javascript("ecommerce/javascript/EcomPayment.js");
				return new OrderForm_Payment($this, 'PaymentForm', $this->currentOrder);
			}
		}
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
				if(singleton($request->param("OtherID")) instanceOf Order_Email) {
					$emailClassName = $request->param("OtherID");
				}
			}
			if(isset($_GET["send"]) && $_GET["send"]) {
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
			elseif(isset($_GET["use"]) && $_GET["use"]) {
				//WE MUST MAKE SURE THAT WE DO NOT SAVE ORDER AS
				$this->currentOrder->StatusID = intval($_GET["use"]);
			}
			//display same data...
			Requirements::clear();
			return $this->currentOrder->renderOrderInEmailFormat($this->message, $emailClassName);
		}
		else {
			return _t('OrderConfirmationPage.RECEIPTNOTSENTNOORDER', 'Order could not be found.');
		}
	}

}



