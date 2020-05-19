<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;

/**
 * create standard country and regions.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks

 **/
class EcommerceTaskCountryAndRegion extends BuildTask
{
    protected $title = 'Create standard countries and regions';

    protected $description = 'Adds all countries to the EcommerceCountry list';

    public function run($request)
    {
        $count = 0;
        $array = EcommerceCountry::get_country_dropdown();
        $allowedArray = EcommerceConfig::get(EcommerceCountry::class, 'allowed_country_codes');
        foreach ($array as $code => $name) {
            $ecommerceCountry = DataObject::get_one(
                EcommerceCountry::class,
                ['Code' => Convert::raw2sql($code)],
                $cacheDataObjectGetOne = false
            );
            if ($ecommerceCountry) {
                //do nothing
                ++$count;
            } else {
                DB::alteration_message("adding ${code} to Ecommerce Country", 'created');
                $ecommerceCountry = EcommerceCountry::create();
                $ecommerceCountry->Code = $code;
            }
            if ($allowedArray && count($allowedArray)) {
                if (in_array($code, $allowedArray, true)) {
                    //do nothing
                    $ecommerceCountry->DoNotAllowSales = 0;
                } else {
                    $ecommerceCountry->DoNotAllowSales = 1;
                }
            }
            $ecommerceCountry->Name = $name;
            $ecommerceCountry->write();
        }
        DB::alteration_message("Created / Checked ${count} Ecommerce Countries", 'edited');
    }
}
