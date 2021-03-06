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
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model

 **/
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
     **/
    public function initStep(Order $order)
    {
        return $order->IsPaid();
    }

    /**
     * execute the step.
     *
     * @param Order $order
     *
     * @return bool
     */
    public function doStep(Order $order)
    {
        if ($this->SendReceiptToCustomer) {
            $adminOnlyOrToEmail = false;
        } else {
            $adminOnlyOrToEmail = true;
        }
        return $this->sendEmailForStep(
            $order,
            $subject = $this->CalculatedEmailSubject($order),
            $message = '',
            $resend = false,
            $adminOnlyOrToEmail,
            $this->getEmailClassName()
        );
    }

    /**
     * can continue if receipt has been sent or if there is no need to send a receipt.
     *
     * @param Order $order
     *
     * @return OrderStep|null - DataObject = next OrderStep
     **/
    public function nextStep(Order $order)
    {
        if ($this->hasBeenSent($order)) {
            return parent::nextStep($order);
        }

        return null;
    }

    /**
     * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields.
     *
     * @param FieldList $fields
     * @param Order     $order
     *
     * @return \SilverStripe\Forms\FieldList
     **/
    public function addOrderStepFields(FieldList $fields, Order $order)
    {
        $fields = parent::addOrderStepFields($fields, $order);
        $title = _t('OrderStep.CANADDGENERALLOG', ' ... if you want to make some notes about this step then do this here...)');
        $fields->addFieldToTab('Root.Next', $order->getOrderStatusLogsTableField(OrderStatusLog::class, $title), 'ActionNextStepManually');

        return $fields;
    }

    /**
     * For some ordersteps this returns true...
     *
     * @return bool
     **/
    protected function hasCustomerMessage()
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
