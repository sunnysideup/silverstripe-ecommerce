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

	protected $title = "Archive all orders with order items and adding a submit record.";

	protected $description = "
	This task moves all orders to the 'Archived' (last) Order Step without running any of the tasks in between.
	NB: It also adds a submit record.
	";

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
		$this->createSubmissionLogForArchivedOrders();
	}

	protected function createSubmissionLogForArchivedOrders(){
		$lastOrderStep = OrderStep::get()->sort("Sort", "DESC")->First();
		$submissionLogClassName = EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order");

		$offSet = 0;
		$orders = Order::get()->filter(array("StatusID" => $lastOrderStep->ID))->limit(100, $offSet);

		while($orders->count()) {
			foreach($orders as $order) {

				$isSubmitted = $submissionLogClassName::get()
					->Filter(array("OrderID" => $order->ID))
					->count();
				if(!$isSubmitted) {
					$obj = $submissionLogClassName::create();
					if(is_a($obj, Object::getCustomClass("OrderStatusLog"))) {
						//save versions
						//@todo: test and implement
						/*
						if($this->Attributes()->exists()){
							foreach($this->Attributes() as $attribute){
								if(is_a($attribute, Object::getCustomClass("OrderItem"))){
									$buyable = $attribute->Buyable();
									if(isset($buyable->Version)) {
										$attribute->Version = $buyable->Version;
										$attribute->write();
									}
								}
							}
						}
						*/
						$obj->OrderID = $order->ID;
						//it is important we add this here so that we can save the 'submitted' version.
						//this is particular important for the Order Item Links.
						$obj->write();
						$obj->OrderAsHTML = $order->ConvertToHTML();
						$obj->write();
					}
					else {
						user_error('EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order") refers to a class that is NOT an instance of OrderStatusLog');
					}
				}
			}
			$offSet += 100;
			$orders = Order::get()->filter(array("StatusID" => $lastOrderStep->ID))->limit(100, $offSet);
		}



	}

}
