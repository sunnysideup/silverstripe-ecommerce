<?php

namespace Sunnysideup\Ecommerce\Tasks;

use Exception;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Email\EcommerceDummyMailer;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderProcessQueue;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

    protected string $title = 'Try to finalise all orders - WILL SEND EMAILS';

    protected static string $commandName = 'ecommerce:try-to-finalise-orders';

    protected static string $description = 'This task can be useful in moving a bunch of orders through the latest order step. It will only move orders if they can be moved through order steps. You may need to run this task several times to move all orders.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        //IMPORTANT!
        if (! $this->sendEmails) {
            Config::modify()->set(Email::class, 'send_all_emails_to', 'no-one@localhost');
            Injector::inst()->registerService(new EcommerceDummyMailer(), MailerInterface::class);
        }

        //get limits
        $limit = $input->getOption('limit');
        if (!$limit) {
            $limit = $this->limit;
        }

        $startAt = $input->getOption('startat');
        if (!$startAt) {
            $startAt = 0;
        }

        //we exclude all orders that are in the queue
        $queueObjectSingleton = Injector::inst()->get(OrderProcessQueue::class);
        $ordersinQueue = $queueObjectSingleton->AllOrdersInQueue();
        //find any other order that may need help ...

        $sort = DB::get_conn()->random();
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
        $output->writeForHtml(sprintf('<h1>In total there, are %d Orders to move</h1>', $orders->count()));
        $this->tryToFinaliseOrders($orders, $limit, $startAt, $output);

        return Command::SUCCESS;
    }

    protected function tryToFinaliseOrders($orders, $limit, $startAt, PolyOutput $output)
    {
        $orders = $orders->limit($limit, $startAt);
        if ($orders->exists()) {
            $output->writeForHtml(sprintf('<h1>Moving %s Orders (starting from %s)</h1>', $limit, $startAt));
            foreach ($orders as $order) {
                if ($order->IsSubmitted()) {
                    $stepBefore = OrderStep::get_by_id($order->StatusID);

                    try {
                        $order->tryToFinaliseOrder();
                    } catch (Exception $exception) {
                        $output->writeln('ERROR: ' . $exception->getMessage());
                    }

                    $stepAfter = OrderStep::get_by_id($order->StatusID);
                    if ($stepBefore) {
                        if ($stepAfter) {
                            if ($stepBefore->ID === $stepAfter->ID) {
                                $output->writeForHtml('could not move Order ' . $order->getTitle() . ', remains at <strong>' . $stepBefore->Name . '</strong>');
                            } else {
                                $output->writeForHtml('Moving Order #' . $order->getTitle() . ' from <strong>' . $stepBefore->Name . '</strong> to <strong>' . $stepAfter->Name . '</strong>');
                            }
                        } else {
                            $output->writeForHtml('Moving Order ' . $order->getTitle() . ' from  <strong>' . $stepBefore->Name . '</strong> to <strong>unknown step</strong>');
                        }
                    } elseif ($stepAfter) {
                        $output->writeForHtml('Moving Order ' . $order->getTitle() . ' from <strong>unknown step</strong> to <strong>' . $stepAfter->Name . '</strong>');
                    } else {
                        $output->writeForHtml('Moving Order ' . $order->getTitle() . ' from <strong>unknown step</strong> to <strong>unknown step</strong>');
                    }
                } else {
                    $output->writeln('ERROR: Moving Order ' . $order->getTitle() . ' IS NOT SUBMITTED YET');
                }

                ++$startAt;
            }
        } else {
            $output->writeForHtml('<br /><br /><br /><br /><h1>COMPLETED!</h1>All orders have been moved.');
        }

        return $startAt;
    }

    public function getOptions(): array
    {
        return [
            new InputOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Maximum number of orders to process'),
            new InputOption('startat', 's', InputOption::VALUE_OPTIONAL, 'Start at this order offset'),
        ];
    }
}
