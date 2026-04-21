<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Email\EcommerceDummyMailer;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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
    private const string AGO_STATEMENT = '-6 months';

    protected static string $commandName = 'ecommerce-archive-old-orders';

    protected string $title = 'Archive all old orders';

    protected static string $description = "This task moves all orders to the 'Archived' (last) Order Step that were created -6 months ago.";

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        //IMPORTANT!
        Config::modify()->set(Email::class, 'send_all_emails_to', 'no-one@localhost');
        Injector::inst()->registerService(new EcommerceDummyMailer(), MailerInterface::class);
        $lastOrderStep = DataObject::get_one(
            OrderStep::class,
            '',
            $cache = true,
            ['Sort' => 'DESC']
        );
        if ($lastOrderStep) {
            $whereSQL = 'WHERE "StatusID" <> ' . $lastOrderStep->ID . ' AND UNIX_TIMESTAMP(LastEdited) < ' . strtotime(self::AGO_STATEMENT);
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
                $output->writeln(sprintf('NOTE: %s records were updated.', $count));
            } else {
                $output->writeln('No records were updated.');
            }
        } else {
            $output->writeln('Could not find a class name for submitted orders.');
        }

        return Command::SUCCESS;
    }
}
