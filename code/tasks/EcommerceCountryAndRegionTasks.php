<?php

/**
 * create standard country and regions
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceCountryAndRegionTasks extends BuildTask{

	protected $title = "Create standard countries and regions";

	protected $description = "Adds all countries to the EcommerceCountry list";

	function run($request){
		$count = 0;
		$array = EcommerceCountry::get_country_dropdown();
		$allowedArray = EcommerceConfig::get("EcommerceCountry", "allowed_country_codes");
		foreach($array as $code => $name) {
			$obj = EcommerceCountry::get()
				->Filter(array("Code" => Convert::raw2sql($code)))
				->First();
			if($obj) {
				//do nothing
				$count++;
			}
			else {
				DB::alteration_message("adding $code to Ecommerce Country", "created");
				$obj = new EcommerceCountry();
				$obj->Code = $code;
			}
			if($allowedArray && count($allowedArray)) {
				if(in_array($code, $allowedArray)) {
					//do nothing
					$obj->DoNotAllowSales = 0;
				}
				else {
					$obj->DoNotAllowSales = 1;
				}
			}
			$obj->Name = $name;
			$obj->write();
		}
		DB::alteration_message("updated $count Ecommerce Countries", "edited");
	}

}
/**
 * update EcommerceCountry.DoNotAllowSales to 1 so that you can not sell to any country
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceCountryAndRegionTasks_DisallowAllCountries extends BuildTask{

	protected $title = "Disallows sale to all countries";

	protected $description = "We add this task to reset all countries from Allow Sales to Disallow Sales - as a good starting point when selling to just a few countries";

	function run($request){
		$count = 0;
		$array = EcommerceCountry::get_country_dropdown();
		$allowedArray = DataObject::get("EcommerceCountry", "\"DoNotAllowSales\" = 0");
		foreach($allowedArray as $obj) {
			$obj->DoNotAllowSales = 1; 
			$obj->write();
			DB::alteration_message("Disallowing sales to ".$obj->Name);
		}
	}

}
