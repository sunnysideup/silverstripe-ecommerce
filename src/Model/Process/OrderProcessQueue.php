<?php

namespace Sunnysideup\Ecommerce\Model\Process;

use DataObject;
use Member;
use Permission;
use Config;
use EcommerceRole;
use DB;
use Order;
use DBField;
use ReadonlyField;
use LiteralField;
use CMSEditLinkField;

/**
 * This class provides a bunch of Meta Objects
 * that do not interact with the object at hand, but rather with the datalist as a whole.
 */

class OrderProcessQueue extends DataObject
{

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * OLD: private static $db (case sensitive)
  * NEW: 
    private static $table_name = '[SEARCH_REPLACE_CLASS_NAME_GOES_HERE]';

    private static $db (COMPLEX)
  * EXP: Check that is class indeed extends DataObject and that it is not a data-extension!
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
    
    private static $table_name = 'OrderProcessQueue';


/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: private static $db = (case sensitive)
  * NEW: private static $db = (COMPLEX)
  * EXP: Make sure to add a private static $table_name!
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
    private static $db = [
        'DeferTimeInSeconds' => 'Int',
        'InProcess' => 'Boolean',
        'ProcessAttempts' => 'Int',
    ];


/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: private static $has_one = (case sensitive)
  * NEW: private static $has_one = (COMPLEX)
  * EXP: Make sure to add a private static $table_name!
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
    private static $has_one = [
        'Order' => 'Order',
        'OrderStep' => 'OrderStep',
    ];

    private static $indexes = [
        'DeferTimeInSeconds' => true,
        'ProcessAttempts' => true,
    ];

    private static $casting = [
        'ToBeProcessedAt' => 'SS_Datetime',
        'HasBeenInQueueSince' => 'SS_Datetime',
    ];

    private static $default_sort = [
        'ID' => 'DESC',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $summary_fields = [
        'Order.Title' => 'Order',
        'Order.Status.Title' => 'Current Step',
        'ProcessAttempts' => 'Attempts',
        'ToBeProcessedAt.Nice' => 'To be processed at',
        'ToBeProcessedAt.Ago' => 'That is ...',
        'HasBeenInQueueForSince.Nice' => 'Added to queue ...',
        'InProcess.Nice' => 'Currently Running',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $searchable_fields = [
        'OrderID' => [

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: NumericField (case sensitive)
  * NEW: NumericField (COMPLEX)
  * EXP: check the number of decimals required and add as ->Step(123)
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            'field' => 'NumericField',
            'title' => 'Order Number',
        ],
    ];

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Order To Be Processed';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Orders to be Processed';

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }
        //is the member is a shop assistant they can always view it
        if (EcommerceRole::current_member_is_shop_assistant($member)) {
            return true;
        }

        return parent::canView($member);
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        return false;
    }

    /**
     * Standard SS method
     * Queues can be deleted if needed.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canDelete($member = null, $context = [])
    {
        return parent::canDelete($member);
    }

    public function i18n_singular_name()
    {
        return _t('OrderProcessQueue.SINGULAR_NAME', 'Order In Queue');
    }

    public function i18n_plural_name()
    {
        return _t('OrderProcessQueue.PLURAL_NAME', 'Orders In Queue');
    }

    /**
     * META METHOD: Add an order to the job list if it does not exist already.
     *
     * @param Order $order
     * @param int   $deferTimeInSeconds
     */
    public function AddOrderToQueue($order, $deferTimeInSeconds)
    {
        if (! $order || ! $order->ID) {
            user_error('No real order provided.');
        }
        $filter = [
            'OrderID' => $order->ID,
            'OrderStepID' => $order->StatusID,
        ];
        $existingEntry = DataObject::get_one(
            'OrderProcessQueue',
            $filter,
            $cacheDataObjectGetOne = false
        );
        $filter['DeferTimeInSeconds'] = $deferTimeInSeconds;
        if (! $existingEntry) {
            $existingEntry = OrderProcessQueue::create($filter);
        } else {
            foreach ($filter as $field => $value) {
                $existingEntry->{$field} = $value;
            }
        }
        $existingEntry->write();

        return $existingEntry;
    }

