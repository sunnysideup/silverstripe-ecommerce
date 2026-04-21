<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @description: cleans up old (abandonned) carts...
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskOrdersWithoutOrderStep extends BuildTask
{
    protected $sendEmails = true;

    protected $limit = 1;

    protected string $title = 'Orders without orderstep';

    protected static string $description = 'Orders where the order step does not exist.';

    protected static string $commandName = 'ecommerce:orders-without-order-step';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $doCancel = $input->getOption('cancel');
        if (! $doCancel) {
            $output->writeForHtml('You can add <strong>--cancel</strong> option to cancel and archive all orders.');
        }

        $submittedOrderStatusLogClassName = EcommerceConfig::get(OrderStatusLog::class, 'order_status_log_class_used_for_submitting_order');
        $submittedOrderStatusLogTableName = EcommerceConfig::get(OrderStatusLog::class, 'table_name');
        if ($submittedOrderStatusLogClassName) {
            $submittedStatusLog = DataObject::get_one($submittedOrderStatusLogClassName);
            if ($submittedStatusLog) {
                $orderStepsIDArray = OrderStep::get()->columnUnique();
                $orders = Order::get()
                    ->where('StatusID NOT IN (' . implode(',', $orderStepsIDArray) . ')')
                    ->innerJoin(
                        'OrderStatusLog',
                        '"OrderStatusLog"."OrderID" = "Order"."ID"'
                    )
                    ->innerJoin(
                        $submittedOrderStatusLogTableName,
                        sprintf('"%s"."ID" = "OrderStatusLog"."ID"', $submittedOrderStatusLogTableName)
                    )
                ;
                if ($orders->exists()) {
                    foreach ($orders as $order) {
                        $archivingNow = 'Open order to rectify.';
                        if ($doCancel) {
                            $archivingNow = 'This order has been cancelled and archived.';
                            $order->Cancel();
                        }

                        $output->writeForHtml(
                            '<a href="' . $order->CMSEditLink() . '">' . $order->getTitle() . '</a><br />' . $archivingNow . '<br /><br />'
                        );
                    }
                } else {
                    $output->writeln('There are no orders without a valid order step.');
                }
            } else {
                $output->writeln('NO submitted order status log.');
            }
        } else {
            $output->writeln('NO EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order")');
        }

        return Command::SUCCESS;
    }

    public function getOptions(): array
    {
        return [
            new InputOption('cancel', 'c', InputOption::VALUE_NONE, 'Cancel and archive all orders without order step'),
        ];
    }
}
