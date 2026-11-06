<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderSteps;

use Psr\Log\LoggerInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\ORM\FieldType\DBDatetime;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogSubmitted;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * Class \Sunnysideup\Ecommerce\Model\Process\OrderSteps\OrderStepSubmitted
 *
 * @property bool $SaveOrderAsHTML
 * @property bool $SaveOrderAsSerializedObject
 */
class OrderStepSubmitted extends OrderStep implements OrderStepInterface
{
    /**
     * The OrderStatusLog that is relevant to the particular step.
     *
     * @var string
     */
    protected $relevantLogEntryClassName = OrderStatusLogSubmitted::class;

    private static $step_logic_conditions = [
        'IsSubmitted' => true,
    ];

    private static $table_name = 'OrderStepSubmitted';

    private static $db = [
        'SaveOrderAsHTML' => 'Boolean',
        'SaveOrderAsSerializedObject' => 'Boolean',
    ];

    private static $defaults = [
        'CustomerCanEdit' => 0,
        'CustomerCanPay' => 1,
        'CustomerCanCancel' => 0,
        'Name' => 'Submit',
        'Code' => 'SUBMITTED',
        'ShowAsInProcessOrder' => 1,
        'SaveOrderAsHTML' => 1,
        'SaveOrderAsSerializedObject' => 0,
    ];

    public function getRelevantLogEntryClassName(): string
    {
        return EcommerceConfig::get(OrderStatusLog::class, 'order_status_log_class_used_for_submitting_order');
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab(
            'Root.Main',
            HeaderField::create(
                'HowToSaveSubmittedOrder',
                _t('OrderStep.HOWTOSAVESUBMITTEDORDER', 'How would you like to make a backup of your order at the moment it is submitted?'),
                3
            ),
            'SaveOrderAsHTML'
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
        // every submitted order needs a valid email address and a customer
        // (this is the one step that every route to submitting an order goes through).
        return (bool) $order->TotalItems() && [] === $order->submissionProblems();
    }

    /**
     * Links the order to a customer (member) and submits the order.
     *
     * Every submitted order has a member, because so much code relies on this.
     * The email address is the unique identifier of a member:
     * - there is no member with the email address of the order: we create one (no password).
     * - there is a member with that email address: the order is linked to that member,
     *   but it is held (Order.EmailNotVerified) until that member verifies their email address.
     * If we can not link the order to a member, then the order is NOT submitted.
     *
     * @param Order $order object
     *
     * @return bool - true if run correctly
     */
    public function doStep(Order $order): bool
    {
        if (! $this->IsSubmitted($order)) {
            // first make sure that there is a customer: without one, we do not submit.
            if (! $this->linkOrderToCustomer($order)) {
                Injector::inst()->get(LoggerInterface::class)->error(
                    'Order #' . (int) $order->ID . ' could not be submitted: it could not be linked to a customer (member) - email address: "' . $order->getValidCustomerEmail() . '".'
                );

                return false;
            }
            $order->write($showDebug = false, $forceInsert = false, $forceWrite = true);
            $className = $this->getRelevantLogEntryClassName();
            if (class_exists($className)) {
                //add currency if needed.
                $order->getHasAlternativeCurrency();

                $obj = $className::create();

                if (is_a($obj, EcommerceConfigClassNames::getName(OrderStatusLog::class))) {
                    $obj->OrderID = $order->ID;
                    $obj->Title = $this->Name;
                    //it is important we add this here so that we can save the 'submitted' version.
                    //this is particular important for the Order Item Links.
                    //order write will also update all the OrderAttributes!
                    $obj->write();
                    $obj = OrderStatusLog::get_by_id($obj->ID);
                    $saved = false;
                    if ($this->SaveOrderAsSerializedObject) {
                        $obj->OrderAsString = $order->ConvertToString();
                        $saved = true;
                    }
                    if ($this->SaveOrderAsHTML || ! $saved) {
                        $obj->OrderAsHTML = $order->ConvertToHTML();
                    }
                    $obj->write();
                } else {
                    user_error('EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order") refers to a class that is NOT an instance of OrderStatusLog');
                }
            } else {
                user_error('EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order") refers to a non-existing class');
            }
            $order->LastEdited = DBDatetime::now()->Rfc2822();

            $order->write($showDebug = false, $forceInsert = false, $forceWrite = true);
        }

        return true;
    }

    /**
     * Makes sure the order has a member, and a valid email address in the billing address.
     * Writes the member and EmailNotVerified to the order (the caller writes the order).
     */
    protected function linkOrderToCustomer(Order $order): bool
    {
        if (! $order->MemberID) {
            //lets see if the checkout has recorded a member (e.g. one that has just been created for this order)
            $request = Controller::curr()->getRequest();
            $session = $request->getSession();
            $memberOrderID = (int) $session->get('Ecommerce_Member_For_Order');
            $session->clear('Ecommerce_Member_For_Order');
            $session->set('Ecommerce_Member_For_Order', 0);
            $session->save($request);
            if ($memberOrderID) {
                $order->MemberID = $memberOrderID;
            }
        }
        if (! $order->MemberID) {
            EcommerceRole::attach_member_to_guest_order($order);
        }
        if (! $order->MemberID) {
            return false;
        }

        // the billing address should have the email address that we send the order to
        // (e.g. staff place an order for a customer: the email address is the one of the customer).
        $billingAddress = $order->BillingAddressID ? $order->BillingAddress() : null;
        if ($billingAddress && $billingAddress->exists() && ! filter_var(trim((string) $billingAddress->Email), FILTER_VALIDATE_EMAIL)) {
            $email = $order->getValidCustomerEmail();
            if ('' !== $email) {
                $billingAddress->Email = $email;
                $billingAddress->write();
            }
        }

        return '' !== $order->getValidCustomerEmail();
    }

    /**
     * we need this function for logic. Important that we recalculate!
     * @param  Order $order
     */
    public function IsSubmitted($order): bool
    {
        return $order->IsSubmitted($recalculate = true);
    }

    /**
     * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields.
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function addOrderStepFields(FieldList $fields, Order $order, ?bool $nothingToDo = false)
    {
        $fields = parent::addOrderStepFields($fields, $order);
        $title = _t('OrderStep.CANADDGENERALLOG', ' ... if you want to make some notes about this step then do this here...');
        $fields->addFieldToTab('Root.Next', $order->getOrderStatusLogsTableField(OrderStatusLog::class, $title));

        return $fields;
    }

    /**
     * Explains the current order step.
     *
     * @return string
     */
    protected function myDescription()
    {
        return _t('OrderStep.SUBMITTED_DESCRIPTION', 'The official moment the order gets submitted by the customer. The hand-shake for a commercial transaction.');
    }
}
