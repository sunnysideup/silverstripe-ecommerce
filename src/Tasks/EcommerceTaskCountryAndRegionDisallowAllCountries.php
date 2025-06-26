<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;

/**
 * update EcommerceCountry.DoNotAllowSales to 1 so that you can not sell to any country.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskCountryAndRegionDisallowAllCountries extends BuildTask
{
    protected $title = 'Disallows sale to all countries';

    protected $description = 'We add this task to reset all countries from Allow Sales to Disallow Sales - as a good starting point when selling to just a few countries';

    public function run($request)
    {
        $allowedArray = EcommerceCountry::get()
            ->filter(['DoNotAllowSales' => 0]);
        if ($allowedArray->exists()) {
            foreach ($allowedArray as $obj) {
                $obj->DoNotAllowSales = true;
                $obj->write();
                DB::alteration_message('Disallowing sales to ' . $obj->Name);
            }
        } else {
            DB::alteration_message('Could not find any countries that are allowed', 'created');
        }
    }
}
