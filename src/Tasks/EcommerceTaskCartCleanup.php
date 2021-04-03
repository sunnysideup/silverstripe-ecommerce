<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Address\BillingAddress;
use Sunnysideup\Ecommerce\Model\Address\ShippingAddress;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\OrderAttribute;
use Sunnysideup\Ecommerce\Model\Process\OrderEmailRecord;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * @description: cleans up old (abandonned) carts...
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks

 **/
class EcommerceTaskCartCleanup extends BuildTask
{
    /**
     *
     * @var bool
     */
    protected $neverDeleteIfLinkedToMember = true;

    /**
     * @var int
     */
    protected $maximumNumberOfObjectsDeleted = 0;

    /**
     * @var int
     */
    protected $limitFromGetVar = 0;

    /**
     * @var string
     */
    protected $sort = '';

    /**
     *
     * @var string
     */
    protected string $leftMemberJoin = '';

    /**
     * @var string
     */
    public $joinShort = '';

    /**
     * @var array
     */
    public $oneToMany = [];

    /**
     * @var array
     */
    public $oneToOne = [];

    /**
     * @var array
     */
    public $manyToMany = [];

    /**
     * @var array
     */
    public $oneToOneIDArray = [];

    /**
     * @var array
     */
    public $oneToManyIDArray = [];

    /**
     * Output feedback about task?
     *
     * @var bool
     */
    public $verbose = false;

    protected $title = 'Clear old carts';

    protected $description = 'Deletes abandonned carts (add ?limit=xxxx to the end of the URL to set the number of records (xxx = number of records) to be deleted in one load).';

    /**
     * @var int
     */
    private static $clear_minutes_empty_carts = 120;

    /**
     * @var int
     */
    private static $clear_minutes = 10080;

    /**
     * @var int
     */
    private static $maximum_number_of_objects_deleted = 10;

    /**
     * @var bool
     */
    private static $never_delete_if_linked_to_member = true;

    /**
     * @var array
     */
    private static $one_to_one_classes = [
        'BillingAddressID' => BillingAddress::class,
        'ShippingAddressID' => ShippingAddress::class,
    ];

    /**
     * @var array
     */
    private static $one_to_many_classes = [
        OrderAttribute::class => OrderAttribute::class,
        OrderStatusLog::class => OrderStatusLog::class,
        OrderEmailRecord::class => OrderEmailRecord::class,
    ];

    /**
     * @var array
     */
    private static $many_to_many_classes = [];

    /**
     * Standard SS Variable
     * TODO: either remove or add to all tasks.
     */
    private static $allowed_actions = [
        '*' => 'SHOPADMIN',
    ];

    /**
     * run in verbose mode.
     */
    public static function run_on_demand()
    {
        $obj = new self();
        $obj->verbose = true;
        $obj->run(null);
    }

    /**
     * runs the task without output.
     */
    public function runSilently()
    {
        $this->verbose = false;

        return $this->run(null);
    }

    public function run($request)
    {
        if ($this->verbose || (isset($_GET['verbose']) && Permission::check('ADMIN'))) {
            $this->verbose = true;
            $this->flush();
            $countAll = DB::query('SELECT COUNT("ID") FROM "Order"')->value();
            DB::alteration_message("<h2>deleting empty and abandonned carts (total cart count = {$countAll})</h2>.");
        }

        $this->neverDeleteIfLinkedToMember = EcommerceConfig::get(EcommerceTaskCartCleanup::class, 'never_delete_if_linked_to_member');
        $this->maximumNumberOfObjectsDeleted = EcommerceConfig::get(EcommerceTaskCartCleanup::class, 'maximum_number_of_objects_deleted');

        //LIMITS ...
        if ($request) {
            $this->limitFromGetVar = $request->getVar('limit');
            if ($this->limitFromGetVar && Permission::check('ADMIN')) {
                $this->maximumNumberOfObjectsDeleted = (int) $this->limitFromGetVar;
            }
        }

        //this->sort
        $this->sort = '"Order"."Created" ASC';

        //join
        $this->leftMemberJoin = 'LEFT JOIN Member ON "Member"."ID" = "Order"."MemberID"';
        $this->joinShort = '"Member"."ID" = "Order"."MemberID"';

        $this->abandonnedCarts();
        $this->emptyCarts();

        $this->oneToMany = EcommerceConfig::get(EcommerceTaskCartCleanup::class, 'one_to_many_classes');
        $this->oneToOne = EcommerceConfig::get(EcommerceTaskCartCleanup::class, 'one_to_one_classes');
        $this->manyToMany = EcommerceConfig::get(EcommerceTaskCartCleanup::class, 'many_to_many_classes');
        if (! is_array($this->oneToOne)) {
            $this->oneToOne = [];
        }
        if (! is_array($this->oneToMany)) {
            $this->oneToMany = [];
        }
        if (! is_array($this->manyToMany)) {
            $this->manyToMany = [];
        }

        $this->clearOneToOnes();

        //one order has many other things so we increase the ability to delete stuff
        $this->maximumNumberOfObjectsDeleted *= 25;

        $this->clearOneToMany();
    }

