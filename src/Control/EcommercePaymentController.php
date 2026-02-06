<?php

namespace Sunnysideup\Ecommerce\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\Form;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\OrderFormPayment;
use Sunnysideup\Ecommerce\Model\Order;

/**
 * Class \Sunnysideup\Ecommerce\Control\EcommercePaymentController
 */
class EcommercePaymentController extends Controller
{
    /**
     * @var Order
     */
    protected $currentOrder;

    /**
     * @var string
     */
    protected $errorMessage = '';

    /**
     * @var string
     */
    protected $goodMessage = '';

    /**
     * @var string
     */
    private static $url_segment = 'ecommercepayment';

    private static $allowed_actions = [
        'thankyou',
        'index',
        'pay',
        'PaymentForm',
    ];

    /**
     * @param int|string $orderID
     *
     * @return string (Link)
     */
    public static function make_payment_link($orderID)
    {
        $urlSegment = EcommerceConfig::get(EcommercePaymentController::class, 'url_segment');

        return Controller::join_links(
            Director::baseURL(),
            $urlSegment . '/pay/' . $orderID . '/'
        );
    }

    public function index()
    {
        return [];
    }

    public function pay()
    {
        return [];
    }

    /**
     * @todoTEST!!!
     */
    public function thankyou()
    {
        $this->goodMessage = _t('EcommercePaymentController.THANKYOU', 'Thank you for your payment.');
        $this->currentOrder = null;

        return [];
    }

    /**
     * @param string $action
     *
     * @return string (Link)
     */
    public function Link($action = null)
    {
        $URLSegment = Config::inst()->get(static::class, 'url_segment');
        if (! $URLSegment) {
            $URLSegment = static::class;
        }

        return Controller::join_links(
            Director::baseURL(),
            $URLSegment,
            $action
        );
    }

    /**
     * @return array|Form (OrderFormPayment)
     */
    public function PaymentForm()
    {
        if ($this->currentOrder) {
            if ($this->currentOrder->canPay()) {
                Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomPayment.js');

                return OrderFormPayment::create($this, 'PaymentForm', $this->currentOrder, $this->Link('thankyou'));
            }
            $this->errorMessage = _t('EcommercePaymentController.CANNOTMAKEPAYMENT', 'You can not make a payment for this order.');
        } else {
            $this->errorMessage = _t('EcommercePaymentController.ORDERCANNOTBEFOUND', 'Order can not be found.');
        }

        return [];
    }

    public function ErrorMessage()
    {
        return $this->errorMessage;
    }

    public function GoodMessage()
    {
        return $this->goodMessage;
    }

    protected function init()
    {
        parent::init();
        // isset($project) ? $themeBaseFolder = $project : $themeBaseFolder = 'app';

        Requirements::javascript('https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js');
        //Requirements::javascript(Director::protocol()."ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
        $id = (int) $this->request->param('ID');
        if (! $id && isset($_REQUEST['OrderID'])) {
            $id = (int) $_REQUEST['OrderID'];
        }
        if ($id !== 0) {
            $order = Order::get_by_id_if_can_view($id);
            if ($order) {
                $this->currentOrder = $order;
            }
        }
    }
}
