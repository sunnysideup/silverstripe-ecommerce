<?php
/**
 * This class provides a bunch of Meta Objects
 * that do not interact with the object at hand, but rather with the datalist as a whole.
 *
 */

class OrderProcessQueue extends DataObject
{
    private static $db = array(
        'DeferTimeInSeconds' => 'Int',
        'InProcess' => 'Boolean'
    );

    private static $has_one = array(
        'Order' => 'Order'
    );

    private static $indexes = array(
        'OrderID' => array(
            'type' => 'unique',
            'value' => '"OrderID"'
        ),
        'Created' => true,
        'DeferTimeInSeconds' => true
    );

    private static $casting = array(
        'ToBeProcessedAt' => 'SS_Datetime'
    );

    private static $default_sort = array(
        'Created' => 'DESC'
    );

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $summary_fields = array(
        'Order.Title' => 'Order',
        'Order.Status.Title' => 'Current Step',
        'ToBeProcessedAt.Nice' => 'To be processed at',
        'ToBeProcessedAt.Ago' => 'That is ...',
        'InProcess.Nice' => 'Currently Running'
    );

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $searchable_fields = array(
        'OrderID' => array(
            'field' => 'NumericField',
            'title' => 'Order Number',
        )
    );


    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canCreate($member = null)
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
    public function canView($member = null)
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
    public function canEdit($member = null)
    {
        return false;
    }

    /**
     * Standard SS method
     * logs can never be deleted...
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Order To Be Processed';
    public function i18n_singular_name()
    {
        return _t('OrderProcessQueue.SINGULAR_NAME', 'Order To Be Processed');
    }

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Orders to be Processed';
    public function i18n_plural_name()
    {
        return _t('OrderProcessQueue.PLURAL_NAME', 'Orders to be Processed');
    }


    /**
     * META METHOD: Add an order to the job list.
     * If the order already exists, it will update the seconds and the creation  time.
     *
     * @param Order $order          [description]
     * @param Int   $deferInSeconds [description]
     */
    public function AddOrderToQueue($order, $deferTimeInSeconds)
    {
        $filter = array('OrderID' => $order->ID);
        $existingEntry = OrderProcessQueue::get()->filter($filter)->first();
        $filter['Created'] = SS_Datetime::now()->Rfc2822();
        $filter['DeferTimeInSeconds'] = $deferTimeInSeconds;
        if (! $existingEntry) {
            $existingEntry = OrderProcessQueue::create($filter);
        } else {
            foreach ($filter as $field => $value) {
                $existingEntry->$field = $value;
            }
        }
        $existingEntry->write();

        return $existingEntry;
    }

    /**
     * processes the order ...
     *
     * @param  Order $order
     */
    public function process($order)
    {
        $queueObjectSingleton = Injector::inst()->get('OrderProcessQueue');
        $myQueueObject = $queueObjectSingleton->getQueueObject($order);
        if ($myQueueObject->isReadyToGo()) {
            $myQueueObject->InProcess = true;
            $myQueueObject->write();
            $order->tryToFinaliseOrder(
                $tryAgain = false,
                $fromOrderQueue = true
            );
            $myQueueObject->delete();
        }
    }

    /**
     * META METHOD: returns the queue object if it exists
     *
     * @param  Order $order
     *
     * @return null |   OrderProcessQueue
     */
    public function getQueueObject($order)
    {
        $filter = array('OrderID' => $order->ID);
        $existingEntry = OrderProcessQueue::get()->filter($filter)->first();
        if ($existingEntry) {
            return $existingEntry;
        }

        return false;
    }

    /**
     * META METHOD: Once you are done, you can remove the item like this ...
     *
     * @param  Order $order
     */
    public function removeOrderFromQueue($order)
    {
        $filter = array('OrderID' => $order->ID);
        $existingEntries = OrderProcessQueue::get()->filter($filter);
        $existingEntries->removeAll();
    }

    /**
     * META METHOD: returns a list of orders to be processed
     * @param int $id force this Order to be processed
     * @param int $limit total number of orders that can be retrieved at any one time
     * @return DataList (of orders)
     */
    public function OrdersToBeProcessed($id = 0, $limit = 9999)
    {
        $sql = '
            SELECT "OrderID"
            FROM "OrderProcessQueue"
            WHERE
                "InProcess" = 0
                AND
                (UNIX_TIMESTAMP("Created") + "DeferTimeInSeconds") < '.time().'
            ORDER BY "Created" DESC
            LIMIT '.$limit.';
        ';
        $rows = DB::query($sql);
        $orderIDs = array($id => $id);
        foreach ($rows as $row) {
            $orderIDs[$row['OrderID']] = $row['OrderID'];
        }

        return Order::get()
            ->filter(array('ID' => $orderIDs));
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
     *
     * casted variable
     * @return SS_DateTime
     */
    public function ToBeProcessedAt()
    {
        return $this->getToBeProcessedAt();
    }

    /**
     *
     * casted variable
     * @return SS_DateTime
     */
    public function getToBeProcessedAt()
    {
        return DBField::create_field('SS_Datetime', (strtotime($this->Created) + $this->DeferTimeInSeconds));
    }


    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        if($this->exists()) {
            $fields->addFieldToTab(
                'Root.main',
                LiteralField::create(
                    'processQueueNow',
                    '<h2><a href="/dev/tasks/EcommerceTaskProcessOrderQueue/?id='.$this->ID.'" target="_blank">'._t('OrderProcessQueue.PROCESS', 'Process now').'</a></h2>'
                )
            );
        }
        return $fields;
    }
}
