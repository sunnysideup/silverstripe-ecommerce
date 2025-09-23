<?php

namespace Sunnysideuo\Ecommerce\Model\Process\OrderSteps;

use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

class OrderStepNotifyDeclinedSale extends OrderStep implements OrderStepInterface
{


    private static $table_name = 'OrderStepNotifyDeclinedSale';

    private static $db = [
        'EmailRecipient' => 'Varchar',
        'WaitTimeInMinutes' => 'Int',
    ];

    private static $defaults = [
        'CustomerCanEdit' => 0,
        'CustomerCanPay' => 0,
        'CustomerCanCancel' => 0,
        'Name' => 'Alert Declined Sale',
        'Code' => 'ALERT_DECLINED_SALE',
        'ShowAsInProcessOrder' => 1,
        'DeferTimeInSeconds' => 0,
        'WaitTimeInMinutes' => 60,
    ];

    public function HideFromEveryone(): bool
    {
        return true;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab(
            'Root.Notifications',
            [
                EmailField::create('EmailRecipient', 'Email Address')
                    ->setDescription(
                        'Notifications will be sent to this address.'
                    ),
            ]
        );


        return $fields;
    }

    /**
     * Can run this step once any items have been submitted.
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
        if ($this->canByPassStep($order) || $this->isReadyToSend($order)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add a member to the order - in case he / she is not a shop admin.
     *
     * @param Order $order object
     *
     * @return bool - true if run correctly
     */
    public function doStep(Order $order): bool
    {
        if ($this->canByPassStep($order)) {
            return true;
        }
        if (! $this->isReadyToSend($order)) {
            return false;
        }

        $toEmail = $this->EmailRecipient;
        if ($toEmail) {
            $this->sendEmailForStep(
                $order,
                $subject = 'Follow up failed payment order #' . $order->ID,
                $message = '',
                $resend = false,
                $toEmail,
                $this->getEmailClassName()
            );
        }

        return true;
    }

    protected static $is_ready_to_send_cache = [];

    protected function isReadyToSend(Order $order): bool
    {
        if (!isset(self::$is_ready_to_send_cache[$order->ID])) {
            if (! $this->WaitTimeInMinutes || $this->WaitTimeInMinutes < 1) {
                $this->WaitTimeInMinutes = 60;
            }
            self::$is_ready_to_send_cache[$order->ID] = true;
            $log = $order->SubmissionLog();
            if ($log) {
                $createdTS = strtotime((string) $log->Created);
                $nowTS = strtotime('now');
                $startSending = strtotime('+' . $this->WaitTimeInMinutes . ' minutes', $createdTS);
                // stop sending is before now... archive it.
                self::$is_ready_to_send_cache[$order->ID] = $startSending < $nowTS;
            }
        }
        return self::$is_ready_to_send_cache[$order->ID] ?? true;
    }

    protected static $cache_order_by_pass = [];

    protected function canByPassStep(Order $order): bool
    {
        if (!isset(self::$cache_order_by_pass[$order->ID])) {
            self::$cache_order_by_pass[$order->ID] = true;
            if (empty($this->EmailRecipient) || $order->IsPaid()) {
                // do nothing - we can by pass
            } else {
                $byPass = true;
                /**
                 * @var EcommercePayment $payment
                 */
                foreach ($order->Payments() as $payment) {
                    $status = $payment->Status;
                    if ($status === EcommercePayment::SUCCESS_STATUS || $status === EcommercePayment::PENDING_STATUS) {
                        continue;
                    }
                    $byPass = false;
                    break;
                }
                self::$cache_order_by_pass[$order->ID] = $byPass;
            }
        }
        return self::$cache_order_by_pass[$order->ID];
    }

    public function addOrderStepFields(FieldList $fields, Order $order, ?bool $nothingToDo = false)
    {
        // we force TRUE
        return parent::addOrderStepFields($fields, $order, true);
    }

    public function hasCustomerMessage(): bool
    {
        return false;
    }

    /**
     * Explains the current order step.
     *
     * @return string
     */
    protected function myDescription()
    {
        return 'Allow admin to follow up on declined payments.';
    }
}
