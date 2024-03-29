<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderSteps;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use Sunnysideup\Ecommerce\Email\OrderInvoiceEmail;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * Class \Sunnysideup\Ecommerce\Model\Process\OrderSteps\OrderStepSentInvoice
 *
 * @property bool $SendInvoiceToCustomer
 */
class OrderStepSentInvoice extends OrderStep implements OrderStepInterface
{
    /**
     * @var string
     */
    protected $emailClassName = OrderInvoiceEmail::class;

    private static $table_name = 'OrderStepSentInvoice';

    private static $db = [
        'SendInvoiceToCustomer' => 'Boolean',
    ];

    private static $step_logic_conditions = [
        'hasBeenSent' => true,
    ];

    private static $defaults = [
        'CustomerCanEdit' => 0,
        'CustomerCanCancel' => 0,
        'CustomerCanPay' => 1,
        'Name' => 'Send Invoice',
        'Code' => 'INVOICED',
        'ShowAsInProcessOrder' => 1,
        'SendInvoiceToCustomer' => 1,
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab(
            'Root.CustomerMessage',
            [
                CheckboxField::create(
                    'SendInvoiceToCustomer',
                    'Send Invoice to Customer?'
                ),
            ]
        );

        return $fields;
    }

    /**
     * can run step once order has been submitted.
     * NOTE: must have a payment (even if it is a fake payment).
     * The reason for this is if people pay straight away then they want to see the payment shown on their invoice.
     *
     * @param Order $order object
     *
     * @return bool - true if the current step is ready to be run...
     */
    public function initStep(Order $order): bool
    {
        return $order->IsSubmitted();
    }

    /**
     * send invoice to customer
     * or in case this is not selected, it will send a message to the shop admin only
     * The latter is useful in case the payment does not go through (and no receipt is received).
     * @return bool
     */
    public function doStep(Order $order): bool
    {
        $adminOnlyOrToEmail = ! (bool) $this->SendInvoiceToCustomer;

        return (bool) $this->sendEmailForStep(
            $order,
            $subject = $this->EmailSubject,
            $message = '',
            $resend = false,
            $adminOnlyOrToEmail,
            $this->getEmailClassName()
        );
    }

    /**
     * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields.
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function addOrderStepFields(FieldList $fields, Order $order, ?bool $nothingToDo = false)
    {
        return parent::addOrderStepFields($fields, $order, true);
    }

    /**
     * For some ordersteps this returns true...
     *
     * @return bool
     */
    public function hasCustomerMessage()
    {
        return $this->SendInvoiceToCustomer;
    }

    /**
     * Explains the current order step.
     *
     * @return string
     */
    protected function myDescription()
    {
        return _t('OrderStep.SENTINVOICE_DESCRIPTION', 'Invoice gets sent to the customer via e-mail. In many cases, it is better to only send a receipt and sent the invoice to the shop admin only so that they know an order is coming, while the customer only sees a receipt which shows payment as well as the order itself.');
    }
}
