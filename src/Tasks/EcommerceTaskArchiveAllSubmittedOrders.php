<?php

namespace Sunnysideup\Ecommerce\Tasks;

use BuildTask;
use Config;
use Email;
use EcommerceDummyMailer;
use EcommerceConfig;
use DataObject;
use DB;


/**
 * After a bug in the saving of orders in the CMS
 * This "fixer"  was introduced to fix older orders
 * without a submission record.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks

 **/
class EcommerceTaskArchiveAllSubmittedOrders extends BuildTask
{
    protected $title = 'Archive all submitted orders';

    protected $description = "
    This task moves all orders to the 'Archived' (last) Order Step without running any of the tasks in between.";

    public function run($request)
    {
        //IMPORTANT!
        Config::modify()->update('Email', 'send_all_emails_to', 'no-one@localhost');
        Email::set_mailer(new EcommerceDummyMailer());
        $orderStatusLogClassName = 'OrderStatusLog';
        $submittedOrderStatusLogClassName = EcommerceConfig::get('OrderStatusLog', 'order_status_log_class_used_for_submitting_order');
        if ($submittedOrderStatusLogClassName) {
            $sampleSubmittedStatusLog = DataObject::get_one(
                $submittedOrderStatusLogClassName
            );
            if ($sampleSubmittedStatusLog) {
                $lastOrderStep = DataObject::get_one(
                    'OrderStep',
                    '',
                    $cache = true,
                    ['Sort' => 'DESC']
                );
                if ($lastOrderStep) {
                    $joinSQL = "INNER JOIN \"${orderStatusLogClassName}\" ON \"${orderStatusLogClassName}\".\"OrderID\" = \"Order\".\"ID\"";
                    $whereSQL = 'WHERE "StatusID" <> ' . $lastOrderStep->ID . " AND \"${orderStatusLogClassName}\".ClassName = '${submittedOrderStatusLogClassName}'";
                    $count = DB::query("
                        SELECT COUNT (\"Order\".\"ID\")
                        FROM \"Order\"
                        ${joinSQL}
                        ${whereSQL}
                    ")->value();
                    DB::query("
                        UPDATE \"Order\"
                        ${joinSQL}
                        SET \"Order\".\"StatusID\" = " . $lastOrderStep->ID . "
                        ${whereSQL}
                    ");
                    if ($count) {
                        DB::alteration_message("NOTE: ${count} records were updated.", 'created');
                    } else {
                        DB::alteration_message('No records were updated.');
                    }
                } else {
                    DB::alteration_message('Could not find the last order step.', 'deleted');
                }
            } else {
                DB::alteration_message('Could not find any submitted order logs.', 'deleted');
            }
        } else {
            DB::alteration_message('Could not find a class name for submitted orders.', 'deleted');
        }
    }
}

