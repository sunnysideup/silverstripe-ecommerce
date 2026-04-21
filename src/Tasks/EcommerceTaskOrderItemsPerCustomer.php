<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\OrderItem;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * set the order id number.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskOrderItemsPerCustomer extends BuildTask
{
    protected string $title = 'Export all order items to CSV per customer';

    protected static string $description = 'Allows download of all sales items with all details as CSV. Excludes sales made by Admins';

    protected static string $commandName = 'ecommerce:export-order-items';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        //reset time limit
        set_time_limit(1200);

        //file data
        $now = date('d-m-Y-H-i');
        $fileName = $input->getOption('output') ?: sprintf('export-%s.csv', $now);

        //data object variables
        $orderStatusSubmissionLog = EcommerceConfig::get(OrderStatusLog::class, 'order_status_log_class_used_for_submitting_order');
        $orderStatusSubmissionLogTableName = $orderStatusSubmissionLog::getSchema()->tableName($orderStatusSubmissionLog);
        $fileData = '';
        $offset = 0;
        $count = 50;
        $orders = Order::get()
            ->sort(['Order.ID' => 'ASC'])
            ->innerJoin('OrderStatusLog', '"Order"."ID" = "OrderStatusLog"."OrderID"')
            ->innerJoin($orderStatusSubmissionLogTableName, sprintf('"%s"."ID" = "OrderStatusLog"."ID"', $orderStatusSubmissionLogTableName))
            ->leftJoin('Member', '"Member"."ID" = "Order"."MemberID"')
            ->limit($count, $offset)
        ;
        $ordersCountExists = $orders->exists();
        $sanityCheck = 0;
        $output->writeln('Starting export...');
        while ($ordersCountExists && $sanityCheck < 1000) {
            ++$sanityCheck;
            $offset += $count;
            foreach ($orders as $order) {
                if ($order->IsSubmitted()) {
                    $memberIsOK = false;
                    $member = $order->Member();
                    if ($member && $member->exists()) {
                        $memberIsOK = true;
                        if ($member->IsShopAssistant()) {
                            $memberIsOK = false;
                        }
                    } else {
                        $memberIsOK = true;
                    }

                    if ($memberIsOK) {
                        $items = OrderItem::get()->filter(['OrderID' => $order->ID]);
                        if ($items->exists()) {
                            $fileData .= $this->generateExportFileData($order->getOrderEmail(), $order->SubmissionLog()->Created, $items);
                        }
                    }
                }
            }

            $orders = Order::get()
                ->sort(['Order.ID' => 'ASC'])
                ->innerJoin('OrderStatusLog', '"Order"."ID" = "OrderStatusLog"."OrderID"')
                ->innerJoin($orderStatusSubmissionLogTableName, sprintf('"%s"."ID" = "OrderStatusLog"."ID"', $orderStatusSubmissionLogTableName))
                ->leftJoin('Member', '"Member"."ID" = "Order"."MemberID"')
                ->limit($count, $offset)
            ;
            $ordersCountExists = $orders->exists();
            if ($sanityCheck % 10 === 0) {
                $output->writeln('Processed ' . ($sanityCheck * $count) . ' orders...');
            }
        }

        unset($orders);
        if ($fileData !== '' && $fileData !== '0') {
            // @TODO (SS6 upgrade) - In HTTP context, this would send the file as download
            // In CLI context, we save to a file instead
            file_put_contents($fileName, $fileData);
            $output->writeln('Export completed. File saved to: ' . $fileName);
        } else {
            $output->writeln('No records found');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    public function generateExportFileData($email, $date, $orderItems)
    {
        $separator = ',';
        $fileData = '';
        $columnData = [];
        $exportFields = [
            'OrderID',
            'InternalItemID',
            'TableTitle',
            'TableSubTitleNOHTML',
            'UnitPrice',
            'Quantity',
            'CalculatedTotal',
        ];

        if ($orderItems) {
            foreach ($orderItems as $item) {
                $columnData = [];
                $columnData[] = '"' . $email . '"';
                $columnData[] = '"' . $date . '"';
                foreach ($exportFields as $field) {
                    $value = $item->{$field};
                    $value = preg_replace('#\s+#', ' ', (string) $value);
                    $value = str_replace(["\r", "\n"], "\n", $value);
                    $tmpColumnData = '"' . str_replace('"', '\"', $value) . '"';
                    $columnData[] = $tmpColumnData;
                }

                $fileData .= implode($separator, $columnData);
                $fileData .= "\n";
                $item->destroy();
                unset($item, $columnData);
            }

            return $fileData;
        }

        return '';
    }

    public function getOptions(): array
    {
        return [
            new InputOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output filename for CSV export'),
        ];
    }
}
