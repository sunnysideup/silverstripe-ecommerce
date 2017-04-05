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
class OrderConfirmationPage extends CartPage
{
    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $icon = 'ecommerce/images/icons/OrderConfirmationPage';

    /**
     * Standard SS variable.
     *
     * @var array
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
        'EnableGoogleAnalytics' => 'Boolean',
    );

    /**
     * Standard SS variable.
     *
     * @var array
     */
    private static $defaults = array(
        'ShowInMenus' => false,
        'ShowInSearch' => false,
        'StartNewOrderLinkLabel' => 'start new order',
        'CopyOrderLinkLabel' => 'copy order items into a new order',
        'OrderCancelledHeader' => 'Order has been cancelled',
        'PaymentSuccessfulHeader' => 'Payment Successful',
        'PaymentNotSuccessfulHeader' => 'Payment not Completed',
        'PaymentPendingHeader' => 'Payment Pending',
        'OrderCancelledMessage' => '<p>This order is no longer valid.</p>',
        'PaymentSuccessfulMessage' => '<p>Your order will be processed.</p>',
        'PaymentNotSuccessfulMessage' => '<p>Your order will not be processed until your payment has been completed.</p>',
        'PaymentPendingMessage' => '<p>Please complete your payment before the order can be processed.</p>',
    );

    private static $casting = array(
        "PaymentMessage" => "HTMLText"
    );

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $singular_name = 'Order Confirmation Page';
    public function i18n_singular_name()
    {
        return _t('OrderConfirmationpage.SINGULARNAME', 'Order Confirmation Page');
    }

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $plural_name = 'Order Confirmation Pages';
    public function i18n_plural_name()
    {
        return _t('OrderConfirmationpage.PLURALNAME', 'Order Confirmation Pages');
    }

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A page where the customer can view her or his submitted order. Every e-commerce site needs an Order Confirmation Page.';

    /**
     * Standard SS function, we only allow for one OrderConfirmation Page to exist
     * but we do allow for extensions to exist at the same time.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canCreate($member = null)
    {
        return OrderConfirmationPage::get()->filter(array('ClassName' => 'OrderConfirmationPage'))->Count() ? false : $this->canEdit($member);
    }

    /**
     * Shop Admins can edit.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canEdit($member = null)
    {
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canPublish($member = null)
    {
        return $this->canEdit($member);
    }

    public function customFieldLabels()
    {
        $newLabels = array(
            'StartNewOrderLinkLabel' => _t('OrderConfirmationPage.STARTNEWORDERLINKLABEL', 'Label for starting new order - e.g. click here to start new order.'),
            'CopyOrderLinkLabel' => _t('OrderConfirmationPage.COPYORDERLINKLABEL', 'Label for copying order items into a new one  - e.g. click here start a new order with the current order items.'),
            'OrderCancelledHeader' => _t('OrderConfirmationPage.ORDERCANCELLEDHEADER', 'Header showing when order has been cancelled.'),
            'PaymentSuccessfulHeader' => _t('OrderConfirmationPage.PAYMENTSUCCESSFULHEADER', 'Header showing when order has been paid in full.'),
            'PaymentNotSuccessfulHeader' => _t('OrderConfirmationPage.PAYMENTNOTSUCCESSFULHEADER', 'Header showing when the order has not been paid in full.'),
            'PaymentPendingHeader' => _t('OrderConfirmationPage.PAYMENTPENDINGHEADER', 'Header showing when the order has not been paid in full - but the payment is pending.'),
            'OrderCancelledMessage' => _t('OrderConfirmationPage.ORDERCANCELLEDMESSAGE', 'Message showing when order has been paid cancelled.'),
            'PaymentSuccessfulMessage' => _t('OrderConfirmationPage.PAYMENTSUCCESSFULMESSAGE', 'Message showing when order has been paid in full.'),
            'PaymentNotSuccessfulMessage' => _t('OrderConfirmationPage.PAYMENTNOTSUCCESSFULMESSAGE', 'Message showing when the order has not been paid in full.'),
            'PaymentPendingMessage' => _t('OrderConfirmationPage.PAYMENTPENDINGMESSAGE', 'Message showing when the order has not been paid in full - but the payment is pending.'),
            'EnableGoogleAnalytics' => _t('OrderConfirmationPage.ENABLEGOOGLEANALYTICS', 'Enable E-commerce Google Analytics.  Make sure it is turned on in your Google Analytics account.'),
        );

        return $newLabels;
    }

    /**
     * standard SS method for decorators.
     *
     * @param bool - $includerelations: array of fields to start with
     *
     * @return array
     */
    public function fieldLabels($includerelations = true)
    {
        $defaultLabels = parent::fieldLabels();
        $newLabels = $this->customFieldLabels();
        $labels = array_merge($defaultLabels, $newLabels);
        $extendedArray = $this->extend('updateFieldLabels', $labels);
        if ($extendedArray !== null && is_array($extendedArray) && count($extendedArray)) {
            foreach ($extendedArray as $extendedResult) {
                $labels = array_merge($labels, $extendedResult);
            }
        }

        return $labels;
    }

