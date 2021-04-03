<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderSteps;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\TextField;
use Sunnysideup\Ecommerce\Email\OrderStatusEmail;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogDispatchPhysicalOrder;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 */
class OrderStepSent extends OrderStep implements OrderStepInterface
{
    /**
     * @var string
     */
    protected $emailClassName = OrderStatusEmail::class;

    /**
     * The OrderStatusLog that is relevant to the particular step.
     *
     * @var string
     */
    protected $relevantLogEntryClassName = OrderStatusLogDispatchPhysicalOrder::class;

    private static $table_name = 'OrderStepSent';

    private static $db = [
        'SendDetailsToCustomer' => 'Boolean',
        'EmailSubjectGift' => 'Varchar(200)',
        'CustomerMessageGift' => 'HTMLText',
    ];

    private static $defaults = [
        'CustomerCanEdit' => 0,
        'CustomerCanCancel' => 0,
        'CustomerCanPay' => 0,
        'Name' => 'Send Order',
        'Code' => 'SENT',
        'ShowAsInProcessOrder' => 1,
    ];

    private static $field_labels = [
        'EmailSubjectGift' => 'Email subject',
        'CustomerMessageGift' => 'Customer message',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab(
            'Root.Main',
            HeaderField::create(
                'ActuallySendDetails',
                _t('OrderStep.ACTUALLYSENDDETAILS', 'Send details to the customer?'),
                3
            ),
            'SendDetailsToCustomer'
        );
        $fields->addFieldsToTab(
            'Root.CustomerMessage',
            [
                HeaderField::create(
                    'GiftHeader',
                    _t('OrderStep.SEPARATE_DELIVERY', 'Message for separate shipping address ...')
                ),
                TextField::create(
                    'EmailSubjectGift',
                    _t('OrderStep.EmailSubjectGift', 'Subject')
                ),
                HTMLEditorField::create(
                    'CustomerMessageGift',
                    _t('OrderStep.CustomerMessageGift', 'Message')
                )->setRows(5),
            ]
        );

        return $fields;
    }

    /**
     *initStep:
     * makes sure the step is ready to run.... (e.g. check if the order is ready to be emailed as receipt).
     * should be able to run this function many times to check if the step is ready.
     *
     * @see Order::doNextStatus
     *
     * @param Order $order object
     *
     * @return bool - true if the current step is ready to be run...
     */
    public function initStep(Order $order): bool
    {
        return true;
    }

    /**
     *doStep:
     * should only be able to run this function once
     * (init stops you from running it twice - in theory....)
     * runs the actual step.
     *
     * @see Order::doNextStatus
     *
     * @param Order $order object
     *
     * @return bool - true if run correctly
     */
    public function doStep(Order $order): bool
    {
        if ($log = $this->RelevantLogEntry($order)) {
            if ($log->InternalUseOnly || $this->hasBeenSent($order, false)) {
                return true; //do nothing
            }

            return $order->sendEmail(
                $this->getEmailClassName(),
                $subject = $this->CalculatedEmailSubject($order),
                $message = $this->CalculatedCustomerMessage($order),
                $resend = false,
                ! (bool) $this->SendDetailsToCustomer
            );
        }

        return false;
    }

    /**
     *nextStep:
     * returns the next step (after it checks if everything is in place for the next step to run...).
     *
     * @see Order::doNextStatus
     *
     * @return null|OrderStep (next step OrderStep object)
     */
    public function nextStep(Order $order)
    {
        $log = $this->RelevantLogEntry($order);
        if (! $this->SendDetailsToCustomer || $log->InternalUseOnly || $this->hasBeenSent($order, false)) {
            return parent::nextStep($order);
        }

        return null;
    }

    /**
     * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields.
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function addOrderStepFields(FieldList $fields, Order $order)
    {
        $fields = parent::addOrderStepFields($fields, $order);
        $title = _t('OrderStep.MUSTENTERDISPATCHRECORD', ' ... To move this order to the next step please enter dispatch details.');
        $fields->addFieldToTab('Root.Next', $order->getOrderStatusLogsTableField(OrderStatusLogDispatchPhysicalOrder::class, $title), 'ActionNextStepManually');

        return $fields;
    }

    public function CalculatedEmailSubject(?Order $order = null): string
    {
        $v = '';
        if ($order && $order->IsSeparateShippingAddress()) {
            $v = $this->EmailSubjectGift;
        }
        if (! $v) {
            $v = $this->EmailSubject;
        }

        return $v;
    }

    public function CalculatedCustomerMessage(Order $order = null): string
    {
        $v = '';
        if ($order && $order->IsSeparateShippingAddress()) {
            $v = $this->CustomerMessageGift;
        }
        if (! $v) {
            $v = $this->CustomerMessage;
        }

        return $v;
    }

    /**
     * For some ordersteps this returns true...
     *
     * @return bool
     */
    protected function hasCustomerMessage()
    {
        return $this->SendDetailsToCustomer;
    }

    /**
     * Explains the current order step.
     *
     * @return string
     */
    protected function myDescription()
    {
        return _t('OrderStep.SENT_DESCRIPTION', 'During this step we record the delivery details for the order such as the courrier ticket number and whatever else is relevant.');
    }
}
