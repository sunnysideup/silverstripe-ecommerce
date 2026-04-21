<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * After a bug in the saving of orders in the CMS
 * This "fixer"  was introduced to fix older orders
 * without a submission record.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskArchiveAllSubmittedOrders extends BuildTask
{
    protected string $title = 'Archive all submitted orders';

    protected static string $description = "This task moves all orders to the 'Archived' (last) Order Step without running any of the tasks in between.";

    protected static string $commandName = 'ecommerce-archive-all-submitted-orders';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        //IMPORTANT - just in case!
        Config::modify()->set(Email::class, 'send_all_emails_to', 'no-one@localhost');
        // Injector::inst()->registerService(new EcommerceDummyMailer(), Mailer::class);
        $orderStatusLogTableName = OrderStatusLog::getSchema()->tableName(OrderStatusLog::class);
        $submittedOrderStatusLogClassName = EcommerceConfig::get(OrderStatusLog::class, 'order_status_log_class_used_for_submitting_order');
        if ($submittedOrderStatusLogClassName) {
            $sampleSubmittedStatusLog = DataObject::get_one(
                $submittedOrderStatusLogClassName
            );
            if ($sampleSubmittedStatusLog) {
                $lastOrderStep = DataObject::get_one(
                    OrderStep::class,
                    '',
                    $cache = true,
                    ['Sort' => 'DESC']
                );
                if ($lastOrderStep) {
                    $joinSQL = sprintf('INNER JOIN "%s" ON "%s"."OrderID" = "Order"."ID"', $orderStatusLogTableName, $orderStatusLogTableName);
                    $whereSQL = 'WHERE "StatusID" <> ' . $lastOrderStep->ID . sprintf(" AND \"%s\".ClassName = '", $orderStatusLogTableName) . Convert::raw2sql($submittedOrderStatusLogClassName) . "'";
                    $count = DB::query("
                        SELECT COUNT (\"Order\".\"ID\")
                        FROM \"Order\"
                        {$joinSQL}
                        {$whereSQL}
                    ")->value();
                    $sql = "
                        UPDATE \"Order\"
                        {$joinSQL}
                        SET \"Order\".\"StatusID\" = " . $lastOrderStep->ID . "
                        {$whereSQL}
                    ";
                    $output->writeln('SQL: ' . $sql);
                    DB::query($sql);
                    if ($count) {
                        $output->writeln(sprintf('NOTE: %s records were updated.', $count));
                    } else {
                        $output->writeln('No records were updated.');
                    }
                } else {
                    $output->writeln('Could not find the last order step.');
                }
            } else {
                $output->writeln('Could not find any submitted order logs.');
            }
        } else {
            $output->writeln('Could not find a class name for submitted orders.');
        }

        return Command::SUCCESS;
    }
}
