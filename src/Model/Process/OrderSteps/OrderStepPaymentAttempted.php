<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderSteps;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\FieldType\DBDatetime;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * An order can not continue until there is at least one payment attempt.
 *
 * A payment attempt is a payment with a status of Success, Failure or Pending
 * (so not Incomplete, which means the payment was never actually submitted).
 * The outcome of the attempt is not relevant for this step, only that there was one.
 *
 * If there is still no payment attempt after a number of days
 * (see `days_to_wait_for_payment_attempt`) then the order is cancelled.
 */
class OrderStepPaymentAttempted extends OrderStep implements OrderStepInterface
{
    public const CODE = 'PAYMENT_ATTEMPTED';

    /**
     * Number of days (counted from the moment the order was submitted)
     * after which an order without any payment attempt is cancelled.
     * Set to zero to never cancel.
     *
     * @var int
     */
    private static $days_to_wait_for_payment_attempt = 30;

    private static $table_name = 'OrderStepPaymentAttempted';

    private static $step_logic_conditions = [
        'hasBeenDone' => true,
    ];

    private static $defaults = [
        'CustomerCanEdit' => 0,
        'CustomerCanCancel' => 0,
        // the customer needs to be able to pay, otherwise they can never get past this step
        'CustomerCanPay' => 1,
        'Name' => 'Payment Attempted',
        'Code' => self::CODE,
        'ShowAsInProcessOrder' => 1,
        'HideStepFromCustomer' => 1,
    ];

    /**
     * The payment statuses that count as an attempt.
     *
     * @return string[]
     */
    public static function attempt_statuses(): array
    {
        return [
            EcommercePayment::SUCCESS_STATUS,
            EcommercePayment::FAILURE_STATUS,
            EcommercePayment::PENDING_STATUS,
        ];
    }

    /**
     * Can be run many times.
     * Returns true only if there is a payment attempt.
     * If there is none, and the order has been waiting for too long, then the order is cancelled.
     */
    public function initStep(Order $order): bool
    {
        if ($this->hasPaymentAttempt($order)) {
            return true;
        }
        if ($this->hasWaitedTooLong($order)) {
            $this->cancelOrder($order);
        }

        return false;
    }

    public function doStep(Order $order): bool
    {
        return true;
    }

    public function hasBeenDone(Order $order): bool
    {
        return $this->hasPaymentAttempt($order);
    }

    public function hasPaymentAttempt(Order $order): bool
    {
        if (! $order->exists()) {
            return false;
        }

        return EcommercePayment::get()
            ->filter(
                [
                    'OrderID' => $order->ID,
                    'Status' => self::attempt_statuses(),
                ]
            )
            ->exists()
        ;
    }

    public function daysToWait(): int
    {
        return (int) $this->config()->get('days_to_wait_for_payment_attempt');
    }

    public function hasWaitedTooLong(Order $order): bool
    {
        $days = $this->daysToWait();
        if ($days <= 0) {
            return false;
        }
        if ($order->CancelledByID) {
            return false;
        }
        $startTs = $this->orderStartTimestamp($order);
        if ($startTs <= 0) {
            return false;
        }

        return DBDatetime::now()->getTimestamp() >= strtotime('+' . $days . ' days', $startTs);
    }

    public function addOrderStepFields(FieldList $fields, Order $order, ?bool $nothingToDo = false)
    {
        $fields = parent::addOrderStepFields($fields, $order, $nothingToDo);
        if (! $this->hasPaymentAttempt($order)) {
            $message = 'This order can not continue until there has been at least one payment attempt '
                . '(a payment with status ' . implode(', ', self::attempt_statuses()) . ').';
            if ($this->daysToWait() > 0) {
                $message .= ' If there is still no payment attempt ' . $this->daysToWait()
                    . ' days after the order was submitted then the order will be cancelled.';
            }
            $fields->addFieldToTab(
                'Root.Next',
                LiteralField::create(
                    'NoPaymentAttemptMessage',
                    '<p class="message warning">' . $message . '</p>'
                )
            );
        }

        return $fields;
    }

    public function hasCustomerMessage()
    {
        return false;
    }

    protected function canBeDeferred(): bool
    {
        return false;
    }

    protected function cancelOrder(Order $order): void
    {
        $order->Cancel(
            null,
            'No payment attempt was made within ' . $this->daysToWait() . ' days of the order being submitted.'
        );
    }

    protected function orderStartTimestamp(Order $order): int
    {
        $log = $order->SubmissionLog();
        $created = $log ? $log->Created : $order->Created;

        return (int) strtotime((string) $created);
    }

    /**
     * Explains the current order step.
     *
     * @return string
     */
    protected function myDescription()
    {
        return 'There is at least one payment attempt (successful, failed or pending) for this order. '
            . 'Orders without a payment attempt are cancelled after a set number of days.';
    }
}
