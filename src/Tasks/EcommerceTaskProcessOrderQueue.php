<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataList;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Email\EcommerceDummyMailer;
use Sunnysideup\Ecommerce\Model\Process\OrderProcessQueue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

    protected string $title = 'Process The Order Queue';

    protected static string $description = 'Go through order queue and try to finalise all the orders in it.';

    protected static string $commandName = 'ecommerce:process-order-queue';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        //as this may run every minute, we have to limit it to fifty seconds.
        set_time_limit(50);
        $now = microtime(true);
        //IMPORTANT!
        if (! $this->sendEmails) {
            Config::modify()->set(Email::class, 'send_all_emails_to', 'no-one@localhost');
            Injector::inst()->registerService(new EcommerceDummyMailer(), MailerInterface::class);
        }

        $id = (int) $input->getOption('id');
        $queueObjectSingleton = Injector::inst()->get(OrderProcessQueue::class);
        $ordersinQueue = $queueObjectSingleton->OrdersToBeProcessed($id);
        if (! $ordersinQueue->exists()) {
            $output->writeln('No orders in queue');

            return Command::SUCCESS;
        }

        $output->writeForHtml('<h3>There are ' . $ordersinQueue->count() . ' in the queue, processing ' . $this->limit . ' now</h3>');
        if ($id !== 0) {
            $output->writeForHtml('<h3>FORCING Order with ID: ' . $id . '</h3>');
            $ordersinQueue = $ordersinQueue->filter(['ID' => $id]);
        }

        $this->tryToFinaliseOrders($ordersinQueue, $output);
        $output->writeln('');
        $output->writeln('');
        $output->writeln('PROCESSED IN: ' . round(((microtime(true) - $now) / 1), 5) . ' seconds');

        return Command::SUCCESS;
    }

    /**
     * @param DataList $orders orders to be processsed
     */
    protected function tryToFinaliseOrders(DataList $orders, PolyOutput $output)
    {
        //limit orders
        $orders = $orders->limit($this->limit);
        //we sort randomly so it is less likely we get stuck with the same ones
        $orders = $orders->shuffle();

        $queueObjectSingleton = Injector::inst()->get(OrderProcessQueue::class);
        foreach ($orders as $order) {
            $output->writeForHtml('<hr />Processing order: ' . $order->ID);
            $outcome = $queueObjectSingleton->process($order);
            if (true === $outcome) {
                $output->writeForHtml('<br />... Order moved successfully.<hr />');
            } else {
                $output->writeForHtml('<br />... ' . $outcome . '<hr />');
            }
        }
    }

    public function getOptions(): array
    {
        return [
            new InputOption('id', 'i', InputOption::VALUE_OPTIONAL, 'Force processing of a specific order ID'),
        ];
    }
}
