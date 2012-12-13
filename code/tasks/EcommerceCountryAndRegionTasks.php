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
		$array = Geoip::getCountryDropDown();
		$allowedArray = EcommerceConfig::get("EcommerceCountry", "allowed_country_codes");
		foreach($array as $code => $name) {
			if($obj = DataObject::get_one("EcommerceCountry", "\"Code\" = '".Convert::raw2sql($code)."'")) {
				//do nothing
				$count++;
			}
			else {
				DB::alteration_message("adding $value to Ecommerce Country", "created");
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
