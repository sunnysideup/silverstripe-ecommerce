<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Order;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * set the order id number.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskSetOrderIDStartingNumber extends BuildTask
{
    protected string $title = 'Set Order ID starting number';

    protected static string $description = 'Sets the starting order number with all order numbers following this number.';

    protected static string $commandName = 'ecommerce:set-order-id-starting-number';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        //set starting order number ID
        $number = EcommerceConfig::get(Order::class, 'order_id_start_number');
        $currentMax = 0;
        //set order ID
        if ($number) {
            $count = DB::query('SELECT COUNT( "ID" ) FROM "Order" ')->value();
            if ($count > 0) {
                $currentMax = DB::Query('SELECT MAX( "ID" ) FROM "Order"')->value();
            }

            if ($number > $currentMax) {
                DB::query(sprintf('ALTER TABLE "Order"  AUTO_INCREMENT = %s ROW_FORMAT = DYNAMIC ', $number));
                $output->writeln('Change OrderID start number to ' . $number);
            } else {
                $output->writeln('Can not set OrderID start number to ' . $number . ' because this number has already been used.');
            }
        } else {
            $output->writeln('Starting OrderID has not been set.');
        }

        return Command::SUCCESS;
    }
}
