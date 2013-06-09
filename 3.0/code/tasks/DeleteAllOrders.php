<?php



/**
 * @description (see $this->description)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class DeleteAllOrders extends BuildTask {


	static $allowed_actions = array(
		'*' => 'ADMIN'
	);

	protected $title = 'Deletes all orders - CAREFUL!';

	protected $description = "Deletes all the orders and payments ever placed - CAREFULL!";

	public $verbose = false;

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
		"OrderEmailRecord" =>"OrderEmailRecord",
		"EcommercePayment" => "EcommercePayment"
	);
		static function set_linked_objects_array($a) {self::$linked_objects_array = $a;}
		static function get_linked_objects_array() {return self::$linked_objects_array;}
		static function add_linked_object($s) {self::$linked_objects_array[] = $s;}

	/**
	 *
	 *key = table where OrderID is saved
	 *value = table where LastEdited is saved
	 **/
	protected static $double_check_objects = array(
		"Order",
		"OrderItem",
		"OrderModifier",
		"Payment"
	);
		static function set_double_check_objects($a) {self::$double_check_objects = $a;}
		static function get_double_check_objects() {return self::$double_check_objects;}
		static function add_double_check_objects($s) {self::$double_check_objects[] = $s;}
/*******************************************************
	 * DELETE OLD SHOPPING CARTS
*******************************************************/

	/**
	 *@return Integer - number of carts destroyed
	 **/
	public function run($request){
		if(!Director::isDev() || Director::isLive() ) {
			DB::alteration_message("you can only run this in dev mode!");
		}
		else {
			if(!isset($_REQUEST["i-am-sure"])) {
				$_REQUEST["i-am-sure"] = "";
			}
			if("yes" != $_REQUEST["i-am-sure"]) {
				die("<h1>ARE YOU SURE?</h1><br /><br /><br /> please add the 'i-am-sure' get variable to your request and set it to 'yes' ... e.g. <br />http://www.mysite.com/dev/ecommerce/deleteallorders/?i-am-sure=yes");
			}
			$oldCarts = Order::get();
			$count = 0;
			if($oldCarts->count()){
				if($this->verbose) {
					$totalToDeleteSQLObject = DB::query("SELECT COUNT(*) FROM \"Order\"");
					$totalToDelete = $totalToDeleteSQLObject->value();
					DB::alteration_message("<h2>Total number of orders: ".$totalToDelete." .... now deleting: </h2>", "deleted");
				}
				foreach($oldCarts as $oldCart){
					$count++;
					if($this->verbose) {
						DB::alteration_message("$count ... deleting abandonned order #".$oldCart->ID, "deleted");
					}
					$oldCart->delete();
					$oldCart->destroy();
				}
			}
			else {
				if($this->verbose) {
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
			$this->doubleCheckModifiersAndItems();
			return $count;
		}
	}

	function cleanupUnlinkedOrderObjects() {
		$classNames = self::get_linked_objects_array();
		if(is_array($classNames) && count($classNames)) {
			foreach($classNames as $classWithOrderID => $classWithLastEdited) {
				if($this->verbose) {
					DB::alteration_message("looking for $classWithOrderID objects without link to order.", "deleted");
				}
				$where = "\"Order\".\"ID\" IS NULL ";
				$join = " LEFT JOIN \"Order\" ON ";
				//the code below is a bit of a hack, but because of the one-to-one relationship we
				//want to check both sides....
				$unlinkedObjects = $classWithLastEdited::get();
				if($classWithLastEdited != $classWithOrderID) {
					$unlinkedObjects = $unlinkedObjects
						->leftJoin($classWithOrderID, "\"OrderAddress\".\"ID\" = \"$classWithOrderID\".\"ID\"");
				}
				$unlinkedObjects = $unlinkedObjects
					->where($where)
					->leftJoin("Order", "\"Order\".\"ID\" = \"$classWithOrderID\".\"OrderID\"");

				if($unlinkedObjects->count()){
					foreach($unlinkedObjects as $unlinkedObject){
						if($this->verbose) {
							DB::alteration_message("Deleting ".$unlinkedObject->ClassName." with ID #".$unlinkedObject->ID." because it does not appear to link to an order.", "deleted");
						}
						//HACK FOR DELETING
						$this->deleteObject($unlinkedObject);
					}
				}
				$countCheck = DB::query("Select COUNT(ID) FROM \"$classWithLastEdited\"")->value();
				if($countCheck) {
					DB::alteration_message("ERROR: in testing <i>".$classWithOrderID."</i> it appears there are ".$countCheck." records left.", "deleted");
				}
				else {
					DB::alteration_message("PASS: in testing <i>".$classWithOrderID."</i> there seem to be no records left.", "created");
				}
			}
		}
	}

	private function doubleCheckModifiersAndItems() {
		DB::alteration_message("<hr />double-check:</hr />");
		foreach(self::$double_check_objects as $table) {
			$countCheck = DB::query("Select COUNT(ID) FROM \"$table\"")->value();
			if($countCheck) {
				DB::alteration_message("ERROR: in testing <i>".$table."</i> it appears there are ".$countCheck." records left.", "deleted");
			}
			else {
				DB::alteration_message("PASS: in testing <i>".$table."</i> there seem to be no records left.", "created");
			}
		}
	}

	private function deleteObject($unlinkedObject){
		if($unlinkedObject) {
			if($unlinkedObject->ClassName) {
				if(class_exists($unlinkedObject->ClassName) && $unlinkedObject instanceOf DataObject) {
					$unlinkedObjectClassName = $unlinkedObject->ClassName;
					$objectToDelete = $unlinkedObjectClassName::get()->byID($unlinkedObject->ID);
					if($objectToDelete) {
						$objectToDelete->delete();
						$objectToDelete->destroy();
					}
					elseif($this->verbose) {
						DB::alteration_message("ERROR: could not find ".$unlinkedObject->ClassName." with ID = ".$unlinkedObject->ID, "deleted");
					}
				}
				elseif($this->verbose) {
					DB::alteration_message("ERROR: trying to delete an object that is not a dataobject: ".$unlinkedObject->ClassName, "deleted");
				}
			}
			elseif($this->verbose) {
				DB::alteration_message("ERROR: trying to delete object without a class name", "deleted");
			}
		}
		elseif($this->verbose) {
			DB::alteration_message("ERROR: trying to delete non-existing object.", "deleted");
		}
	}

}
