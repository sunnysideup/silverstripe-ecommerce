<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * After a bug in the saving of orders in the CMS
 * This "fixer"  was introduced to fix older orders
 * without a submission record.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskArchiveAllOrdersWithItems extends BuildTask
{
    protected $title = 'Archive all orders with order items and payment and add a submit record.';

    protected $description = "
        This task moves all orders to the 'Archived' (last) Order Step without running any of the tasks in between.
        NB: It also adds a submit record.
        This task is basically for orders that never got archived.
    ";

    private static $payment_table = 'EcommercePayment';

    public function run($request)
    {
        set_time_limit(1200);
        //IMPORTANT!
        $lastOrderStep = DataObject::get_one(
            OrderStep::class,
            '',
            $cache = true,
            ['Sort' => 'DESC']
        );
        if ($lastOrderStep) {
            $joinSQL = '
            INNER JOIN "OrderAttribute" ON "Order"."ID" = "OrderAttribute"."OrderID"
            INNER JOIN "OrderItem" ON "OrderItem"."ID" = "OrderAttribute"."ID"
            INNER JOIN "' . self::$payment_table . '" ON "' . self::$payment_table . '"."OrderID" = "Order"."ID"
            ';
            $whereSQL = 'WHERE "StatusID" <> ' . $lastOrderStep->ID . ' ';
            $count = DB::query("
                SELECT COUNT (\"Order\".\"ID\")
                FROM \"Order\"
                {$joinSQL}
                {$whereSQL}
            ")->value();
            DB::query("
                UPDATE \"Order\"
                {$joinSQL}
                SET \"Order\".\"StatusID\" = " . $lastOrderStep->ID . "
                {$whereSQL}
            ");
            if ($count) {
                DB::alteration_message("NOTE: {$count} records were updated.", 'created');
            } else {
                DB::alteration_message('No records were updated.');
            }
        } else {
            DB::alteration_message('Could not find the last order step.', 'deleted');
        }
        $this->createSubmissionLogForArchivedOrders();
    }

    public function getOrdersForCreateSubmissionLogForArchivedOrders($lastOrderStep, $orderStatusLogTableName, $offset)
    {
        return Order::get()
            ->filter(['StatusID' => $lastOrderStep->ID])
            ->leftJoin($orderStatusLogTableName, "\"{$orderStatusLogTableName}\".\"OrderID\" = \"Order\".\"ID\"")
            ->where("\"{$orderStatusLogTableName}\".\"ID\" IS NULL")
            ->limit(100, $offset)
        ;
    }

    protected function createSubmissionLogForArchivedOrders()
    {
        $lastOrderStep = DataObject::get_one(
            OrderStep::class,
            '',
            $cache = true,
            ['Sort' => 'DESC']
        );
        $submissionLogClassName = EcommerceConfig::get(OrderStatusLog::class, 'order_status_log_class_used_for_submitting_order');
        $obj = $submissionLogClassName::create();

        if (! is_a($obj, EcommerceConfigClassNames::getName(OrderStatusLog::class))) {
            user_error('EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order") refers to a class that is NOT an instance of OrderStatusLog');
        }
        $orderStatusLogTableName = OrderStatusLog::getSchema()->tableName(OrderStatusLog::class);
        $offset = 0;
        $orders = $this->getOrdersForCreateSubmissionLogForArchivedOrders($lastOrderStep, $orderStatusLogTableName, $offset);
        while ($orders->exists()) {
            foreach ($orders as $order) {
                $isSubmitted = $submissionLogClassName::get()
                    ->Filter(['OrderID' => $order->ID])
                    ->count()
                ;
                if (! $isSubmitted) {
                    $obj = $submissionLogClassName::create();

                    $obj->OrderID = $order->ID;
                    //it is important we add this here so that we can save the 'submitted' version.
                    //this is particular important for the Order Item Links.
                    $obj->write();
                    $obj->OrderAsHTML = $order->ConvertToHTML();
                    $obj->write();
                    DB::alteration_message('creating submission log for Order #' . $obj->OrderID, 'created');
                }
            }
            $offset += 100;
            $orders = $this->getOrdersForCreateSubmissionLogForArchivedOrders($lastOrderStep, $orderStatusLogTableName, $offset);
        }
    }
}
