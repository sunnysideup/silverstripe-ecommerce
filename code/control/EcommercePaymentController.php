<?php


/**
 * @description: Used to diplay the payment form.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: control
 **/

class EcommercePaymentController extends Controller
{

    private static $allowed_actions = array(
        "thankyou",
        "index",
        "pay",
        "PaymentForm"
    );

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
    public static function make_payment_link($orderID)
    {
        $urlSegment = EcommerceConfig::get("EcommercePaymentController", "url_segment");
        $link = Controller::join_links(
            Director::baseURL(),
            $urlSegment."/pay/".$orderID."/"
        );
        return $link;
    }

    public function init()
    {
        parent::init();
        isset($project) ? $themeBaseFolder = $project : $themeBaseFolder = "mysite";
        Requirements::themedCSS("typography", $themeBaseFolder);
        Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
        //Requirements::block(THIRDPARTY_DIR."/jquery/jquery.js");
        //Requirements::javascript(Director::protocol()."ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
        $id = intval($this->request->param("ID"));
        if (!$id && isset($_REQUEST["OrderID"])) {
            $id = intval($_REQUEST["OrderID"]);
        }
        if ($id) {
            $order = Order::get_by_id_if_can_view($id);
            if ($order) {
                $this->currentOrder = $order;
            }
        }
    }

    public function index()
    {
        return array();
    }


    public function pay()
    {
        return array();
    }

    /**
     * TO DO: TEST!!!
     *
     */
    public function thankyou()
    {
        $this->goodMessage = _t("EcommercePaymentController.THANKYOU", "Thank you for your payment.");
        $this->currentOrder = null;
        return array();
    }

    /**
     * @param String $action
     * @return String (Link)
     */
    public function Link($action = null)
    {
        $URLSegment = Config::inst()->get($this->class, "url_segment");
        if (!$URLSegment) {
            $URLSegment = $this->class;
        }
        return Controller::join_links(
            Director::baseURL(),
            $URLSegment,
            $action
        );
    }

    /**
     * @return Form (OrderForm_Payment) | Array
     **/
    public function PaymentForm()
    {
        if ($this->currentOrder) {
            if ($this->currentOrder->canPay()) {
                Requirements::javascript("ecommerce/javascript/EcomPayment.js");
                return OrderForm_Payment::create($this, 'PaymentForm', $this->currentOrder, $this->Link("thankyou"));
            } else {
                $this->errorMessage = _t("EcommercePaymentController.CANNOTMAKEPAYMENT", "You can not make a payment for this order.");
            }
        } else {
            $this->errorMessage = _t("EcommercePaymentController.ORDERCANNOTBEFOUND", "Order can not be found.");
        }
        return array();
    }

    public function ErrorMessage()
    {
        return $this->errorMessage;
    }

    public function GoodMessage()
    {
        return $this->goodMessage;
    }
}
