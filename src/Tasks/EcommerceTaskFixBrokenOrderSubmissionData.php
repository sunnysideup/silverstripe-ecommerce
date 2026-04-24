<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
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
class EcommerceTaskFixBrokenOrderSubmissionData extends BuildTask
{
    protected string $title = 'Fixes broken order submission links';

    protected static string $description = 'Fixes broken order submission links (submission records without an order).';

    protected static string $commandName = 'ecommerce-fix-broken-order-submission';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $problem = DB::query('SELECT COUNT(OrderStatusLog.ID) FROM OrderStatusLogSubmitted INNER JOIN OrderStatusLog ON OrderStatusLogSubmitted.ID = OrderStatusLog.ID WHERE OrderID = 0');
        if ($problem->value()) {
            $output->writeln('the size of the problem is: ' . $problem->value());
        } else {
            $output->writeln('No broken links found.');
        }

        $rows = DB::query('Select "ID" from "Order" WHERE "StatusID" > 1');
        if ($rows) {
            foreach ($rows as $row) {
                $orderID = $row['ID'];
                $inners = DB::query('SELECT COUNT(OrderStatusLog.ID) FROM OrderStatusLogSubmitted INNER JOIN OrderStatusLog ON OrderStatusLogSubmitted.ID = OrderStatusLog.ID WHERE OrderID = ' . $orderID);
                if ($inners->value() < 1) {
                    $sql = "
                    SELECT *
                    FROM OrderStatusLogSubmitted
                    WHERE
                        \"OrderAsString\" LIKE '%s:7:\"OrderID\";i:" . $orderID . "%'
                        OR \"OrderAsHTML\" LIKE '%Order #" . $orderID . "%'

                    LIMIT 1";
                    $innerInners = DB::query($sql);
                    if ($innerInners) {
                        foreach ($innerInners as $innerInnerRow) {
                            $output->writeln('FOUND ' . $innerInnerRow['ID']);
                            DB::query(sprintf('UPDATE "OrderStatusLog" SET "OrderID" = %s WHERE "OrderStatusLog"."ID" = ', $orderID) . $innerInnerRow['ID'] . ' AND "OrderID" < 1');
                        }
                    }
                }
            }
        }

        return Command::SUCCESS;
    }
}
