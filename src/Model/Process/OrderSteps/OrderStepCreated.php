<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderSteps;

use SilverStripe\Forms\CheckboxField;
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
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
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
     * Add the member to the order, in case the member is not an admin.
     */
    public function doStep(Order $order): bool
    {
        if (! $order->MemberID) {
            $member = Security::getCurrentUser();
            if ($member) {
                if (! $member->IsShopAdmin()) {
                    $order->MemberID = $member->ID();
                    $order->write();
                }
            }
        }

        return true;
    }

    /**
     * We can run the next step, once any items have been added.
     *
     * @see Order::doNextStatus
     *
     * @return null|OrderStep (next step OrderStep object)
     */
    public function nextStep(Order $order)
    {
        $order->TotalItems($recalculate = true);
        if ($order) {
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
        if (! $order->IsSubmitted()) {
            //LINE BELOW IS NOT REQUIRED
            $header = _t('OrderStep.SUBMITORDER', 'Submit Order');
            $label = _t('OrderStep.SUBMITNOW', 'Submit Now');
            $msg = _t('OrderStep.MUSTDOSUBMITRECORD', '<p>Tick the box below to submit this order.</p>');
            $problems = [];
            if (! $order->getTotalItems()) {
                $problems[] = 'There are no --- Order Items (products) --- associated with this order.';
            }
            if (! $order->MemberID) {
                $billingAddress = null;
                $problems[] = 'There is no --- Customer --- associated with this order.';
            }
            if ($order->BillingAddressID) {
                $billingAddress = $order->BillingAddress();
            } else {
                $problems[] = 'There is no --- Billing Address --- associated with this order.';
            }
            if ($billingAddress) {
                $requiredBillingFields = $billingAddress->getRequiredFields();
                if ($requiredBillingFields && is_array($requiredBillingFields) && count($requiredBillingFields)) {
                    foreach ($requiredBillingFields as $requiredBillingField) {
                        if (! $billingAddress->{$requiredBillingField}) {
                            $problems[] = "There is no --- {$requiredBillingField} --- recorded in the billing address.";
                        }
                    }
                }
            }
            if (count($problems)) {
                $msg = '<p>You can not submit this order because:</p> <ul><li>' . implode('</li><li>', $problems) . '</li></ul>';
            }
            $fields->addFieldToTab('Root.Next', new HeaderField('CreateSubmitRecordHeader', $header, 3));
            $fields->addFieldToTab('Root.Next', new LiteralField('CreateSubmitRecordMessage', $msg));
            if (! $problems) {
                $fields->addFieldToTab('Root.Next', new CheckboxField('SubmitOrderViaCMS', $label));
            }
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
