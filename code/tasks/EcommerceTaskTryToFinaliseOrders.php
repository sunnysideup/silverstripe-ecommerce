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
class EcommerceTaskTryToFinaliseOrders extends BuildTask
{

    protected $doNotSendEmails = true;

    protected $limit = 100;

    protected $title = 'Try to finalise all orders WITHOUT SENDING EMAILS';

    protected $description = 'This task can be useful in moving a bunch of orders through the latest order step. It will only move orders if they can be moved through order steps.  You may need to run this task several times to move all orders.';

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

        //get limits
        $limit = null;
        if (isset($_GET['limit'])) {
            $limit = intval($_GET['limit']);
        }
        if (!intval($limit)) {
            $limit = $this->limit;
        }
        $startAt = null;
        if (isset($_GET['startat'])) {
            $startAt = intval($_GET['startat']);
        }
        if (!intval($startAt)) {
            $startAt = intval(Session::get('EcommerceTaskTryToFinaliseOrders'));
            if (!$startAt) {
                $startAt = 0;
            }
        }


        $this->tryToFinaliseOrders($ordersinQueue, $limit, $startAt);

        //find any other order that may need help ...
        $orderStatusLogClassName = 'OrderStatusLog';
        $submittedOrderStatusLogClassName = EcommerceConfig::get('OrderStatusLog', 'order_status_log_class_used_for_submitting_order');
        if ($submittedOrderStatusLogClassName) {
            $submittedStatusLog = $submittedOrderStatusLogClassName::get()->First();
            if ($submittedStatusLog) {
                $startAtOrderStep = OrderStep::get()->sort('Sort', 'DESC')->First();
                if ($startAtOrderStep) {
                    $joinSQL = 'INNER JOIN "" ON ';
                    $whereSQL = '"StatusID" <> '.$startAtOrderStep->ID.'';
                    $orders = Order::get()
                        ->where($whereSQL)
                        ->sort('ID', 'ASC')
                        ->exclude(array('ID' => $ordersinQueue->column('ID')))
                        ->innerJoin($orderStatusLogClassName, "\"$orderStatusLogClassName\".\"OrderID\" = \"Order\".\"ID\"")
                    $startAt = $this->tryToFinaliseOrders($orders, $limit, $startAt)
                } else {
                    DB::alteration_message('NO  order step.', 'deleted');
                }
            } else {
                DB::alteration_message('NO submitted order status log.', 'deleted');
            }
        } else {
            DB::alteration_message('NO EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order")', 'deleted');
        }
        if (Session::get('EcommerceTaskTryToFinaliseOrders')) {
            DB::alteration_message('WAIT: we are still moving more orders ... this page will automatically load the next lot in 5 seconds.', 'deleted');
            echo '<script type="text/javascript">window.setTimeout(function() {location.reload();}, 5000);</script>';
        }
    }

    protected function sendEmails()
    {
        $this->doNotSendEmails = false;
    }

    protected function tryToFinaliseOrders($orders, $limit, $startAt) {
        $orders = $orders->limit($limit, $startAt);
        if ($orders->limit()) {
            DB::alteration_message("<h1>Moving $limit Orders (starting from $startAt)</h1>");
            foreach ($orders as $order) {
                ++$startAt;
                Session::set('EcommerceTaskTryToFinaliseOrders', $startAt);
                $stepBefore = OrderStep::get()->byID($order->StatusID);
                try {
                    $order->tryToFinaliseOrder();
                } catch (Exception $e) {
                    DB::alteration_message($e, 'deleted');
                }
                $stepAfter = OrderStep::get()->byID($order->StatusID);
                if ($stepBefore) {
                    if($stepAfter){
                        if ($stepBefore->ID == $stepAfter->ID) {
                            DB::alteration_message('could not move Order '.$order->getTitle().', remains at <strong>'.$stepBefore->Name.'</strong>');
                        } else {
                            DB::alteration_message('Moving Order #'.$order->getTitle().' from <strong>'.$stepBefore->Name.'</strong> to <strong>'.$stepAfter->Name.'</strong>', 'created');
                        }
                    } else {
                        DB::alteration_message('Moving Order '.$order->getTitle().' from  <strong>'.$stepBefore->Name.'</strong> to <strong>unknown step</strong>', 'deleted');
                    }
                } elseif($stepAfter) {
                    DB::alteration_message('Moving Order '.$order->getTitle().' from <strong>unknown step</strong> to <strong>'.$stepAfter->Name.'</strong>', 'deleted');
                } else {
                    DB::alteration_message('Moving Order '.$order->getTitle().' from <strong>unknown step</strong> to <strong>unknown step</strong>', 'deleted');
                }
            }
        } else {
            Session::clear('EcommerceTaskTryToFinaliseOrders');
            DB::alteration_message('<br /><br /><br /><br /><h1>COMPLETED!</h1>All orders have been moved.', 'created');
        }

        return $startAt;
    }

}

class EcommerceTaskTryToFinaliseOrders_Mailer extends mailer
{
    /**
     * FAKE Send a plain-text email.
     *
     * @return bool
     */
    public function sendPlain($to, $from, $subject, $plainContent, $attachedFiles = false, $customheaders = false)
    {
        return true;
    }

    /**
     * FAKE Send a multi-part HTML email.
     *
     * @return bool
     */
    public function sendHTML($to, $from, $subject, $htmlContent, $attachedFiles = false, $customheaders = false, $plainContent = false)
    {
        return true;
    }
}
