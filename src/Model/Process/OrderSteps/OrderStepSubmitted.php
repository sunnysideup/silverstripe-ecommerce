<?php

namespace Sunnysideup\Ecommerce\Model\Process\OrderSteps;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\ORM\FieldType\DBDatetime;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogSubmitted;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model

 **/
class OrderStepSubmitted extends OrderStep implements OrderStepInterface
{
    /**
     * The OrderStatusLog that is relevant to the particular step.
     *
     * @var string
     */
    protected $relevantLogEntryClassName = OrderStatusLogSubmitted::class;

    /**
     * ### @@@@ START REPLACEMENT @@@@ ###
     * OLD: private static $db (case sensitive)
     * NEW:
    private static $db (COMPLEX)
     * EXP: Check that is class indeed extends DataObject and that it is not a data-extension!
     * ### @@@@ STOP REPLACEMENT @@@@ ###
     */
    private static $table_name = 'OrderStepSubmitted';

    /**
     * ### @@@@ START REPLACEMENT @@@@ ###
     * WHY: automated upgrade
     * OLD: private static $db = (case sensitive)
     * NEW: private static $db = (COMPLEX)
     * EXP: Make sure to add a private static $table_name!
     * ### @@@@ STOP REPLACEMENT @@@@ ###
     */
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

    /**
     * @return string
     */
    public function getRelevantLogEntryClassName()
    {
        return EcommerceConfig::get(OrderStatusLog::class, 'order_status_log_class_used_for_submitting_order');
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Main', new HeaderField('HowToSaveSubmittedOrder', _t('OrderStep.HOWTOSAVESUBMITTEDORDER', 'How would you like to make a backup of your order at the moment it is submitted?'), 3), 'SaveOrderAsHTML');

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
     **/
    public function initStep(Order $order)
    {
        return (bool) $order->TotalItems($recalculate = true);
    }

    /**
     * Add a member to the order - in case he / she is not a shop admin.
     *
     * @param Order $order object
     *
     * @return bool - true if run correctly.
     **/
    public function doStep(Order $order)
    {
        if (! $order->IsSubmitted()) {
            $className = $this->getRelevantLogEntryClassName();
            if (class_exists($className)) {
                //add currency if needed.
                $order->getHasAlternativeCurrency();

                /**
                 * ### @@@@ START REPLACEMENT @@@@ ###
                 * WHY: automated upgrade
                 * OLD: $className (case sensitive)
                 * NEW: $className (COMPLEX)
                 * EXP: Check if the class name can still be used as such
                 * ### @@@@ STOP REPLACEMENT @@@@ ###
                 */
                $obj = $className::create();

                /**
                 * ### @@@@ START REPLACEMENT @@@@ ###
                 * WHY: automated upgrade
                 * OLD:  Object:: (case sensitive)
                 * NEW:  SilverStripe\\Core\\Injector\\Injector::inst()-> (COMPLEX)
                 * EXP: Check if this is the right implementation, this is highly speculative.
                 * ### @@@@ STOP REPLACEMENT @@@@ ###
                 */
                if (is_a($obj, EcommerceConfigClassNames::getName(OrderStatusLog::class))) {
                    $obj->OrderID = $order->ID;
                    $obj->Title = $this->Name;
                    //it is important we add this here so that we can save the 'submitted' version.
                    //this is particular important for the Order Item Links.
                    //order write will also update all the OrderAttributes!
                    $obj->write();
                    $obj = OrderStatusLog::get()->byID($obj->ID);
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
            $order->LastEdited = "'" . DBDatetime::now()->Rfc2822() . "'";

            //add member if needed...
            if (! $order->MemberID) {
                //lets see if we can find a member

                /**
                 * ### @@@@ START REPLACEMENT @@@@ ###
                 * WHY: automated upgrade
                 * OLD: Session:: (case sensitive)
                 * NEW: SilverStripe\Control\Controller::curr()->getRequest()->getSession()-> (COMPLEX)
                 * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly.
                 * ### @@@@ STOP REPLACEMENT @@@@ ###
                 */
                $memberOrderID = SilverStripe\Control\Controller::curr()->getRequest()->getSession()->get('Ecommerce_Member_For_Order');

                /**
                 * ### @@@@ START REPLACEMENT @@@@ ###
                 * WHY: automated upgrade
                 * OLD: Session:: (case sensitive)
                 * NEW: SilverStripe\Control\Controller::curr()->getRequest()->getSession()-> (COMPLEX)
                 * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly.
                 * ### @@@@ STOP REPLACEMENT @@@@ ###
                 */
                SilverStripe\Control\Controller::curr()->getRequest()->getSession()->clear('Ecommerce_Member_For_Order');

                /**
                 * ### @@@@ START REPLACEMENT @@@@ ###
                 * WHY: automated upgrade
                 * OLD: Session:: (case sensitive)
                 * NEW: SilverStripe\Control\Controller::curr()->getRequest()->getSession()-> (COMPLEX)
                 * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly.
                 * ### @@@@ STOP REPLACEMENT @@@@ ###
                 */
                SilverStripe\Control\Controller::curr()->getRequest()->getSession()->set('Ecommerce_Member_For_Order', 0);

                /**
                 * ### @@@@ START REPLACEMENT @@@@ ###
                 * WHY: automated upgrade
                 * OLD: Session:: (case sensitive)
                 * NEW: SilverStripe\Control\Controller::curr()->getRequest()->getSession()-> (COMPLEX)
                 * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly.
                 * ### @@@@ STOP REPLACEMENT @@@@ ###
                 */
                SilverStripe\Control\Controller::curr()->getRequest()->getSession()->save();
                if ($memberOrderID) {
                    $order->MemberID = $memberOrderID;
                }
            }
            $order->write($showDebug = false, $forceInsert = false, $forceWrite = true);
        }

        return true;
    }

    /**
     * go to next step if order has been submitted.
     *
     * @param Order $order
     *
     * @return OrderStep | Null    (next step OrderStep)
     **/
    public function nextStep(Order $order)
    {
        if ($order->IsSubmitted()) {
            return parent::nextStep($order);
        }

        return;
    }

    /**
     * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields.
     *
     * @param FieldList $fields
     * @param Order     $order
     *
     * @return FieldList
     **/
    public function addOrderStepFields(FieldList $fields, Order $order)
    {
        $fields = parent::addOrderStepFields($fields, $order);
        $title = _t('OrderStep.CANADDGENERALLOG', ' ... if you want to make some notes about this step then do this here...');
        $fields->addFieldToTab('Root.Next', $order->getOrderStatusLogsTableField(OrderStatusLog::class, $title), 'ActionNextStepManually');

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
