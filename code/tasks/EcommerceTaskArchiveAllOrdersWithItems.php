<?php

/**
 * After a bug in the saving of orders in the CMS
 * This "fixer"  was introduced to fix older orders
 * without a submission record.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/


class EcommerceTaskArchiveAllOrdersWithItems extends BuildTask{

	protected $title = "Archive all orders with order items";

	protected $description = "
	This task moves all orders to the 'Archived' (last) Order Step without running any of the tasks in between.";

	function run($request){
		//IMPORTANT!
		$lastOrderStep = OrderStep::get()->sort("Sort", "DESC")->First();
		if($lastOrderStep) {
			$joinSQL = "
			INNER JOIN \"OrderAttribute\" ON \"Order\".\"ID\" = \"OrderAttribute\".\"OrderID\"
			INNER JOIN \"OrderItem\" ON \"OrderItem\".\"ID\" = \"OrderAttribute\".\"ID\"
			";
			$whereSQL = "WHERE \"StatusID\" <> ".$lastOrderStep->ID." ";
			$count = DB::query("
				SELECT COUNT (\"Order\".\"ID\")
				FROM \"Order\"
				$joinSQL
				$whereSQL
			")->value();
			$do = DB::query("
				UPDATE \"Order\"
				$joinSQL
				SET \"Order\".\"StatusID\" = ".$lastOrderStep->ID."
				$whereSQL
			");
			if($count) {
				DB::alteration_message("NOTE: $count records were updated.", "created");
			}
			else {
				DB::alteration_message("No records were updated.");
			}
		}
		else {
			DB::alteration_message("Could not find the last order step.", "deleted");
		}
	}
}
