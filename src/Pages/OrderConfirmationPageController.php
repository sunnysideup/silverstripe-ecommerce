<?php

namespace Sunnysideup\Ecommerce\Pages;

use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
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
    ];

    /**
     * standard controller function.
     **/
    public function init()
    {
        //we retrieve the order in the parent page
        //the parent page also takes care of the security

        /**
         * ### @@@@ START REPLACEMENT @@@@ ###
         * WHY: automated upgrade
         * OLD: Session:: (case sensitive)
         * NEW: SilverStripe\Control\Controller::curr()->getRequest()->getSession()-> (COMPLEX)
         * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly.
         * ### @@@@ STOP REPLACEMENT @@@@ ###
         */
        if ($sessionOrderID = SilverStripe\Control\Controller::curr()->getRequest()->getSession()->get('CheckoutPageCurrentOrderID')) {
            $this->currentOrder = Order::get()->byID($sessionOrderID);
            if ($this->currentOrder) {
                $this->overrideCanView = true;
                //more than an hour has passed...

                /**
                 * ### @@@@ START REPLACEMENT @@@@ ###
                 * WHY: automated upgrade
                 * OLD: Session:: (case sensitive)
                 * NEW: SilverStripe\Control\Controller::curr()->getRequest()->getSession()-> (COMPLEX)
                 * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly.
                 * ### @@@@ STOP REPLACEMENT @@@@ ###
                 */
                $validUntil = intval(SilverStripe\Control\Controller::curr()->getRequest()->getSession()->get('CheckoutPageCurrentRetrievalTime')) - 0;
                if ($validUntil < time()) {
                    $this->clearRetrievalOrderID();
                    $this->overrideCanView = false;
                    $this->currentOrder = null;
                }
            }
        }
        parent::init();
        Requirements::themedCSS('sunnysideup/ecommerce: Order', 'ecommerce');
        Requirements::themedCSS('sunnysideup/ecommerce: Order_Print', 'ecommerce', 'print');
        Requirements::themedCSS('sunnysideup/ecommerce: CheckoutPage', 'ecommerce');
        Requirements::javascript('sunnysideup/ecommerce: ecommerce/javascript/EcomPayment.js');
        Requirements::javascript('sunnysideup/ecommerce: ecommerce/javascript/EcomPrintAndMail.js');
    }

    /**
     * This method exists just so that template
     * sets CurrentOrder variable.
     *
     * @param HTTPRequest $request
     *
     * @return array
     **/
    public function showorder(HTTPRequest $request)
    {
        isset($project) ? $themeBaseFolder = $project : $themeBaseFolder = 'mysite';
        if (isset($_REQUEST['print'])) {
            Requirements::clear();
            Requirements::themedCSS('typography', $themeBaseFolder); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            Requirements::themedCSS('OrderReport', 'ecommerce'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            Requirements::themedCSS('Order_Invoice', 'ecommerce'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            Requirements::themedCSS('Order_Invoice_Print_Only', 'ecommerce', 'print'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            Config::nest();
            Config::inst()->update(SSViewer::class, 'theme_enabled', true);
            $html = $this->renderWith('Invoice');
            Config::unnest();

            return $html;
        } elseif (isset($_REQUEST['packingslip'])) {
            Requirements::clear();
            Requirements::themedCSS('typography', $themeBaseFolder); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            Requirements::themedCSS('OrderReport', 'ecommerce'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            Requirements::themedCSS('Order_PackingSlip', 'ecommerce'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            Config::nest();
            Config::inst()->update(SSViewer::class, 'theme_enabled', true);
            $html = $this->renderWith('PackingSlip');
            Config::unnest();

            return $html;
        }

        return [];
    }

    /**
     * This is an additional way to look at an order.
     * The order is already retrieved from the init function.
     *
     * @return array
     **/
    public function retrieveorder(HTTPRequest $request)
    {
        return [];
    }

    /**
     * copies either the current order into the shopping cart.
     *
     * TO DO: untested
     * TO DO: what to do with old order
     *
     * @param SS_HTTPRequest $request
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
     * @param int $number - if set, it returns that one step.
     */
    public function CheckoutSteps($number = 0)
    {
        $where = '';
        if ($number) {
            $where = "\"CheckoutPageStepDescription\".\"ID\" = ${number}";
        }
        if (EcommerceConfig::get('OrderConfirmationPage_Controller', 'include_as_checkout_step')) {
            if ($this->currentOrder->IsInSession()) {
                $dos = CheckoutPageStepDescription::get()->where($where)->sort('ID', 'ASC');
                if ($number) {
                    if ($dos && $dos->count()) {
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
                if ($do) {
                    $arrayList->push($do);
                }

                return $arrayList;
            }
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
     * @return string
     */
    public function PaymentHeader()
    {
        if ($this->Order()) {
            if ($this->OrderIsCancelled()) {
                return $this->OrderCancelledHeader;
            } elseif ($this->PaymentIsPending()) {
                return $this->PaymentPendingHeader;
            } elseif ($this->IsPaid()) {
                return $this->PaymentSuccessfulHeader;
            }
            return $this->PaymentNotSuccessfulHeader;
        }
    }

    /**
     * @return string | null
     */
    public function PaymentMessage()
    {
        if ($this->Order()) {
            if ($this->OrderIsCancelled()) {
                return $this->OrderCancelledMessage;
            } elseif ($this->PaymentIsPending()) {
                return $this->PaymentPendingMessage;
            } elseif ($this->IsPaid()) {
                return $this->PaymentSuccessfulMessage;
            }
            return $this->PaymentNotSuccessfulMessage;
        }
    }

    /**
     * @return string | null
     */
    public function PaymentMessageType()
    {
        if ($this->Order()) {
            if ($this->OrderIsCancelled()) {
                return 'bad';
            } elseif ($this->PaymentIsPending()) {
                return 'warning';
            } elseif ($this->IsPaid()) {
                return 'good';
            }
            return 'bad';
        }
    }

    /**
     * @return bool
     */
    public function OrderIsCancelled()
    {
        if ($order = $this->Order()) {
            return $order->getIsCancelled();
        }
    }

    /**
     * Is the Order paid?
     * This can be useful for choosing what header to show.
     *
     * @return bool
     */
    public function IsPaid()
    {
        if ($order = $this->Order()) {
            return $order->IsPaid();
        }
    }

    /**
     * Are there any order Payments Pending
     * This can be useful for choosing what header to show.
     *
     * @return bool
     */
    public function PaymentIsPending()
    {
        if ($order = $this->Order()) {
            return $order->PaymentIsPending();
        }
    }

    /**
     * Returns the form to cancel the current order,
     * checking to see if they can cancel their order
     * first of all.
     *
     * @return OrderFormCancel
     */
    public function CancelForm()
    {
        if ($this->Order()) {
            if ($this->currentOrder->canCancel()) {
                return OrderFormCancel::create($this, 'CancelForm', $this->currentOrder);
            }
        }
        //once cancelled, you will be redirected to main page - hence we need this...
        if ($this->orderID) {
            return [];
        }
    }

    /**
     * Returns the form for providing feedback about current order,
     * checking to see if IsFeedbackEnabled is true
     * first of all.
     *
     * @return OrderFormFeedback
     */
    public function FeedbackForm()
    {
        if ($this->Order()) {
            if ($this->IsFeedbackEnabled) {
                return OrderFormFeedback::create($this, 'FeedbackForm', $this->currentOrder);
            }
        }
    }

    /**
     * show the payment form.
     *
     * @return Form (OrderFormPayment) or Null
     **/
    public function PaymentForm()
    {
        if ($this->currentOrder) {
            if ($this->currentOrder->canPay()) {
                Requirements::javascript('ecommerce/javascript/EcomPayment.js');

                return OrderFormPayment::create($this, 'PaymentForm', $this->currentOrder);
            }
        }

        return [];
    }

    /**
     * sends an order email, which can be specified in the URL
     * and displays a sample email
     * typically this link is opened in a new window.
     *
     * @param SS_HTTPRequest $request
     *
     * @return HTML
     **/
    public function sendemail(HTTPRequest $request)
    {
        if ($this->currentOrder) {
            $subject = '';
            $message = '';
            $emailClassName = OrderReceiptEmail::class;
            if (class_exists($request->param('OtherID'))) {
                if (is_a(singleton($request->param('OtherID')), EcommerceConfigClassNames::getName(OrderEmail::class))) {
                    $emailClassName = $request->param('OtherID');
                }
            }
            if ($statusID = intval($request->getVar('test'))) {
                $step = OrderStep::get()->byID($statusID);
                $subject = $step->CalculatedEmailSubject($this->currentOrder);
                $message = $step->CalculatedCustomerMessage($this->currentOrder);
                if ($step) {
                    $emailClassName = $step->getEmailClassName();
                }
                if ($request->getVar('send')) {
                    $email = filter_var($request->getVar('send'), FILTER_SANITIZE_EMAIL);
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
            } elseif ($request->getVar('send')) {
                if ($email = $this->currentOrder->getOrderEmail()) {
                    $step = OrderStep::get()->byID($statusID);
                    $subject = $step->CalculatedEmailSubject($this->currentOrder);
                    $message = $step->CalculatedCustomerMessage($this->currentOrder);
                    $emailClassName = OrderInvoiceEmail::class;
                    if ($this->currentOrder->sendEmail(
                        $emailClassName,
                        $subject,
                        $message,
                        $resend = true,
                        $adminOnlyOrToEmail = false
                    )
                    ) {
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
     * Can this page only show Submitted Orders (e.g. OrderConfirmationPage) ?
     *
     * @return bool
     */
    protected function onlyShowSubmittedOrders()
    {
        return true;
    }

    /**
     * Can this page only show Unsubmitted Orders (e.g. CartPage) ?
     *
     * @return bool
     */
    protected function onlyShowUnsubmittedOrders()
    {
        return false;
    }
}
