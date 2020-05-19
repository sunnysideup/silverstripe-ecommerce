<?php


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
    protected $emailClassName = 'OrderReceiptEmail';

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
        if ($order->IsSubmitted()) {
            return true;
        }

        return false;
    }

    /**
     * send invoice to customer
     * or in case this is not selected, it will send a message to the shop admin only
     * The latter is useful in case the payment does not go through (and no receipt is received).
     *
     * @param DataObject $order Order
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
     *
     * @param Order $order
     *
     * @return OrderStep | Null (next step OrderStep object)
     **/
    public function nextStep(Order $order)
    {
        if ($this->hasBeenSent($order)) {
            return parent::nextStep($order);
        }

        return;
    }

    /**
     * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields.
     *
     *@param FieldList $fields
     *@param Order $order
     *
     *@return FieldList
     **/
    public function addOrderStepFields(FieldList $fields, Order $order)
    {
        $fields = parent::addOrderStepFields($fields, $order);
        $title = _t('OrderStep.CANADDGENERALLOG', ' ... if you want to make some notes about this step then do this here...');
        $fields->addFieldToTab('Root.Next', $order->getOrderStatusLogsTableField('OrderStatusLog', $title), 'ActionNextStepManually');

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

