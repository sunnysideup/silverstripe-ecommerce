<?php

namespace Sunnysideup\Ecommerce\Model\Process;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataList;
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
 *
 * @property int $DeferTimeInSeconds
 * @property bool $InProcess
 * @property int $ProcessAttempts
 * @property int $OrderID
 * @property int $OrderStepID
 * @method \Sunnysideup\Ecommerce\Model\Order Order()
 * @method \Sunnysideup\Ecommerce\Model\Process\OrderStep OrderStep()
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

    /**
     * An entry still flagged InProcess after this many seconds is assumed to belong
     * to a run that died (fatal error, timeout, killed cron) and is picked up again.
     *
     * @var int
     */
    private static $in_process_timeout_in_seconds = 600;

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
        'InProcess.NiceAndColourfull' => 'Currently Running',
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
        // an order is only ever at one step: entries for other steps are stale
        // and would otherwise keep taking up slots in every run
        $staleEntries = OrderProcessQueue::get()
            ->filter(['OrderID' => $order->ID])
            ->exclude(['OrderStepID' => (int) $order->StatusID]);
        foreach ($staleEntries as $staleEntry) {
            $staleEntry->delete();
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
        if (! $order instanceof \Sunnysideup\Ecommerce\Model\Order) {
            $order = $this->getOrderCached();
            $myQueueObject = $this;
        } else {
            $myQueueObject = $this->getQueueObject($order);
        }
        //delete if order is gone ...
        if ($order instanceof \Sunnysideup\Ecommerce\Model\Order) {
            //if order has moved already ... delete
            if (
                $order->IsCancelled() ||
                $order->IsArchived()
            ) {
                $myQueueObject?->delete();
                $message = 'Order is archived already and/or cancelled.';
            } elseif (
                // was $this->OrderStepID, which is 0 when called on a singleton,
                // so stale entries were never cleaned up
                $myQueueObject
                && $myQueueObject->OrderStepID > 0
                && (int) $order->StatusID !== (int) $myQueueObject->OrderStepID
            ) {
                $message = 'Order has already moved on.';
                $myQueueObject->delete();
            } elseif ($myQueueObject instanceof \Sunnysideup\Ecommerce\Model\Process\OrderProcessQueue) {
                if ($myQueueObject->isReadyToGo()) {
                    $oldOrderStatusID = (int) $order->StatusID;
                    $myQueueObject->InProcess = true;
                    ++$myQueueObject->ProcessAttempts;
                    $myQueueObject->write();
                    $hasMoved = false;
                    try {
                        $order->tryToFinaliseOrder(
                            false,
                            true
                        );
                        $hasMoved = $oldOrderStatusID !== (int) $order->StatusID;
                    } finally {
                        // always release the entry, even if finalising throws,
                        // otherwise it is excluded from every future run
                        if ($hasMoved) {
                            $myQueueObject->delete();
                        } else {
                            $myQueueObject->InProcess = false;
                            $myQueueObject->write();
                        }
                    }
                    if ($hasMoved) {
                        return true;
                    }
                    $message = 'Attempt to move order was not successful.';
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
    public function getQueueObject($order): ?OrderProcessQueue
    {
        $filter = ['OrderID' => $order->ID];
        // do not use cache here!
        return OrderProcessQueue::get()->filter($filter)->first();
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
        // round robin: whatever was tried least recently goes first,
        // so every due entry gets a turn within ceil(due entries / $limit) runs
        $orderIDs = [-1 => -1];
        if ($id) {
            $orderIDs = [$id => $id];
        } else {
            $staleBefore = date(
                'Y-m-d H:i:s',
                DBDatetime::now()->getTimestamp() - (int) $this->config()->get('in_process_timeout_in_seconds')
            );
            $ids = $this->queueEntriesByReadiness(true)
                ->filterAny([
                    'InProcess' => 0,
                    'LastEdited:LessThan' => $staleBefore,
                ])
                ->orderBy($this->sortPhrase())
                ->limit((int) $limit)
                ->column('OrderID');
            // keep -1 so that the ID filter and FIELD() sort below never get an empty list
            $orderIDs += array_combine($ids, $ids);
        }

        return Order::get()
            ->filter(['ID' => $orderIDs])
            ->orderBy($this->sortPhraseForOrderIDs($orderIDs))
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
            ->orderBy($this->sortPhraseForOrderIDs($orderIDs))
            ->limit($limit);
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
        $ids = $this->queueEntriesByReadiness(false)
            ->orderBy($this->dueAtSql() . ' ASC')
            ->limit((int) $limit)
            ->column('OrderID');
        $orderIDs = ArrayMethods::filter_array(array_combine($ids, $ids));

        return Order::get()
            ->filter(['ID' => $orderIDs])
            ->orderBy($this->sortPhraseForOrderIDs($orderIDs))
        ;
    }

    /**
     * non-database method of working out if an Order is ready to go.
     *
     * @return bool
     */
    public function isReadyToGo()
    {
        return (strtotime((string) $this->Created) + $this->DeferTimeInSeconds) < DBDatetime::now()->getTimestamp();
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

    /**
     * Queue entries that are (or are not yet) past their defer time.
     *
     * The comparison uses DBDatetime::now(), i.e. the same PHP clock / timezone
     * that wrote "Created", rather than the MySQL server clock. This also makes
     * it respect DBDatetime::set_mock_now() in tests.
     */
    protected function queueEntriesByReadiness(bool $ready): DataList
    {
        $operator = $ready ? '<' : '>=';

        return OrderProcessQueue::get()
            ->where([
                $this->dueAtSql() . ' ' . $operator . ' ?' => DBDatetime::now()->getValue(),
            ]);
    }

    /**
     * SQL expression for when an entry becomes due.
     */
    protected function dueAtSql(): string
    {
        return '("OrderProcessQueue"."Created" + INTERVAL "OrderProcessQueue"."DeferTimeInSeconds" SECOND)';
    }

    /**
     * Round-robin order for processing.
     *
     * LastEdited is bumped on every attempt (InProcess / ProcessAttempts change),
     * so an entry that was just tried moves to the back of the line, and one that
     * has never been tried sorts by when it was added.
     * Previously this sorted on ProcessAttempts first, which meant that entries that
     * had failed a few times were never reached while the backlog was bigger than $limit.
     */
    protected function sortPhrase()
    {
        return '"OrderProcessQueue"."LastEdited" ASC, "OrderProcessQueue"."ID" ASC';
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
