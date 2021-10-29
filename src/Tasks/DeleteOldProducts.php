<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;

use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Adds all members, who have bought something, to the customer group.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class DeleteOldProducts extends BuildTask
{
    protected $title = 'Delete products that are not for sale and have not been sold for a year';

    protected $description = 'Takes all the Members that have ordered something and adds them to the Customer Security Group.';

    private static $last_sold_days_ago = '365';

    public function run($request)
    {
        $cutOfTs = strtotime('-'.$this->Config()->get('last_sold_days_ago').' days');
        DB::alteration_message('<h1>Deleting products that are not for sale, last sold since: '.date('Y-m-d', $cutOfTs).'</h1>');
        $products = Product::get()->filter(['AllowPurchase' => false]);
        foreach($products as $product) {
            $markForDeletion = false;
            if($product->hasBeenSold()) {
                $lastSoldTs = strtotime($product->SalesRecord()->max('LastEdited'));
                if($lastSoldTs < $cutOfTs) {
                    $markForDeletion = true;
                }
            } else {
                $markForDeletion = true;
            }
            if($markForDeletion) {
                $product->DeleteFromStage(Versioned::LIVE);
                $product->DeleteFromStage(Versioned::DRAFT);
                DB::alteration_message('Deleting '.$product->FullName, 'deleted');
            } else {
                DB::alteration_message('Keeping '.$product->FullName, 'deleted');
            }
        }
    }
}
