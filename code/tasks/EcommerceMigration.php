<?php


/**
 * @description: migrates older versions of e-commerce to the latest one.
 * This has been placed here rather than in the individual classes for the following reasons:
 * - not to clog up individual classes
 * - to get a complete overview in one class
 * - to be able to run parts and older and newer version without having to go through several clases to retrieve them
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 * @todo: change methods to simple names f10, f20, etc... and then allow individual ones to be run.
 * @todo: 200 + 210 need attention.
 **/




class EcommerceMigration extends BuildTask {

	protected $limit = 100;

	protected $start = 0;

	protected $title = "Ecommerce Migration";

	protected $description = "
		Migrates all the data from the oldest version of e-commerce to the current one.
		Any obsolete fields will be renamed like this: _obsolete_MyField, but not deleted.  The migration will not work so well if you have a very high number of Orders.
		You may run it several times.
	";

	protected $listOfMigrationTasks = array(
		"shopMemberToMemberTableMigration_10",
		"moveItemToBuyable_20",
		"productVersionToOrderItem_25",
		"productIDToBuyableID_26",
		"amountToCalculatedTotal_27",
		"currencyToMoneyFields_30",
		"orderShippingCost_40",
		"orderTax_45",
		"orderShippingAddress_50",
		"orderBillingAddress_51",
		"memberBillingAddress_52",
		"moveOrderStatus_60",
		"fixBadOrderStatus_68",
		"updateProductGroups_110",
		"setFixedPriceForSubmittedOrderItems_120",
		"moveSiteConfigToEcommerceDBConfig_140",
		"addClassNameToOrderItems_150",
		"addTermsAndConditionsMessage_160",
		"mergeUncompletedOrderForOneMember_170",
		"updateFullSiteTreeSortFieldForAllProducts_180",
		"updateOrderStatusLogSequentialOrderNumber_190",
		"resaveAllPRoducts_200",
		"resaveAllPRoductsVariations_210",
		"theEnd_9999"
	);


