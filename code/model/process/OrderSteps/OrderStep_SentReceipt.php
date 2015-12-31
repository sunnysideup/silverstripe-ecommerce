<?php

/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderStep_SentReceipt extends OrderStep implements OrderStepInterface
{

    /**
     * @var String
     */
    protected $emailClassName = "Order_ReceiptEmail";

    private static $db = array(
        "SendReceiptToCustomer" => "Boolean"
    );

    private static $defaults = array(
        "CustomerCanEdit" => 0,
        "CustomerCanCancel" => 0,
        "CustomerCanPay" => 0,
        "Name" => "Send receipt",
        "Code" => "RECEIPTED",
        "ShowAsInProcessOrder" => 1,
        "SendReceiptToCustomer" => 1
    );


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab("Root.CustomerMessage", new HeaderField("ActuallySendReceiptToCustomer", _t("OrderStep.ACTUALLYSENDRECEIPT", "Actually send the receipt?"), 3));
        $fields->addFieldToTab("Root.CustomerMessage", new CheckboxField("SendReceiptToCustomer", _t("OrderStep.SENDRECEIPTTOCUSTOMER", "Send receipt to customer?"), 3));
        return $fields;
    }

    /**
     *initStep:
     * makes sure the step is ready to run.... (e.g. check if the order is ready to be emailed as receipt).
     * should be able to run this function many times to check if the step is ready
     * @see Order::doNextStatus
     * @param Order object
     * @return Boolean - true if the current step is ready to be run...
     **/
    public function initStep(Order $order)
    {
        return $order->IsPaid();
    }

    /**
     * execute the step
     *
     * @param Order $order
     * @return Boolean
     */
    public function doStep(Order $order)
    {
        return $this->sendEmailForStep($order, $subject = $this->EmailSubject, $message = "", $resend = false, $adminOnly = false, $this->getEmailClassName());
    }

    /**
     * can continue if receipt has been sent or if there is no need to send a receipt.
     *
     * @param Order $order
     * @return OrderStep | Null - DataObject = next OrderStep
     **/
    public function nextStep(Order $order)
    {
        if (!$this->SendReceiptToCustomer || $this->hasBeenSent($order)) {
            return parent::nextStep($order);
        }
        return null;
    }

    /**
     * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
     *
     * @param FieldList $fields
     * @param Order $order
     * @return FieldList
     **/
    public function addOrderStepFields(FieldList $fields, Order $order)
    {
        $fields = parent::addOrderStepFields($fields, $order);
        $title = _t("OrderStep.CANADDGENERALLOG", " ... if you want to make some notes about this step then do this here...)");
        $fields->addFieldToTab("Root.Next", $order->getOrderStatusLogsTableField("OrderStatusLog", $title), "ActionNextStepManually");
        return $fields;
    }


    /**
     * For some ordersteps this returns true...
     * @return Boolean
     **/
    protected function hasCustomerMessage()
    {
        return $this->SendReceiptToCustomer;
    }

    /**
     * Explains the current order step.
     * @return String
     */
    protected function myDescription()
    {
        return _t("OrderStep.SENTRECEIPT_DESCRIPTION", "The customer is sent a receipt.");
    }
}
