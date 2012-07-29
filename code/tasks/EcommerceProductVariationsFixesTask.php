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
			$products = Versioned::get_by_stage("Product", $stage);
			$count = 0;
			if($products) {
				foreach($products as $product) {
					if($this->hasExtension("ProductWithVariationDecorator")) {
						if($product->cleaningUpVariationData($verbose = true)) {
							$count++;
						}
					}
				}
			}
			DB::alteration_message("Updated $count Products (".$products->count()." products on $stage)");
		}
	}

}

