<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderSteps;

use SilverStripe\Core\Convert;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\ORM\FieldType\DBDatetime;

use SilverStripe\Control\Controller;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogSubmitted;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
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
        return (bool) $order->TotalItems($recalculate = true);
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
        if (! $order->IsSubmitted($order)) {
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
                        $obj->OrderAsHTML = Convert::raw2sql($order->ConvertToHTML());
                    }
                    $obj->write();
                } else {
                    user_error('EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order") refers to a class that is NOT an instance of OrderStatusLog');
                }
            } else {
                user_error('EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order") refers to a non-existing class');
            }
            $order->LastEdited = DBDatetime::now()->Rfc2822();

            //add member if needed...
            if (! $order->MemberID) {
                //lets see if we can find a member
                $memberOrderID = Controller::curr()->getRequest()->getSession()->get('Ecommerce_Member_For_Order');
                Controller::curr()->getRequest()->getSession()->clear('Ecommerce_Member_For_Order');
                Controller::curr()->getRequest()->getSession()->set('Ecommerce_Member_For_Order', 0);
                Controller::curr()->getRequest()->getSession()->save();
                if ($memberOrderID) {
                    $order->MemberID = $memberOrderID;
                }
            }
            $order->write($showDebug = false, $forceInsert = false, $forceWrite = true);
        }

        return true;
    }


    public function IsSubmitted($order) : bool
    {
        return $order->IsSubmitted();
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
