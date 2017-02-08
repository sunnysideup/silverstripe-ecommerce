<?php


/**
 * @description: cleans up old (abandonned) carts...
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
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

        $submittedOrderStatusLogClassName = EcommerceConfig::get('OrderStatusLog', 'order_status_log_class_used_for_submitting_order');
        if ($submittedOrderStatusLogClassName) {
            $submittedStatusLog = $submittedOrderStatusLogClassName::get()->First();
            if ($submittedStatusLog) {
                $orderStepsIDArray = OrderStep::get()->column('ID');
                $orders = Order::get()
                    ->where('StatusID NOT IN (' . implode(',', $orderStepsIDArray).')')
                    ->innerJoin(
                        'OrderStatusLog',
                        "\"OrderStatusLog\".\"OrderID\" = \"Order\".\"ID\""
                    )
                    ->innerJoin(
                        $submittedOrderStatusLogClassName,
                        "\"$submittedOrderStatusLogClassName\".\"ID\" = \"OrderStatusLog\".\"ID\""
                    );
                if($orders->count()) {
                    foreach($orders as $order) {
                        DB::alteration_message('<a href="'.$order->CMSEditLink().'">'.$order->getTitle().'</a><br /><br />', 'deleted');
                        $order->Cancel();
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
