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
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Email\EcommerceDummyMailer;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderProcessQueue;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Symfony\Component\Mailer\MailerInterface;

/**
 * @description: cleans up old (abandonned) carts...
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskTryToFinaliseOrders extends BuildTask
{
    protected $sendEmails = true;

    protected $limit = 1;

    protected $title = 'Try to finalise all orders - WILL SEND EMAILS';

    private static $segment = 'EcommerceTaskTryToFinaliseOrders';

    protected $description = '
        This task can be useful in moving a bunch of orders through the latest order step.
        It will only move orders if they can be moved through order steps.
        You may need to run this task several times to move all orders.';

    public function run($request)
    {
        //IMPORTANT!
        if (! $this->sendEmails) {
            Config::modify()->set(Email::class, 'send_all_emails_to', 'no-one@localhost');
            Injector::inst()->registerService(new EcommerceDummyMailer(), MailerInterface::class);
        }

        //get limits
        $limit = null;
        if (isset($_GET['limit'])) {
            $limit = (int) $_GET['limit'];
        }
        if (! (int) $limit) {
            $limit = $this->limit;
        }
        $startAt = null;
        if (isset($_GET['startat'])) {
            $startAt = (int) $_GET['startat'];
        }
        if (! (int) $startAt) {
            $startAt = $this->getStart();
            if (! $startAt) {
                $startAt = 0;
            }
        }

        //we exclude all orders that are in the queue
        $queueObjectSingleton = Injector::inst()->get(OrderProcessQueue::class);
        $ordersinQueue = $queueObjectSingleton->AllOrdersInQueue();
        //find any other order that may need help ...

        $sort = $this->isCli() ? DB::get_conn()->random() : 'Order.ID ASC';
        $ordersInQueueArray = $ordersinQueue ? $ordersinQueue->columnUnique() : [];
        if (is_array($ordersInQueueArray) && count($ordersInQueueArray)) {
            //do nothing...
        } else {
            $ordersInQueueArray = ArrayMethods::filter_array([]);
        }
        $orders = Order::get()
            ->orderBy($sort)
            ->filter(['StatusID' => OrderStep::admin_manageable_steps()->columnUnique()])
            ->exclude(['ID' => $ordersInQueueArray]);
        ;
        DB::alteration_message("<h1>In total there, are {$orders->count()} Orders to move</h1>");
        $startAt = $this->tryToFinaliseOrders($orders, $limit, $startAt);
        if (! $this->isCli()) {
            if ($this->getStart()) {
                DB::alteration_message('WAIT: we are still moving more orders ... this page will automatically load the next lot in 5 seconds.', 'deleted');
                echo '<script type="text/javascript">window.setTimeout(function() {location.reload();}, 5000);</script>';
            }
        }
    }

    protected function tryToFinaliseOrders($orders, $limit, $startAt)
    {
        $orders = $orders->limit($limit, $startAt);
        if ($orders->exists()) {
            DB::alteration_message("<h1>Moving {$limit} Orders (starting from {$startAt})</h1>");
            foreach ($orders as $order) {
                if ($order->IsSubmitted()) {
                    $stepBefore = OrderStep::get_by_id($order->StatusID);

                    try {
                        $order->tryToFinaliseOrder();
                    } catch (Exception $exception) {
                        DB::alteration_message($exception, 'deleted');
                    }
                    $stepAfter = OrderStep::get_by_id($order->StatusID);
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
                } else {
                    DB::alteration_message('ERROR: Moving Order ' . $order->getTitle() . ' IS NOT SUBMITTED YET', 'deleted');
                }
                // completed - so can move on
                ++$startAt;
                $this->setStart($startAt);
            }
        } else {
            $this->clearStart();
            DB::alteration_message('<br /><br /><br /><br /><h1>COMPLETED!</h1>All orders have been moved.', 'created');
        }

        return $startAt;
    }

    protected function isCli()
    {
        return Director::is_cli();
    }

    protected function setStart(int $startAt)
    {
        if (!Director::is_cli()) {
            Controller::curr()->getRequest()->getSession()->set('EcommerceTaskTryToFinaliseOrdersStartAt', $startAt);
        }
    }

    protected function getStart(): int
    {
        if (!Director::is_cli()) {
            return (int) Controller::curr()->getRequest()->getSession()->get('EcommerceTaskTryToFinaliseOrdersStartAt');
        }
        return 0;
    }
    protected function clearStart()
    {
        if (!Director::is_cli()) {
            return Controller::curr()->getRequest()->getSession()->clear('EcommerceTaskTryToFinaliseOrdersStartAt');
        }
    }
}