    /**
     *@return FieldList
     **/
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeFieldFromTab('Root.Messages.Messages.Actions', 'ProceedToCheckoutLabel');
        $fields->removeFieldFromTab('Root.Messages.Messages.Actions', 'ContinueShoppingLabel');
        $fields->removeFieldFromTab('Root.Messages.Messages.Actions', 'ContinuePageID');
        $fields->removeFieldFromTab('Root.Messages.Messages.Actions', 'SaveOrderLinkLabel');
        $fields->removeFieldFromTab('Root.Messages.Messages.Errors', 'NoItemsInOrderMessage');
        $fieldLabels = $this->fieldLabels();
        $fields->addFieldToTab('Root.Messages.Messages.Actions', new TextField('StartNewOrderLinkLabel', $fieldLabels['StartNewOrderLinkLabel']));
        $fields->addFieldToTab('Root.Messages.Messages.Actions', new TextField('CopyOrderLinkLabel', $fieldLabels['CopyOrderLinkLabel']));
        $fields->addFieldsToTab('Root.Messages.Messages.Payment', array(
            HeaderField::create('Successful'),
            TextField::create('PaymentSuccessfulHeader', $fieldLabels['PaymentSuccessfulHeader']),
            HTMLEditorField::create('PaymentSuccessfulMessage', $fieldLabels['PaymentSuccessfulMessage'])->setRows(3),
            HeaderField::create('Unsuccessful'),
            TextField::create('PaymentNotSuccessfulHeader', $fieldLabels['PaymentNotSuccessfulHeader']),
            HTMLEditorField::create('PaymentNotSuccessfulMessage', $fieldLabels['PaymentNotSuccessfulMessage'])->setRows(3),
            HeaderField::create('Pending'),
            TextField::create('PaymentPendingHeader', $fieldLabels['PaymentPendingHeader']),
            HTMLEditorField::create('PaymentPendingMessage', $fieldLabels['PaymentPendingMessage'])->setRows(3),
            HeaderField::create('Cancelled'),
            TextField::create('OrderCancelledHeader', $fieldLabels['OrderCancelledHeader']),
            HTMLEditorField::create('OrderCancelledMessage', $fieldLabels['OrderCancelledMessage'])->setRows(3),
        ));
        $fields->addFieldToTab('Root.Analytics', new CheckboxField('EnableGoogleAnalytics', $fieldLabels['EnableGoogleAnalytics']));