    /**
     * META METHOD
     * processes the order ...
     * returns TRUE if SUCCESSFUL and a message if unsuccessful ...
     *
     * @param  Order $order optional
     * @return boolean | string
     */
    public function process($order = null)
    {
        //find variables
        if (! $order) {
            $order = $this->Order();
            $myQueueObject = $this;
        } else {
            $myQueueObject = $this->getQueueObject($order);
        }
        //delete if order is gone ...
        if ($order) {
            //if order has moved already ... delete
            if ($order->IsCancelled() ||
                $order->IsArchived()
            ) {
                $myQueueObject->delete();
                $message = 'Order is archived already and/or cancelled.';
            } elseif ($this->OrderStepID > 0
                && (int) $order->StatusID !== (int) $myQueueObject->OrderStepID
            ) {
                $message = 'Order has already moved on.';
                $myQueueObject->delete();
            } else {
                if ($myQueueObject) {
                    if ($myQueueObject->isReadyToGo()) {
                        $oldOrderStatusID = $order->StatusID;
                        $myQueueObject->InProcess = true;
                        ++$myQueueObject->ProcessAttempts;
                        $myQueueObject->write();
                        $order->tryToFinaliseOrder(
                            $tryAgain = false,
                            $fromOrderQueue = true
                        );
                        $newOrderStatusID = $order->StatusID;
                        if ($oldOrderStatusID !== $newOrderStatusID) {
                            $myQueueObject->delete();
                            return true;
                        }
                        $message = 'Attempt to move order was not successful.';
                        $myQueueObject->InProcess = false;
                        $myQueueObject->write();
                    } else {
                        $message = 'Minimum order queue time has not been passed.';
                    }
                } else {
                    $message = 'Could not find queue object.';
                }
            }
        } else {
            $message = 'Can not find order.';
            $myQueueObject->delete();
        }

        return $message;
    }

    /**
     * META METHOD: returns the queue object if it exists
     *
     * @param  Order $order
     */
    public function getQueueObject($order)
    {
        $filter = ['OrderID' => $order->ID];

        return DataObject::get_one('OrderProcessQueue', $filter);
    }

    /**
     * META METHOD: Once you are done, you can remove the item like this ...
     *
     * @param  Order $order
     */
    public function removeOrderFromQueue($order)
    {
        $queueEntries = OrderProcessQueue::get()->filter(['OrderID' => $order->ID]);
        foreach ($queueEntries as $queueEntry) {
            $queueEntry->delete();
        }
    }

    /**
     * META METHOD: returns a list of orders to be processed
     * @param int $id force this Order to be processed
     * @param int $limit total number of orders that can be retrieved at any one time
     *
     * @return DataList (of orders)
     */
    public function OrdersToBeProcessed($id = 0, $limit = 9999)
    {

        //we sort the order randomly so that we get a nice mixture
        //not always the same ones holding up the process
        $sql = '
            SELECT "OrderID"
            FROM "OrderProcessQueue"
            WHERE
                "InProcess" = 0
                AND
                (UNIX_TIMESTAMP("Created") + "DeferTimeInSeconds") < UNIX_TIMESTAMP()
            ORDER BY ' . $this->sortPhrase() . '
            LIMIT ' . $limit . ';
        ';
        $rows = DB::query($sql);
        $orderIDs = [$id => $id];
        foreach ($rows as $row) {
            $orderIDs[$row['OrderID']] = $row['OrderID'];
        }

        return Order::get()
            ->filter(['ID' => $orderIDs])
            ->sort($this->sortPhraseForOrderIDs($orderIDs));
    }

    /**
     * META METHOD: all orders with a queue object
     * @param int $id force this Order to be processed
     * @param int $limit total number of orders that can be retrieved at any one time
     *
     * @return DataList (of orders)
     */
    public function AllOrdersInQueue($limit = 9999)
    {
        $orderIDs = OrderProcessQueue::get()->column('OrderID');

        return Order::get()
            ->filter(['ID' => $orderIDs])
            ->sort($this->sortPhraseForOrderIDs($orderIDs))
            ->limit($limit);
    }

