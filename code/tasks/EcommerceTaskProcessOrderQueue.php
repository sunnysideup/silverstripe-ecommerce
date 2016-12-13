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
class EcommerceTaskProcessOrderQueue extends BuildTask
{

    protected $doNotSendEmails = true;

    protected $limit = 5;

    protected $title = 'Process The Order Queue';

    protected $description = 'Go through order queue and try to finalise all the orders in it.';

    /**
     *@return int - number of carts destroyed
     **/
    public function run($request)
    {
        //as this may run every minute, we have to limit it to fifty seconds.
        set_time_limit(50);
        echo 'start at: '.microtime();
        //IMPORTANT!
        if ($this->doNotSendEmails) {
            Config::inst()->update('Email', 'send_all_emails_to', 'no-one@localhost');
            Email::set_mailer(new EcommerceTaskTryToFinaliseOrders_Mailer());
        }
        $queueObjectSingleton = Injector::inst()->get('OrderProcessQueue');
        $ordersinQueue = $queueObjectSingleton->OrdersToBeProcessed();

        $this->tryToFinaliseOrders($ordersinQueue);
        echo '<hr />';
        echo 'stop at: '.microtime();

    }


    protected function tryToFinaliseOrders($orders) {
        $queueObjectSingleton = Injector::inst()->get('OrderProcessQueue');
        $ordersinQueue = $queueObjectSingleton->OrdersToBeProcessed()->limit($this->limit);
        foreach($orders as $order) {
            echo '<hr />'.$order->ID;
            $queueObjectSingleton->process($order);
        }
    }

}