    protected function abandonnedCarts()
    {
        //ABANDONNED CARTS
        $clearMinutes = EcommerceConfig::get(EcommerceTaskCartCleanup::class, 'clear_minutes');
        $createdStepID = OrderStep::get_status_id_from_code('CREATED');
        $time = strtotime('-' . $clearMinutes . ' minutes');
        $where = '"StatusID" = ' . $createdStepID . " AND UNIX_TIMESTAMP(\"Order\".\"LastEdited\") < {$time} ";
        if ($this->neverDeleteIfLinkedToMember) {
            $userStatement = 'or have a user associated with it';
            $withoutMemberWhere = ' AND "Member"."ID" IS NULL ';
            $withMemberWhere = ' OR "Member"."ID" IS NOT NULL ';
            $memberDeleteNote = '(Carts linked to a member will NEVER be deleted)';
        } else {
            $userStatement = '';
            $withoutMemberWhere = '  ';
            $withMemberWhere = '';
            $memberDeleteNote = '(We will also delete carts in this category that are linked to a member)';
        }
        $oldCarts = Order::get()
            ->where($where . $withoutMemberWhere)
            ->this->sort($this->sort)
            ->limit($this->maximumNumberOfObjectsDeleted);
        $oldCarts = $oldCarts->leftJoin(Config::inst()->get(Member::class, 'table_name'), $this->joinShort);
        if ($oldCarts->count()) {
            $count = 0;
            if ($this->verbose) {
                $this->flush();
                $totalToDeleteSQLObject = DB::query(
                    '
                    SELECT COUNT(*)
                    FROM "Order"
                        ' . $this->leftMemberJoin . '
                    WHERE '
                        . $where
                        . $withoutMemberWhere
                    . ';'
                );
                $totalToDelete = $totalToDeleteSQLObject->value();
                DB::alteration_message('
                        <h2>Total number of abandonned carts: ' . $totalToDelete . '</h2>
                        <br /><b>number of records deleted at one time:</b> ' . $this->maximumNumberOfObjectsDeleted . '
                        <br /><b>Criteria:</b> last edited ' . $clearMinutes . ' (~' . round($clearMinutes / 60 / 24, 2) . " days)
                        minutes ago or more {$memberDeleteNote}", 'created');
            }
            foreach ($oldCarts as $oldCart) {
                ++$count;
                if ($this->verbose) {
                    $this->flush();
                    DB::alteration_message("{$count} ... deleting abandonned order #" . $oldCart->ID, 'deleted');
                }
                $this->deleteObject($oldCart);
            }
        } elseif ($this->verbose) {
            $this->flush();
            DB::alteration_message('There are no old carts', 'created');
        }
        if ($this->verbose) {
            $this->flush();
            $timeLegible = date('Y-m-d H:i:s', $time);
            $countCart = DB::query('SELECT COUNT("ID") FROM "Order" WHERE "StatusID" = ' . $createdStepID . ' ')->value();
            $countCartWithinTimeLimit = DB::query('
                SELECT COUNT("Order"."ID")
                FROM "Order"
                    ' . $this->leftMemberJoin . '
                WHERE "StatusID" = ' . $createdStepID . '
                AND
                (
                    UNIX_TIMESTAMP("Order"."LastEdited") >= ' . $time . '
                    ' . $withMemberWhere . '
                );
            ')->value();
            DB::alteration_message(
                "
                    {$countCart} Orders are still in the CREATED cart state (not submitted),
                    {$countCartWithinTimeLimit} of them are within the time limit (last edited after {$timeLegible})
                    " . $userStatement . ' so they are not deleted.',
                'created'
            );
        }
    }

    protected function emptyCarts()
    {
        //EMPTY ORDERS
        $clearMinutes = EcommerceConfig::get(EcommerceTaskCartCleanup::class, 'clear_minutes_empty_carts');
        $time = strtotime('-' . $clearMinutes . ' minutes');
        $where = "\"StatusID\" = 0 AND UNIX_TIMESTAMP(\"Order\".\"LastEdited\") < {$time} ";
        $oldCarts = Order::get()
            ->where($where)
            ->this->sort($this->sort)
            ->limit($this->maximumNumberOfObjectsDeleted);
        $oldCarts = $oldCarts->leftJoin(Config::inst()->get(Member::class, 'table_name'), $this->joinShort);
        if ($oldCarts->count()) {
            $count = 0;
            if ($this->verbose) {
                $this->flush();
                $totalToDelete = DB::query(
                    '
                    SELECT COUNT(*)
                    FROM "Order"
                        ' . $this->leftMemberJoin . '
                    WHERE '
                        . $where
                        . $withoutMemberWhere
                    . ';'
                )->value();
                DB::alteration_message('
                        <h2>Total number of empty carts: ' . $totalToDelete . '</h2>
                        <br /><b>number of records deleted at one time:</b> ' . $this->maximumNumberOfObjectsDeleted . "
                        <br /><b>Criteria:</b> there are no order items and
                        the order was last edited {$clearMinutes} minutes ago {$memberDeleteNote}", 'created');
            }
            foreach ($oldCarts as $oldCart) {
                ++$count;
                if ($this->verbose) {
                    $this->flush();
                    DB::alteration_message("{$count} ... deleting empty order #" . $oldCart->ID, 'deleted');
                }
                $this->deleteObject($oldCart);
            }
        }
        if ($this->verbose) {
            $this->flush();
            $timeLegible = date('Y-m-d H:i:s', $time);
            $countCart = DB::query(
                '
                SELECT COUNT("Order"."ID")
                FROM "Order"
                    ' . $this->leftMemberJoin . '
                WHERE "StatusID" = 0 '
            )->value();
            $countCartWithinTimeLimit = DB::query(
                '
                SELECT COUNT("Order"."ID")
                FROM "Order"
                    ' . $this->leftMemberJoin . '
                WHERE "StatusID" = 0 AND
                (
                    UNIX_TIMESTAMP("Order"."LastEdited") >= ' . $time . '
                    ' . $withMemberWhere . '
                )'
            )->value();
            DB::alteration_message(
                "
                    {$countCart} Orders are without status at all,
                    {$countCartWithinTimeLimit} are within the time limit (last edited after {$timeLegible})
                    " . $userStatement . 'so they are not deleted yet.',
                'created'
            );
        }
    }

    protected function clearOneToOnes()
    {

        /***********************************************
        //CLEANING ONE-TO-ONES
        ************************************************/
        if ($this->verbose) {
            $this->flush();
            DB::alteration_message('<h2>Checking one-to-one relationships</h2>.');
        }
        if (count($this->oneToOne)) {
            foreach ($this->oneToOne as $orderFieldName => $className) {
                $tableName = Config::inst()->get($className, 'table_name');
                if (! in_array($className, $this->oneToMany, true) && ! in_array($className, $this->manyToMany, true)) {
                    if ($this->verbose) {
                        $this->flush();
                        DB::alteration_message("looking for {$className} objects without link to order.");
                    }
                    $rows = DB::query("
                        SELECT \"{$tableName}\".\"ID\"
                        FROM \"{$tableName}\"
                            LEFT JOIN \"Order\"
                                ON \"Order\".\"{$orderFieldName}\" = \"{$tableName}\".\"ID\"
                        WHERE \"Order\".\"ID\" IS NULL
                        LIMIT 0, " . $this->maximumNumberOfObjectsDeleted);
                    //the code below is a bit of a hack, but because of the one-to-one relationship we
                    //want to check both sides....
                    $this->oneToOneIDArray = [];
                    if ($rows) {
                        foreach ($rows as $row) {
                            $this->oneToOneIDArray[$row['ID']] = $row['ID'];
                        }
                    }
                    if (count($this->oneToOneIDArray)) {
                        $unlinkedObjects = $className::get()
                            ->filter(['ID' => $this->oneToOneIDArray]);
                        if ($unlinkedObjects->count()) {
                            foreach ($unlinkedObjects as $unlinkedObject) {
                                if ($this->verbose) {
                                    $this->flush();
                                    DB::alteration_message('Deleting ' . $unlinkedObject->ClassName . ' with ID #' . $unlinkedObject->ID . ' because it does not appear to link to an order.', 'deleted');
                                }
                                $this->deleteObject($unlinkedObject);
                            }
                        } elseif ($this->verbose) {
                            $this->flush();
                            DB::alteration_message("No objects where found for {$className} even though there appear to be missing links.", 'created');
                        }
                    } elseif ($this->verbose) {
                        $this->flush();
                        DB::alteration_message("All references in Order to {$className} are valid.", 'created');
                    }
                    if ($this->verbose) {
                        $this->flush();
                        $countAll = DB::query("SELECT COUNT(\"ID\") FROM \"{$tableName}\"")->value();
                        $countUnlinkedOnes = DB::query("SELECT COUNT(\"{$tableName}\".\"ID\") FROM \"{$tableName}\" LEFT JOIN \"Order\" ON \"{$tableName}\".\"ID\" = \"Order\".\"{$orderFieldName}\" WHERE \"Order\".\"ID\" IS NULL")->value();
                        DB::alteration_message("In total there are {$countAll} {$className} ({$orderFieldName}), of which there are {$countUnlinkedOnes} not linked to an order. ", 'created');
                        if ($countUnlinkedOnes) {
                            DB::alteration_message("There should be NO {$orderFieldName} ({$className}) without link to Order - un error is suspected", 'deleted');
                        }
                    }
                }
            }
        }
    }

    protected function clearOneToMany()
    {
        if ($this->verbose) {
            $this->flush();
            DB::alteration_message('<h2>Checking one-to-many relationships</h2>.');
        }
        if (count($this->oneToMany)) {
            foreach ($this->oneToMany as $classWithOrderID => $classWithLastEdited) {
                $tableWithOrderID = Config::inst()->get($classWithOrderID, 'table_name');
                if (! in_array($classWithLastEdited, $this->oneToOne, true) && ! in_array($classWithLastEdited, $this->manyToMany, true)) {
                    if ($this->verbose) {
                        $this->flush();
                        DB::alteration_message('looking for ' . $tableWithOrderID . ' objects without link to order.');
                    }
                    $rows = DB::query("
                        SELECT \"{$tableWithOrderID}\".\"ID\"
                        FROM \"{$tableWithOrderID}\"
                            LEFT JOIN \"Order\"
                                ON \"Order\".\"ID\" = \"{$tableWithOrderID}\".\"OrderID\"
                        WHERE \"Order\".\"ID\" IS NULL
                        LIMIT 0, " . $this->maximumNumberOfObjectsDeleted);
                    $this->oneToManyIDArray = [];
                    if ($rows) {
                        foreach ($rows as $row) {
                            $this->oneToManyIDArray[$row['ID']] = $row['ID'];
                        }
                    }
                    if (count($this->oneToManyIDArray)) {
                        $unlinkedObjects = $classWithLastEdited::get()
                            ->filter(['ID' => $this->oneToManyIDArray]);
                        if ($unlinkedObjects->count()) {
                            foreach ($unlinkedObjects as $unlinkedObject) {
                                if ($this->verbose) {
                                    DB::alteration_message('Deleting ' . $unlinkedObject->ClassName . ' with ID #' . $unlinkedObject->ID . ' because it does not appear to link to an order.', 'deleted');
                                }
                                $this->deleteObject($unlinkedObject);
                            }
                        } elseif ($this->verbose) {
                            $this->flush();
                            DB::alteration_message("{$classWithLastEdited} objects could not be found even though they were referenced.", 'deleted');
                        }
                    } elseif ($this->verbose) {
                        $this->flush();
                        DB::alteration_message("All {$classWithLastEdited} objects have a reference to a valid order.", 'created');
                    }
                    if ($this->verbose) {
                        $this->flush();
                        $countAll = DB::query("SELECT COUNT(\"ID\") FROM \"{$tableWithOrderID}\"")->value();
                        $countUnlinkedOnes = DB::query("SELECT COUNT(\"{$tableWithOrderID}\".\"ID\") FROM \"{$tableWithOrderID}\" LEFT JOIN \"Order\" ON \"{$tableWithOrderID}\".\"OrderID\" = \"Order\".\"ID\" WHERE \"Order\".\"ID\" IS NULL")->value();
                        DB::alteration_message("In total there are {$countAll} {$classWithOrderID} ({$classWithLastEdited}), of which there are {$countUnlinkedOnes} not linked to an order. ", 'created');
                    }
                }
            }
        }
        if ($this->verbose) {
            $this->flush();
            DB::alteration_message('---------------- DONE --------------------');
        }
    }

    /**
     * delete an object
     */
    protected function deleteObject(DataObject $objectToDelete)
    {
        $objectToDelete->delete();
        $objectToDelete->destroy();
    }

    protected function flush()
    {
        if ((PHP_SAPI === 'cli')) {
            echo "\n";
        } else {
            ob_flush();
            flush();
        }
    }
}
