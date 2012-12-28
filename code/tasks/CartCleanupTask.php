<?php


/**
 * @description: cleans up old (abandonned) carts...
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class CartCleanupTask extends BuildTask {

	/**
	 * Standard SS Variable
	 * TODO: either remove or add to all tasks
	 */
	static $allowed_actions = array(
		'*' => 'ADMIN',
		'*' => 'SHOPADMIN'
	);

	protected $title = 'Clear old carts';

	protected $description = "Deletes abandonned carts (add ?limit=xxxx to the end of the URL to set the number of records (xxx = number of records) to be deleted in one load).";

	/**
	 * Output feedback about task?
	 * @var Boolean
	 */
	public $verbose = false;


	/**
	 * run in verbose mode
	 */
	public static function run_on_demand() {
		$obj = new CartCleanupTask();
		$obj->verbose = true;
		$obj->run(null);
	}

	/**
	 * runs the task without output
	 */
	public function runSilently(){
		$this->verbose = false;
		return $this->run(null);
	}
	/**
	 *@return Integer - number of carts destroyed
	 **/
	public function run($request){
		if($this->verbose) {
			$countAll = DB::query("SELECT COUNT(\"ID\") FROM \"Order\"")->value();
			DB::alteration_message("<h2>deleting empty and abandonned carts (total cart count = $countAll)</h2>.");
		}

		//ABANDONNED CARTS
		$clearMinutes = EcommerceConfig::get("CartCleanupTask", "clear_minutes");
		$maximumNumberOfObjectsDeleted = EcommerceConfig::get("CartCleanupTask", "maximum_number_of_objects_deleted");
		if(isset($_GET["limit"]) && $this->verbose) {
			$maximumNumberOfObjectsDeleted = intval($_GET["limit"]);
		}
		$time = strtotime("-".$clearMinutes." minutes");
		$where = "\"StatusID\" = ".OrderStep::get_status_id_from_code("CREATED")." AND UNIX_TIMESTAMP(\"Order\".\"LastEdited\") < '$time'";
		$sort = "\"Order\".\"Created\" ASC";
		$join = "";
		$limit = $maximumNumberOfObjectsDeleted;
		$neverDeleteIfLinkedToMember = EcommerceConfig::get("CartCleanupTask", "never_delete_if_linked_to_member");
		if($neverDeleteIfLinkedToMember) {
			$where .= " AND \"Member\".\"ID\" IS NULL";
			$join .= "LEFT JOIN \"Member\" ON \"Member\".\"ID\" = \"Order\".\"MemberID\" ";
			$memberDeleteNote = "(Carts linked to a member will NEVER be deleted)";
		}
		else {
			$memberDeleteNote = "(We will also delete carts in this category that are linked to a member)";
		}
		$oldCarts = Order::get()->where($where)->sort($sort)->leftJoin("Member", "\"Member\".\"ID\" = \"Order\".\"MemberID\"")->limit(0, $maximumNumberOfObjectsDeleted);
		if($oldCarts){
			$count = 0;
			if($this->verbose) {
				$totalToDeleteSQLObject = DB::query("SELECT COUNT(*) FROM \"Order\" $join WHERE $where");
				$totalToDelete = $totalToDeleteSQLObject->value();
				DB::alteration_message("
					<h2>Total number of abandonned carts: ".$totalToDelete."</h2>
					<br /><b>number of records deleted at one time:</b> ".$maximumNumberOfObjectsDeleted."
					<br /><b>Criteria:</b> last edited ".$clearMinutes." minutes ago or more $memberDeleteNote", "created");
			}
			foreach($oldCarts as $oldCart){
				$count++;
				if($this->verbose) {
					DB::alteration_message("$count ... deleting abandonned order #".$oldCart->ID, "deleted");
				}
				$this->deleteObject($oldCart);
			}
		}
		if($this->verbose) {
			$timeLegible = date('Y-m-d H:i:s', $time);
			$countCart = DB::query("SELECT COUNT(\"ID\") FROM \"Order\" WHERE \"StatusID\" = ".OrderStep::get_status_id_from_code("CREATED")." ")->value();
			$countCartWithinTimeLimit = DB::query("SELECT COUNT(\"ID\") FROM \"Order\" WHERE \"StatusID\" = ".OrderStep::get_status_id_from_code("CREATED")." AND UNIX_TIMESTAMP(\"Order\".\"LastEdited\") >= '$time' ")->value();
			DB::alteration_message("$countCart Orders are still in the intial cart state (not submitted), $countCartWithinTimeLimit of them are within the time limit (last edited after $timeLegible) so they are not deleted yet.", "created");
		}

		//EMPTY ORDERS
		$clearMinutes = EcommerceConfig::get("CartCleanupTask", "clear_minutes_empty_carts");
		$time = strtotime("-".$clearMinutes." minutes");
		$where = "\"StatusID\" = 0 AND UNIX_TIMESTAMP(\"Order\".\"LastEdited\") < '$time'";
		$sort = "\"Order\".\"Created\" ASC";
		$join = "";
		$limit = "0, ".$maximumNumberOfObjectsDeleted;
		$neverDeleteIfLinkedToMember = EcommerceConfig::get("CartCleanupTask", "never_delete_if_linked_to_member");
		if($neverDeleteIfLinkedToMember) {
			$where .= " AND \"Member\".\"ID\" IS NULL";
			$join .= "LEFT JOIN \"Member\" ON \"Member\".\"ID\" = \"Order\".\"MemberID\" ";
			$memberDeleteNote = "(Carts linked to a member will NEVER be deleted)";
		}
		else {
			$memberDeleteNote = "(We will also delete carts in this category that are linked to a member)";
		}
		$oldCarts = DataObject::get('Order',$where, $sort, $join, $limit);
		if($oldCarts){
			$count = 0;
			if($this->verbose) {
				$totalToDeleteSQLObject = DB::query("SELECT COUNT(*) FROM \"Order\" $join WHERE $where");
				$totalToDelete = $totalToDeleteSQLObject->value();
				DB::alteration_message("
					<h2>Total number of empty carts: ".$totalToDelete."</h2>
					<br /><b>number of records deleted at one time:</b> ".$maximumNumberOfObjectsDeleted."
					<br /><b>Criteria:</b> there are no order items and the order was last edited $clearMinutes minutes ago $memberDeleteNote", "created");
			}
			foreach($oldCarts as $oldCart){
				$count++;
				if($this->verbose) {
					DB::alteration_message("$count ... deleting empty order #".$oldCart->ID, "deleted");
				}
				$this->deleteObject($oldCart);
			}
		}
		if($this->verbose) {
			$timeLegible = date('Y-m-d H:i:s', $time);
			$countCart = DB::query("SELECT COUNT(\"ID\") FROM \"Order\" WHERE \"StatusID\" = 0 ")->value();
			$countCartWithinTimeLimit = DB::query("SELECT COUNT(\"ID\") FROM \"Order\" WHERE \"StatusID\" = 0 AND UNIX_TIMESTAMP(\"Order\".\"LastEdited\") <= '$time'")->value();
			DB::alteration_message("$countCart Orders are empty, $countCartWithinTimeLimit are within the time limit (last edited after $timeLegible) so they are not deleted yet.", "created");
		}

		$oneToMany = EcommerceConfig::get("CartCleanupTask", "one_to_many_classes");
		$oneToOne = EcommerceConfig::get("CartCleanupTask", "one_to_one_classes");
		$manyToMany = EcommerceConfig::get("CartCleanupTask", "many_to_many_classes");

		/***********************************************
		//CLEANING ONE-TO-ONES
		************************************************/
		if($this->verbose) {
			DB::alteration_message("<h2>Checking one-to-one relationships</h2>.");
		}
		if(is_array($oneToOne) && count($oneToOne)) {
			foreach($oneToOne as $orderFieldName => $className) {
				if(!in_array($className, $oneToMany) && !in_array($className, $manyToMany)) {
					if($this->verbose) {
						DB::alteration_message("looking for $className objects without link to order.");
					}
					$rows = DB::query("
						SELECT \"$className\".\"ID\"
						FROM \"$className\"
							LEFT JOIN \"Order\"
								ON \"Order\".\"$orderFieldName\" = \"$className\".\"ID\"
						WHERE \"Order\".\"ID\" IS NULL
						LIMIT 0, ".$maximumNumberOfObjectsDeleted);
					//the code below is a bit of a hack, but because of the one-to-one relationship we
					//want to check both sides....
					$oneToOneIDArray = array();
					if($rows) {
						foreach($rows as $row) {
							$oneToOneIDArray[$row["ID"]] = $row["ID"];
						}
					}
					if(count($oneToOneIDArray)) {
						$unlinkedObjects = DataObject::get($className, "\"$className\".\"ID\" IN (".implode(",", $oneToOneIDArray).")");
						if($unlinkedObjects){
							foreach($unlinkedObjects as $unlinkedObject){
								if($this->verbose) {
									DB::alteration_message("Deleting ".$unlinkedObject->ClassName." with ID #".$unlinkedObject->ID." because it does not appear to link to an order.", "deleted");
								}
								$this->deleteObject($unlinkedObject);
							}
						}
						else {
							if($this->verbose) {
								DB::alteration_message("No objects where found for $className even though there appear to be missing links.", "created");
							}
						}
					}
					elseif($this->verbose) {
						DB::alteration_message("All references in Order to $className are valid.", "created");
					}
					if($this->verbose) {
						$countAll = DB::query("SELECT COUNT(\"ID\") FROM \"$className\"")->value();
						$countUnlinkedOnes = DB::query("SELECT COUNT(\"$className\".\"ID\") FROM \"$className\" LEFT JOIN \"Order\" ON \"$className\".\"ID\" = \"Order\".\"$orderFieldName\" WHERE \"Order\".\"ID\" IS NULL")->value();
						DB::alteration_message("In total there are $countAll $className ($orderFieldName), of which there are $countUnlinkedOnes not linked to an order. ", "created");
						if($countUnlinkedOnes) {
							DB::alteration_message("There should be NO $orderFieldName ($className) without link to Order - un error is suspected","deleted");
						}
					}
				}
			}
		}

		/***********************************************
		//CLEANING ONE-TO-MANY
		*************************************************/
		if($this->verbose) {
			DB::alteration_message("<h2>Checking one-to-many relationships</h2>.");
		}
		if(is_array($oneToMany) && count($oneToMany)) {
			foreach($oneToMany as $classWithOrderID => $classWithLastEdited) {
				if(!in_array($classWithLastEdited, $oneToOne) && !in_array($classWithLastEdited, $manyToMany)) {
					if($this->verbose) {
						DB::alteration_message("looking for $classWithOrderID objects without link to order.");
					}
					$rows = DB::query("
						SELECT \"$classWithOrderID\".\"ID\"
						FROM \"$classWithOrderID\"
							LEFT JOIN \"Order\"
								ON \"Order\".\"ID\" = \"$classWithOrderID\".\"OrderID\"
						WHERE \"Order\".\"ID\" IS NULL
						LIMIT 0, ".$maximumNumberOfObjectsDeleted);
					$oneToManyIDArray = array();
					if($rows) {
						foreach($rows as $row) {
							$oneToManyIDArray[$row["ID"]] = $row["ID"];
						}
					}
					if(count($oneToManyIDArray)) {
						$unlinkedObjects = DataObject::get($classWithLastEdited, "\"$classWithLastEdited\".\"ID\" IN (".implode(",", $oneToManyIDArray).")");
						if($unlinkedObjects){
							foreach($unlinkedObjects as $unlinkedObject){
								if($this->verbose) {
									DB::alteration_message("Deleting ".$unlinkedObject->ClassName." with ID #".$unlinkedObject->ID." because it does not appear to link to an order.", "deleted");
								}
								$this->deleteObject($unlinkedObject);
							}
						}
						elseif($this->verbose) {
							DB::alteration_message("$classWithLastEdited objects could not be found even though they were referenced.", "deleted");
						}
					}
					elseif($this->verbose) {
						DB::alteration_message("All $classWithLastEdited objects have a reference to a valid order.", "created");
					}
					if($this->verbose) {
						$countAll = DB::query("SELECT COUNT(\"ID\") FROM \"$classWithLastEdited\"")->value();
						$countUnlinkedOnes = DB::query("SELECT COUNT(\"$classWithOrderID\".\"ID\") FROM \"$classWithOrderID\" LEFT JOIN \"Order\" ON \"$classWithOrderID\".\"OrderID\" = \"Order\".\"ID\" WHERE \"Order\".\"ID\" IS NULL")->value();
						DB::alteration_message("In total there are $countAll $classWithOrderID ($classWithLastEdited), of which there are $countUnlinkedOnes not linked to an order. ", "created");
					}
				}
			}
		}
	}


	private function deleteObject($objectToDelete){
		$objectToDelete->delete();
		$objectToDelete->destroy();
	}


}
