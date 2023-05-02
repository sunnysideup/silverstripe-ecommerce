<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderSteps;

use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * Class \Sunnysideup\Ecommerce\Model\Process\OrderSteps\OrderStepArchived
 *
 */
class OrderStepArchived extends OrderStep implements OrderStepInterface
{
    private static $defaults = [
        'CustomerCanEdit' => 0,
        'CustomerCanCancel' => 0,
        'CustomerCanPay' => 0,
        'Name' => 'Archived Order',
        'Code' => 'ARCHIVED',
        'ShowAsCompletedOrder' => 1,
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

    /**
     *nextStep:
     * returns the next step (after it checks if everything is in place for the next step to run...)
     * As this is the last step, we always return NULL!
     *
     * @see Order::doNextStatus
     *
     * @return null|OrderStep (next step OrderStep object)
     */
    public function nextStep(Order $order): ?OrderStep
    {
        //IMPORTANT
        return null;
    }

    /**
     * Explains the current order step.
     *
     * @return string
     */
    protected function myDescription()
    {
        return _t('OrderStep.ARCHIVED_DESCRIPTION', 'This is typically the last step in the order process. Nothing needs to be done to the order anymore.  We keep the order in the system for record-keeping and statistical purposes.');
    }
}