        return $fields;
    }

    /**
     * Returns the link or the Link to the OrderConfirmationPage page on this site.
     * @param string $action [optional]
     * @return string (URLSegment)
     */
    public static function find_link($action = null)
    {
        if ($page = OrderConfirmationPage::get()->filter(array('ClassName' => 'OrderConfirmationPage'))->First()) {
            return $page->Link($action);
        } elseif ($page = OrderConfirmationPage::get()->First()) {
            return $page->Link($action);
        }

        return CartPage::find_link();
    }

    /**
     * Return a link to view the order on this page.
     *
     * @param int|string $orderID ID of the order
     *
     * @return string (URLSegment)
     */
    public static function get_order_link($orderID)
    {
        return OrderConfirmationPage::find_link().'showorder/'.$orderID.'/';
    }

    /**
     * Return a link to view the order on this page.
     *
     * @param int|string $orderID                ID of the order
     * @param string     $type                   - the type of email you want to send.
     * @param bool       $actuallySendEmail      - do we actually send the email
     * @param int        $alternativeOrderStepID - OrderStep to use
     *
     * @return string (URLSegment)
     */
    public static function get_email_link($orderID, $emailClassName = 'Order_StatusEmail', $actuallySendEmail = false, $alternativeOrderStepID = 0)
    {
        $link = OrderConfirmationPage::find_link().'sendemail/'.$orderID.'/'.$emailClassName;
        $getParams = array();
        if ($actuallySendEmail) {
            $getParams['send'] = 1;
        }
        if ($alternativeOrderStepID) {
            $getParams['test'] = $alternativeOrderStepID;
        }
        $getParams = http_build_query($getParams);
        $link .= '?'.$getParams;

        return $link;
    }

    /**
     * Return a link to view the order on this page.
     *
     * @param int|string $orderID ID of the order
     *
     * @return string (URLSegment)
     */
    public function getOrderLink($orderID)
    {
        return OrderConfirmationPage::get_order_link($orderID);
    }

    /**
     * returns the Checkout_StepDescription assocatiated with the final step: the order confirmation.
     *
     * @param bool $isCurrentStep
     *
     * @return Checkout_StepDescription
     */
    public function CurrentCheckoutStep($isCurrentStep = false)
    {
        $do = new CheckoutPage_StepDescription();
        $do->Link = $this->Link;
        $do->Heading = $this->MenuTitle;
        $do->Code = $this->URLSegment;
        $do->LinkingMode = 'notCompleted';
        if ($isCurrentStep) {
            $do->LinkingMode .= ' current';
        }
        $do->Completed = 0;
        $do->ID = 99;

        return $do;
    }

    /**
     * standard SS method for use in templates
     * we are overriding the code from the Cart Page here.
     *
     * @return string
     */
    public function LinkingMode()
    {
        return parent::LinkingMode();
    }

    /**
     * standard SS method for use in templates
     * we are overriding the code from the Cart Page here.
     *
     * @return string
     */
    public function LinkOrSection()
    {
        return parent::LinkOrSection();
    }

    /**
     * standard SS method for use in templates
     * we are overriding the code from the Cart Page here.
     *
     * @return string
     */
    public function LinkOrCurrent()
    {
        return parent::LinkOrCurrent();
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $checkoutPage = CheckoutPage::get()->first();
        if ($checkoutPage) {
            $orderConfirmationPage = OrderConfirmationPage::get()->first();
            if (!$orderConfirmationPage) {
                $orderConfirmationPage = OrderConfirmationPage::create();
                $orderConfirmationPage->Title = 'Order Confirmation';
                $orderConfirmationPage->MenuTitle = 'Order Confirmation';
                $orderConfirmationPage->URLSegment = 'order-confirmation';
                $orderConfirmationPage->writeToStage('Stage');
                $orderConfirmationPage->publish('Stage', 'Live');
            }
        }
    }
}

