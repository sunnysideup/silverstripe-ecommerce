<?php

/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderStep_Sent extends OrderStep implements OrderStepInterface
{

    /**
     * @var String
     */
    protected $emailClassName = "Order_StatusEmail";

    private static $db = array(
        "SendDetailsToCustomer" => "Boolean"
    );

    private static $defaults = array(
        "CustomerCanEdit" => 0,
        "CustomerCanCancel" => 0,
        "CustomerCanPay" => 0,
        "Name" => "Send order",
        "Code" => "SENT",
        "ShowAsInProcessOrder" => 1
    );

    /**
     * The OrderStatusLog that is relevant to the particular step.
     * @var String
     */
    protected $relevantLogEntryClassName = "OrderStatusLog_DispatchPhysicalOrder";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab("Root.Main", new HeaderField("ActuallySendDetails", _t("OrderStep.ACTUALLYSENDDETAILS", "Send details to the customer?"), 3), "SendDetailsToCustomer");
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
        return true;
    }

    /**
     *doStep:
     * should only be able to run this function once
     * (init stops you from running it twice - in theory....)
     * runs the actual step
     * @see Order::doNextStatus
     * @param Order object
     * @return Boolean - true if run correctly.
     **/
    public function doStep(Order $order)
    {
        if ($this->RelevantLogEntry($order)) {
            return true;
        }
    }


    /**
     *nextStep:
     * returns the next step (after it checks if everything is in place for the next step to run...)
     * @see Order::doNextStatus
     * @param Order $order
     * @return OrderStep | Null (next step OrderStep object)
     **/
    public function nextStep(Order $order)
    {
        if ($this->sendEmailForStep($order, $subject = $this->EmailSubject, $message = "", $resend = false, $adminOnly = false, $this->getEmailClassName())) {
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
        $title = _t("OrderStep.MUSTENTERDISPATCHRECORD", " ... To move this order to the next step you enter the dispatch details in the logs.");
        $fields->addFieldToTab("Root.Next", $order->getOrderStatusLogsTableField("OrderStatusLog_DispatchPhysicalOrder", $title), "ActionNextStepManually");
        return $fields;
    }

    /**
     * For some ordersteps this returns true...
     * @return Boolean
     **/
    protected function hasCustomerMessage()
    {
        return $this->SendDetailsToCustomer;
    }

    /**
     * Explains the current order step.
     * @return String
     */
    protected function myDescription()
    {
        return _t("OrderStep.SENT_DESCRIPTION", "During this step we record the delivery details for the order such as the courrier ticket number and whatever else is relevant.");
    }
}
