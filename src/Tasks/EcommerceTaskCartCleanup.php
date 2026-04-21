<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Security\Member;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskCartCleanup extends BuildTask
{
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

    /**
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
     * @var string
     */
    protected $withoutMemberWhere = '';

    /**
     * @var string
     */
    protected $memberDeleteNote = '';

    /**
     * @var string
     */
    protected $userStatement = '';

    /**
     * @var string
     */
    protected $withMemberWhere = '';

    /**
     * @var string
     */
    protected $leftMemberJoin = '';

    protected static string $commandName = 'ecommerce-cart-cleanup';

    protected string $title = 'Clear old carts';

    protected static string $description = 'Deletes abandonned carts. Use --limit option to set the number of records to be deleted in one load.';

    /**
     * @var int
     */
    private static $clear_minutes_empty_carts = 120;

    /**
     * one week.
     *
     * @var int
     */
    private static $clear_minutes = 10080;

    /**
     * two weeks.
     *
     * @var int
     */
    private static $clear_minutes_with_member = 20160;

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
     * run in verbose mode.
     * @deprecated Use execute() method instead
     */
    public static function run_on_demand()
    {
        // @TODO (SS6 upgrade) - refactor calls to this method
        $obj = new self();
        $obj->verbose = true;
        // This method is deprecated and should be refactored
    }

    /**
     * runs the task without output.
     * @deprecated Use execute() method instead
     */
    public function runSilently()
    {
        // @TODO (SS6 upgrade) - refactor calls to this method
        $this->verbose = false;
        // This method is deprecated and should be refactored
    }

    public function getOptions(): array
    {
        return [
            new InputOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Maximum number of objects to delete'),
            new InputOption('purge', 'p', InputOption::VALUE_NONE, 'Purge carts linked to members as well'),
            new InputOption('verbose', 'v', InputOption::VALUE_NONE, 'Verbose output'),
        ];
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        if ($this->verbose || $input->getOption('verbose')) {
            $this->verbose = true;
            $this->flushOutput($output);
            $countAll = DB::query('SELECT COUNT("ID") FROM "Order"')->value();
            $output->writeForHtml(sprintf('<h2>deleting empty and abandonned carts (total cart count = %s)</h2>.', $countAll));
        }

        $this->neverDeleteIfLinkedToMember = EcommerceConfig::get(EcommerceTaskCartCleanup::class, 'never_delete_if_linked_to_member');
        $this->maximumNumberOfObjectsDeleted = EcommerceConfig::get(EcommerceTaskCartCleanup::class, 'maximum_number_of_objects_deleted');

        //LIMITS ...
        if ($input->getOption('limit')) {
            $this->maximumNumberOfObjectsDeleted = (int) $input->getOption('limit');
        }

        if ($input->getOption('purge')) {
            $this->neverDeleteIfLinkedToMember = false;
        }

        //this->sort
        $this->sort = '"Order"."ID" ASC';

        //join
        $this->leftMemberJoin = 'LEFT JOIN Member ON "Member"."ID" = "Order"."MemberID"';
        $this->joinShort = '"Member"."ID" = "Order"."MemberID"';

        $this->userStatement = '';
        $this->withoutMemberWhere = '  ';
        $this->withMemberWhere = '';
        $this->memberDeleteNote = '(We will also delete carts in this category that are linked to a member)';
        if ($this->neverDeleteIfLinkedToMember) {
            $this->userStatement = 'or have a user associated with it';
            $this->withoutMemberWhere = ' AND "Member"."ID" IS NULL ';
            $this->withMemberWhere = ' OR "Member"."ID" IS NOT NULL ';
            $this->memberDeleteNote = '(Carts linked to a member will NEVER be deleted)';
        }

        $this->abandonnedCarts($output);
        $this->emptyCarts($output);

        $this->oneToMany = (array) EcommerceConfig::get(EcommerceTaskCartCleanup::class, 'one_to_many_classes');
        $this->oneToOne = (array) EcommerceConfig::get(EcommerceTaskCartCleanup::class, 'one_to_one_classes');
        $this->manyToMany = (array) EcommerceConfig::get(EcommerceTaskCartCleanup::class, 'many_to_many_classes');

        $this->clearOneToOnes($output);

        //one order has many other things so we increase the ability to delete stuff
        $this->maximumNumberOfObjectsDeleted *= 25;

        $this->clearOneToMany($output);

        return Command::SUCCESS;
    }

    protected function abandonnedCarts(PolyOutput $output)
    {
        //ABANDONNED CARTS
        $createdStepID = OrderStep::get_status_id_from_code('CREATED');

        $clearMinutesWithoutMember = EcommerceConfig::get(EcommerceTaskCartCleanup::class, 'clear_minutes');
        $timeWithoutMember = strtotime('-' . $clearMinutesWithoutMember . ' minutes');
        $whereWithoutMember = '"StatusID" = ' . $createdStepID . sprintf(' AND UNIX_TIMESTAMP("Order"."LastEdited") < %s ', $timeWithoutMember) . $this->withoutMemberWhere;

        $clearMinutesWithMember = EcommerceConfig::get(EcommerceTaskCartCleanup::class, 'clear_minutes_with_member');
        $timeWithMember = strtotime('-' . $clearMinutesWithMember . ' minutes');
        $whereWithMember = '"StatusID" = ' . $createdStepID . sprintf(' AND UNIX_TIMESTAMP("Order"."LastEdited") < %s ', $timeWithMember);

        $where = '(' . $whereWithoutMember . ') OR (' . $whereWithMember . ')';
        $oldCarts = $this->getOldCarts($where);
        if ($oldCarts->exists()) {
            $count = 0;
            if ($this->verbose) {
                $this->flushOutput($output);
                $totalToDeleteSQLObject = DB::query(
                    '
                    SELECT COUNT(*)
                    FROM "Order"
                        ' . $this->leftMemberJoin . '
                    WHERE '
                        . $where
                    . ';'
                );
                $totalToDelete = $totalToDeleteSQLObject->value();
                $output->writeForHtml('
                        <h2>Total number of abandonned carts: ' . $totalToDelete . '</h2>
                        <br /><b>number of records deleted at one time:</b> ' . $this->maximumNumberOfObjectsDeleted . '
                        <br /><b>Criteria:</b> last edited ' . $clearMinutesWithoutMember . ' (~' . round($clearMinutesWithoutMember / 60 / 24, 2) . (' days)
                        minutes ago or more ' . $this->memberDeleteNote));
            }

            foreach ($oldCarts as $oldCart) {
                ++$count;
                if ($this->verbose) {
                    $this->flushOutput($output);
                    $output->writeln($count . ' ... deleting abandonned order #' . $oldCart->ID);
                }

                $this->deleteObject($oldCart);
            }
        } elseif ($this->verbose) {
            $this->flushOutput($output);
            $output->writeln('There are no old carts');
        }

        if ($this->verbose) {
            $this->flushOutput($output);
            $timeLegible = date('Y-m-d H:i:s', $timeWithoutMember);
            $countCart = DB::query('SELECT COUNT("ID") FROM "Order" WHERE "StatusID" = ' . $createdStepID . ' ')->value();
            $countCartWithinTimeLimit = DB::query('
                SELECT COUNT("Order"."ID")
                FROM "Order"
                WHERE
                    "StatusID" = ' . $createdStepID . ' AND ' . '
                    UNIX_TIMESTAMP("Order"."LastEdited") >= ' . $timeWithoutMember . ';
            ')->value();
            $output->writeln(
                "
                    {$countCart} Orders are still in the CREATED cart state (not submitted),
                    {$countCartWithinTimeLimit} of them are within the time limit (last edited after {$timeLegible})
                    " . $this->userStatement . ' so they are not deleted.'
            );
        }
    }

    protected function emptyCarts(PolyOutput $output)
    {
        //EMPTY ORDERS
        $clearMinutes = EcommerceConfig::get(EcommerceTaskCartCleanup::class, 'clear_minutes_empty_carts');
        $time = strtotime('-' . $clearMinutes . ' minutes');
        $where = sprintf('"StatusID" = 0 AND UNIX_TIMESTAMP("Order"."LastEdited") < %s ', $time);
        $oldCarts = $this->getOldCarts($where);

        if ($oldCarts->exists()) {
            $count = 0;
            if ($this->verbose) {
                $this->flushOutput($output);
                $totalToDelete = DB::query(
                    '
                    SELECT COUNT(*)
                    FROM "Order"
                        ' . $this->leftMemberJoin . '
                    WHERE '
                        . $where
                        . $this->withoutMemberWhere
                    . ';'
                )->value();
                $output->writeForHtml('
                        <h2>Total number of empty carts: ' . $totalToDelete . '</h2>
                        <br /><b>number of records deleted at one time:</b> ' . $this->maximumNumberOfObjectsDeleted . "
                        <br /><b>Criteria:</b> there are no order items and
                        the order was last edited {$clearMinutes} minutes ago {$this->memberDeleteNote}");
            }

            foreach ($oldCarts as $oldCart) {
                ++$count;
                if ($this->verbose) {
                    $this->flushOutput($output);
                    $output->writeln($count . ' ... deleting empty order #' . $oldCart->ID);
                }

                $this->deleteObject($oldCart);
            }
        }

        if ($this->verbose) {
            $this->flushOutput($output);
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
                    ' . $this->withMemberWhere . '
                )'
            )->value();
            $output->writeln(
                "
                    {$countCart} Orders are without status at all,
                    {$countCartWithinTimeLimit} are within the time limit (last edited after {$timeLegible})
                    " . $this->userStatement . 'so they are not deleted yet.'
            );
        }
    }

    protected function clearOneToOnes(PolyOutput $output)
    {
        // //CLEANING ONE-TO-ONES
        if ($this->verbose) {
            $this->flushOutput($output);
            $output->writeForHtml('<h2>Checking one-to-one relationships</h2>.');
        }

        foreach ($this->oneToOne as $orderFieldName => $className) {
            $tableName = Config::inst()->get($className, 'table_name');
            if (! in_array($className, $this->oneToMany, true) && ! in_array($className, $this->manyToMany, true)) {
                if ($this->verbose) {
                    $this->flushOutput($output);
                    $output->writeln(sprintf('looking for %s objects without link to order.', $className));
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

                if ([] !== $this->oneToOneIDArray) {
                    $unlinkedObjects = $className::get()
                        ->filter(['ID' => $this->oneToOneIDArray])
                    ;
                    if ($unlinkedObjects->exists()) {
                        foreach ($unlinkedObjects as $unlinkedObject) {
                            if ($this->verbose) {
                                $this->flushOutput($output);
                                $output->writeln('Deleting ' . $unlinkedObject->ClassName . ' with ID #' . $unlinkedObject->ID . ' because it does not appear to link to an order.');
                            }

                            $this->deleteObject($unlinkedObject);
                        }
                    } elseif ($this->verbose) {
                        $this->flushOutput($output);
                        $output->writeln(sprintf('No objects where found for %s even though there appear to be missing links.', $className));
                    }
                } elseif ($this->verbose) {
                    $this->flushOutput($output);
                    $output->writeln(sprintf('All references in Order to %s are valid.', $className));
                }

                if ($this->verbose) {
                    $this->flushOutput($output);
                    $countAll = DB::query(sprintf('SELECT COUNT("ID") FROM "%s"', $tableName))->value();
                    $countUnlinkedOnes = DB::query(sprintf('SELECT COUNT("%s"."ID") FROM "%s" LEFT JOIN "Order" ON "%s"."ID" = "Order"."%s" WHERE "Order"."ID" IS NULL', $tableName, $tableName, $tableName, $orderFieldName))->value();
                    $output->writeln(sprintf('In total there are %s %s (%s), of which there are %s not linked to an order. ', $countAll, $className, $orderFieldName, $countUnlinkedOnes));
                    if ($countUnlinkedOnes) {
                        $output->writeln(sprintf('There should be NO %s (%s) without link to Order - un error is suspected', $orderFieldName, $className));
                    }
                }
            }
        }
    }

    protected function clearOneToMany(PolyOutput $output)
    {
        if ($this->verbose) {
            $this->flushOutput($output);
            $output->writeForHtml('<h2>Checking one-to-many relationships</h2>.');
        }

        foreach ($this->oneToMany as $classWithOrderID => $classWithLastEdited) {
            $tableWithOrderID = Config::inst()->get($classWithOrderID, 'table_name');
            if (! in_array($classWithLastEdited, $this->oneToOne, true) && ! in_array($classWithLastEdited, $this->manyToMany, true)) {
                if ($this->verbose) {
                    $this->flushOutput($output);
                    $output->writeln('looking for ' . $tableWithOrderID . ' objects without link to order.');
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

                if ([] !== $this->oneToManyIDArray) {
                    $unlinkedObjects = $classWithLastEdited::get()
                        ->filter(['ID' => $this->oneToManyIDArray])
                    ;
                    if ($unlinkedObjects->exists()) {
                        foreach ($unlinkedObjects as $unlinkedObject) {
                            if ($this->verbose) {
                                $output->writeln('Deleting ' . $unlinkedObject->ClassName . ' with ID #' . $unlinkedObject->ID . ' because it does not appear to link to an order.');
                            }

                            $this->deleteObject($unlinkedObject);
                        }
                    } elseif ($this->verbose) {
                        $this->flushOutput($output);
                        $output->writeln($classWithLastEdited . ' objects could not be found even though they were referenced.');
                    }
                } elseif ($this->verbose) {
                    $this->flushOutput($output);
                    $output->writeln(sprintf('All %s objects have a reference to a valid order.', $classWithLastEdited));
                }

                if ($this->verbose) {
                    $this->flushOutput($output);
                    $countAll = DB::query(sprintf('SELECT COUNT("ID") FROM "%s"', $tableWithOrderID))->value();
                    $countUnlinkedOnes = DB::query(sprintf('SELECT COUNT("%s"."ID") FROM "%s" LEFT JOIN "Order" ON "%s"."OrderID" = "Order"."ID" WHERE "Order"."ID" IS NULL', $tableWithOrderID, $tableWithOrderID, $tableWithOrderID))->value();
                    $output->writeln(sprintf('In total there are %s %s (%s), of which there are %s not linked to an order. ', $countAll, $classWithOrderID, $classWithLastEdited, $countUnlinkedOnes));
                }
            }
        }

        if ($this->verbose) {
            $this->flushOutput($output);
            $output->writeln('---------------- DONE --------------------');
        }
    }

    /**
     * delete an object.
     */
    protected function deleteObject(DataObject $objectToDelete)
    {
        $objectToDelete->delete();
        $objectToDelete->destroy();
    }

    protected function flushOutput(PolyOutput $output)
    {
        // Flush handled by PolyOutput automatically
        // No need to manually flush in SS6
    }

    protected function getOldCarts($where): DataList
    {
        $oldCarts = Order::get()
            ->where($where)
            ->limit($this->maximumNumberOfObjectsDeleted)
        ;
        $oldCarts = is_array($this->sort) ? $oldCarts->sort($this->sort) : $oldCarts->orderBy($this->sort);
        return $oldCarts->leftJoin(Config::inst()->get(Member::class, 'table_name'), $this->joinShort);
    }
}
