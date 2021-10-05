<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderSteps;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
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

    /**
     * can go to next step if order has been paid.
     *
     * @see Order::doNextStatus
     *
     * @return null|OrderStep (next step OrderStep object)
     */
    public function nextStep(Order $order)
    {
        if ($order->IsPaid()) {
            return parent::nextStep($order);
        }

        return null;
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
            $fields->addFieldToTab('Root.Next', new LiteralField('NotPaidMessage', '<p>' . $msg . '</p>'), 'ActionNextStepManually');
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
