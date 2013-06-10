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


class EcommerceTaskArchiveAllSubmittedOrders extends BuildTask{

	protected $title = "Archive all submitted orders";

	protected $description = "
	This task moves all orders to the 'Archived' (last) Order Step without running any of the tasks in between.";

	function run($request){
		//IMPORTANT!
		Email::send_all_emails_to("no-one@lets-hope-this-goes-absolutely-no-where.co.nz");
		Email::set_mailer( new EcommerceTryToFinaliseOrdersTask_Mailer() );
		$orderStatusLogClassName = "OrderStatusLog";
		$submittedOrderStatusLogClassName = EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order");
		if($submittedOrderStatusLogClassName) {
			$sampleSubmittedStatusLog = $submittedOrderStatusLogClassName::get()
				->First();
			if($sampleSubmittedStatusLog) {
				$lastOrderStep = OrderStep::get()->sort("Sort", "DESC")->First();
				if($lastOrderStep) {
					$joinSQL = "INNER JOIN \"$orderStatusLogClassName\" ON \"$orderStatusLogClassName\".\"OrderID\" = \"Order\".\"ID\"";
					$whereSQL = "WHERE \"StatusID\" <> ".$lastOrderStep->ID." AND \"$orderStatusLogClassName\".ClassName = '$submittedOrderStatusLogClassName'";
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
			else {
				DB::alteration_message("Could not find any submitted order logs.", "deleted");
			}
		}
		else {
			DB::alteration_message("Could not find a class name for submitted orders.", "deleted");
		}
	}

}
