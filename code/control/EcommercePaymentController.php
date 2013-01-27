<?php


/**
 * @description: Used to diplay the payment form.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: control
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommercePaymentController extends Controller {

	/**
	 *
	 * @var Order
	 */
	protected $currentOrder = null;

	/**
	 *
	 * @var String
	 */
	protected $errorMessage = "";


	/**
	 *
	 * @var String
	 */
	protected $goodMessage = "";

	/**
	 * @param String | Int $orderID
	 * @return String (Link)
	 */
	static function make_payment_link($orderID){
		$urlSegment = EcommerceConfig::get("EcommercePaymentController", "url_segment");
		$s = "/".$urlSegment."/pay/".$orderID."/";
		return $s;
	}

	function init(){
		parent::init();
		isset($project) ? $themeBaseFolder = $project : $themeBaseFolder = "mysite";
		Requirements::themedCSS("typography", $themeBaseFolder);
		Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
		//Requirements::block(THIRDPARTY_DIR."/jquery/jquery.js");
		//Requirements::javascript(Director::protocol()."ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
		$id = intval($this->request->param("ID"));
		if(!$id && isset($_REQUEST["OrderID"])) {
			$id = intval($_REQUEST["OrderID"]);
		}
		if($id) {
			$order = Order::get_by_id_if_can_view($id);
			if($order) {
				$this->currentOrder = $order;
			}
		}
	}

	function index() {
		return array();
	}


	function pay() {
		return array();
	}

	/**
	 * TO DO: TEST!!!
	 *
	 */
	function thankyou() {
		$this->goodMessage = _t("EcommercePaymentController.THANKYOU", "Thank you for your payment.");
		$this->currentOrder = null;
		return array();
	}

	/**
	 * @param String $action
	 * @return String (Link)
	 */
	function Link($action = ''){
		$urlSegment = EcommerceConfig::get("EcommercePaymentController", "url_segment");
		$urlSegmentWithSlashes = "/".$urlSegment."/";
		if($action) {
			$urlSegmentWithSlashes .= $action."/";
		}
		return $urlSegmentWithSlashes;
	}

	/**
	 * @return Form (OrderForm_Payment) | Array
	 **/
	function PaymentForm(){
		if($this->currentOrder){
			if($this->currentOrder->canPay()) {
				Requirements::javascript("ecommerce/javascript/EcomPayment.js");
				return new OrderForm_Payment($this, 'PaymentForm', $this->currentOrder, $this->Link("thankyou"));
			}
			else {
				$this->errorMessage = _t("EcommercePaymentController.CANNOTMAKEPAYMENT", "You can not make a payment for this order.");
			}
		}
		else {
			$this->errorMessage = _t("EcommercePaymentController.ORDERCANNOTBEFOUND", "Order can not be found.");
		}
		return Array();
	}

	function ErrorMessage() {
		return $this->errorMessage;
	}

	function GoodMessage() {
		return $this->goodMessage;
	}


}
