<?php

class EcommerceTemplateTest extends Page_Controller {

	function RandomProduct(){
		$products = DataObject::get("Product", "\"AllowPurchase\" = 1  AND \"Price\" > 0", "RAND()", null, 100);
		foreach($products as $product) {
			if($product->canPurchase()) {
				return $product;
			}
		}
	}

}
