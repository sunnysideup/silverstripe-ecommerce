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

	protected $title = "Create standard country and regions";

	protected $description = "Adds all the countries to the EcommerceCountry List";

	function run($request){
		$count = 0;
		$array = Geoip::getCountryDropDown();
		foreach($array as $key => $value) {
			if(!DataObject::get_one("EcommerceCountry", "\"Code\" = '".Convert::raw2sql($key)."'")) {
				$obj = new EcommerceCountry();
				$obj->Code = $key;
				$obj->Name = $value;
				$obj->write();
				DB::alteration_message("adding $value to Ecommerce Country", "created");
				$count++;
			}
		}
		DB::alteration_message("$count countries created");
	}

}
