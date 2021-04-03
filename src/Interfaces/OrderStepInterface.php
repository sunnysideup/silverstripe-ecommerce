<?php

namespace Sunnysideup\Ecommerce\Interfaces;

use SilverStripe\Forms\FieldList;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: buyables
 */
interface OrderStepInterface
{
    /**
     * Initiate the step. REturns true if the step is ready to run.
     * You should be able to run this method many times without causing problems.
     */
    public function initStep(Order $order): bool;

    /**
     * Do the actual step.
     * Returns true if the step runs successfully.
     * You should be able to run this method many times without causing problems.
     */
    public function doStep(Order $order): bool;

    /**
     * Returns the nextStep when we are ready or null if we are not ready.
     * You should be able to run this method many times without causing problems.
     *
     * @return null|OrderStep (nextStep DataObject)
     */
    public function nextStep(Order $order);

    /**
     * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
     * You should be able to run this method many times without causing problems.
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function addOrderStepFields(FieldList $fields, Order $order);
}