    /**
     * META METHOD: returns a list of orders NOT YET to be processed
     * @param int $limit total number of orders that can be retrieved at any one time
     *
     * @return DataList (of orders)
     */
    public function OrdersInQueueThatAreNotReady($limit = 9999)
    {

        //we sort the order randomly so that we get a nice mixture
        //not always the same ones holding up the process
        $sql = '
            SELECT "OrderID"
            FROM "OrderProcessQueue"
            WHERE
                (UNIX_TIMESTAMP("Created") + "DeferTimeInSeconds") >= UNIX_TIMESTAMP()
            ORDER BY ' . $this->sortPhrase() . '
            LIMIT ' . $limit . ';
        ';
        $rows = DB::query($sql);
        $orderIDs = [0 => 0];
        foreach ($rows as $row) {
            $orderIDs[$row['OrderID']] = $row['OrderID'];
        }

        return Order::get()
            ->filter(['ID' => $orderIDs])
            ->sort($this->sortPhraseForOrderIDs($orderIDs));
    }

    /**
     * non-database method of working out if an Order is ready to go.
     *
     * @return bool
     */
    public function isReadyToGo()
    {
        return (strtotime($this->Created) + $this->DeferTimeInSeconds) < time();
    }

    /**
     * casted variable
     * @return SS_DateTime
     */
    public function ToBeProcessedAt()
    {
        return $this->getToBeProcessedAt();
    }

    /**
     * casted variable
     * @return SS_DateTime
     */
    public function getToBeProcessedAt()
    {
        return DBField::create_field('SS_Datetime', (strtotime($this->Created) + $this->DeferTimeInSeconds));
    }

    /**
     * casted variable
     * @return SS_DateTime
     */
    public function HasBeenInQueueForSince()
    {
        return $this->getHasBeenInQueueForSince();
    }

    /**
     * casted variable
     * @return SS_DateTime
     */
    public function getHasBeenInQueueForSince()
    {
        return DBField::create_field('SS_Datetime', strtotime($this->Created));
    }

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        if ($this->exists()) {
            $fields->addFieldToTab(
                'Root.Main',
                ReadonlyField::create(
                    'HasBeenInQueueForSinceCompilations',
                    _t('OrderProcessQueue.SINCE', 'In the queue since'),
                    $this->getHasBeenInQueueForSince()->Nice() . ' - ' . $this->getHasBeenInQueueForSince()->Ago()
                ),
                'DeferTimeInSeconds'
            );
            $fields->addFieldToTab(
                'Root.Main',
                ReadonlyField::create(
                    'ToBeProcessedAtCompilations',
                    _t('OrderProcessQueue.TO_BE_PROCESSED', 'To Be Processed'),
                    $this->getToBeProcessedAt()->Nice() . ' - ' . $this->getToBeProcessedAt()->Ago()
                ),
                'InProcess'
            );
            $fields->addFieldToTab(
                'Root.Main',
                LiteralField::create(
                    'processQueueNow',
                    '<h2>
                        <a href="/dev/tasks/EcommerceTaskProcessOrderQueue/?id=' . $this->OrderID . '" target="_blank">' .
                            _t('OrderProcessQueue.PROCESS', 'Process now') .
                        '</a>
                    </h2>'
                )
            );
            $fields->replaceField(
                'OrderID',
                CMSEditLinkField::create(
                    'OrderID',
                    'Order',
                    $this->Order()
                )
            );
        }
        return $fields;
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $errors = OrderProcessQueue::get()->filter(['OrderID' => 0]);
        foreach ($errors as $error) {
            DB::alteration_message(' DELETING ROGUE OrderProcessQueue', 'deleted');
            $error->delete();
        }
    }

    protected function sortPhrase()
    {
        return '
            "ProcessAttempts" ASC,
            (UNIX_TIMESTAMP("Created") + "DeferTimeInSeconds") ASC
        ';
    }

    /**
     * sort phrase for orders, based in order IDs...
     * @param  array $orderIDs
     * @return string
     */
    protected function sortPhraseForOrderIDs($orderIDs)
    {
        return 'FIELD("Order"."ID", ' . implode(',', $orderIDs) . ')';
    }
}

