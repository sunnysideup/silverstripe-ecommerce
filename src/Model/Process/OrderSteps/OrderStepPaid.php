<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderSteps;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use Sunnysideup\Ecommerce\Forms\Fields\EcommerceCMSButtonField;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * Class \Sunnysideup\Ecommerce\Model\Process\OrderSteps\OrderStepPaid
 *
 */
class OrderStepPaid extends OrderStep implements OrderStepInterface
{
    private static $defaults = [
        'CustomerCanEdit' => 0,
        'CustomerCanCancel' => 0,
        //the one below may seem a bit paradoxical, but the thing is that the customer can pay up to and inclusive of this step
        //that ist he code PAID means that the Order has been paid ONCE this step is completed
        'CustomerCanPay' => 1,
        'Name' => 'Pay',
        'Code' => 'PAID',
        'ShowAsInProcessOrder' => 1,
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
        'IsPaid' => true,
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
     * @param Order $order object
     *
     * @return bool - true if run correctly
     */
    public function doStep(Order $order): bool
    {
        return true;
    }

    public function IsPaid($order): bool
    {
        return (bool) $order->IsPaid();
    }

    /**
     * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields.
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function addOrderStepFields(FieldList $fields, Order $order, ?bool $nothingToDo = false)
    {
        $fields = parent::addOrderStepFields($fields, $order);
        if (! $order->IsPaid()) {
            $msg = _t(
                'OrderStep.ORDERNOTPAID',
                '
                    This order can not be completed, because it has not been paid.
                    You can either create a payment or change the status of any existing payment to <i>success</i>.
                    See Payments tab for more details.
                '
            );
            $lastPayment = $order->Payments()->last();
            if ($lastPayment) {
                $fields->addFieldsToTab(
                    'Root.Next',
                    [
                        new LiteralField('NotPaidMessage', '<p>' . $msg . '</p>'),
                        EcommerceCMSButtonField::create(
                            'EditPayment',
                            $lastPayment->CMSEditLink(),
                            'View / Edit Payment'
                        ),
                    ]
                );
            }
        }
        $paymentField = $fields->fieldByName('Root.Payments.Payments');
        if ($paymentField) {
            $fields->addFieldsToTab(
                'Root.Next',
                [
                    $paymentField,
                ]
            );
        }

        return $fields;
    }

    /**
     * Explains the current order step.
     *
     * @return string
     */
    protected function myDescription()
    {
        return _t('OrderStep.PAID_DESCRIPTION', 'The order is paid in full.');
    }
}
