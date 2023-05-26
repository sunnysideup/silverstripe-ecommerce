<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

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
    protected $title = 'Fixes broken order submission links';

    protected $description = 'Fixes broken order submission links (submission records without an order).';

    public function run($request)
    {
        $problem = DB::query('SELECT COUNT(OrderStatusLog.ID) FROM OrderStatusLogSubmitted INNER JOIN OrderStatusLog ON OrderStatusLogSubmitted.ID = OrderStatusLog.ID WHERE OrderID = 0');
        if ($problem->value()) {
            DB::alteration_message('the size of the problem is: ' . $problem->value(), 'deleted');
        } else {
            DB::alteration_message('No broken links found.', 'created');
        }
        $rows = DB::query('Select "ID" from "Order" WHERE "StatusID" > 1');
        if ($rows) {
            foreach ($rows as $row) {
                $orderID = $row['ID'];
                $inners = DB::query("SELECT COUNT(OrderStatusLog.ID) FROM OrderStatusLogSubmitted INNER JOIN OrderStatusLog ON OrderStatusLogSubmitted.ID = OrderStatusLog.ID WHERE OrderID = {$orderID}");
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
                            DB::alteration_message('FOUND ' . $innerInnerRow['ID'], 'created');
                            DB::query("UPDATE \"OrderStatusLog\" SET \"OrderID\" = {$orderID} WHERE \"OrderStatusLog\".\"ID\" = " . $innerInnerRow['ID'] . ' AND "OrderID" < 1');
                        }
                    }
                }
            }
        }
    }
}
