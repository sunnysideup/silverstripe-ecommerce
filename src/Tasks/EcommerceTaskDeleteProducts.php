<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;

/**
 * @description: see description
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks

 **/
class EcommerceTaskDeleteProducts extends BuildTask
{
    protected $title = 'Delete e-commerce Buyables';

    protected $description = 'Removes all Buyables (Products) from the database.';

    private static $allowed_actions = [
        '*' => 'ADMIN',
    ];

    public function run($request)
    {
        $arrayOfBuyables = EcommerceConfig::get(EcommerceDBConfig::class, 'array_of_buyables');
        foreach ($arrayOfBuyables as $buyable) {
            $allproducts = $buyable::get();
            if ($allproducts->count()) {
                foreach ($allproducts as $product) {
                    DB::alteration_message('Deleting ' . $product->ClassName . ' ID = ' . $product->ID, 'deleted');
                    if (is_a($product, EcommerceConfigClassNames::getName(SiteTree::class))) {
                        $product->deleteFromStage('Live');
                        $product->deleteFromStage('Draft');
                    } else {
                        $product->delete();
                    }
                    $product->destroy();
                    //TODO: remove versions
                }
            }
        }
    }
}