	function run($request) {
		$canDoNext = true;
		$step = ($request->Param("ID"));
		$this->start = intval($request->Param("OtherID"))-0;
		if(in_array($step, $this->listOfMigrationTasks)) {
			$method = $step;
			$nextLimit = $this->$method();
			if($canDoNext && $nextLimit) {
				$canDoNext = false;
				$next = "".$method."/".$nextLimit;
				$nextDescription = "run next batch ...";
			}
		}
		$html = "<ol>";
		if($canDoNext) {
			$next = $this->listOfMigrationTasks[0];
			$nextDescription = "Start Migration by clicking on <i>'Next'</i> until all tasks have been completed. Make sure to read all the messages carefully.";
		}
		foreach($this->listOfMigrationTasks as $key => $task) {
			if($task == $step) {
				if($canDoNext) {
					$keyPlusOne = $key + 1;
					if(isset($this->listOfMigrationTasks[$keyPlusOne])) {
						$next = $this->listOfMigrationTasks[$keyPlusOne];
						$nextDescription = "".$this->listOfMigrationTasks[$keyPlusOne];
					}
					else {
						$next = "/";
						$nextDescription = "DONE!";
					}
				}
			}
			$html .=  "<li><a href=\"/dev/ecommerce/ecommercemigration/".$task."/\">$task </a></li>";
		}
		$html .= "</ol>";
		echo "
			<hr />
			<h3><a href=\"/dev/ecommerce/ecommercemigration/".$next."/\">NEXT: $nextDescription</a></h3>
			<hr />
			<h3>ALL MIGRATION TASKS: </h3>
			$html
		";
		DB::alteration_message("
			<h1>IMPORTANT</h1>
			<p>Make sure that this page ends with <i>THE END</i> to ensure that all migrations were completed.</p>");

	}


	/**
	 * Returns true if the table and field (within this table) exist.
	 * Otherwise it returns false.
	 * @param String - $field - name of the field to be tested
	 * @param String - $table - name of the table to be tested
	 * @return Boolean
	 */
	protected function hasTableAndField($table, $field) {
		$db = DB::getConn();
		if($db->hasTable($table)) {
			$fieldArray = $db->fieldList($table);
			if(isset($fieldArray[$field])) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns true if the table and field (within this table) exist.
	 * Otherwise it returns false.
	 * @param String - $field - name of the field to be tested
	 * @param String - $table - name of the table to be tested
	 * @return Boolean
	 */
	protected function makeFieldObsolete($table, $field, $format = "") {
		if($this->hasTableAndField($table, $field)) {
			$db = DB::getConn();
			$db->dontRequireField($table, $field);
			DB::alteration_message("removed $field from $table", "deleted");
		}
		else {
			DB::alteration_message("ERROR: could not find $field in $table so it could not be removed", "deleted");
		}
		if($this->hasTableAndField($table, $field)) {
			DB::alteration_message("ERROR: tried to remove $field from $table but it still seems to be there", "deleted");
		}
	}


	protected function shopMemberToMemberTableMigration_10() {
		DB::alteration_message("
			<h1>10. ShopMember to Member</h1>
			<p>In the first version of e-commerce we had the ShopMember class, then we moved this data to Member.</p>
		");
		if($this->hasTableAndField("ShopMember", "ID")) {
			$exist = DB::query("SHOW TABLES LIKE 'ShopMember'")->numRecords();
			if($exist > 0) {
				DB::query("
					UPDATE \"Member\", \"ShopMember\"
					SET
						\"Member\".\"ClassName\" = 'Member',
						\"Member\".\"Address\" = \"ShopMember\".\"Address\",
						\"Member\".\"AddressLine2\" = \"ShopMember\".\"AddressLine2\",
						\"Member\".\"City\" = \"ShopMember\".\"City\",
						\"Member\".\"State\" = \"ShopMember\".\"State\",
						\"Member\".\"Country\" = \"ShopMember\".\"Country\",
						\"Member\".\"Notes\" = \"ShopMember\".\"Notes\"
					WHERE \"Member\".\"ID\" = \"ShopMember\".\"ID\"
				");
				DB::alteration_message("Successfully migrated ShopMember To Member.", "created");
			}
			else {
				DB::alteration_message("No need to migrate ShopMember To Member because it does not have any records.");
			}
			DB::query("DROP TABLE \"ShopMember\";");
 		}
 		else {
			DB::alteration_message("There is no need to migrate the ShopMember table.");
		}
	}

	protected function moveItemToBuyable_20(){
		DB::alteration_message("
			<h1>20. Move ItemID to Buyable</h1>
			<p>Move the Product ID in OrderItem as ItemID to a new field called BuyableID.</p>
		");
		if($this->hasTableAndField("OrderItem", "ItemID")) {
			DB::query("
				UPDATE \"OrderItem\"
				SET \"OrderItem\".\"BuyableID\" = \"OrderItem\".\"ItemID\"
				WHERE \"BuyableID\" = 0 OR \"BuyableID\" IS NULL
			");
 			$this->makeFieldObsolete("OrderItem", "ItemID");
 			DB::alteration_message('Moved ItemID to BuyableID in OrderItem', 'created');
		}
		else {
			DB::alteration_message('There is no need to move from ItemID to BuyableID');
		}
	}

	protected function productVersionToOrderItem_25() {
		DB::alteration_message("
			<h1>25. ProductVersion to Version</h1>
			<p>Move the product version in the Product_OrderItem table to the OrderItem table.</p>
		");
		if($this->hasTableAndField("Product_OrderItem", "ProductVersion")) {
			DB::query("
				UPDATE \"OrderItem\", \"Product_OrderItem\"
					SET \"OrderItem\".\"Version\" = \"Product_OrderItem\".\"ProductVersion\"
				WHERE \"OrderItem\".\"ID\" = \"Product_OrderItem\".\"ID\"
			");
			$this->makeFieldObsolete("Product_OrderItem", "ProductVersion");
			DB::alteration_message("Migrating Product_OrderItem.ProductVersion to OrderItem.Version.", "created");
		}
		else {
			DB::alteration_message("There is no need to migrate Product_OrderItem.ProductVersion to OrderItem.Version.");
		}
	}

	protected function productIDToBuyableID_26() {
		DB::alteration_message("
			<h1>26. ProductID to to BuyableID</h1>
			<p>Move the product ID saved as Product_OrderItem.ProductID to OrderItem.BuyableID.</p>
		");
		if($this->hasTableAndField("Product_OrderItem", "ProductID")) {
			DB::query("
				UPDATE \"OrderItem\"
					INNER JOIN \"Product_OrderItem\"
						ON \"OrderItem\".\"ID\" = \"Product_OrderItem\".\"ID\"
				SET \"OrderItem\".\"BuyableID\" = \"Product_OrderItem\".\"ProductID\"
				WHERE \"BuyableID\" = 0 OR \"BuyableID\" IS NULL
			");
			$this->makeFieldObsolete("Product_OrderItem", "ProductID");
			DB::alteration_message("Migrating Product_OrderItem.ProductID to OrderItem.BuyableID", "created");
		}
		else {
			DB::alteration_message("There is no need to migrate Product_OrderItem.ProductID to OrderItem.BuyableID");
		}
		// we must check for individual database types here because each deals with schema in a none standard way
		//can we use Table::has_field ???
		if($this->hasTableAndField("ProductVariation_OrderItem", "ProductVariationVersion")) {
			DB::query("
				UPDATE \"OrderItem\", \"ProductVariation_OrderItem\"
					SET \"OrderItem\".\"Version\" = \"ProductVariation_OrderItem\".\"ProductVariationVersion\"
				WHERE \"OrderItem\".\"ID\" = \"ProductVariation_OrderItem\".\"ID\"
			");
			$this->makeFieldObsolete("ProductVariation_OrderItem", "ProductVariationVersion");
			DB::alteration_message("Migrating ProductVariation_OrderItem.ProductVariationVersion to OrderItem.Version", "created");
		}
		else {
			DB::alteration_message("No need to migrate ProductVariation_OrderItem.ProductVariationVersion");
		}
		if(class_exists("ProductVariation_OrderItem")) {
			if($this->hasTableAndField("ProductVariation_OrderItem", "ProductVariationID")) {
				DB::query("
					UPDATE \"OrderItem\", \"ProductVariation_OrderItem\"
						SET \"OrderItem\".\"BuyableID\" = \"ProductVariation_OrderItem\".\"ProductVariationID\",
								\"OrderItem\".\"BuyableClassName\" = 'ProductVariation'
					WHERE \"OrderItem\".\"ID\" = \"ProductVariation_OrderItem\".\"ID\"
				");
				$this->makeFieldObsolete("ProductVariation_OrderItem", "ProductVariationID");
				DB::alteration_message("Migrating ProductVariation_OrderItem.ProductVariationID to OrderItem.BuyableID and adding BuyableClassName = ProductVariation", "created");
			}
			else {
				DB::alteration_message("No need to migrate ProductVariation_OrderItem.ProductVariationID");
			}
		}
		else {
			DB::alteration_message("There are not ProductVariations in this project");
		}
	}

	protected function amountToCalculatedTotal_27(){
		DB::alteration_message("
			<h1>27. Move OrderModifier.Amount to OrderAttribute.CalculatedTotal</h1>
			<p>Move the amount of the modifier in the OrderModifier.Amount field to the OrderAttribute.CalculatedTotal field.</p>
		");
		if($this->hasTableAndField("OrderModifier", "Amount")) {
			DB::query("
				UPDATE \"OrderModifier\"
					INNER JOIN \"OrderAttribute\"
						ON \"OrderAttribute\".\"ID\" = \"OrderModifier\".\"ID\"
				SET \"OrderAttribute\".\"CalculatedTotal\" = \"OrderModifier\".\"Amount\"
				WHERE \"OrderAttribute\".\"CalculatedTotal\" IS NULL OR \"OrderAttribute\".\"CalculatedTotal\" = 0
			");
 			$this->makeFieldObsolete("OrderModifier", "Amount");
 			DB::alteration_message('Moved OrderModifier.Amount to OrderAttribute.CalculatedTotal', 'created');
		}
		else {
			DB::alteration_message('There is no need to move OrderModifier.Amount to OrderAttribute.CalculatedTotal');
		}
	}

	protected function currencyToMoneyFields_30(){
		DB::alteration_message("
			<h1>30. Currency to Money Fields</h1>
			<p>Move the Payment Amount in the Amount field to a composite DB field (AmountAmount + AmountCurrency) </p>
		");
		if($this->hasTableAndField("Payment", "Amount")) {
		//ECOMMERCE PAYMENT *************************
			DB::query("
				UPDATE \"Payment\"
				SET \"AmountAmount\" = \"Amount\"
				WHERE
					\"Amount\" > 0
					AND (
						\"AmountAmount\" IS NULL OR \"AmountAmount\" = 0
					)
			");
			$countAmountChanges = DB::affectedRows();
			if($countAmountChanges) {
				DB::alteration_message("Updated Payment.Amount field to 2.4 - $countAmountChanges rows updated", "edited");
			}
		}
		else {
			DB::alteration_message('There is no need to move Payment.Amount to Payment.AmountAmount');
		}
		if($this->hasTableAndField("Payment", "Currency")) {
			DB::query("
				UPDATE \"Payment\"
				SET \"AmountCurrency\" = \"Currency\"
				WHERE
					\"Currency\" <> ''
					AND \"Currency\" IS NOT NULL
					AND (
						\"AmountCurrency\" IS NULL
						OR \"AmountCurrency\" = ''
					)
			");
			$countCurrencyChanges = DB::affectedRows();
			if($countCurrencyChanges) {
				DB::alteration_message("Updated Payment.Currency field to 2.4  - $countCurrencyChanges rows updated", "edited");
			}
			if($countAmountChanges != $countCurrencyChanges) {
				DB::alteration_message("Potential error in Payment fields update to 2.4, please review data", "deleted");
			}
		}
		else {
			DB::alteration_message('There is no need to move Payment.Currency to Payment.AmountCurrency');
		}
	}

	protected function orderShippingCost_40(){
		DB::alteration_message("
			<h1>40. Order Shipping Cost</h1>
			<p>Move the shipping cost in the order to its own modifier.</p>
		");
		if($this->hasTableAndField("Order", "Shipping") && $this->hasTableAndField("Order", "HasShippingCost")) {
			$orders = DataObject::get(
				'Order',
				"\"HasShippingCost\" = 1 AND \"Shipping\" IS NOT NULL",
				null,
				null,
				$this->Start.", ".$this->limit
			);
			if($orders) {
				foreach($orders as $order) {
					$modifier1 = new SimpleShippingModifier();
					$modifier1->CalculatedTotal = $shipping < 0 ? abs($shipping) : $shipping;
					$modifier1->TableValue = $shipping < 0 ? abs($shipping) : $shipping;
					$modifier1->OrderID = $id;
					$modifier1->TableTitle = 'Delivery';
					$modifier1->write();
					DB::alteration_message(" ------------- Added shipping cost.", "created");
				}
				return $this->limit + $this->start;
			}
			else {
				DB::alteration_message("There are no orders with HasShippingCost =1 and Shipping IS NOT NULL.");
				return 0;
			}
			$this->makeFieldObsolete("Order", "HasShippingCost", "tinyint(1)");
			$this->makeFieldObsolete("Order", "Shipping", "decimal(9,2)");
		}
		else {
			DB::alteration_message("No need to update shipping cost.");
		}
	}

	protected function orderTax_45(){
		DB::alteration_message("
			<h1>45. Order Added Tax</h1>
			<p>Move the tax in the order to its own modifier.</p>
		");
		if($this->hasTableAndField("Order", "AddedTax")) {
			DB::alteration_message("Moving Order.AddedTax to Modifier.", "created");
			$orders = DataObject::get(
				'Order',
				"\"AddedTax\" > 0",
				null,
				null,
				$this->start.",".$this->limit
			);
			if($orders) {
				foreach($orders as $order) {
					$id = $order->ID;
					$hasShippingCost = DB::query("SELECT \"AddedTax\" FROM \"Order\" WHERE \"ID\" = '$id'")->value();
					$addedTax = DB::query("SELECT \"AddedTax\" FROM \"Order\" WHERE \"ID\" = '$id'")->value();
					if($addedTax != null && $addedTax > 0) {
						$modifier1 = new FlatTaxModifier();
						$modifier1->CalculatedTotal = $addedTax < 0 ? abs($addedTax) : $addedTax;
						$modifier1->TableValue = $addedTax < 0 ? abs($addedTax) : $addedTax;
						$modifier1->OrderID = $id;
						$modifier1->TableTitle = 'Tax';
						$modifier1->write();
						DB::alteration_message(" ------------- Added tax.", "created");
					}
					else {
						DB::alteration_message(" ------------- No need to add tax even though field is present");
					}
				}
				return $this->start + $this->limit;
			}
			else {
				DB::alteration_message("There are no orders with a AddedTax field greater than zero.");
				return 0;
			}
			$this->makeFieldObsolete("Order", "AddedTax");
		}
		else {
			DB::alteration_message("No need to update taxes.");
		}
	}


	protected function orderShippingAddress_50(){
		DB::alteration_message("
			<h1>50. Order Shipping Address</h1>
			<p>Move a shipping address from within Order to its own class.</p>
		");
		if($this->hasTableAndField("Order", "ShippingAddress")) {
			if($this->hasTableAndField("Order", "UseShippingAddress")) {
				$orders = DataObject::get(
					'Order',
					"\"UseShippingAddress\" = 1 AND \"ShippingAddress\".\"ID\" IS NULL",
					"",
					" LEFT JOIN \"ShippingAddress\" ON \"Order\".\"ShippingAddressID\" = \"ShippingAddress\".\"ID\"",
					$this->start.",".$this->limit
				);
				if($orders) {
					foreach($orders as $order) {
						if(!$order->ShippingAddressID) {
							$obj = new ShippingAddress();
							if(isset($order->ShippingName)) {$obj->ShippingName = $order->ShippingName;}
							if(isset($order->ShippingAddress)) {$obj->ShippingAddress = $order->ShippingAddress;}
							if(isset($order->ShippingAddress2)) {$obj->ShippingAddress2 = $order->ShippingAddress2;}
							if(isset($order->ShippingCity)) {$obj->ShippingCity = $order->ShippingCity;}
							if(isset($order->ShippingPostalCode)) {$obj->ShippingPostalCode = $order->ShippingPostalCode;}
							if(isset($order->ShippingState)) {$obj->ShippingState = $order->ShippingState;}
							if(isset($order->ShippingCountry)) {$obj->ShippingCountry = $order->ShippingCountry;}
							if(isset($order->ShippingPhone)) {$obj->ShippingPhone = $order->ShippingPhone;}
							if(isset($order->ShippingHomePhone)) {$obj->ShippingPhone .= $order->ShippingHomePhone;}
							if(isset($order->ShippingMobilePhone)) {$obj->ShippingMobilePhone = $order->ShippingMobilePhone;}
							$obj->OrderID = $order->ID;
							$obj->write();
							$order->ShippingAddressID = $obj->ID;
							$order->write();
						}
						else {
							DB::alteration_message("Strange contradiction occurred in Order with ID".$order->ID, "deleted");
						}
					}
					return $this->start + $this->limit;
				}
				else {
					DB::alteration_message("No orders need adjusting even though they followed the old pattern.");
					return 0;
				}
				$this->makeFieldObsolete("Order", "ShippingName");
				$this->makeFieldObsolete("Order", "ShippingAddress");
				$this->makeFieldObsolete("Order", "ShippingAddress2");
				$this->makeFieldObsolete("Order", "ShippingCity");
				$this->makeFieldObsolete("Order", "ShippingPostalCode");
				$this->makeFieldObsolete("Order", "ShippingState");
				$this->makeFieldObsolete("Order", "ShippingCountry");
				$this->makeFieldObsolete("Order", "ShippingPhone");
				$this->makeFieldObsolete("Order", "ShippingHomePhone");
				$this->makeFieldObsolete("Order", "ShippingMobilePhone");
			}
			else {
				DB::alteration_message("There is no UseShippingAddress field even though there is a ShippingAddress Field - this is an issue.", "deleted");
			}
		}
		else {
			DB::alteration_message("Orders do not have the shipping address to migrate.");
		}
	}


	protected function orderBillingAddress_51(){
		DB::alteration_message("
			<h1>51. Order Billing Address</h1>
			<p>Move the billing address from the order to its own class.</p>
		");
		if($this->hasTableAndField("Order", "Address")) {
			if($this->hasTableAndField("Order", "City")) {
				if($orders = DataObject::get('Order', " AND \"BillingAddress\".\"ID\" IS NULL", "", " LEFT JOIN \"BillingAddress\" ON \"Order\".\"BillingAddressID\" = \"BillingAddress\".\"ID\"")) {
					foreach($orders as $order) {
						if(!$order->BillingAddressID) {
							$obj = new BillingAddress();
							if(isset($order->Email)) {$obj->BillingEmail = $order->Email;}
							if(isset($order->Surname)) {$obj->BillingSurname = $order->Surname;}
							if(isset($order->FirstName)) {$obj->BillingFirstName = $order->FirstName;}
							if(isset($order->Address)) {$obj->BillingAddress = $order->Address;}
							if(isset($order->AddressLine2)) {$obj->BillingAddress2 = $order->AddressLine2;}
							if(isset($order->Address2)) {$obj->BillingAddress2 .= $order->Address2;}
							if(isset($order->City)) {$obj->BillingCity = $order->City;}
							if(isset($order->PostalCode)) {$obj->BillingPostalCode = $order->PostalCode;}
							if(isset($order->State)) {$obj->BillingState = $order->State;}
							if(isset($order->Country)) {$obj->BillingCountry = $order->Country;}
							if(isset($order->Phone)) {$obj->BillingPhone = $order->Phone;}
							if(isset($order->HomePhone)) {$obj->BillingPhone .= $order->HomePhone;}
							if(isset($order->MobilePhone)) {$obj->BillingMobilePhone = $order->MobilePhone;}
							$obj->OrderID = $order->ID;
							$obj->write();
							$order->BillingAddressID = $obj->ID;
							$order->write();
						}
						else {
							DB::alteration_message("Strange contradiction occurred in Order with ID".$order->ID, "deleted");
						}
					}
				}
				else {
					DB::alteration_message("No orders need adjusting even though they followed the old pattern.");
				}
				$this->makeFieldObsolete("Order", "Email");
				$this->makeFieldObsolete("Order", "FirstName");
				$this->makeFieldObsolete("Order", "Surname");
				$this->makeFieldObsolete("Order", "Address");
				$this->makeFieldObsolete("Order", "Address2");
				$this->makeFieldObsolete("Order", "City");
				$this->makeFieldObsolete("Order", "PostalCode");
				$this->makeFieldObsolete("Order", "State");
				$this->makeFieldObsolete("Order", "Country");
				$this->makeFieldObsolete("Order", "Phone");
				$this->makeFieldObsolete("Order", "HomePhone");
				$this->makeFieldObsolete("Order", "MobilePhone");
			}
			else {
				DB::alteration_message("There is no UseBillingAddress field even though there is a BillingAddress Field - this is an issue.", "deleted");
			}
		}
		else {
			DB::alteration_message("Orders do not have the Billing address to migrate.");
		}
	}



	protected function memberBillingAddress_52(){
		DB::alteration_message("
			<h1>52. Member Billing Address</h1>
			<p>Move address details in the member table to its own class (billingaddress)</p>
		");
		if($this->hasTableAndField("Member", "Address")) {
			if($this->hasTableAndField("Member", "City")) {
				if($orders = DataObject::get('Order', "\"MemberID\" > 0 AND \"BillingAddress\".\"ID\" IS NULL AND \"BillingAddressID\" = 0", "", " LEFT JOIN \"BillingAddress\" ON \"Order\".\"BillingAddressID\" = \"BillingAddress\".\"ID\"")) {
					foreach($orders as $order) {
						if(!$order->BillingAddressID) {
							$member = DataObject::get_by_id("Member", $order->MemberID);
							if($member) {
								$obj = new BillingAddress();
								if(isset($member->Email)) {$obj->BillingEmail = $member->Email;}
								if(isset($member->FirstName)) {$obj->BillingFirstName = $member->FirstName;}
								if(isset($member->Surname)) {$obj->BillingSurname = $member->Surname;}
								if(isset($member->Address)) {$obj->BillingAddress = $member->Address;}
								if(isset($member->AddressLine2)) {$obj->BillingAddress2 = $member->AddressLine2;}
								if(isset($member->City)) {$obj->BillingCity = $member->City;}
								if(isset($member->PostalCode)) {$obj->BillingPostalCode = $member->PostalCode;}
								if(isset($member->State)) {$obj->BillingState = $member->State;}
								if(isset($member->Country)) {$obj->BillingCountry = $member->Country;}
								if(isset($member->Phone)) {$obj->BillingPhone = $member->Phone;}
								if(isset($member->HomePhone)) {$obj->BillingPhone .= $member->HomePhone;}
								if(isset($member->MobilePhone)) {$obj->MobilePhone = $member->MobilePhone;}
								$obj->OrderID = $order->ID;
								$obj->write();
								$order->BillingAddressID = $obj->ID;
								$order->write();
							}
							else {
								DB::alteration_message("There is no memmber associated with this order ".$order->ID, "deleted");
							}
						}
						else {
							DB::alteration_message("Strange contraduction occurred!", "deleted");
						}
					}
				}
				else {
					DB::alteration_message("No orders need adjusting even though they followed the old pattern.");
				}
			}
			else {
				DB::alteration_message("There is no Address2 field, but there is an Address field in Member - this might be an issue.", "deleted");
			}
		}
		else {
			DB::alteration_message("Members do not have a billing address to migrate.");
		}
	}


	protected function moveOrderStatus_60() {
		DB::alteration_message("
			<h1>60. Move Order Status</h1>
			<p>Moving order status from the enum field to Order Step.</p>
		");
		if($this->hasTableAndField("Order", "Status")) {
		// 2) Cancel status update
			$orders = DataObject::get('Order', "\"Status\" = 'Cancelled'");
			if($orders) {
				$admin = Member::currentMember();
				if($admin && $admin->IsAdmin()) {
					foreach($orders as $order) {
						$order->CancelledByID = $admin->ID;
						$order->write();
					}

					DB::alteration_message('The orders which status was \'Cancelled\' have been successfully changed to the status \'AdminCancelled\'', 'changed');
				}
				else {
					DB::alteration_message("You need to be logged in as admin to run this task", "deleted");
					return 0;
				}
			}
			else {
				DB::alteration_message('There are no orders that are cancelled');
			}
			$rows = DB::query("SELECT \"ID\", \"Status\" FROM \"Order\"");
			if($rows) {
				$CartObject = null;
				$UnpaidObject = null;
				$PaidObject = null;
				$SentObject = null;
				$AdminCancelledObject = null;
				$MemberCancelledObject = null;
 				foreach($rows as $row) {
					switch($row["Status"]) {
						case "Cart":
							if(!$CartObject) {
								if(!($CartObject = DataObject::get_one("OrderStep", "\"Code\" = 'CREATED'"))) {
									DB::alteration_message("Creating default steps", "created");
									singleton('OrderStep')->requireDefaultRecords();
								}
							}
							if($CartObject = DataObject::get_one("OrderStep", "\"Code\" = 'CREATED'")) {
								DB::query("UPDATE \"Order\" SET \"StatusID\" = ".$CartObject->ID." WHERE \"Order\".\"ID\" = ".$row["ID"]. " AND (\"StatusID\" = 0 OR \"StatusID\" IS NULL)");
							}
							else {
								DB::alteration_message("Could not find CREATED status", "deleted");
							}
							break;
						case "Query":
						case "Unpaid":
							if(!$UnpaidObject) {
								if(!($UnpaidObject = DataObject::get_one("OrderStep", "\"Code\" = 'SUBMITTED'"))) {
									DB::alteration_message("Creating default steps", "created");
									singleton('OrderStep')->requireDefaultRecords();
								}
							}
							if($UnpaidObject = DataObject::get_one("OrderStep", "\"Code\" = 'SUBMITTED'")) {
								DB::query("UPDATE \"Order\" SET \"StatusID\" = ".$UnpaidObject->ID." WHERE \"Order\".\"ID\" = ".$row["ID"]." AND (\"StatusID\" = 0 OR \"StatusID\" IS NULL)");
							}
							else {
								DB::alteration_message("Could not find SUBMITTED status", "deleted");
							}
							break;
						case "Processing":
						case "Paid":
							if(!$PaidObject) {
								if(!($PaidObject = DataObject::get_one("OrderStep", "\"Code\" = 'PAID'"))) {
									DB::alteration_message("Creating default steps", "created");
									singleton('OrderStep')->requireDefaultRecords();
								}
							}
							if($PaidObject = DataObject::get_one("OrderStep", "\"Code\" = 'PAID'")) {
								DB::query("UPDATE \"Order\" SET \"StatusID\" = ".$PaidObject->ID." WHERE \"Order\".\"ID\" = ".$row["ID"]. " AND (\"StatusID\" = 0 OR \"StatusID\" IS NULL)");
								DB::alteration_message("Updating to PAID status", "created");
							}
							else {
								DB::alteration_message("Could not find new status", "deleted");
							}
							break;
						case "Sent":
						case "Complete":
							if(!$PaidObject) {
								if(!($SentObject = DataObject::get_one("OrderStep", "\"Code\" = 'SENT'"))) {
									DB::alteration_message("Creating default steps", "created");
									singleton('OrderStep')->requireDefaultRecords();
								}
							}
							if($SentObject = DataObject::get_one("OrderStep", "\"Code\" = 'SENT'")) {
								DB::alteration_message("Updating to SENT status", "created");
								DB::query("UPDATE \"Order\" SET \"StatusID\" = ".$SentObject->ID." WHERE \"Order\".\"ID\" = ".$row["ID"]." AND (\"StatusID\" = 0 OR \"StatusID\" IS NULL)");
							}
							elseif($archivedObject = DataObject::get_one("OrderStep", "\"Code\" = 'ARCHIVED'")) {
								DB::alteration_message("Updating to ARCHIVED status", "created");
								DB::query("UPDATE \"Order\" SET \"StatusID\" = ".$archivedObject->ID." WHERE \"Order\".\"ID\" = ".$row["ID"]." AND (\"StatusID\" = 0 OR \"StatusID\" IS NULL)");
							}
							else {
								DB::alteration_message("Could not find new status", "deleted");
							}
							break;
						case "AdminCancelled":
							if(!$AdminCancelledObject) {
								if(!($AdminCancelledObject  = DataObject::get_one("OrderStep", "\"Code\" = 'SENT'"))) {
									singleton('OrderStep')->requireDefaultRecords();
								}
							}
							$adminID = Member::currentUserID();
							if(!$adminID) {
								$adminID = 1;
							}
							DB::alteration_message("Updating to Admin Cancelled", "created");
							DB::query("UPDATE \"Order\" SET \"StatusID\" = ".$AdminCancelledObject->ID.", \"CancelledByID\" = ".$adminID." WHERE \"Order\".\"ID\" = ".$row["ID"]." AND (\"StatusID\" = 0 OR \"StatusID\" IS NULL)");
							break;
						case "MemberCancelled":
							if(!$MemberCancelledObject) {
								if(!($MemberCancelledObject = DataObject::get_one("OrderStep", "\"Code\" = 'SENT'"))) {
									singleton('OrderStep')->requireDefaultRecords();
								}
							}
							DB::alteration_message("Updating to MemberCancelled", "created");
							DB::query("UPDATE \"Order\" SET \"StatusID\" = ".$MemberCancelledObject->ID.", \"CancelledByID\" = \"MemberID\" WHERE \"Order\".\"ID\" = ".$row["ID"]." AND (\"StatusID\" = 0 OR \"StatusID\" IS NULL)");
							break;
						default:
							DB::alteration_message("Unexpected status", "deleted");
					}
				}
			}
			else {
				DB::alteration_message("No orders could be found.");
			}
			$this->makeFieldObsolete("Order", "Status");
		}
		else {
			DB::alteration_message("There is no Status field in the Order Table.");
		}
	}

	protected function fixBadOrderStatus_68(){
		DB::alteration_message("
			<h1>68. Fix Bad Order Status</h1>
			<p>Fixing any orders with an StatusID that is not in use...</p>
		");
		$firstOption = DataObject::get_one("OrderStep");
		if($firstOption) {
			$badOrders = DataObject::get(
				"Order",
				"\"StatusID\" = 0 OR \"StatusID\" IS NULL OR \"OrderStep\".\"ID\" IS NULL", "", " LEFT JOIN \"OrderStep\" ON \"Order\".\"StatusID\" = \"OrderStep\".\"ID\"");
			if($badOrders) {
				foreach($badOrders as $order) {
					if($order->TotalItems() > 0) {
						$order->StatusID = $firstOption->ID;
						$order->write();
						DB::alteration_message("No order status for order number #".$order->ID." reverting to: $firstOption->Name.","error");
					}
				}
			}
			else {
				DB::alteration_message("There are no orders with incorrect order status.");
			}
		}
		else {
			DB::alteration_message("No first order step.","error");
		}
	}


	protected function updateProductGroups_110(){
		DB::alteration_message("
			<h1>110. Update Product Groups: </h1>
			<p>Set the product groups 'show products' to the default.</p>
		");
		$checkIfAnyLevelsAreSetAtAll = DB::query("SELECT COUNT(ID) FROM \"ProductGroup\" WHERE \"LevelOfProductsToShow\" <> 0 AND \"LevelOfProductsToShow\" IS NOT NULL")->value();
		if($checkIfAnyLevelsAreSetAtAll == 0 && ProductGroup::$defaults["LevelOfProductsToShow"] != 0) {
			//level of products to show
			DB::query("
				UPDATE \"ProductGroup\"
				SET \"LevelOfProductsToShow\" = ".ProductGroup::$defaults["LevelOfProductsToShow"]."
				WHERE \"LevelOfProductsToShow\" = 0 OR \"LevelOfProductsToShow\" IS NULL "
			);
			DB::query("
				UPDATE \"ProductGroup_Live\"
				SET \"LevelOfProductsToShow\" = ".ProductGroup::$defaults["LevelOfProductsToShow"]."
				WHERE \"LevelOfProductsToShow\" = 0 OR \"LevelOfProductsToShow\"  IS NULL "
			);
			DB::alteration_message("resetting product 'show' levels", "created");
			//default sort order
			DB::query("
				UPDATE \"ProductGroup\"
				SET \"DefaultSortOrder\" = ".ProductGroup::$defaults["DefaultSortOrder"]."
				WHERE \"DefaultSortOrder\" = 0 OR  \"DefaultSortOrder\" = '' OR  \"DefaultSortOrder\" IS NULL "
			);
			DB::query("
				UPDATE \"ProductGroup_Live\"
				SET \"DefaultSortOrder\" = ".ProductGroup::$defaults["DefaultSortOrder"]."
				WHERE \"DefaultSortOrder\" = 0 OR  \"DefaultSortOrder\" = '' OR  \"DefaultSortOrder\" IS NULL "
			);
			DB::alteration_message("resetting product default sort order", "created");
			//default filter
			DB::query("
				UPDATE \"ProductGroup\"
				SET \"DefaultFilter\" = ".ProductGroup::$defaults["DefaultFilter"]."
				WHERE \"DefaultFilter\" = 0 OR  \"DefaultFilter\" = '' OR  \"DefaultFilter\" IS NULL "
			);
			DB::query("
				UPDATE \"ProductGroup_Live\"
				SET \"DefaultFilter\" = ".ProductGroup::$defaults["DefaultFilter"]."
				WHERE \"DefaultFilter\" = 0 OR  \"DefaultFilter\" = '' OR  \"DefaultFilter\" IS NULL "
			);
			DB::alteration_message("resetting product default filter", "created");
		}
		else {
			DB::alteration_message("there is no need for resetting product 'show' levels");
		}
	}

	protected function setFixedPriceForSubmittedOrderItems_120() {
		DB::alteration_message("
			<h1>120. Set Fixed Price for Submitted Order Items: </h1>
			<p>Migration task to fix the price for submitted order items.</p>
		");
		if($this->hasTableAndField("OrderModifier", "CalculationValue")) {
			DB::query("
				UPDATE \"OrderAttribute\"
				INNER JOIN \"OrderModifier\"
					ON \"OrderAttribute\".\"ID\" = \"OrderModifier\".\"ID\"
				SET \"OrderAttribute\".\"CalculatedTotal\" = \"OrderModifier\".\"CalculationValue\"
				WHERE \"OrderAttribute\".\"CalculatedTotal\" = 0"
			);
			$this->makeFieldObsolete("OrderModifier", "CalculationValue");
			DB::alteration_message("Moving values from OrderModifier.CalculationValue to OrderAttribute.CalculatedTotal", "created");
		}
		else {
			DB::alteration_message("There is no need to move values from OrderModifier.CalculationValue to OrderAttribute.CalculatedTotal");
		}
		$orderItems = DataObject::get(
			"OrderItem",
			"\"Quantity\" <> 0 AND \"OrderAttribute\".\"CalculatedTotal\" = 0",
			"\"Created\" ASC",
			"INNER JOIN
				\"Order\" ON \"Order\".\"ID\" = \"OrderAttribute\".\"OrderID\""
		);
		return;
		/////////////////////////////////
		///////// We should not include the code below
		///////// Because it may affect past orders badly.
		/////////////////////////////////
		/////////////////////////////////
		$count = 0;
		if($orderItems) {
			foreach($orderItems as $orderItem) {
				if($orderItem->Order()) {
					if($orderItem->Order()->IsSubmitted()) {
						//TO DO: WHAT THE HELL IS THAT (true)
						$unitPrice = $orderItem->UnitPrice($recalculate = true);
						if($unitPrice) {
							$orderItem->CalculatedTotal = $unitPrice * $orderItem->Quantity;
							$orderItem->write();
							$count++;
							DB::alteration_message("RECALCULATING: ".$orderItem->UnitPrice($recalculate = true)." * ".$orderItem->Quantity  ." = ".$orderItem->CalculatedTotal." for OrderItem #".$orderItem->ID, "created");
						}
					}
					else {
						DB::alteration_message("OrderItem is part of not-submitted order.");
					}
				}
				else {
					DB::alteration_message("OrderItem does not have an order! (OrderItemID: ".$orderItem->ID.")", "deleted");
				}
			}
		}
		else {
			DB::alteration_message("All order items have a calculated total....");
		}
		if($count) {
			DB::alteration_message("Fixed price for all submmitted orders without a fixed one - affected: $count order items", "created");
		}
	}

	protected function moveSiteConfigToEcommerceDBConfig_140(){
		DB::alteration_message("
			<h1>140. Move Site Config fields to Ecommerce DB Config</h1>
			<p>Moving the general config fields from the SiteConfig to the EcommerceDBConfig.</p>
		");
		$fields = array(
			"ShopClosed",
			"ShopPricesAreTaxExclusive",
			"ShopPhysicalAddress",
			"ReceiptEmail",
			"PostalCodeURL",
			"PostalCodeLabel",
			"NumberOfProductsPerPage",
			"OnlyShowProductsThatCanBePurchased",
			"ProductsHaveWeight",
			"ProductsHaveModelNames",
			"ProductsHaveQuantifiers",
			"ProductsAlsoInOtherGroups",
			"ProductsHaveVariations",
			"EmailLogoID",
			"DefaultProductImageID"
		);
		$ecomConfig = DataObject::get_one("EcommerceDBConfig");
		if(!$ecomConfig) {
			$ecomConfig = new EcommerceDBConfig();
			$ecomConfig->write();
		}
		$sc = SiteConfig::current_site_config();
		if($ecomConfig && $sc) {
			foreach($fields as $field) {
				if($this->hasTableAndField("SiteConfig", $field)) {
					if(!$this->hasTableAndField("EcommerceDBConfig", $field)) {
						DB::alteration_message("Could not find EcommerceDBConfig.$field - this is unexpected!", "deleted");
					}
					else {
						DB::alteration_message("Migrated SiteConfig.$field", "created");
						$ecomConfig->$field = DB::query("SELECT \"$field\" FROM \"SiteConfig\" WHERE \"ID\" = ".$sc->ID)->value();
						$ecomConfig->write();
						$this->makeFieldObsolete("SiteConfig", $field);
					}
				}
				else {
					DB::alteration_message("SiteConfig.$field has been moved");
				}
			}
		}
		else {
			DB::alteration_message("ERROR: SiteConfig or EcommerceDBConfig are not available", "deleted");
		}
	}

	function addClassNameToOrderItems_150() {
		DB::alteration_message("
			<h1>150. Add a class name to all buyables.</h1>
			<p>ClassNames used to be implied, this is now saved as OrderItem.BuyableClassName.</p>
		");
		$rows = DB::query("
			SELECT \"OrderAttribute\".\"ID\", \"ClassName\"
			FROM \"OrderAttribute\"
				INNER JOIN \"OrderItem\" ON \"OrderItem\".\"ID\" = \"OrderAttribute\".\"ID\"
			WHERE \"BuyableClassName\" = '' OR \"BuyableClassName\" IS NULL;
		");
		if($rows) {
			foreach($rows as $row) {
				$orderItemPostFix = "_OrderItem";
				$id = $row["ID"];
				$className = str_replace($orderItemPostFix, "", $row["ClassName"]);
				if(class_exists($className) && ClassInfo::is_subclass_of($className, "DataObject")) {
					DB::query("
						UPDATE \"OrderItem\"
						SET \"BuyableClassName\" = '$className'
						WHERE \"ID\" = $id;
					");
					DB::alteration_message("Updating Order.BuyableClassName ( ID = $id ) to $className.", "created");
				}
				else {
					DB::alteration_message("Order Item with ID = $id does not have a valid class name. This needs investigation.", "deleted");
				}
			}
		}
		else {
			DB::alteration_message("No order items could be found that need updating.");
		}
	}


	function addTermsAndConditionsMessage_160() {
		DB::alteration_message("
			<h1>160. Add checkout message TermsAndConditionsMessage message.</h1>
			<p>Adds TermsAndConditionsMessage if there is a terms page.</p>
		");
		$checkoutPage = DataObject::get_one("CheckoutPage");
		if($checkoutPage) {
			if($checkoutPage->TermsPageID) {
				if(!$checkoutPage->TermsAndConditionsMessage) {
					$checkoutPage->TermsAndConditionsMessage = CheckoutPage::$defaults["TermsAndConditionsMessage"];
					$checkoutPage->writeToStage('Stage');
					$checkoutPage->publish('Stage', 'Live');
					DB::alteration_message("Added TermsAndConditionsMessage", "created");
					return 0;
				}
				else {
					DB::alteration_message("There was no need to add a terms and conditions message because there was already a message.");
				}
			}
			else {
				DB::alteration_message("There was no need to add a terms and conditions message because there is no terms and conditions page.");
			}
		}
		else {
			DB::alteration_message("There was no need to add a terms and conditions message because there is no checkout page", "deleted");
		}
	}

	function mergeUncompletedOrderForOneMember_170() {
		DB::alteration_message("
			<h1>170. Merge uncompleted orders into one.</h1>
			<p>Merges uncompleted orders by the same user into one.</p>
		");
		$orders = DataObject::get(
			"Order",
			"\"MemberID\" > 0",
			"\"MemberID\", \"Order\".\"Created\" DESC", // THIS ORDER IS CRUCIAL!!!!
			"INNER JOIN \"Member\" ON \"Order\".\"MemberID\" = \"Member\".\"ID\" ",
			$this->start.", ".$this->limit
		);
		$count = 0;
		$previousOrderMemberID = 0;
		$lastOrderFromMember = null;
		if($orders) {
			foreach($orders as $order) {
				//crucial ONLY for non-submitted orders...
				if($order->IsSubmitted()) {
					//do nothing!
					$count++;
				}
				else {
					$memberID = $order->MemberID;
					//recurring member
					if($previousOrderMemberID == $memberID && $lastOrderFromMember) {
						DB::alteration_message("We have a duplicate order for a member: ".$order->Member()->Email, "created");
						$orderAttributes = DataObject::get("OrderAttribute", "\"OrderID\" = ".$order->ID);
						if($orderAttributes) {
							foreach($orderAttributes as $orderAttribute) {
								$orderAttribute->OrderID = $lastOrderFromMember->ID;
								$orderAttribute->write();
								DB::alteration_message("Moving attribute #".$orderAttribute->ID, "created");
							}
						}
						else {
							DB::alteration_message("There are no attributes for this order");
						}
						$orderStatusLogs = DataObject::get("OrderStatusLog", "\"OrderID\" = ".$order->ID);
						if($orderStatusLogs) {
							foreach($orderStatusLogs as $orderStatusLog) {
								$orderStatusLog->OrderID = $lastOrderFromMember->ID;
								$orderStatusLog->write();
								DB::alteration_message("Moving order status log #".$orderStatusLog->ID, "created");
							}
						}
						else {
							DB::alteration_message("There are no order status logs for this order");
						}
						$orderEmailRecords = DataObject::get("OrderEmailRecord", "\"OrderID\" = ".$order->ID);
						if($orderEmailRecords) {
							foreach($orderEmailRecords as $orderEmailRecord) {
								$orderEmailRecord->OrderID = $lastOrderFromMember->ID;
								$orderEmailRecord->write();
								DB::alteration_message("Moving email #".$orderEmailRecord->ID, "created");
							}
						}
						else {
							DB::alteration_message("There are no emails for this order.");
						}
					}
					//new member
					else {
						$previousOrderMemberID = $order->MemberID;
						$lastOrderFromMember = $order;
						DB::alteration_message("Found last order from member.");
					}
					if($order->BillingAddressID && !$lastOrderFromMember->BillingAddressID) {
						$lastOrderFromMember->BillingAddressID = $order->BillingAddressID;
						$lastOrderFromMember->write();
						DB::alteration_message("Moving Billing Address.");
					}
					if($order->ShippingAddressID && !$lastOrderFromMember->ShippingAddressID) {
						$lastOrderFromMember->ShippingAddressID = $order->ShippingAddressID;
						$lastOrderFromMember->write();
						DB::alteration_message("Moving Shipping Address.");
					}
					$order->delete();
				}
			}
			DB::alteration_message("Ignored $count Orders that have already been submitted.");
		}
		else {
			DB::alteration_message("There were no orders at all to work through.");
		}

	}


	function updateFullSiteTreeSortFieldForAllProducts_180() {
		DB::alteration_message("
			<h1>180. Set starting value Product.FullSiteTreeSort Field.</h1>
			<p>Sets a starting value for a new field: FullSiteTreeSortField.</p>
		");
		//level 10
		$task = new CleanupProductFullSiteTreeSorting();
		$task->setDeleteFirst(false);
		$task->run(null);
	}



	function updateOrderStatusLogSequentialOrderNumber_190() {
		DB::alteration_message("
			<h1>190. Set sequential order numbers</h1>
			<p>Prepopulates old orders for OrderStatusLog_Submitted.SequentialOrderNumber.</p>
		");
		$objects = DataObject::get(
			"OrderStatusLog_Submitted",
			"",
			"\"Created\" ASC",
			null,
			$this->start.", ".$this->limit
		);
		$changes = 0;
		if($objects) {
			foreach($objects as $object) {
				$old = $object->SequentialOrderNumber;
				$object->write();
				$new = $object->SequentialOrderNumber;
				if($old != $new) {
					$changes++;
					DB::alteration_message("Changed the SequentialOrderNumber for order #".$object->OrderID." from $old to $new ");
				}
			}
			if(!$changes) {
				DB::alteration_message("There were no changes in any of the OrderStatusLog_Submitted.SequentialOrderNumber fields.");
			}
			return $this->limit + $this->start;
		}
		else {
			DB::alteration_message("There are no logs to update.");
			return 0;
		}
	}

	function resaveAllPRoducts_200() {
		DB::alteration_message("
			<h1>200. Resave All Products to update the FullName and FullSiteTreeSort Field</h1>
			<p>Saves and PUBLISHES all the products on the site. You may need to run this task several times.</p>
		");
		$count = 0;
		$objects = DataObject::get(
			"Product",
			"\"FullName\" = '' OR \"FullName\" IS NULL OR 1 = 1",
			"\"FullName\" ASC",
			null,
			$this->start.", ".$this->limit
		);
		if($objects) {
			foreach($objects as $object) {
				if($object->prepareFullFields()) {
					$count++;
					$object->writeToStage('Stage');
					$object->publish('Stage', 'Live');
				}
			}
			if($count >= $this->limit) {
				DB::alteration_message("This task has not completed yet, please run again!", "deleted");
			}
			return $this->start + $this->limit;
		}
		else {
			DB::alteration_message("No products to update.");
			return 0;
		}
	}

	function resaveAllPRoductsVariations_210() {
		DB::alteration_message("
			<h1>210. Resave All Product Variations to update the FullName and FullSiteTreeSort Field</h1>
			<p>Saves all the product variations on the site. You may need to run this task several times.</p>
		");
		$count = 0;
		if(class_exists("ProductVariation")) {
			$objects = DataObject::get(
				"ProductVariation",
				"\"FullName\" = '' OR \"FullName\" IS NULL",
				"\"FullName\" ASC",
				null,
				$this->start.", ".$this->limit
			);
			if($objects) {
				foreach($objects as $object) {
					if($object->prepareFullFields()) {
						$count++;
						$object->write();
					}
				}
				if($count >= $this->limit) {
					DB::alteration_message("This task has not completed yet, please run again!", "deleted");
				}
				return $this->start + $this->limit;
			}
			else {
				DB::alteration_message("No product variations to update.");
				return 0;
			}
		}
		else {
			DB::alteration_message("There are not ProductVariations in this project");
		}
	}

	function addConfirmationPage_250(){
		$checkoutPage = DataObject::get_one("CheckoutPage");
		if($checkoutPage) {
			if(!DataObject::get_one("OrderConfirmationPage")) {
				$orderConfirmationPage = new OrderConfirmationPage();
				$orderConfirmationPage->ParentID = $checkoutPage->ID;
				$orderConfirmationPage->writeToStage('Stage');
				$orderConfirmationPage->publish('Stage', 'Live');
			}
		}
	}

	function theEnd_9999(){
		DB::alteration_message("<hr /><hr /><hr /><hr />THE END <hr /><hr /><hr /><hr /><hr /><hr />");
	}

}

