<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderSteps;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * This is the first Order Step.
 */
class OrderStepCreated extends OrderStep implements OrderStepInterface
{
    private static $defaults = [
        'CustomerCanEdit' => 1,
        'CustomerCanPay' => 1,
        'CustomerCanCancel' => 1,
        'Name' => 'Create',
        'Code' => 'CREATED',
        'ShowAsUncompletedOrder' => 1,
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
        'HasItems' => true,
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
        // An order can only leave this step if it can be linked to a customer
        // (a valid email address, and - for staff - a customer that is not the staff member).
        // If we did not stop it here, the order would end up in the next step without being submitted.
        return [] === $order->submissionProblems();
    }

    /**
     * Add the member to the order, in case the member is not an admin.
     */
    public function doStep(Order $order): bool
    {
        if (! $order->MemberID) {
            $member = Security::getCurrentUser();
            if ($member && ! $member->IsShopAdmin()) {
                $order->MemberID = $member->ID();
                $order->write();
            }
        }

        return true;
    }

    public function HasItems($order): bool
    {
        $count = $order->TotalItems();

        return $count > 0;
    }

    /**
     * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields.
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function addOrderStepFields(FieldList $fields, Order $order, ?bool $nothingToDo = false)
    {
        $fields = parent::addOrderStepFields($fields, $order);
        if (! $order->IsSubmitted()) {
            // Orders can NOT be submitted from the CMS: this makes sure that the customer,
            // their email address and all the checks are in place for every order.
            $header = _t('OrderStep.SUBMITORDER', 'Submit Order');
            $msg = _t(
                'OrderStep.CAN_NOT_SUBMIT_IN_CMS',
                '<p>Orders can not be submitted in the CMS. To place an order for a customer, open the customer (Security) and click "Place order for customer". The order is then placed on the website, as the customer would.</p>'
            );
            $fields->addFieldToTab('Root.Next', new HeaderField('CreateSubmitRecordHeader', $header, 3));
            $fields->addFieldToTab('Root.Next', new LiteralField('CreateSubmitRecordMessage', $msg));
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
        return _t('OrderStep.CREATED_DESCRIPTION', 'During this step the customer creates her or his order. The shop admininistrator does not do anything during this step.');
    }
}
