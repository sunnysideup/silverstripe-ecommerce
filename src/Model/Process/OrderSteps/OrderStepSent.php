<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderSteps;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\TextField;
use Sunnysideup\Ecommerce\Email\OrderStatusEmail;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogDispatchPhysicalOrder;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * Class \Sunnysideup\Ecommerce\Model\Process\OrderSteps\OrderStepSent
 *
 * @property bool $SendDetailsToCustomer
 * @property string $EmailSubjectGift
 * @property string $CustomerMessageGift
 */
class OrderStepSent extends OrderStep implements OrderStepInterface
{
    /**
     * @var string
     */
    protected $emailClassName = OrderStatusEmail::class;

    /**
     * The OrderStatusLog that is relevant to the particular step.
     *
     * @var string
     */
    protected $relevantLogEntryClassName = OrderStatusLogDispatchPhysicalOrder::class;

    private static $max_days_before_sending_it = 3;

    private static $table_name = 'OrderStepSent';

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
        'MarkedAsSent' => true,
    ];

    private static $db = [
        'SendDetailsToCustomer' => 'Boolean',
        'EmailSubjectGift' => 'Varchar(200)',
        'CustomerMessageGift' => 'HTMLText',
    ];

    private static $defaults = [
        'CustomerCanEdit' => 0,
        'CustomerCanCancel' => 0,
        'CustomerCanPay' => 0,
        'Name' => 'Send Order',
        'Code' => 'SENT',
        'ShowAsInProcessOrder' => 1,
    ];

    private static $field_labels = [
        'EmailSubjectGift' => 'Email subject',
        'CustomerMessageGift' => 'Customer message',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab(
            'Root.CustomerMessage',
            HeaderField::create(
                'ActuallySendDetails',
                _t('OrderStep.ACTUALLYSENDDETAILS', 'Send details to the customer?'),
                3
            ),
            'SendDetailsToCustomer'
        );
        $fields->addFieldsToTab(
            'Root.CustomerMessage',
            [
                HeaderField::create(
                    'GiftHeader',
                    _t('OrderStep.SEPARATE_DELIVERY', 'Message for separate shipping address ...')
                ),
                TextField::create(
                    'EmailSubjectGift',
                    _t('OrderStep.EmailSubjectGift', 'Subject')
                ),
                HTMLEditorField::create(
                    'CustomerMessageGift',
                    _t('OrderStep.CustomerMessageGift', 'Message')
                )->setRows(5),
            ]
        );

        return $fields;
    }

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
        if (false === $this->RelevantLogEntries($order)->exists()) {
            $className = $this->getRelevantLogEntryClassName();
            $log = $className::create();
            $log->OrderID = $order->ID;
            $log->write();
        }

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
        $log = $this->RelevantLogEntry($order);
        if ($log) {
            // can be sent weeks later... hence the FALSE in the hasBeenSent
            if ($log->InternalUseOnly || $this->hasBeenSent($order, false)) {
                return true; //do nothing
            }
            if($this->BypassSendingGoods) {
                return true;
            }
            if ($log->Sent) {
                // too late to send
                $maxDays = $this->Config('max_days_before_sending_it') ?: 3;
                if(strtotime($log->LastEdited) < strtotime('-'.$maxDays.' days')) {
                    return true;
                }
                $order->sendEmail(
                    $this->getEmailClassName(), // class name
                    $this->CalculatedEmailSubject($order), // subject
                    $this->CalculatedCustomerMessage($order), // message
                    false, // resend
                    ! (bool) $this->SendDetailsToCustomer // admin only or To Email
                );

                return true;
            }
        }

        return false;
    }

    public function MarkedAsSent($order): bool
    {
        $log = $this->RelevantLogEntry($order);
        if ($log) {
            return (bool) ($log->Sent || $log->BypassSendingGoods);
        }

        return false;
    }

    /**
     * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields.
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function addOrderStepFields(FieldList $fields, Order $order, ?bool $nothingToDo = false)
    {
        $fields = parent::addOrderStepFields($fields, $order);
        $title = _t('OrderStep.MUSTENTERDISPATCHRECORD', ' ... To move this order to the next step please enter dispatch details.');
        $fields->addFieldsToTab(
            'Root.Next',
            [
                $order->getOrderStatusLogsTableField(OrderStatusLogDispatchPhysicalOrder::class, $title),
            ]
        );

        return $fields;
    }

    public function CalculatedEmailSubject(?Order $order = null): string
    {
        $v = '';
        if ($order && $order->IsSeparateShippingAddress()) {
            $v = $this->EmailSubjectGift;
        }
        if (! $v) {
            $v = $this->EmailSubject;
        }

        return (string) $v;
    }

    public function CalculatedCustomerMessage(Order $order = null): string
    {
        $v = '';
        if ($order && $order->IsSeparateShippingAddress()) {
            $v = $this->CustomerMessageGift;
        }
        if (! $v) {
            $v = $this->CustomerMessage;
        }

        return (string) $v;
    }

    /**
     * For some ordersteps this returns true...
     *
     * @return bool
     */
    protected function hasCustomerMessage()
    {
        return $this->SendDetailsToCustomer;
    }

    /**
     * Explains the current order step.
     *
     * @return string
     */
    protected function myDescription()
    {
        return _t('OrderStep.SENT_DESCRIPTION', 'During this step we record the delivery details for the order such as the courrier ticket number and whatever else is relevant.');
    }
}
