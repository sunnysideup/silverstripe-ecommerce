<?php

/**
 * see description in class
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceProductVariationsFixesTask extends BuildTask{

	protected $title = "Fix Product Variations";

	protected $description = "Fixes a bunch of links between Products and their Variations ";

	function run($request){
		$stagingArray = array("Live", "Stage");
		foreach($stagingArray as $stage) {
			$products = Versioned::get_one_by_stage("Product", $stage, "", "", "", $limit = 1000);
			if($products) {
				foreach($products as $product) {
					$product->cleaningUpVariationData();
				}
			}
		}
	}

}

