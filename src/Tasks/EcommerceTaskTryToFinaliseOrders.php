<?php

namespace Sunnysideup\Ecommerce\Tasks;

use Exception;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\Email\Mailer;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Email\EcommerceDummyMailer;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderProcessQueue;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * @description: cleans up old (abandonned) carts...
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks

 **/
class EcommerceTaskTryToFinaliseOrders extends BuildTask
{
    protected $sendEmails = true;

    protected $limit = 1;

    protected $title = 'Try to finalise all orders - WILL SEND EMAILS';

    protected $description = '
        This task can be useful in moving a bunch of orders through the latest order step.
        It will only move orders if they can be moved through order steps.
        You may need to run this task several times to move all orders.';

    public function run($request)
    {
        //IMPORTANT!
        if (! $this->sendEmails) {
            Config::modify()->update(Email::class, 'send_all_emails_to', 'no-one@localhost');
            Injector::inst()->registerService(new EcommerceDummyMailer(), Mailer::class);
        }

        //get limits
        $limit = null;
        if (isset($_GET['limit'])) {
            $limit = intval($_GET['limit']);
        }
        if (! intval($limit)) {
            $limit = $this->limit;
        }
        $startAt = null;
        if (isset($_GET['startat'])) {
            $startAt = intval($_GET['startat']);
        }
        if (! intval($startAt)) {
            $startAt = intval(Controller::curr()->getRequest()->getSession()->get(EcommerceTaskTryToFinaliseOrders::class));
            if (! $startAt) {
                $startAt = 0;
            }
        }

        //we exclude all orders that are in the queue
        $queueObjectSingleton = Injector::inst()->get(OrderProcessQueue::class);
        $ordersinQueue = $queueObjectSingleton->AllOrdersInQueue();
        //find any other order that may need help ...

        $submittedOrderStatusLogClassName = EcommerceConfig::get(OrderStatusLog::class, 'order_status_log_class_used_for_submitting_order');
        $submittedOrderStatusLogTableName = EcommerceConfig::get(OrderStatusLog::class, 'table_name');
        if ($submittedOrderStatusLogClassName) {
            $submittedStatusLog = DataObject::get_one($submittedOrderStatusLogClassName);
            if ($submittedStatusLog) {
                $lastOrderStep = OrderStep::last_order_step();
                if ($lastOrderStep) {
                    if ($this->isCli()) {
                        $sort = 'RAND() ASC';
                    } else {
                        $sort = ['ID' => 'ASC'];
                    }
                    $ordersInQueueArray = $ordersinQueue ? $ordersinQueue->column('ID') : [];
                    if (is_array($ordersInQueueArray) && count($ordersInQueueArray)) {
                        //do nothing...
                    } else {
                        $ordersInQueueArray = [-1 => -1];
                    }
                    $orders = Order::get()
                        ->sort($sort)
                        ->where('StatusID <> ' . $lastOrderStep->ID)
                        ->exclude(['ID' => $ordersInQueueArray])
                        ->innerJoin(
                            'OrderStatusLog',
                            '"OrderStatusLog"."OrderID" = "Order"."ID"'
                        )
                        ->innerJoin(
                            $submittedOrderStatusLogTableName,
                            "\"${submittedOrderStatusLogTableName}\".\"ID\" = \"OrderStatusLog\".\"ID\""
                        );
                    $startAt = $this->tryToFinaliseOrders($orders, $limit, $startAt);
                } else {
                    DB::alteration_message('NO  order step.', 'deleted');
                }
            } else {
                DB::alteration_message('NO submitted order status log.', 'deleted');
            }
        } else {
            DB::alteration_message('NO EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order")', 'deleted');
        }

        if (Controller::curr()->getRequest()->getSession()->get(EcommerceTaskTryToFinaliseOrders::class)) {
            if (! $this->isCli()) {
                DB::alteration_message('WAIT: we are still moving more orders ... this page will automatically load the next lot in 5 seconds.', 'deleted');
                echo '<script type="text/javascript">window.setTimeout(function() {location.reload();}, 5000);</script>';
            }
        }
    }

    protected function tryToFinaliseOrders($orders, $limit, $startAt)
    {
        $orders = $orders->limit($limit, $startAt);
        if ($orders->count()) {
            DB::alteration_message("<h1>Moving ${limit} Orders (starting from ${startAt})</h1>");
            foreach ($orders as $order) {
                ++$startAt;

                Controller::curr()->getRequest()->getSession()->set(EcommerceTaskTryToFinaliseOrders::class, $startAt);
                $stepBefore = OrderStep::get()->byID($order->StatusID);
                try {
                    $order->tryToFinaliseOrder();
                } catch (Exception $e) {
                    DB::alteration_message($e, 'deleted');
                }
                $stepAfter = OrderStep::get()->byID($order->StatusID);
                if ($stepBefore) {
                    if ($stepAfter) {
                        if ($stepBefore->ID === $stepAfter->ID) {
                            DB::alteration_message('could not move Order ' . $order->getTitle() . ', remains at <strong>' . $stepBefore->Name . '</strong>');
                        } else {
                            DB::alteration_message('Moving Order #' . $order->getTitle() . ' from <strong>' . $stepBefore->Name . '</strong> to <strong>' . $stepAfter->Name . '</strong>', 'created');
                        }
                    } else {
                        DB::alteration_message('Moving Order ' . $order->getTitle() . ' from  <strong>' . $stepBefore->Name . '</strong> to <strong>unknown step</strong>', 'deleted');
                    }
                } elseif ($stepAfter) {
                    DB::alteration_message('Moving Order ' . $order->getTitle() . ' from <strong>unknown step</strong> to <strong>' . $stepAfter->Name . '</strong>', 'deleted');
                } else {
                    DB::alteration_message('Moving Order ' . $order->getTitle() . ' from <strong>unknown step</strong> to <strong>unknown step</strong>', 'deleted');
                }
            }
        } else {
            Controller::curr()->getRequest()->getSession()->clear(EcommerceTaskTryToFinaliseOrders::class);
            DB::alteration_message('<br /><br /><br /><br /><h1>COMPLETED!</h1>All orders have been moved.', 'created');
        }

        return $startAt;
    }

    protected function isCli()
    {
        return Director::is_cli();
    }
}
