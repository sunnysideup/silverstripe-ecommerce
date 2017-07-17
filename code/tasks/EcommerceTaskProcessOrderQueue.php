<?php


/**
 * @description:
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class EcommerceTaskProcessOrderQueue extends BuildTask
{
    protected $sendEmails = true;

    protected $limit = 1;

    protected $title = 'Process The Order Queue';

    protected $description = 'Go through order queue and try to finalise all the orders in it.';

    /**
     *@return int - number of carts destroyed
     **/
    public function run($request)
    {
        //as this may run every minute, we have to limit it to fifty seconds.
        set_time_limit(50);
        $now = microtime(true);
        //IMPORTANT!
        if ( ! $this->sendEmails) {
            Config::inst()->update('Email', 'send_all_emails_to', 'no-one@localhost');
            Email::set_mailer(new Ecommerce_Dummy_Mailer());
        }
        $id = intval($request->getVar('id')) - 0;
        $queueObjectSingleton = Injector::inst()->get('OrderProcessQueue');
        $ordersinQueue = $queueObjectSingleton->OrdersToBeProcessed($id);
        if($ordersinQueue->count() == 0) {
            echo 'No orders in queue';
            return;
        }
        echo '<h3>There are '.$ordersinQueue->count().' in the queue, processing '.$this->limit.' now</h3>';
        if($id) {
            echo '<h3>FORCING Order with ID: '.$id.'</h3>';
            $ordersinQueue = $ordersinQueue->filter(array('ID' => $id));
        }
        $this->tryToFinaliseOrders($ordersinQueue);
        echo '<hr />';
        echo '<hr />';
        echo 'PROCESSED IN: '.round(((microtime(true) - $now) / 1), 5).' seconds';
    }


    /**
     *
     * @param  DataList $orders orders to be processsed.
     */
    protected function tryToFinaliseOrders($orders)
    {
        //limit orders
        $orders = $orders->limit($this->limit);
        //we sort randomly so it is less likely we get stuck with the same ones
        $orders = $orders->sort('RAND()');
        $queueObjectSingleton = Injector::inst()->get('OrderProcessQueue');
        foreach ($orders as $order) {
            echo '<hr />Processing order: '.$order->ID;
            $outcome = $queueObjectSingleton->process($order);
            if($outcome === true) {
                echo '<br />... Order moved successfully.<hr />';
            } else {
                echo '<br />... '.$outcome.'<hr />';
            }
        }
    }
}
