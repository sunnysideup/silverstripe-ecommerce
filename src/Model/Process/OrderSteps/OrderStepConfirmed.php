<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderSteps;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogPaymentCheck;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * Class \Sunnysideup\Ecommerce\Model\Process\OrderSteps\OrderStepConfirmed
 */
class OrderStepConfirmed extends OrderStep implements OrderStepInterface
{
    /**
     * @var string
     */
    protected $relevantLogEntryClassName = OrderStatusLogPaymentCheck::class;

    /**
     * @var array
     */
    private static $list_of_things_to_check = [
        'check1' => 'Payment has arrived in Bank Account',
        'check2' => 'Products are available',
    ];

    /**
     * ```php
     *     [
     *         'MethodToReturnTrue' => StepClassName
     *     ]
     * ```
     * MethodToReturnTrue must have an $order as a parameter and bool as the return value
     * e.g. MyMethod(Order $order) : bool;.
     *
     * @var array
     */
    private static $step_logic_conditions = [
        'PaymentConfirmed' => true,
    ];

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
     * @return bool - true if run correctly
     */
    public function doStep(Order $order): bool
    {
        return true;
    }

    public function PaymentConfirmed($order): bool
    {
        $paymentConfirmedLog = $this->getRelevantLogEntryClassName();
        $paymentConfirmedList = $paymentConfirmedLog::get()
            ->Filter([
                'OrderID' => $order->ID,
                'PaymentConfirmed' => 1,
            ])
        ;

        return (bool) $paymentConfirmedList->exists();
    }

    /**
     * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields.
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function addOrderStepFields(FieldList $fields, Order $order, ?bool $nothingToDo = false)
    {
        $fields = parent::addOrderStepFields($fields, $order);
        $title = _t('OrderStep.MUSTDOPAYMENTCHECK', ' ... To move this order to the next step you must carry out a payment check (is the money in the bank?) by creating a record here (click me)');
        $fields->addFieldsToTab(
            'Root.Next',
            [
                $order->getOrderStatusLogsTableField(OrderStatusLogPaymentCheck::class, $title),
                new LiteralField('ExampleOfThingsToCheck', '<ul><li>' . implode('</li><li>', EcommerceConfig::get(OrderStepConfirmed::class, 'list_of_things_to_check')) . '</li></ul>'),
            ]
        );

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
