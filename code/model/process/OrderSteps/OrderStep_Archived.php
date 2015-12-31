<?php

/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderStep_Archived extends OrderStep implements OrderStepInterface
{

    private static $defaults = array(
        "CustomerCanEdit" => 0,
        "CustomerCanCancel" => 0,
        "CustomerCanPay" => 0,
        "Name" => "Archived order",
        "Code" => "ARCHIVED",
        "ShowAsCompletedOrder" => 1
    );

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
        return true;
    }


    /**
     *nextStep:
     * returns the next step (after it checks if everything is in place for the next step to run...)
     * As this is the last step, we always return NULL!
     * @see Order::doNextStatus
     * @param Order $order
     * @return OrderStep | Null (next step OrderStep object)
     **/
    public function nextStep(Order $order)
    {
        //IMPORTANT
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
        $title = _t("OrderStep.CANADDGENERALLOG", " ... if you want to make some notes about this order then do this here ...");
        $fields->addFieldToTab("Root.Next", $order->getOrderStatusLogsTableField("OrderStatusLog_Archived", $title), "ActionNextStepManually");
        return $fields;
    }

    /**
     * Explains the current order step.
     * @return String
     */
    protected function myDescription()
    {
        return _t("OrderStep.ARCHIVED_DESCRIPTION", "This is typically the last step in the order process. Nothing needs to be done to the order anymore.  We keep the order in the system for record-keeping and statistical purposes.");
    }
}
