<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Adds all members, who have bought something, to the customer group.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
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
        $cutOfTs = strtotime('-' . $this->Config()->get('last_sold_days_ago') . ' days');
        DB::alteration_message('<h1>Deleting products that are not for sale, last sold since: ' . date('Y-m-d', $cutOfTs) . '</h1>');

        $products = Product::get()->filter(['ID' => $this->getListOfCandidates()]);
        foreach ($products as $product) {
            DB::alteration_message('Deleting ' . $product->FullName, 'deleted');
            $product->DeleteFromStage(Versioned::LIVE);
            $product->DeleteFromStage(Versioned::DRAFT);
        }
    }

    public function getListOfCandidates(): array
    {
        $ids = [];
        $cutOfTs = strtotime('-' . $this->Config()->get('last_sold_days_ago') . ' days');
        $products = Product::get()->filter(['AllowPurchase' => false]);
        foreach ($products as $product) {
            if (! $product->hasBeenSold()) {
                $ids[$product->ID] = $product->ID;
            }
        }

        return ArrayMethods::filter_array($ids);
    }
}
