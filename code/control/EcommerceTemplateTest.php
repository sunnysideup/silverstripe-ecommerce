<?php

/**
 * @description: used to display a random product in the Template Test.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: control
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceTemplateTest extends Page_Controller {

	function RandomProduct(){
		$products = DataObject::get("Product", "\"AllowPurchase\" = 1  AND \"Price\" > 0", "RAND()", null, 100);
		foreach($products as $product) {
			if($product->canPurchase()) {
				return $product;
			}
		}
	}

	function IsEcommercePage(){
		return true;
	}

}
