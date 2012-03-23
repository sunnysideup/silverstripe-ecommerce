<?php


class EcommercePaymentController extends Controller {

	protected static $url_segment = "ecommercepayment";
		static function set_url_segment($s) {self::$url_segment = $s;}
		static function get_url_segment() {return self::$url_segment;}

	protected $currentOrder = null;
	protected $errorMessage = "";
	protected $goodMessage = "";

	static function make_payment_link($orderID){
		$s = "/".self::get_url_segment()."/pay/".$orderID."/";
		return $s;
	}

	function init(){
		parent::init();
		Requirements::themedCSS("typography");
		Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
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

	function thankyou() {
		return $this->goodMessage = _t("EcommercePaymentController.THANKYOU", "Thank you for your payment.");
		$this->currentOrder = null;
		return array();
	}

	function Link($action = ''){
		$s = "/".self::get_url_segment()."/";
		if($action) {
			$s .= $action."/";
		}
		return $s;
	}

	/**
	 *@return Form (OrderForm_Payment) or Null
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
