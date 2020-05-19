<?php

namespace Sunnysideup\Ecommerce\Tasks;

use BuildTask;
use EcommerceCountry;
use DB;


/**
 * update EcommerceCountry.DoNotAllowSales to 0 so that you can sell to all countries.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks

 **/
class EcommerceTaskCountryAndRegionAllowAllCountries extends BuildTask
{
    protected $title = 'Allows sale to all countries';

    protected $description = 'We add this task to reset all countries from Allow Sales to Disallow Sales - as a good starting point when selling to just a few countries';

    public function run($request)
    {
        $allowedArray = EcommerceCountry::get()
            ->filter(['DoNotAllowSales' => 1]);
        if ($allowedArray->count()) {
            foreach ($allowedArray as $obj) {
                $obj->DoNotAllowSales = 0;
                $obj->write();
                DB::alteration_message('Disallowing sales to ' . $obj->Name);
            }
        } else {
            DB::alteration_message('Could not find any countries that are not allowed', 'created');
        }
    }
}

