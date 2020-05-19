<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * @description: cleans up old (abandonned) carts...
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks

 **/
class EcommerceTaskOrdersWithoutOrderStep extends BuildTask
{
    protected $sendEmails = true;

    protected $limit = 1;

    protected $title = 'Orders without orderstep';

    protected $description = '
        Orders where the order step does not exist.';

    /**
     * @param SS_Request $request
     **/
    public function run($request)
    {
        $doCancel = $request->getVar('cancel');
        if (! $doCancel) {
            DB::alteration_message('You can add <strong>cancel</strong> as a getvar to cancel and archive all orders.', 'edited');
        }
        $submittedOrderStatusLogClassName = EcommerceConfig::get(OrderStatusLog::class, 'order_status_log_class_used_for_submitting_order');
        if ($submittedOrderStatusLogClassName) {
            $submittedStatusLog = DataObject::get_one($submittedOrderStatusLogClassName);
            if ($submittedStatusLog) {
                $orderStepsIDArray = OrderStep::get()->column('ID');
                $orders = Order::get()
                    ->where('StatusID NOT IN (' . implode(',', $orderStepsIDArray) . ')')
                    ->innerJoin(
                        OrderStatusLog::class,
                        '"OrderStatusLog"."OrderID" = "Order"."ID"'
                    )
                    ->innerJoin(
                        $submittedOrderStatusLogClassName,
                        "\"${submittedOrderStatusLogClassName}\".\"ID\" = \"OrderStatusLog\".\"ID\""
                    );
                if ($orders->count()) {
                    foreach ($orders as $order) {
                        $archivingNow = 'Open order to rectify.';
                        if ($doCancel) {
                            $archivingNow = 'This order has been cancelled and archived.';
                            $order->Cancel();
                        }
                        DB::alteration_message(
                            '<a href="' . $order->CMSEditLink() . '">' . $order->getTitle() . '</a><br />' . $archivingNow . '<br /><br />',
                            'deleted'
                        );
                    }
                } else {
                    DB::alteration_message('There are no orders without a valid order step.', 'created');
                }
            } else {
                DB::alteration_message('NO submitted order status log.', 'deleted');
            }
        } else {
            DB::alteration_message('NO EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order")', 'deleted');
        }
    }
}
