<?php

namespace Sunnysideup\Ecommerce\Pages;
use Sunnysideup\Ecommerce\Api\SetThemed;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Email\OrderEmail;
use Sunnysideup\Ecommerce\Email\OrderInvoiceEmail;
use Sunnysideup\Ecommerce\Email\OrderReceiptEmail;
use Sunnysideup\Ecommerce\Forms\OrderFormCancel;
use Sunnysideup\Ecommerce\Forms\OrderFormFeedback;
use Sunnysideup\Ecommerce\Forms\OrderFormPayment;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\CheckoutPageStepDescription;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

class OrderConfirmationPageController extends CartPageController
{
    /**
     * @static array
     * standard SS variable
     * it is important that we list all the options here
     */
    private static $allowed_actions = [
        'saveorder',
        'CreateAccountForm',
        'retrieveorder',
        'loadorder',
        'startneworder',
        'showorder',
        'copyorder',
        'sendemail',
        'CancelForm',
        'FeedbackForm',
        'PaymentForm',
        'CustomerOrderStepForm',
    ];

    /**
     * @var bool
     */
    private static $include_as_checkout_step = true;

    /**
     * This method exists just so that template
     * sets CurrentOrder variable.
     *
     * @return array
     */
    public function showorder(HTTPRequest $request)
    {
        // isset($project) ? $themeBaseFolder = $project : $themeBaseFolder = 'mysite';
        if (isset($_REQUEST['print'])) {
            Requirements::clear();
            Requirements::themedCSS('client/css/OrderReport'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            Requirements::themedCSS('client/css/Order_Invoice'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            Requirements::themedCSS('client/css/Order_Invoice_Print_Only', 'print'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            SetThemed::start();
            $html = $this->renderWith('Sunnysideup\\Ecommerce\\Invoice');
            SetThemed::end();

            return $html;
        }
        if (isset($_REQUEST['packingslip'])) {
            Requirements::clear();
            Requirements::themedCSS('client/css/OrderReport'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            Requirements::themedCSS('client/css/Order_PackingSlip'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            SetThemed::start();
            $html = $this->renderWith('Sunnysideup\Ecommerce\PackingSlip');
            SetThemed::end();

            return $html;
        }

        return [];
    }

    /**
     * This is an additional way to look at an order.
     * The order is already retrieved from the init function.
     *
     * @return array
     */
    public function retrieveorder(HTTPRequest $request)
    {
        return [];
    }

    /**
     * copies either the current order into the shopping cart.
     *
     * @todountested
     * @todowhat to do with old order
     *
     * @return array
     */
    public function copyorder(HTTPRequest $request)
    {
        self::set_message(_t('CartPage.ORDERLOADED', 'Order has been loaded.'));
        ShoppingCart::singleton()->copyOrder($this->currentOrder->ID);

        return $this->redirect(CheckoutPage::find_link());
    }

    /**
     * Returns a dataobject set of the checkout steps if
     * the OrderConfirmationPage is shown as part of the checkout process
     * We repeat these here so that you can show the user that (s)he has reached the last step.
     *
     * @param int $number - if set, it returns that one step
     */
    public function CheckoutSteps($number = 0)
    {
        $where = '';
        if ($number) {
            $where = "\"CheckoutPageStepDescription\".\"ID\" = {$number}";
        }

        if (EcommerceConfig::get(OrderConfirmationPageController::class, 'include_as_checkout_step')) {
            $dos = CheckoutPageStepDescription::get()->where($where)->sort('ID', 'ASC');
            if ($number) {
                if ($dos->exists()) {
                    return $dos->First();
                }
            }
            $arrayList = new ArrayList([]);
            foreach ($dos as $do) {
                $do->LinkingMode = 'link completed';
                $do->Completed = 1;
                $do->Link = '';
                $arrayList->push($do);
            }
            $do = $this->CurrentCheckoutStep(true);
            $do->LinkingMode = 'current';
            if ($do) {
                $arrayList->push($do);
            }

            return $arrayList;
        }
    }

    /**
     * returns the percentage of checkout steps done (0 - 100).
     *
     * @return int
     */
    public function PercentageDone()
    {
        return 100;
    }

    /**
     * @return null|string
     */
    public function PaymentHeader()
    {
        if ($this->getOrderCached()) {
            if ($this->OrderIsCancelled()) {
                return $this->OrderCancelledHeader;
            }
            if ($this->PaymentIsPending()) {
                return $this->PaymentPendingHeader;
            }
            if ($this->IsPaid()) {
                return $this->PaymentSuccessfulHeader;
            }

            return $this->PaymentNotSuccessfulHeader;
        }

        return null;
    }

    /**
     * @return null|string
     */
    public function PaymentMessage()
    {
        if ($this->getOrderCached()) {
            if ($this->OrderIsCancelled()) {
                return $this->OrderCancelledMessage;
            }
            if ($this->PaymentIsPending()) {
                return $this->PaymentPendingMessage;
            }
            if ($this->IsPaid()) {
                return $this->PaymentSuccessfulMessage;
            }

            return $this->PaymentNotSuccessfulMessage;
        }

        return null;
    }

    /**
     * @return null|string
     */
    public function PaymentMessageType()
    {
        if ($this->getOrderCached()) {
            if ($this->OrderIsCancelled()) {
                return 'bad';
            }
            if ($this->PaymentIsPending()) {
                return 'warning';
            }
            if ($this->IsPaid()) {
                return 'good';
            }

            return 'bad';
        }

        return null;
    }

    /**
     * @return null|bool
     */
    public function OrderIsCancelled()
    {
        $order = $this->getOrderCached();
        if ($order) {
            return $order->getIsCancelled();
        }

        return null;
    }

    /**
     * Is the Order paid?
     * This can be useful for choosing what header to show.
     *
     * @return null|bool
     */
    public function IsPaid()
    {
        $order = $this->getOrderCached();
        if ($order) {
            return $order->IsPaid();
        }

        return null;
    }

    /**
     * Are there any order Payments Pending
     * This can be useful for choosing what header to show.
     *
     * @return null|bool
     */
    public function PaymentIsPending()
    {
        $order = $this->getOrderCached();
        if ($order) {
            return $order->PaymentIsPending();
        }

        return null;
    }

    /**
     * Returns the form to cancel the current order,
     * checking to see if they can cancel their order
     * first of all.
     *
     * @return null|array|OrderFormCancel
     */
    public function CancelForm()
    {
        if ($this->getOrderCached()) {
            if ($this->currentOrder->canCancel()) {
                return OrderFormCancel::create($this, 'CancelForm', $this->currentOrder);
            }
        }
        //once cancelled, you will be redirected to main page - hence we need this...
        if ($this->orderID) {
            return [];
        }

        return null;
    }

    /**
     * Returns the form for providing feedback about current order,
     * checking to see if IsFeedbackEnabled is true
     * first of all.
     *
     * @return null|array|OrderFormFeedback
     */
    public function FeedbackForm()
    {
        if ($this->getOrderCached()) {
            if ($this->IsFeedbackEnabled) {
                return OrderFormFeedback::create($this, 'FeedbackForm', $this->currentOrder);
            }

            return [];
        }

        return null;
    }

    /**
     * show the payment form.
     *
     * @return null|array|OrderFormPayment
     */
    public function PaymentForm()
    {
        if ($this->currentOrder) {
            if ($this->currentOrder->canPay()) {
                Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomPayment.js');

                return OrderFormPayment::create($this, 'PaymentForm', $this->currentOrder);
            }

            return [];
        }

        return null;
    }

    /**
     * @return null|array|Form
     */
    public function CustomerOrderStepForm()
    {
        $order = $this->currentOrder;
        if ($order) {
            $status = $order->Status();
            if ($status) {
                $form = $status->CustomerOrderStepForm($this, 'CustomerOrderStepForm', $order);
                if ($form) {
                    Requirements::javascript('sunnysideup/ecommerce: client/javascript/CustomerOrderStepForm.js');

                    return $form;
                }
            }

            return [];
        }

        return null;
    }

    /**
     * sends an order email, which can be specified in the URL
     * and displays a sample email
     * typically this link is opened in a new window.
     *
     * @return string
     */
    public function sendemail(HTTPRequest $request)
    {
        if ($this->currentOrder) {
            $subject = '';
            $message = '';
            $emailClassName = OrderReceiptEmail::class;

            // different classname
            $otherId = str_replace('\\', '-', $request->param('OtherID'));
            if (class_exists($otherId)) {
                if (is_a(singleton($otherId), EcommerceConfigClassNames::getName(OrderEmail::class))) {
                    $emailClassName = $otherId;
                }
            }

            $statusIDSend = (int) $request->getVar('send');
            if ($statusIDSend) {
                $step = OrderStep::get()->byID($statusIDSend);
                $subject = $step->CalculatedEmailSubject($this->currentOrder);
                $message = $step->CalculatedCustomerMessage($this->currentOrder);
                if ($step) {
                    $emailClassName = $step->getEmailClassName();
                }
                $email = filter_var($statusIDSend, FILTER_SANITIZE_EMAIL);
                if (! $email) {
                    $email = true;
                }
                $this->currentOrder->sendEmail(
                    $emailClassName,
                    _t('Account.TEST_ONLY', '--- TEST ONLY ---') . ' ' . $subject,
                    $message,
                    $resend = true,
                    $adminOnlyOrToEmail = $email
                );
            }

            //test
            $statusIDTest = (int) $request->getVar('test');
            if ($statusIDTest) {
                $email = $this->currentOrder->getOrderEmail();
                if ($email) {
                    $step = OrderStep::get()->byID($statusIDTest);
                    $subject = $step->CalculatedEmailSubject($this->currentOrder);
                    $message = $step->CalculatedCustomerMessage($this->currentOrder);
                    $emailClassName = OrderInvoiceEmail::class;
                    $adminOnlyOrToEmail = false;
                    $resend = true;
                    if ($this->currentOrder->sendEmail(
                        $emailClassName,
                        $subject,
                        $message,
                        $resend,
                        $adminOnlyOrToEmail
                    )) {
                        $message = _t('OrderConfirmationPage.RECEIPTSENT', 'An email has been sent to: ') . $email . '.';
                    } else {
                        $message = _t('OrderConfirmationPage.RECEIPT_NOT_SENT', 'Email could NOT be sent to: ') . $email;
                    }
                } else {
                    $message = _t('OrderConfirmationPage.RECEIPTNOTSENTNOEMAIL', 'No customer details found.  EMAIL NOT SENT.');
                }
            }
            //display same data...
            Requirements::clear();

            return $this->currentOrder->renderOrderInEmailFormat(
                $emailClassName,
                $subject,
                $message
            );
        }

        return _t('OrderConfirmationPage.RECEIPTNOTSENTNOORDER', 'Order could not be found.');
    }

    /**
     * standard controller function.
     */
    protected function init()
    {
        //we retrieve the order in the parent page
        //the parent page also takes care of the security
        $sessionOrderID = $this->getRequest()->getSession()->get('CheckoutPageCurrentOrderID');
        if ($sessionOrderID) {
            $this->currentOrder = Order::get_order_cached((int) $sessionOrderID);
            if ($this->currentOrder) {
                $this->overrideCanView = true;
                //more than an hour has passed...
                $validUntil = (int) $this->getRequest()->getSession()->get('CheckoutPageCurrentRetrievalTime') - 0;
                if ($validUntil < time()) {
                    $this->clearRetrievalOrderID();
                    $this->overrideCanView = false;
                    $this->currentOrder = null;
                }
            }
        }
        parent::init();
        Requirements::themedCSS('client/css/Order');
        Requirements::themedCSS('client/css/Order_Print', 'print');
        Requirements::themedCSS('client/css/CheckoutPage');
        Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomPayment.js');
        Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomPrintAndMail.js');
    }

    /**
     * Can this page only show Submitted Orders (e.g. OrderConfirmationPage) ?
     */
    protected function onlyShowSubmittedOrders(): bool
    {
        return true;
    }

    /**
     * Can this page only show Unsubmitted Orders (e.g. CartPage) ?
     */
    protected function onlyShowUnsubmittedOrders(): bool
    {
        return false;
    }
}
