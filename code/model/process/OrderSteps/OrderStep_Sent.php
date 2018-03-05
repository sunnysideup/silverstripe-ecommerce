<?php

/**
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderStep_Sent extends OrderStep implements OrderStepInterface
{
    /**
     * @var string
     */
    protected $emailClassName = 'Order_StatusEmail';

    private static $db = array(
        'SendDetailsToCustomer' => 'Boolean',
        'EmailSubjectGift' => 'Varchar(200)',
        'CustomerMessageGift' => 'HTMLText'
    );

    private static $defaults = array(
        'CustomerCanEdit' => 0,
        'CustomerCanCancel' => 0,
        'CustomerCanPay' => 0,
        'Name' => 'Send Order',
        'Code' => 'SENT',
        'ShowAsInProcessOrder' => 1
    );

    private static $field_labels = [
        'EmailSubjectGift' => 'Email subject',
        'CustomerMessageGift' => 'Customer message'
    ];

    /**
     * The OrderStatusLog that is relevant to the particular step.
     *
     * @var string
     */
    protected $relevantLogEntryClassName = 'OrderStatusLog_DispatchPhysicalOrder';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Main', new HeaderField('ActuallySendDetails', _t('OrderStep.ACTUALLYSENDDETAILS', 'Send details to the customer?'), 3), 'SendDetailsToCustomer');
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
                )->setRows(5)
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
     * @param Order object
     *
     * @return bool - true if the current step is ready to be run...
     **/
    public function initStep(Order $order)
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
     * @param Order object
     *
     * @return bool - true if run correctly.
     **/
    public function doStep(Order $order)
    {
        if ($this->RelevantLogEntry($order)) {
            return $this->sendEmailForStep(
                $order,
                $subject = $this->CalculatedEmailSubject($order),
                $message = $this->CalculatedCustomerMessage($order),
                $resend = false,
                $this->SendDetailsToCustomer ? false : true,
                $this->getEmailClassName()
            );
        }
    }

    /**
     *nextStep:
     * returns the next step (after it checks if everything is in place for the next step to run...).
     *
     * @see Order::doNextStatus
     *
     * @param Order $order
     *
     * @return OrderStep | Null (next step OrderStep object)
     **/
    public function nextStep(Order $order)
    {
        if (!$this->SendDetailsToCustomer || $this->hasBeenSent($order)) {
            return parent::nextStep($order);
        }

        return;
    }

    /**
     * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields.
     *
     * @param FieldList $fields
     * @param Order     $order
     *
     * @return FieldList
     **/
    public function addOrderStepFields(FieldList $fields, Order $order)
    {
        $fields = parent::addOrderStepFields($fields, $order);
        $title = _t('OrderStep.MUSTENTERDISPATCHRECORD', ' ... To move this order to the next step please enter dispatch details.');
        $fields->addFieldToTab('Root.Next', $order->getOrderStatusLogsTableField('OrderStatusLog_DispatchPhysicalOrder', $title), 'ActionNextStepManually');

        return $fields;
    }

    /**
     * For some ordersteps this returns true...
     *
     * @return bool
     **/
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

    public function CalculatedEmailSubject($order = null)
    {
        $v = null;
        if ($order && $order->IsSeparateShippingAddress()) {
            $v = $this->EmailSubjectGift;
        }
        if (! $v) {
            $v = $this->EmailSubject;
        }

        return $v;
    }

    public function CalculatedCustomerMessage($order = null)
    {
        $v = null;
        if ($order && $order->IsSeparateShippingAddress()) {
            $v = $this->CustomerMessageGift;
        }
        if (! $v) {
            $v = $this->CustomerMessage;
        }

        return $v;
    }
}
