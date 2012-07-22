<?php

/**
 * works out how many products have been sold, per product.
 *
 * @TODO: consider whether this does not sit better in its own module.
 * @TODO: refactor based on new database fields
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class LinkOrderAddressesAtBothEnds extends BuildTask{

	protected $title = "Links the Order Addresses at the Order And Address side";

	protected $description = "This only needs to be run if you have an outdated version of e-commerce where the links seem broken";

	function run($request){
		$this->linkOrderWithBillingAndShippingAddress(true);
	}

	/**
	 * make sure that the link between order and the two addresses is made on
	 * both sides.
	 * @param Boolean $verbose - show output?
	 *
	 */
	protected function linkOrderWithBillingAndShippingAddress($verbose = false) {
		DB::query("
			UPDATE \"Order\"
				INNER JOIN \"BillingAddress\" ON \"Order\".\"BillingAddressID\" = \"BillingAddress\".\"ID\"
			SET \"BillingAddress\".\"OrderID\" = \"Order\".\"ID\"
			WHERE
				(\"BillingAddress\".\"OrderID\" IS NULL OR \"BillingAddress\".\"OrderID\" <> \"Order\".\"ID\")
				AND
				(\"Order\".\"BillingAddressID\" IS NOT NULL AND \"Order\".\"BillingAddressID\" > 0)
		");
		DB::query("
			UPDATE \"Order\"
				INNER JOIN \"BillingAddress\" ON \"BillingAddress\".\"OrderID\" = \"Order\".\"ID\"
			SET \"Order\".\"BillingAddressID\" = \"BillingAddress\".\"ID\"
			WHERE
				(\"Order\".\"BillingAddressID\" IS NULL OR \"Order\".\"BillingAddressID\" <> \"BillingAddress\".\"ID\")
				AND
				(\"BillingAddress\".\"OrderID\" IS NOT NULL AND \"BillingAddress\".\"OrderID\" > 0)
		");
		DB::query("
			UPDATE \"Order\"
				INNER JOIN \"ShippingAddress\" ON \"Order\".\"ShippingAddressID\" = \"ShippingAddress\".\"ID\"
			SET \"ShippingAddress\".\"OrderID\" = \"Order\".\"ID\"
			WHERE
				(\"ShippingAddress\".\"OrderID\" IS NULL OR \"ShippingAddress\".\"OrderID\" <> \"Order\".\"ID\")
				AND
				(\"Order\".\"ShippingAddressID\" IS NOT NULL AND \"Order\".\"ShippingAddressID\" > 0)
		");
		DB::query("
			UPDATE \"Order\"
				INNER JOIN \"ShippingAddress\" ON \"ShippingAddress\".\"OrderID\" = \"Order\".\"ID\"
			SET \"Order\".\"ShippingAddressID\" = \"ShippingAddress\".\"ID\"
			WHERE
				(\"Order\".\"ShippingAddressID\" IS NULL OR \"Order\".\"ShippingAddressID\" <> \"ShippingAddress\".\"ID\")
				AND
				(\"ShippingAddress\".\"OrderID\" IS NOT NULL AND \"ShippingAddress\".\"OrderID\" > 0)
		");
		if($verbose){
			DB::alteration_message("Linking Order to Billing and Shipping Address on both sides");
		}
	}
}
