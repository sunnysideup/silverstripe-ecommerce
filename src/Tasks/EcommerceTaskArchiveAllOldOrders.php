<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Control\Email\Email;
use SilverStripe\Control\Email\Mailer;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Email\EcommerceDummyMailer;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Symfony\Component\Mailer\MailerInterface;

/**
 * After a bug in the saving of orders in the CMS
 * This "fixer"  was introduced to fix older orders
 * without a submission record.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskArchiveAllOldOrders extends BuildTask
{
    private const AGO_STATEMENT = '-6 months';
    protected $title = 'Archive all old orders';

    protected $description = "This task moves all orders to the 'Archived' (last) Order Step that were created " . self::AGO_STATEMENT;

    public function run($request)
    {
        //IMPORTANT!
        Config::modify()->set(Email::class, 'send_all_emails_to', 'no-one@localhost');
        Injector::inst()->registerService(new EcommerceDummyMailer(), MailerInterface::class);
        $orderStatusLogTableName = OrderStatusLog::getSchema()->tableName(OrderStatusLog::class);
        $lastOrderStep = DataObject::get_one(
            OrderStep::class,
            '',
            $cache = true,
            ['Sort' => 'DESC']
        );
        if ($lastOrderStep) {
            $whereSQL = 'WHERE "StatusID" <> ' . $lastOrderStep->ID . ' AND UNIX_TIMESTAMP(Created) < ' . strtotime((string) self::AGO_STATEMENT);
            $count = DB::query("
                SELECT COUNT (\"Order\".\"ID\")
                FROM \"Order\"
                {$whereSQL}
            ")->value();
            DB::query('
                UPDATE "Order"
                SET "Order"."StatusID" = ' . $lastOrderStep->ID . "
                {$whereSQL}
            ");
            if ($count) {
                DB::alteration_message("NOTE: {$count} records were updated.", 'created');
            } else {
                DB::alteration_message('No records were updated.');
            }
        } else {
            DB::alteration_message('Could not find a class name for submitted orders.', 'deleted');
        }
    }
}
