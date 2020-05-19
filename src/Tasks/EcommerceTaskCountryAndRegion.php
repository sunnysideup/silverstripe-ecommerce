<?php

namespace Sunnysideup\Ecommerce\Tasks;

use BuildTask;
use EcommerceCountry;
use EcommerceConfig;
use DataObject;
use Convert;
use DB;


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
        $allowedArray = EcommerceConfig::get('EcommerceCountry', 'allowed_country_codes');
        foreach ($array as $code => $name) {
            $ecommerceCountry = DataObject::get_one(
                'EcommerceCountry',
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

