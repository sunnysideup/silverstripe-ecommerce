<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * see description in class.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks

 **/
class EcommerceTaskProductVariationsFixes extends BuildTask
{
    protected $title = 'Fix Product Variations';

    protected $description = 'Fixes a bunch of links between Products and their Variations ';

    public function run($request)
    {
        $stagingArray = ['Live', 'Stage'];
        foreach ($stagingArray as $stage) {
            $products = Versioned::get_by_stage(Product::class, $stage);
            $count = 0;
            if ($products) {
                foreach ($products as $product) {
                    if ($this->hasExtension('ProductWithVariationDecorator')) {
                        if ($product->cleaningUpVariationData($verbose = true)) {
                            ++$count;
                        }
                    }
                }
            }
            DB::alteration_message("Updated ${count} Products (" . $products->count() . " products on ${stage})");
        }
    }
}
