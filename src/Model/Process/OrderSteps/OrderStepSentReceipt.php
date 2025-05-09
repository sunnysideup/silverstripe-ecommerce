<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderSteps;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use Sunnysideup\Ecommerce\Email\OrderReceiptEmail;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * Class \Sunnysideup\Ecommerce\Model\Process\OrderSteps\OrderStepSentReceipt
 *
 * @property bool $SendReceiptToCustomer
 */
class OrderStepSentReceipt extends OrderStep implements OrderStepInterface
{
    /**
     * @var string
     */
    protected $emailClassName = OrderReceiptEmail::class;

    private static $table_name = 'OrderStepSentReceipt';

    private static $db = [
        'SendReceiptToCustomer' => 'Boolean',
    ];

    private static $step_logic_conditions = [
        'hasBeenSent' => true,
    ];

    private static $defaults = [
        'CustomerCanEdit' => 0,
        'CustomerCanCancel' => 0,
        'CustomerCanPay' => 0,
        'Name' => 'Send Receipt',
        'Code' => 'RECEIPTED',
        'ShowAsInProcessOrder' => 1,
        'SendReceiptToCustomer' => 1,
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab(
            'Root.CustomerMessage',
            HeaderField::create(
                'ActuallySendReceiptToCustomer',
                _t(
                    'OrderStep.ACTUALLYSENDRECEIPT',
                    'Actually send the receipt?'
                ),
                3
            )
        );
        $fields->addFieldToTab('Root.CustomerMessage', new CheckboxField('SendReceiptToCustomer', _t('OrderStep.SENDRECEIPTTOCUSTOMER', 'Send receipt to customer?'), 3));

        return $fields;
    }

    /**
     * initStep:
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
        return $order->IsPaid();
    }

    /**
     * execute the step.
     */
    public function doStep(Order $order): bool
    {
        $adminOnlyOrToEmail = ! (bool) $this->SendReceiptToCustomer;

        return (bool) $this->sendEmailForStep(
            $order,
            $subject = $this->CalculatedEmailSubject($order),
            $message = '',
            $resend = false,
            $adminOnlyOrToEmail,
            $this->getEmailClassName()
        );
    }

    /**
     * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields.
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function addOrderStepFields(FieldList $fields, Order $order, ?bool $nothingToDo = false)
    {
        $fields = parent::addOrderStepFields($fields, $order);
        $title = _t('OrderStep.CANADDGENERALLOG', ' ... if you want to make some notes about this step then do this here...)');
        $fields->addFieldToTab('Root.Next', $order->getOrderStatusLogsTableField(OrderStatusLog::class, $title));

        return $fields;
    }

    /**
     * For some ordersteps this returns true...
     *
     * @return bool
     */
    public function hasCustomerMessage()
    {
        return $this->SendReceiptToCustomer;
    }

    /**
     * Explains the current order step.
     *
     * @return string
     */
    protected function myDescription()
    {
        return _t('OrderStep.SENTRECEIPT_DESCRIPTION', 'The customer is sent a receipt.');
    }
}
