<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderSteps;







use Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogPaymentCheck;
use Sunnysideup\Ecommerce\Model\Order;
use SilverStripe\Forms\FieldList;
use Sunnysideup\Ecommerce\Model\Process\OrderSteps\OrderStepConfirmed;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use SilverStripe\Forms\LiteralField;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;



/**
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model

 **/
class OrderStepConfirmed extends OrderStep implements OrderStepInterface
{
    /**
     * @var string
     */
    protected $relevantLogEntryClassName = OrderStatusLogPaymentCheck::class;

    private static $defaults = [
        'CustomerCanEdit' => 0,
        'CustomerCanCancel' => 0,
        'CustomerCanPay' => 0,
        'Name' => 'Confirm',
        'Code' => 'CONFIRMED',
        'ShowAsInProcessOrder' => 1,
        'HideStepFromCustomer' => 1,
    ];

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
     * @param Order $order object
     *
     * @return bool - true if run correctly.
     **/
    public function doStep(Order $order)
    {
        return true;
    }

    /**
     * can go to next step if order payment has been confirmed...
     *
     * @param DataObject $order Order
     *
     * @return DataObject | Null - DataObject = OrderStep
     **/
    public function nextStep(Order $order)
    {
        $className = $this->getRelevantLogEntryClassName();
        $orderStatusLog_PaymentChecks = $className::get()
            ->Filter(['OrderID' => $order->ID, 'PaymentConfirmed' => 1]);
        if ($orderStatusLog_PaymentChecks->Count()) {
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
        $title = _t('OrderStep.MUSTDOPAYMENTCHECK', ' ... To move this order to the next step you must carry out a payment check (is the money in the bank?) by creating a record here (click me)');
        $fields->addFieldToTab('Root.Next', $order->getOrderStatusLogsTableField(OrderStatusLogPaymentCheck::class, $title), 'ActionNextStepManually');
        $fields->addFieldToTab('Root.Next', new LiteralField('ExampleOfThingsToCheck', '<ul><li>' . implode('</li><li>', EcommerceConfig::get(OrderStepConfirmed::class, 'list_of_things_to_check')) . '</li></ul>'), 'ActionNextStepManually');

        return $fields;
    }

    /**
     * Explains the current order step.
     *
     * @return string
     */
    protected function myDescription()
    {
        return _t('OrderStep.CONFIRMED_DESCRIPTION', 'The shop administrator confirms all the details for the current order.');
    }
}

