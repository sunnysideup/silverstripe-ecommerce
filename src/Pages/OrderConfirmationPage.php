<?php

namespace Sunnysideup\Ecommerce\Pages;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Email\OrderStatusEmail;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Process\CheckoutPageStepDescription;

/**
 * @description:
 * The Order Confirmation page shows order history.
 * It also serves as the end point for the current order...
 * once submitted, the Order Confirmation page shows the
 * finalised detail of the order.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: Pages

 **/
class OrderConfirmationPage extends CartPage
{
    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $icon = 'sunnysideup/ecommerce: client/images/icons/OrderConfirmationPage-file.gif';

    /**
     * Standard SS variable.
     *
     * @var array
     */
    private static $table_name = 'OrderConfirmationPage';

    private static $db = [
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
        'IsFeedbackEnabled' => 'Boolean',
        'FeedbackFormLinkText' => 'Varchar(255)',
        'FeedbackHeader' => 'Varchar(255)',
        'FeedbackValuesFieldLabel' => 'Varchar(255)',
        'FeedbackValuesOptions' => 'Text',
        'FeedbackNotesFieldLabel' => 'Varchar(255)',
        'FeedbackFormSubmitLabel' => 'Varchar(255)',
        'FeedbackFormThankYou' => 'Varchar(255)',
    ];

    /**
     * Standard SS variable.
     *
     * @var array
     */
    private static $defaults = [
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
        'FeedbackHeader' => 'Feedback',
        'FeedbackValuesFieldLabel' => 'How likely are you to recommend us to your friends?',
        'FeedbackValuesOptions' => 'Not At All, Not Likely, Not Sure, Likely, Very Likely',
        'FeedbackNotesFieldLabel' => 'What can we do to improve the ordering experience?',
        'FeedbackFormSubmitLabel' => 'Submit Your Feedback',
        'FeedbackFormThankYou' => 'Thank you for taking the time to submit your feedback, we appreciate it!',
    ];

    private static $casting = [
        'PaymentMessage' => 'HTMLText',
    ];

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Order Confirmation Page';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Order Confirmation Pages';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A page where the customer can view her or his submitted order. Every e-commerce site needs an Order Confirmation Page.';

    public function i18n_singular_name()
    {
        return _t('OrderConfirmationpage.SINGULARNAME', 'Order Confirmation Page');
    }

    public function i18n_plural_name()
    {
        return _t('OrderConfirmationpage.PLURALNAME', 'Order Confirmation Pages');
    }

