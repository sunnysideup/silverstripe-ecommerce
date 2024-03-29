<?php

namespace Sunnysideup\Ecommerce\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\DataObject;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * This call can be used when you need input from the customer
 * in the order process.
 *
 * To use
 *
 * 1. create class that extends OrderStepController
 * 2. make sure the class has a $url_segment static var
 * 3. create content and/or form for page
 * 4. make sure you set up route (route.yml) to get to the
 *
 */
abstract class OrderStepController extends Controller
{
    /**
     * @var string
     */
    protected $alternativeContent = '';

    /**
     * @var array
     */
    private static $allowed_actions = [
        'error' => true,
    ];

    /**
     * @var Order
     */
    private static $_order;

    /**
     * when no action is selected
     * this action runs...
     *
     * @param mixed $request
     */
    public function index($request)
    {
        $this->alternativeContent = '<p class="message bad">Sorry, we can not find the page you are looking for.</p>';

        return $this->renderWith(\Page::class);
    }

    /**
     * there is an error ...
     *
     * @param mixed $request
     */
    public function error($request)
    {
        $this->alternativeContent = '<p class="message bad">Sorry, an error occurred, please contact us for more information....</p>';

        return $this->renderWith(\Page::class);
    }

    /**
     * main content ...
     *
     * @return string
     */
    public function Content(?Order $order = null)
    {
        if ($this->alternativeContent) {
            return $this->alternativeContent;
        }

        return $this->standardContent($order);
    }

    /**
     * @oaram string $action
     *
     * @param null|mixed $action
     *
     * @return string
     */
    public function Link($action = null)
    {
        $link = '/' . Config::inst()->get($this->nameOfControllerClass(), 'url_segment') . '/';
        if ($action) {
            $link .= $action . '/';
        }

        return $link . $this->getOrderGetParams();
    }

    public function errorLink(): string
    {
        return $this->Link('error');
    }

    protected static function name_of_controller_class(): string
    {
        return static::class;
    }

    protected static function secure_hash(Order $order): string
    {
        $obj = Injector::inst()->get(self::name_of_controller_class());

        return $obj->secureHash($order);
    }

    protected function nameOfControllerClass(): string
    {
        return self::name_of_controller_class();
    }

    /**
     * related OrderStatusLog class.
     */
    abstract protected function nameOfLogClass(): string;

    /**
     * @return string ($html)
     */
    protected function standardContent(?Order $order = null): string
    {
        user_error('Make sure to put some content here in classes that extend ' . static::class);

        return '';
    }

    /**
     * the form on the field.
     *
     * @return Form
     */
    protected function Form()
    {
        return $this->Form;
    }

    /**
     * code of related order step.
     *
     * @return string
     */
    abstract protected function codeOfRelevantOrderStep();

    /**
     * used to secure page.
     */
    abstract protected function secureHash(Order $order): string;

    /**
     * is the order valid?
     *
     * @param null|mixed $dataOrRequest
     */
    protected function checkOrder($dataOrRequest = null): bool
    {
        $order = $this->Order($dataOrRequest);

        return $order && $order->exists();
    }

    /**
     * finds the order ...
     *
     * @param mixed $dataOrRequest
     *
     * @return Order
     */
    protected function myOrder($dataOrRequest = null)
    {
        if (! self::$_order) {
            if (is_array($dataOrRequest) &&
                isset($dataOrRequest['OrderID'], $dataOrRequest['OrderSessionID'])
            ) {
                $id = (int) $dataOrRequest['OrderID'];
                $sessionID = Convert::raw2sql($dataOrRequest['OrderSessionID']);
            } elseif (isset($_POST['OrderID'], $_POST['OrderSessionID'])) {
                $id = (int) $_POST['OrderID'];
                $sessionID = Convert::raw2sql($_POST['OrderSessionID']);
            } elseif (isset($_GET['OrderID'], $_GET['OrderSessionID'])) {
                $id = (int) $_GET['OrderID'];
                $sessionID = Convert::raw2sql($_GET['OrderSessionID']);
            } elseif ($dataOrRequest instanceof HTTPRequest) {
                $id = (int) $dataOrRequest->param('ID');
                $sessionID = Convert::raw2sql($dataOrRequest->param('OtherID'));
            } else {
                $id = (int) $this->request->param('ID');
                $sessionID = Convert::raw2sql($this->request->param('OtherID'));
            }

            self::$_order = Order::get_order_cached((int) $id);
            if (self::$_order) {
                if ($this->secureHash(self::$_order) !== $sessionID) {
                    self::$_order = null;
                }
            }
        }

        return self::$_order;
    }

    /**
     * @return string
     */
    protected function getOrderGetParams()
    {
        $order = $this->myOrder();
        if ($order) {
            return '?OrderID=' . $order->ID . '&OrderSessionID=' . self::secure_hash($order);
        }
    }

    /**
     * @return null|OrderStep|\SilverStripe\ORM\DataObject
     */
    protected function orderStep()
    {
        return DataObject::get_one(
            OrderStep::class,
            ['Code' => $this->codeOfRelevantOrderStep()]
        );
    }
}
