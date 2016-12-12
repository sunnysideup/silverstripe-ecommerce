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
        //IMPORTANT!
        if ($this->doNotSendEmails) {
            Config::inst()->update('Email', 'send_all_emails_to', 'no-one@localhost');
            Email::set_mailer(new EcommerceTaskTryToFinaliseOrders_Mailer());
        }

        $ordersinQueue = OrderProcessQueue::OrdersToBeProcessed()->limit($this->limit);

        $this->tryToFinaliseOrders($ordersinQueue);

    }


    protected function tryToFinaliseOrders($orders, $limit, $startAt) {
        $queueObject = Injector::inst()->get('OrderProcessQueue');
        foreach($orders as $order) {
            $order->tryToFinaliseOrders();
            $queueObject->removeOrderFromQueue($order);

        }
    }

}
