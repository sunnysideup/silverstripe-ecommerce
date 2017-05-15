<?php

/**
 * create standard country and regions.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
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
                array('Code' => Convert::raw2sql($code)),
                $cacheDataObjectGetOne = false
            );
            if ($ecommerceCountry) {
                //do nothing
                ++$count;
            } else {
                DB::alteration_message("adding $code to Ecommerce Country", 'created');
                $ecommerceCountry = EcommerceCountry::create();
                $ecommerceCountry->Code = $code;
            }
            if ($allowedArray && count($allowedArray)) {
                if (in_array($code, $allowedArray)) {
                    //do nothing
                    $ecommerceCountry->DoNotAllowSales = 0;
                } else {
                    $ecommerceCountry->DoNotAllowSales = 1;
                }
            }
            $ecommerceCountry->Name = $name;
            $ecommerceCountry->write();
        }
        DB::alteration_message("Created / Checked $count Ecommerce Countries", 'edited');
    }
}

/**
 * update EcommerceCountry.DoNotAllowSales to 1 so that you can not sell to any country.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class EcommerceTaskCountryAndRegion_DisallowAllCountries extends BuildTask
{
    protected $title = 'Disallows sale to all countries';

    protected $description = 'We add this task to reset all countries from Allow Sales to Disallow Sales - as a good starting point when selling to just a few countries';

    public function run($request)
    {
        $count = 0;
        $array = EcommerceCountry::get_country_dropdown();
        $allowedArray = EcommerceCountry::get()
            ->filter(array('DoNotAllowSales' => 0));
        if ($allowedArray->count()) {
            foreach ($allowedArray as $obj) {
                $obj->DoNotAllowSales = 1;
                $obj->write();
                DB::alteration_message('Disallowing sales to '.$obj->Name);
            }
        } else {
            DB::alteration_message('Could not find any countries that are allowed', 'created');
        }
    }
}
/**
 * update EcommerceCountry.DoNotAllowSales to 0 so that you can sell to all countries.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class EcommerceTaskCountryAndRegion_AllowAllCountries extends BuildTask
{
    protected $title = 'Allows sale to all countries';

    protected $description = 'We add this task to reset all countries from Allow Sales to Disallow Sales - as a good starting point when selling to just a few countries';

    public function run($request)
    {
        $count = 0;
        $array = EcommerceCountry::get_country_dropdown();
        $allowedArray = EcommerceCountry::get()
            ->filter(array('DoNotAllowSales' => 1));
        if ($allowedArray->count()) {
            foreach ($allowedArray as $obj) {
                $obj->DoNotAllowSales = 0;
                $obj->write();
                DB::alteration_message('Disallowing sales to '.$obj->Name);
            }
        } else {
            DB::alteration_message('Could not find any countries that are not allowed', 'created');
        }
    }
}
