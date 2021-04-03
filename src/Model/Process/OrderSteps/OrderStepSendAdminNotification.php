<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderSteps;

use SilverStripe\Forms\FieldList;
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
class OrderStepSendAdminNotification extends OrderStep implements OrderStepInterface
{
    /**
     * @var string
     */
    protected $emailClassName = OrderReceiptEmail::class;

    private static $defaults = [
        'CustomerCanEdit' => 0,
        'CustomerCanCancel' => 0,
        'CustomerCanPay' => 1,
        'Name' => 'Send Admin Notification',
        'Code' => 'ADMINNOTIFIED',
        'ShowAsInProcessOrder' => 1,
    ];

    /**
     * can run step once order has been submitted.
     *
     * @param Order $order object
     *
     * @return bool - true if the current step is ready to be run...
     **/
    public function initStep(Order $order)
    {
        return $order->IsSubmitted();
    }

    /**
     * send invoice to customer
     * or in case this is not selected, it will send a message to the shop admin only
     * The latter is useful in case the payment does not go through (and no receipt is received).
     *
     * @param SilverStripe\ORM\DataObject | Order $order
     *
     * @return bool
     **/
    public function doStep(Order $order)
    {
        return $this->sendEmailForStep(
            $order,
            $subject = $this->EmailSubject,
            $message = '',
            $resend = false,
            $adminOnlyOrToEmail = true,
            $this->getEmailClassName()
        );
    }

    /**
     * can do next step once the admin notification has been sent
     * @return OrderStep|null (next step OrderStep object)
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
     * @return \SilverStripe\Forms\FieldList
     **/
    public function addOrderStepFields(FieldList $fields, Order $order)
    {
        $fields = parent::addOrderStepFields($fields, $order);
        $title = _t('OrderStep.CANADDGENERALLOG', ' ... if you want to make some notes about this step then do this here...');
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
        return false;
    }

    /**
     * Explains the current order step.
     *
     * @return string
     */
    protected function myDescription()
    {
        return _t(
            'OrderStep.SENDADMIN_NOTIFICATION',
            'Admin notification to admin about order.'
        );
    }
}
