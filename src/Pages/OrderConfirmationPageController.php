<?php

namespace Sunnysideup\Ecommerce\Pages;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Api\SetThemed;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Email\OrderEmail;
use Sunnysideup\Ecommerce\Email\OrderReceiptEmail;
use Sunnysideup\Ecommerce\Forms\OrderFormCancel;
use Sunnysideup\Ecommerce\Forms\OrderFormFeedback;
use Sunnysideup\Ecommerce\Forms\OrderFormPayment;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\CheckoutPageStepDescription;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * Class \Sunnysideup\Ecommerce\Pages\OrderConfirmationPageController
 *
 * @property \Sunnysideup\Ecommerce\Pages\OrderConfirmationPage $dataRecord
 * @method \Sunnysideup\Ecommerce\Pages\OrderConfirmationPage data()
 * @mixin \Sunnysideup\Ecommerce\Pages\OrderConfirmationPage
 */
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
     * Customers who own an order can have the email for the current step of their order re-sent
     * to their own account email. This is the minimum number of seconds between two such sends (per order).
     *
     * @var int
     */
    private static $owner_email_resend_cooldown_in_seconds = 300;

    /**
     * This method exists just so that template
     * sets CurrentOrder variable.
     *
     * @return array|string
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
            $dos = CheckoutPageStepDescription::get()
                ->where($where)
                ->sort(['ID' => 'ASC']);
            if ($number && $dos->exists()) {
                return $dos->First();
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
        if ($this->Order()) {
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
        if ($this->Order()) {
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
        if ($this->Order()) {
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
        $order = $this->Order();
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
        $order = $this->Order();
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
        $order = $this->Order();
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
        if ($this->Order() && $this->currentOrder->canCancel()) {
            return OrderFormCancel::create($this, 'CancelForm', $this->currentOrder);
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
        if ($this->Order()) {
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

                $form = OrderFormPayment::create($this, 'PaymentForm', $this->currentOrder);
                if ($form instanceof OrderFormPayment) {
                    return $form;
                }
                return [];
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
            $email = '';
            $emailClassName = OrderReceiptEmail::class;

            // different classname
            $potentialClassName = str_replace('-', '\\', $request->param('OtherID'));
            if (class_exists($potentialClassName) && is_a(singleton($potentialClassName), EcommerceConfigClassNames::getName(OrderEmail::class))) {
                $emailClassName = $potentialClassName;
            }
            // POST vars are used by the "send" form, GET vars by the (CMS) links that open the preview.
            $sendStepID = (int) $request->requestVar('send');
            $testStepID = (int) $request->requestVar('test');
            $stepID = 0;
            if ($sendStepID !== 0) {
                $stepID = $sendStepID;
                $isTest = false;
            } elseif ($testStepID !== 0) {
                $stepID = $testStepID;
                $isTest = true;
            }
            if ($stepID !== 0) {
                // Shop admins can send any step email (and test emails, and to any address).
                // The owner of the order can only re-send the email for the current step of their order,
                // and only to their own account email (see below).
                $isShopAdmin = Permission::check('ADMIN') || EcommerceRole::current_member_is_shop_admin();
                $ownerEmail = $isShopAdmin ? '' : $this->orderOwnerEmail();
                if (! $isShopAdmin) {
                    if ($ownerEmail === '' || $isTest || $stepID !== (int) $this->currentOrder->StatusID) {
                        return $this->httpError(403, 'You do not have permission to send order emails.');
                    }
                }
                $to = '';
                if (Permission::check('ADMIN')) {
                    $to = (string) $request->requestVar('to');
                }
                $step = OrderStep::get_by_id($stepID);
                if ($step) {
                    $subject = $step->CalculatedEmailSubject($this->currentOrder);
                    $message = $step->CalculatedCustomerMessage($this->currentOrder);
                    $emailClassName = $step->getEmailClassName();
                    $adminOnlyOrToEmail = $isTest;
                    if ($to !== '' && $to !== '0') {
                        $to = filter_var($to, FILTER_SANITIZE_EMAIL);
                        if ($to) {
                            $adminOnlyOrToEmail = $to;
                            $email = $adminOnlyOrToEmail;
                        }
                    }
                    if (! $to) {
                        $email = $adminOnlyOrToEmail ? 'site administrator' : $this->currentOrder->getOrderEmail();
                        // goes to Email for order.
                    }
                    if ($ownerEmail !== '') {
                        // owner of the order: always send to their account email, nowhere else.
                        $adminOnlyOrToEmail = $ownerEmail;
                        $email = $ownerEmail;
                        $to = '';
                    }
                    if ($request->isPOST()) {
                        // only a POST with a valid security token actually sends the email.
                        if (! SecurityToken::inst()->checkRequest($request)) {
                            return $this->httpError(400, 'Invalid security token');
                        }
                        $session = $request->getSession();
                        $sessionKey = 'OrderEmailResentAt.' . $this->currentOrder->ID;
                        $cooldown = (int) $this->config()->get('owner_email_resend_cooldown_in_seconds');
                        if ($ownerEmail !== '' && time() - (int) $session->get($sessionKey) < $cooldown) {
                            $outcome = false;
                            $tooSoon = true;
                        } else {
                            $tooSoon = false;
                            if ($ownerEmail !== '') {
                                $session->set($sessionKey, time());
                            }
                            $outcome = $this->currentOrder->sendEmail(
                                $emailClassName,
                                $subject,
                                $message,
                                $resend = true,
                                $adminOnlyOrToEmail
                            );
                        }
                        if ($tooSoon) {
                            $message = _t('OrderConfirmationPage.RECEIPT_TOO_SOON', 'This email has been sent recently. Please wait a few minutes before sending it again.');
                        } elseif ($outcome) {
                            $message = _t('OrderConfirmationPage.RECEIPTSENT', 'This email has been sent to: <strong>') . Convert::raw2xml($email) . '</strong><br >with subject: <strong>' . Convert::raw2xml($subject) . '</strong><hr />';
                        } else {
                            $message = _t('OrderConfirmationPage.RECEIPT_NOT_SENT', 'Email could NOT be sent to: ') . Convert::raw2xml($email);
                        }
                    } else {
                        // GET: never send, only show a preview and a form to send it.
                        $message = $this->sendEmailConfirmationForm($request, $isTest ? 'test' : 'send', $stepID, (string) $to, (string) $email, (string) $subject);
                    }
                } else {
                    $message = _t('OrderConfirmationPage.RECEIPTNOTSENTNOEMAIL', 'No customer details found.  EMAIL NOT SENT.');
                }
            }
            //display same data...
            Requirements::clear();
            echo $message;

            return $this->currentOrder->renderOrderInEmailFormat(
                $emailClassName,
                $subject,
                ''
            );
        }

        return _t('OrderConfirmationPage.RECEIPTNOTSENTNOORDER', 'Order could not be found.');
    }

    /**
     * If the current user is logged in and owns the (submitted) current order,
     * then this returns their account email (if valid), otherwise an empty string.
     */
    protected function orderOwnerEmail(): string
    {
        $member = Security::getCurrentUser();
        $order = $this->currentOrder;
        if ($member && $member->exists() && $order && $order->exists() && $order->IsSubmitted() && (int) $order->MemberID === (int) $member->ID) {
            $email = (string) $member->Email;
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        return '';
    }

    /**
     * returns the HTML for a small form that sends the email (POST + security token).
     */
    protected function sendEmailConfirmationForm(HTTPRequest $request, string $stepVarName, int $stepID, string $to, string $email, string $subject): string
    {
        $action = Controller::join_links(Director::baseURL(), $request->getURL());
        $token = SecurityToken::inst();
        $html = '<form method="post" action="' . Convert::raw2att($action) . '" style="padding: 1rem; background: #ffc; border-bottom: 1px solid #ccc;">'
            . '<input type="hidden" name="' . Convert::raw2att($token->getName()) . '" value="' . Convert::raw2att($token->getValue()) . '" />'
            . '<input type="hidden" name="' . Convert::raw2att($stepVarName) . '" value="' . $stepID . '" />';
        if ($to !== '') {
            $html .= '<input type="hidden" name="to" value="' . Convert::raw2att($to) . '" />';
        }
        $html .= '<p>' . _t('OrderConfirmationPage.PREVIEWONLY', 'Preview only - nothing has been sent yet.')
            . '<br />' . _t('OrderConfirmationPage.SENDTO', 'Send to:') . ' <strong>' . Convert::raw2xml($email) . '</strong>'
            . '<br />' . _t('OrderConfirmationPage.WITHSUBJECT', 'Subject:') . ' <strong>' . Convert::raw2xml($subject) . '</strong></p>'
            . '<button type="submit">' . _t('OrderConfirmationPage.SENDEMAILNOW', 'Send this email now') . '</button>'
            . '</form>';

        return $html;
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
            if ($this->currentOrder instanceof \Sunnysideup\Ecommerce\Model\Order) {
                $this->overrideCanView = true;
                // this override is only valid for this order (see CartPageController::init)
                $this->overrideCanViewOrderID = (int) $this->currentOrder->ID;
                //more than an hour has passed...
                $validUntil = (int) $this->getRequest()->getSession()->get('CheckoutPageCurrentRetrievalTime');
                if ($validUntil < time()) {
                    $this->clearRetrievalOrderID();
                    $this->overrideCanView = false;
                    $this->overrideCanViewOrderID = 0;
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
