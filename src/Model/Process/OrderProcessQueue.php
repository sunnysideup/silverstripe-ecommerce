<?php

namespace Sunnysideup\Ecommerce\Model\Process;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\CmsEditLinkField\Forms\Fields\CMSEditLinkField;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Forms\Fields\EcommerceCMSButtonField;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Traits\OrderCached;

/**
 * This class provides a bunch of Meta Objects
 * that do not interact with the object at hand, but rather with the datalist as a whole.
 */
class OrderProcessQueue extends DataObject
{
    use OrderCached;

    private static $table_name = 'OrderProcessQueue';

    private static $db = [
        'DeferTimeInSeconds' => 'Int',
        'InProcess' => 'Boolean',
        'ProcessAttempts' => 'Int',
    ];

    private static $has_one = [
        'Order' => Order::class,
        'OrderStep' => OrderStep::class,
    ];

    private static $indexes = [
        'DeferTimeInSeconds' => true,
        'ProcessAttempts' => true,
    ];

    private static $casting = [
        'ToBeProcessedAt' => 'DBDatetime',
        'HasBeenInQueueSince' => 'DBDatetime',
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
            'field' => NumericField::class,
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
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
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
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
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
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
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
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
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
            OrderProcessQueue::class,
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
     * @param Order $order optional
     *
     * @return bool|string
     */
    public function process(?Order $order = null)
    {
        //find variables
        if (! $order) {
            $order = $this->getOrderCached();
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
            } elseif ($myQueueObject) {
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
        } else {
            $message = 'Can not find order.';
            $myQueueObject->delete();
        }

        return $message;
    }

    /**
     * META METHOD: returns the queue object if it exists.
     *
     * @param Order $order
     */
    public function getQueueObject($order)
    {
        $filter = ['OrderID' => $order->ID];

        return DataObject::get_one(OrderProcessQueue::class, $filter);
    }

    /**
     * META METHOD: Once you are done, you can remove the item like this ...
     *
     * @param Order $order
     */
    public function removeOrderFromQueue($order)
    {
        $queueEntries = OrderProcessQueue::get()->filter(['OrderID' => $order->ID]);
        foreach ($queueEntries as $queueEntry) {
            $queueEntry->delete();
        }
    }

    /**
     * META METHOD: returns a list of orders to be processed.
     *
     * @param int $id    force this Order to be processed
     * @param int $limit total number of orders that can be retrieved at any one time
     *
     * @return \SilverStripe\ORM\DataList (of orders)
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
            ->sort($this->sortPhraseForOrderIDs($orderIDs))
        ;
    }

    /**
     * META METHOD: all orders with a queue object.
     *
     * @param int $limit total number of orders that can be retrieved at any one time
     *
     * @return \SilverStripe\ORM\DataList (of orders)
     */
    public function AllOrdersInQueue($limit = 9999)
    {
        $orderIDs = OrderProcessQueue::get()->column('OrderID');

        return empty($orderIDs) ? null : Order::get()
            ->filter(['ID' => $orderIDs])
            ->sort($this->sortPhraseForOrderIDs($orderIDs))
            ->limit($limit)
        ;
    }

    /**
     * META METHOD: returns a list of orders NOT YET to be processed.
     *
     * @param int $limit total number of orders that can be retrieved at any one time
     *
     * @return \SilverStripe\ORM\DataList (of orders)
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
        $orderIDs = [];
        foreach ($rows as $row) {
            $orderIDs[$row['OrderID']] = $row['OrderID'];
        }
        $orderIDs = ArrayMethods::filter_array($orderIDs);

        return Order::get()
            ->filter(['ID' => $orderIDs])
            ->sort($this->sortPhraseForOrderIDs($orderIDs))
        ;
    }

    /**
     * non-database method of working out if an Order is ready to go.
     *
     * @return bool
     */
    public function isReadyToGo()
    {
        return (strtotime((string) $this->Created) + $this->DeferTimeInSeconds) < time();
    }

    /**
     * casted variable.
     *
     * @return \SilverStripe\ORM\FieldType\DBDatetime|\SilverStripe\ORM\FieldType\DBField
     */
    public function ToBeProcessedAt()
    {
        return $this->getToBeProcessedAt();
    }

    /**
     * casted variable.
     *
     * @return \SilverStripe\ORM\FieldType\DBDatetime|\SilverStripe\ORM\FieldType\DBField
     */
    public function getToBeProcessedAt()
    {
        return DBField::create_field(DBDatetime::class, (strtotime((string) $this->Created) + $this->DeferTimeInSeconds));
    }

    /**
     * casted variable.
     *
     * @return \SilverStripe\ORM\FieldType\DBDatetime|\SilverStripe\ORM\FieldType\DBField
     */
    public function HasBeenInQueueForSince()
    {
        return $this->getHasBeenInQueueForSince();
    }

    /**
     * casted variable.
     *
     * @return \SilverStripe\ORM\FieldType\DBDatetime|\SilverStripe\ORM\FieldType\DBField
     */
    public function getHasBeenInQueueForSince()
    {
        return DBField::create_field(DBDatetime::class, strtotime((string) $this->Created));
    }

    /**
     * CMS Fields.
     *
     * @return \SilverStripe\Forms\FieldList
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
                EcommerceCMSButtonField::create(
                    'ProcessQueue',
                    '/dev/tasks/EcommerceTaskProcessOrderQueue/?id=' . $this->OrderID,
                    _t('Order.PROCESS_QUEUE', 'process queue')
                ),
            );
            $fields->replaceField(
                'OrderID',
                CMSEditLinkField::create(
                    'OrderID',
                    'Order',
                    $this->getOrderCached()
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
     *
     * @param array $orderIDs
     *
     * @return string
     */
    protected function sortPhraseForOrderIDs($orderIDs)
    {
        return 'FIELD("Order"."ID", ' . implode(',', $orderIDs) . ')';
    }
}
