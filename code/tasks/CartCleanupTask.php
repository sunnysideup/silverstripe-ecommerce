<?php


/**
 * @description: cleans up old (abandonned) carts...
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: cms
 *
 **/


class CartCleanupTask extends HourlyTask {

	/**
	 * Standard SS Variable
	 * TODO: either remove or add to all tasks
	 */
	static $allowed_actions = array(
		'*' => 'ADMIN',
		'*' => 'SHOPADMIN'
	);


	protected $title = 'Clear old carts';

	protected $description = "Deletes abandonned carts";

	public static function run_on_demand() {
		$obj = new CartCleanupTask();
		$obj->verbose = true;
		$obj->run(null);
	}

	/**
	 * Output feedback about task?
	 * @var Boolean
	 */
	public $verbose = false;

	public function runSilently(){
		$this->verbose = false;
		return $this->run(null);
	}
	/**
	 *@return Integer - number of carts destroyed
	 **/
	public function run($request){
		if($this->verbose) {
			DB::alteration_message("<h2>deleting carts</h2>.");
		}
		$count = 0;
		$clearMinutes = EcommerceConfig::get("CartCleanupTask", "clear_minutes");
		$maximumNumberOfObjectsDeleted = EcommerceConfig::get("CartCleanupTask", "maximum_number_of_objects_deleted");
		$time = date('Y-m-d H:i:s', strtotime("-".$clearMinutes." minutes"));
		$where = "\"StatusID\" = ".OrderStep::get_status_id_from_code("CREATED")." AND \"Order\".\"LastEdited\" < '$time'";
		$sort = "\"Order\".\"Created\" ASC";
		$join = "";
		$limit = "0, ".$maximumNumberOfObjectsDeleted;
		$neverDeleteIfLinkedToMember = EcommerceConfig::get("CartCleanupTask", "never_delete_if_linked_to_member");
		if($neverDeleteIfLinkedToMember) {
			$where .= " AND \"Member\".\"ID\" IS NULL";
			$join .= "LEFT JOIN \"Member\" ON \"Member\".\"ID\" = \"Order\".\"MemberID\" ";
		}
		$oldCarts = DataObject::get('Order',$where, $sort, $join, $limit);
		if($oldCarts){
			if($this->verbose) {
				$totalToDeleteSQLObject = DB::query("SELECT COUNT(*) FROM \"Order\" $join WHERE $where");
				$totalToDelete = $totalToDeleteSQLObject->value();
				DB::alteration_message("
					<h2>Total number of abandonned carts: ".$totalToDelete."</h2>
					<br />now deleting: ".$maximumNumberOfObjectsDeleted."
					<br />Criteria: last edited ".$clearMinutes." minutes ago or more and not linked to a member", "created");
				if($neverDeleteIfLinkedToMember) {
					DB::alteration_message("<h3>Carts linked to a member will NEVER be deleted.</h3>", "edited");
				}
				else {
					DB::alteration_message("<h3>We will also delete carts in this category that are linked to a member.</h3>", "edited");
				}
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
			$countAll = DB::query("SELECT COUNT(\"ID\") FROM \"Order\"")->value();
			$countCart = DB::query("SELECT COUNT(\"ID\") FROM \"Order\" WHERE \"StatusID\" = ".OrderStep::get_status_id_from_code("CREATED")." ")->value();
			DB::alteration_message("There are no abandonned orders. There are $countAll orders, $countCart of them are still in the intial cart state (not submitted).", "created");
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
		$objectToDelete = DataObject::get_by_id($objectToDelete->ClassName,$objectToDelete->ID);
		$objectToDelete->delete();
		$objectToDelete->destroy();
	}


}
