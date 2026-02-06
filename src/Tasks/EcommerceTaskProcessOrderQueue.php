<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataList;
use Sunnysideup\Ecommerce\Email\EcommerceDummyMailer;
use Sunnysideup\Ecommerce\Model\Process\OrderProcessQueue;
use Symfony\Component\Mailer\MailerInterface;

/**
 * @description:
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskProcessOrderQueue extends BuildTask
{
    protected $sendEmails = true;

    protected $limit = 1;

    protected $title = 'Process The Order Queue';

    protected $description = 'Go through order queue and try to finalise all the orders in it.';

    private static $segment = 'EcommerceTaskProcessOrderQueue';

    public function run($request)
    {
        //as this may run every minute, we have to limit it to fifty seconds.
        set_time_limit(50);
        $now = microtime(true);
        //IMPORTANT!
        if (! $this->sendEmails) {
            Config::modify()->set(Email::class, 'send_all_emails_to', 'no-one@localhost');
            Injector::inst()->registerService(new EcommerceDummyMailer(), MailerInterface::class);
        }
        $id = (int) $request?->getVar('id');
        $queueObjectSingleton = Injector::inst()->get(OrderProcessQueue::class);
        $ordersinQueue = $queueObjectSingleton->OrdersToBeProcessed($id);
        if (! $ordersinQueue->exists()) {
            echo 'No orders in queue';

            return;
        }
        echo '<h3>There are ' . $ordersinQueue->count() . ' in the queue, processing ' . $this->limit . ' now</h3>';
        if ($id !== 0) {
            echo '<h3>FORCING Order with ID: ' . $id . '</h3>';
            $ordersinQueue = $ordersinQueue->filter(['ID' => $id]);
        }
        $this->tryToFinaliseOrders($ordersinQueue);
        echo '<hr />';
        echo '<hr />';
        echo 'PROCESSED IN: ' . round(((microtime(true) - $now) / 1), 5) . ' seconds';
    }

    /**
     * @param DataList $orders orders to be processsed
     */
    protected function tryToFinaliseOrders(DataList $orders)
    {
        //limit orders
        $orders = $orders->limit($this->limit);
        //we sort randomly so it is less likely we get stuck with the same ones
        $orders = $orders->shuffle();

        $queueObjectSingleton = Injector::inst()->get(OrderProcessQueue::class);
        foreach ($orders as $order) {
            echo '<hr />Processing order: ' . $order->ID;
            $outcome = $queueObjectSingleton->process($order);
            if (true === $outcome) {
                echo '<br />... Order moved successfully.<hr />';
            } else {
                echo '<br />... ' . $outcome . '<hr />';
            }
        }
    }
}
