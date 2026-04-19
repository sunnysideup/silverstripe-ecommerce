<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Order;

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

    protected $description = 'Sets the starting order number with all order numbers following this number.';

    public function run($request)
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
                DB::alteration_message('Change OrderID start number to ' . $number, 'created');
            } else {
                DB::alteration_message('Can not set OrderID start number to ' . $number . ' because this number has already been used.', 'deleted');
            }
        } else {
            DB::alteration_message('Starting OrderID has not been set.', 'deleted');
        }
    }
}
