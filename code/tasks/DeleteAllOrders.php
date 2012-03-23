<?php


/**
 * @description:
 * Deletes all orders
 *
 * @authors: Nicolaas
 *
 * @package: ecommerce
 * @sub-package: cms
 *
 **/


class DeleteAllOrders extends BuildTask {


	static $allowed_actions = array(
		'*' => 'ADMIN'
	);

	protected $title = 'Deletes all orders - CAREFUL!';

	protected $description = "Deletes all the orders ever placed - CAREFULL!";

	public static function run_on_demand() {
		$obj = new CartCleanupTask();
		$obj->run($verbose = true);
		$obj->cleanupUnlinkedOrderObjects($verbose = true);
	}



	/**
	 *
	 *key = table where OrderID is saved
	 *value = table where LastEdited is saved
	 **/
	protected static $linked_objects_array = array(
		"OrderAttribute" =>"OrderAttribute",
		"BillingAddress" => "OrderAddress",
		"ShippingAddress" => "OrderAddress",
		"OrderStatusLog" =>"OrderStatusLog",
		"OrderEmailRecord" =>"OrderEmailRecord"
	);
		static function set_linked_objects_array($a) {self::$linked_objects_array = $a;}
		static function get_linked_objects_array() {return self::$linked_objects_array;}
		static function add_linked_object($s) {self::$linked_objects_array[] = $s;}
/*******************************************************
	 * DELETE OLD SHOPPING CARTS
*******************************************************/

	/**
	 *@return Integer - number of carts destroyed
	 **/
	public function run($verbose = false){
		$oldCarts = DataObject::get('Order');
		if($oldCarts){
			if($verbose) {
				$totalToDeleteSQLObject = DB::query("SELECT COUNT(*) FROM \"Order\"");
				$totalToDelete = $totalToDeleteSQLObject->value();
				DB::alteration_message("<h2>Total number of orders: ".$totalToDelete." .... now deleting: </h2>", "deleted");
			}
			$count = 0;
			foreach($oldCarts as $oldCart){
				$count++;
				if($verbose) {
					DB::alteration_message("$count ... deleting abandonned order #".$oldCart->ID, "deleted");
				}
				$oldCart->delete();
				$oldCart->destroy();
			}
		}
		else {
			if($verbose) {
				$count = DB::query("SELECT COUNT(\"ID\") FROM \"Order\"")->value();
				DB::alteration_message("There are no abandonned orders. There are $count 'live' Orders.", "created");
			}
		}
		$countCheck = DB::query("Select COUNT(ID) FROM \"Order\"")->value();
		if($countCheck) {
			DB::alteration_message("ERROR: in testing <i>Orders</i> it appears there are ".$countCheck." records left.", "deleted");
		}
		else {
			DB::alteration_message("PASS: in testing <i>Orders</i> there seem to be no records left.", "created");
		}
		$this->cleanupUnlinkedOrderObjects();
		return $count;
	}

	function cleanupUnlinkedOrderObjects($verbose = false) {
		$classNames = self::get_linked_objects_array();
		if(is_array($classNames) && count($classNames)) {
			foreach($classNames as $classWithOrderID => $classWithLastEdited) {
				if($verbose) {
					DB::alteration_message("looking for $classWithOrderID objects without link to order.", "deleted");
				}
				$where = "\"Order\".\"ID\" IS NULL ";
				$sort = '';
				$join = " LEFT JOIN \"Order\" ON \"Order\".\"ID\" = \"$classWithOrderID\".\"OrderID\"";
				//the code below is a bit of a hack, but because of the one-to-one relationship we
				//want to check both sides....
				$unlinkedObjects = DataObject::get($classWithLastEdited, $where, $sort, $join);
				if($unlinkedObjects){
					foreach($unlinkedObjects as $unlinkedObject){
						if($verbose) {
							DB::alteration_message("Deleting ".$unlinkedObject->ClassName." with ID #".$unlinkedObject->ID." because it does not appear to link to an order.", "deleted");
						}
						//HACK FOR DELETING
						$this->deleteObject($unlinkedObject);
					}
				}
				$countCheck = DB::query("Select COUNT(ID) FROM \"$classWithLastEdited\"")->value();
				if($countCheck) {
					DB::alteration_message("ERROR: in testing <i>".$classWithLastEdited."</i> it appears there are ".$countCheck." records left.", "deleted");
				}
				else {
					DB::alteration_message("PASS: in testing <i>".$classWithLastEdited."</i> there seem to be no records left.", "created");
				}
			}
		}
	}

	private function deleteObject($objectToDelete){
		$objectToDelete = DataObject::get_by_id($unlinkedObject->ClassName,$unlinkedObject->ID);
		$objectToDelete->delete();
		$objectToDelete->destroy();
	}

}