    /**
     * Standard SS function, we only allow for one OrderConfirmation Page to exist
     * but we do allow for extensions to exist at the same time.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return OrderConfirmationPage::get()->filter(['ClassName' => OrderConfirmationPage::class])->Count() ? false : $this->canEdit($member);
    }

    /**
     * Shop Admins can edit.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canDelete($member = null, $context = [])
    {
        return false;
    }

    /**
     * Standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canPublish($member = null)
    {
        return $this->canEdit($member);
    }

    public function customFieldLabels()
    {
        return [
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
            'IsFeedbackEnabled' => _t('OrderConfirmationPage.ISFEEDBACKENABLED', 'Enable Feedback Form'),
            'FeedbackHeader' => _t('OrderConfirmationPage.FEEDBACKHEADER', 'Feedback Form Header'),
            'FeedbackValuesFieldLabel' => _t('OrderConfirmationPage.FEEDBACKVALUESFIELDLABEL', 'Feedback Form Options Label'),
            'FeedbackValuesOptions' => _t('OrderConfirmationPage.FEEDBACKVALUESOPTIONS', 'Feedback Form Options'),
            'FeedbackNotesFieldLabel' => _t('OrderConfirmationPage.FEEDBACKVALUESFIELDLABEL', 'Feedback Form Notes Label'),
            'FeedbackFormSubmitLabel' => _t('OrderConfirmationPage.FEEDBACKFORMSUBMITLABEL', 'Feedback Form Submit Button Text'),
            'FeedbackFormThankYou' => _t('OrderConfirmationPage.FEEDBACKFORMTHANKYOU', 'Feedback Form Thank you Message'),
        ];
    }

    /**
     * standard SS method for decorators.
     *
     * @return array
     */
    public function fieldLabels(?bool $includerelations = true)
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
     * @return \SilverStripe\Forms\FieldList
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
        $fields->addFieldToTab('Root.Messages.Messages.Actions', TextField::create('StartNewOrderLinkLabel', $fieldLabels['StartNewOrderLinkLabel']));
        $fields->addFieldToTab('Root.Messages.Messages.Actions', TextField::create('CopyOrderLinkLabel', $fieldLabels['CopyOrderLinkLabel']));
        $fields->addFieldsToTab('Root.Messages.Messages.Payment', [
            HeaderField::create('Successful', 'Successful'),
            TextField::create('PaymentSuccessfulHeader', $fieldLabels['PaymentSuccessfulHeader']),
            HTMLEditorField::create('PaymentSuccessfulMessage', $fieldLabels['PaymentSuccessfulMessage'])->setRows(3),
            HeaderField::create('Unsuccessful', 'Unsuccessful'),
            TextField::create('PaymentNotSuccessfulHeader', $fieldLabels['PaymentNotSuccessfulHeader']),
            HTMLEditorField::create('PaymentNotSuccessfulMessage', $fieldLabels['PaymentNotSuccessfulMessage'])->setRows(3),
            HeaderField::create('Pending', 'Pending'),
            TextField::create('PaymentPendingHeader', $fieldLabels['PaymentPendingHeader']),
            HTMLEditorField::create('PaymentPendingMessage', $fieldLabels['PaymentPendingMessage'])->setRows(3),
            HeaderField::create('Cancelled', 'Cancelled'),
            TextField::create('OrderCancelledHeader', $fieldLabels['OrderCancelledHeader']),
            HTMLEditorField::create('OrderCancelledMessage', $fieldLabels['OrderCancelledMessage'])->setRows(3),
        ]);
        if ($this->IsFeedbackEnabled) {
            $fields->addFieldsToTab(
                'Root.FeedbackForm',
                [
                    CheckboxField::create('IsFeedbackEnabled', $fieldLabels['IsFeedbackEnabled'])
                        ->setDescription(_t('OrderConfirmationPage.IsFeedbackEnabled_RIGHT', 'Enabling this option will display a feedback form on the order confirmation page and include links to the form in all order emails')),
                    TextField::create('FeedbackHeader', $fieldLabels['FeedbackHeader'])
                        ->setDescription(_t('OrderConfirmationPage.FeedbackHeader_RIGHT', 'e.g. Please let us know what you think')),
                    TextField::create('FeedbackValuesFieldLabel', $fieldLabels['FeedbackValuesFieldLabel'])
                        ->setDescription(_t('OrderConfirmationPage.FeedbackValuesFieldLabel_RIGHT', 'e.g. Please rate our service')),
                    TextField::create('FeedbackValuesOptions', $fieldLabels['FeedbackValuesOptions'])
                        ->setDescription(_t('OrderConfirmationPage.FeedbackValuesOptions_RIGHT', 'Comma separated list of feedback rating options (eg Good, Neutral, Bad)')),
                    TextField::create('FeedbackNotesFieldLabel', $fieldLabels['FeedbackNotesFieldLabel'])
                        ->setDescription(_t('OrderConfirmationPage.FeedbackNotesFieldLabel_RIGHT', 'e.g. Please add any comments')),
                    TextField::create('FeedbackFormSubmitLabel', $fieldLabels['FeedbackFormSubmitLabel'])
                        ->setDescription(_t('OrderConfirmationPage.FeedbackFormSubmitLabel_RIGHT', 'e.g. Submit Feedback Now')),
                    TextField::create('FeedbackFormThankYou', $fieldLabels['FeedbackFormThankYou'])
                        ->setDescription(_t('OrderConfirmationPage.FeedbackFormThankYou_RIGHT', 'Thank you message displayed to user after submitting the feedback form')),
                ]
            );
        } else {
            $fields->addFieldsToTab(
                'Root.FeedbackForm',
                [
                    CheckboxField::create('IsFeedbackEnabled', $fieldLabels['IsFeedbackEnabled'])
                        ->setDescription('Enabling this option will display a feedback form on the order confirmation page and include links to the form in all order emails'),
                ]
            );
        }
        return $fields;
    }

    /**
     * Returns the link or the Link to the OrderConfirmationPage page on this site.
     * @param string $action [optional]
     * @return string (URLSegment)
     */
    public static function find_link($action = null)
    {
        if ($page = DataObject::get_one(OrderConfirmationPage::class, ['ClassName' => OrderConfirmationPage::class])) {
            return $page->Link($action);
        } elseif ($page = DataObject::get_one(OrderConfirmationPage::class)) {
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
        return OrderConfirmationPage::find_link() . 'showorder/' . $orderID . '/';
    }

    /**
     * Return a link to view the order on this page.
     *
     * @param int|string $orderID                ID of the order
     * @param string     $emailClassName                   - the type of email you want to send.
     * @param bool       $actuallySendEmail      - do we actually send the email
     * @param int        $alternativeOrderStepID - OrderStep to use
     *
     * @return string (URLSegment)
     */
    public static function get_email_link($orderID, $emailClassName = OrderStatusEmail::class, $actuallySendEmail = false, $alternativeOrderStepID = 0)
    {
        $link = OrderConfirmationPage::find_link() . 'sendemail/' . $orderID . '/' . $emailClassName;
        $getParams = [];
        if ($actuallySendEmail) {
            $getParams['send'] = 1;
        }
        if ($alternativeOrderStepID) {
            $getParams['test'] = $alternativeOrderStepID;
        }
        $getParams = http_build_query($getParams);
        return $link . '?' . $getParams;
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
     * @return \SilverStripe\ORM\DataObject
     */
    public function CurrentCheckoutStep($isCurrentStep = false)
    {
        $do = new CheckoutPageStepDescription();
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

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $checkoutPage = DataObject::get_one(CheckoutPage::class);
        if ($checkoutPage) {
            $orderConfirmationPage = DataObject::get_one(OrderConfirmationPage::class);
            if (! $orderConfirmationPage) {
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
