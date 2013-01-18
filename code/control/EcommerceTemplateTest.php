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
		$offSet = 0;
		$product = true;
		$notForSale = true;
		while($product && $notForSale) {
			$notForSale = false;
			$product = Product::get()
				->where("\"AllowPurchase\" = 1  AND \"Price\" > 0")
				->sort("RAND()")
				->limit(1, $offSet)
				->First();
			if($product) {
				$notForSale = $product->canPurchase() ? false : true;
			}
			$offSet++;
		}
		return $product;
	}

	function IsEcommercePage(){
		return true;
	}

}