class OrderConfirmationPage_Controller extends CartPage_Controller
{
    /**
     * @static array
     * standard SS variable
     * it is important that we list all the options here
     */
    private static $allowed_actions = array(
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
     * standard controller function.
     **/
    public function init()
    {
        //we retrieve the order in the parent page
        //the parent page also takes care of the security
        if ($sessionOrderID = Session::get('CheckoutPageCurrentOrderID')) {
            $this->currentOrder = Order::get()->byID($sessionOrderID);
            if ($this->currentOrder) {
                $this->overrideCanView = true;
                //more than an hour has passed...
                $validUntil = intval(Session::get('CheckoutPageCurrentRetrievalTime')) - 0;
                if ($validUntil < time()) {
                    $this->clearRetrievalOrderID();
                    $this->overrideCanView = false;
                    $this->currentOrder = null;
                }
            }
        }
        parent::init();
        Requirements::themedCSS('Order', 'ecommerce');
        Requirements::themedCSS('Order_Print', 'ecommerce', 'print');
        Requirements::themedCSS('CheckoutPage', 'ecommerce');
        Requirements::javascript('ecommerce/javascript/EcomPayment.js');
        Requirements::javascript('ecommerce/javascript/EcomPrintAndMail.js');
        $this->includeGoogleAnalyticsCode();
    }

    /**
     * This method exists just so that template
     * sets CurrentOrder variable.
     *
     * @param HTTPRequest
     *
     * @return array
     **/
    public function showorder(SS_HTTPRequest $request)
    {
        isset($project) ? $themeBaseFolder = $project : $themeBaseFolder = 'mysite';
        if (isset($_REQUEST['print'])) {
            Requirements::clear();
            Requirements::themedCSS('typography', $themeBaseFolder); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            Requirements::themedCSS('OrderReport', 'ecommerce'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            Requirements::themedCSS('Order_Invoice', 'ecommerce'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            Requirements::themedCSS('Order_Invoice_Print_Only', 'ecommerce', 'print'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            Config::nest();
            Config::inst()->update('SSViewer', 'theme_enabled', true);
            $html = $this->renderWith('Invoice');
            Config::unnest();

            return $html;
        } elseif (isset($_REQUEST['packingslip'])) {
            Requirements::clear();
            Requirements::themedCSS('typography', $themeBaseFolder); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            Requirements::themedCSS('OrderReport', 'ecommerce'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            Requirements::themedCSS('Order_PackingSlip', 'ecommerce'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
            Config::nest();
            Config::inst()->update('SSViewer', 'theme_enabled', true);
            $html = $this->renderWith('PackingSlip');
            Config::unnest();

            return $html;
        }

        return array();
    }

    /**
     * This is an additional way to look at an order.
     * The order is already retrieved from the init function.
     *
     * @return array
     **/
    public function retrieveorder(SS_HTTPRequest $request)
    {
        return array();
    }

    /**
     * copies either the current order into the shopping cart.
     *
     * TO DO: untested
     * TO DO: what to do with old order
     *
     * @param SS_HTTPRequest
     *
     * @return array
     */
    public function copyorder(SS_HTTPRequest $request)
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
            $where = "\"CheckoutPage_StepDescription\".\"ID\" = $number";
        }
        if (EcommerceConfig::get('OrderConfirmationPage_Controller', 'include_as_checkout_step')) {
            if ($this->currentOrder->IsInSession()) {
                $dos = CheckoutPage_StepDescription::get()->where($where)->sort('ID', 'ASC');
                if ($number) {
                    if ($dos && $dos->count()) {
                        return $dos->First();
                    }
                }
                $arrayList = new ArrayList(array());
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
        if ($order = $this->Order()) {
            if ($this->OrderIsCancelled()) {
                return $this->OrderCancelledHeader;
            } elseif ($this->PaymentIsPending()) {
                return $this->PaymentPendingHeader;
            } elseif ($this->IsPaid()) {
                return $this->PaymentSuccessfulHeader;
            } else {
                return $this->PaymentNotSuccessfulHeader;
            }
        }
    }

    /**
     * @return string | null
     */
    public function PaymentMessage()
    {
        if ($order = $this->Order()) {
            if ($this->OrderIsCancelled()) {
                return $this->OrderCancelledMessage;
            } elseif ($this->PaymentIsPending()) {
                return $this->PaymentPendingMessage;
            } elseif ($this->IsPaid()) {
                return $this->PaymentSuccessfulMessage;
            } else {
                return $this->PaymentNotSuccessfulMessage;
            }
        }
    }

    /**
     * @return string | null
     */
    public function PaymentMessageType()
    {
        if ($order = $this->Order()) {
            if ($this->OrderIsCancelled()) {
                return "bad";
            } elseif ($this->PaymentIsPending()) {
                return "warning";
            } elseif ($this->IsPaid()) {
                return "good";
            } else {
                return "bad";
            }
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
     * @return OrderForm_Cancel
     */
    public function CancelForm()
    {
        if ($this->Order()) {
            if ($this->currentOrder->canCancel()) {
                return OrderForm_Cancel::create($this, 'CancelForm', $this->currentOrder);
            }
        }
        //once cancelled, you will be redirected to main page - hence we need this...
        if ($this->orderID) {
            return array();
        }
    }

    /**
     * show the payment form.
     *
     * @return Form (OrderForm_Payment) or Null
     **/
    public function PaymentForm()
    {
        if ($this->currentOrder) {
            if ($this->currentOrder->canPay()) {
                Requirements::javascript('ecommerce/javascript/EcomPayment.js');

                return OrderForm_Payment::create($this, 'PaymentForm', $this->currentOrder);
            }
        }

        return array();
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

    /**
     * sends an order email, which can be specified in the URL
     * and displays a sample email
     * typically this link is opened in a new window.
     *
     * @param SS_HTTPRequest $request
     *
     * @return HTML
     **/
    public function sendemail(SS_HTTPRequest $request)
    {
        if ($this->currentOrder) {
            $subject = '';
            $message = '';
            $emailClassName = 'Order_ReceiptEmail';
            if (class_exists($request->param('OtherID'))) {
                if (is_a(singleton($request->param('OtherID')), Object::getCustomClass('Order_Email'))) {
                    $emailClassName = $request->param('OtherID');
                }
            }
            if ($statusID = intval($request->getVar('test'))) {
                $step = OrderStep::get()->byID($statusID);
                $subject = $step->EmailSubject;
                $message = $step->CustomerMessage;
                if ($step) {
                    $emailClassName = $step->getEmailClassName();
                }
                if ($request->getVar('send')) {
                    $email = filter_var($request->getVar('send'), FILTER_SANITIZE_EMAIL);
                    if(! $email) {
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
            }
            elseif ($request->getVar('send')) {
                if ($email = $this->currentOrder->getOrderEmail()) {
                    $step = OrderStep::get()->byID($this->currentOrder->StatusID);
                    $ecomConfig = $this->EcomConfig();
                    $subject = $ecomConfig->InvoiceTitle ? $ecomConfig->InvoiceTitle : _t('OrderConfirmationPage.INVOICE', 'Invoice');
                    $message = $ecomConfig->InvoiceMessage ? $ecomConfig->InvoiceMessage : _t('OrderConfirmationPage.MESSAGE', '<p>Thank you for your order.</p>');
                    $emailClassName = 'Order_InvoiceEmail';
                    if (
                        $this->currentOrder->sendEmail(
                            $emailClassName,
                            $subject,
                            $message,
                            $resend = true,
                            $adminOnlyOrToEmail = false
                        )
                    ) {
                        $message = _t('OrderConfirmationPage.RECEIPTSENT', 'An email has been sent to: ').$email.'.';
                    } else {
                        $message = _t('OrderConfirmationPage.RECEIPT_NOT_SENT', 'Email could NOT be sent to: ').$email;
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
        } else {
            return _t('OrderConfirmationPage.RECEIPTNOTSENTNOORDER', 'Order could not be found.');
        }
    }

    protected function includeGoogleAnalyticsCode()
    {
        if ($this->EnableGoogleAnalytics && $this->currentOrder && Director::isLive()) {
            $currencyUsedObject = $this->currentOrder->CurrencyUsed();
            if ($currencyUsedObject) {
                $currencyUsedString = $currencyUsedObject->Code;
            }
            if (empty($currencyUsedString)) {
                $currencyUsedString = EcommerceCurrency::default_currency_code();
            }
            $js = '
            jQuery(document).ready(
                function(){
                    _gaq(\'require\', \'ecommerce\');
                    _gaq(
                        \'ecommerce:addTransaction\',
                        {
                            \'id\': \''.$this->currentOrder->ID.'\',
                            \'revenue\': \''.$this->currentOrder->getSubTotal().'\',
                            \'currency\': \''.$currencyUsedString.'\'
                        }
                    );
                    _gaq(\'ecommerce:send\');
                }
            );
';
            Requirements::customScript($js, 'GoogleAnalyticsEcommerce');
        }
    }
}
