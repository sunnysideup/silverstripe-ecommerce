<?php


/**
 * @description: migrates older versions of e-commerce to the latest one.
 * This has been placed here rather than in the individual classes for the following reasons:
 * - not to clog up individual classes
 * - to get a complete overview in one class
 * - to be able to run parts and older and newer versionw without having to go through several clases to retrieve them
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: setup
 *
 **/




class EcommerceMigration extends BuildTask {

	protected $title = "Ecommerce Migration";

	protected $description = "
		Migrates all the data from the oldest version of e-commerce to the current one.
		Any obsolete fields will be renamed like this: _obsolete_MyField, but not deleted.  The migration will not work so well if you have a very high number of Orders.
		You may run it several times.
	";

	function run($request) {
		$this->ShopMemberToMemberTableMigration_10();
		$this->MoveItemToBuyable_20();
		$this->ProductVersionToOrderItem_25();
		$this->ProductIDToBuyableID_26();
		$this->AmountToCalculatedTotal_27();
		$this->CurrencyToMoneyFields_30();
		$this->OrderShippingCost_40();
		$this->OrderTax_45();
		$this->OrderShippingAddress_50();
		$this->OrderBillingAddress_51();
		$this->MemberBillingAddress_52();
		$this->MoveOrderStatus_60();
		$this->FixBadOrderStatus_68();
		$this->UpdateProductGroups_110();
		$this->SetFixedPriceForSubmittedOrderItems_120();
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
		$db = DB::getConn();
		if($this->hasTableAndField($table, $field)) {
			$db->dontRequireField($table, $field);
			DB::alteration_message("removed $field from $table", "deleted");
		}
		DB::alteration_message("could not find $field in $table so it could not be removed", "deleted");
	}


	function ShopMemberToMemberTableMigration_10() {
		DB::alteration_message("<h1>10. ShopMember to Member</h1>");
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

	function MoveItemToBuyable_20(){
		DB::alteration_message("<h1>20. Move ItemID to Buyable</h1>");
		if($this->hasTableAndField("OrderItem", "ItemID")) {
			DB::query("UPDATE \"OrderItem\" SET \"OrderItem\".\"BuyableID\" = \"OrderItem\".\"ItemID\"");
 			$this->makeFieldObsolete("OrderItem", "ItemID");
 			DB::alteration_message('Moved ItemID to BuyableID in OrderItem', 'created');
		}
		else {
			DB::alteration_message('There is no need to move from ItemID to BuyableID');
		}
	}

	function ProductVersionToOrderItem_25() {
		DB::alteration_message("<h1>25. ProductVersion to Version</h1>");
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
	function ProductIDToBuyableID_26() {
		DB::alteration_message("<h1>26. ProductID to to BuyableID</h1>");
		if($this->hasTableAndField("Product_OrderItem", "ProductID")) {
			DB::query("
				UPDATE \"OrderItem\", \"Product_OrderItem\"
					SET \"OrderItem\".\"BuyableID\" = \"Product_OrderItem\".\"ProductID\"
				WHERE \"OrderItem\".\"ID\" = \"Product_OrderItem\".\"ID\"
			");
			$this->makeFieldObsolete("Product_OrderItem", "ProductID");
			DB::alteration_message("Migrating Product_OrderItem.ProductID to OrderItem.BuyableID", "created");
		}
		else {
			DB::alteration_message("There is no need to migrate Product_OrderItem.ProductID to OrderItem.BuyableID");
		}
	}

	function AmountToCalculatedTotal_27(){
		DB::alteration_message("<h1>27. Move OrderModifier.Amount to OrderAttribute.CalculatedTotal</h1>");
		if($this->hasTableAndField("OrderModifier", "Amount")) {
			DB::query("
				UPDATE \"OrderItem\"
					INNER JOIN \"OrderAttribute\"
						ON \"OrderAttribute\".\"ID\" = \"OrderModifier\".\"ID\" =
				SET \"OrderAttribute\".\"CalculatedTotal\" = \"OrderModifier\".\"Amount\"
			");
 			$this->makeFieldObsolete("OrderModifier", "Amount");
 			DB::alteration_message('Moved OrderModifier.Amount to OrderAttribute.CalculatedTotal', 'created');
		}
		else {
			DB::alteration_message('There is no need to move OrderModifier.Amount to OrderAttribute.CalculatedTotal');
		}
	}
	function CurrencyToMoneyFields_30(){
		DB::alteration_message("<h1>30. Currency to Money Fields</h1>");
		if($this->hasTableAndField("Payment", "Amount")) {
		//ECOMMERCE PAYMENT *************************
			DB::query("
				UPDATE \"Payment\"
				SET \"AmountAmount\" = \"Amount\"
				WHERE
					\"Amount\" > 0
					AND (
						\"AmountAmount\" IS NULL
						OR \"AmountAmount\" = 0
					)
			");
			$countAmountChanges = DB::affectedRows();
			if($countAmountChanges) {
				DB::alteration_message("Updated Payment.Amount field to 2.4 - $countAmountChanges rows updated", "edited");
			}
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
	}


	function OrderShippingCost_40(){
		DB::alteration_message("<h1>40. Order Shipping Cost</h1>");
		if($this->hasTableAndField("Order", "Shipping") && $this->hasTableAndField("Order", "HasShippingCost")) {
			if($orders = DataObject::get('Order', "\"HasShippingCost\" = 1 AND \"Shipping\" IS NOT NULL")) {
				foreach($orders as $order) {
					$modifier1 = new SimpleShippingModifier();
					$modifier1->CalculatedTotal = $shipping < 0 ? abs($shipping) : $shipping;
					$modifier1->TableValue = $shipping < 0 ? abs($shipping) : $shipping;
					$modifier1->OrderID = $id;
					$modifier1->TableTitle = 'Delivery';
					$modifier1->write();
					DB::alteration_message(" ------------- Added shipping cost.", "created");
				}
			}
			else {
				DB::alteration_message("There are no orders with HasShippingCost =1 and Shipping IS NOT NULL.");
			}
			$this->makeFieldObsolete("Order", "HasShippingCost", "tinyint(1)");
			$this->makeFieldObsolete("Order", "Shipping", "decimal(9,2)");
		}
		else {
			DB::alteration_message("No need to update shipping cost.");
		}
	}

	function OrderTax_45(){
		DB::alteration_message("<h1>45. Order Added Tax</h1>");
		if($this->hasTableAndField("Order", "AddedTax")) {
			DB::alteration_message("Moving Order.AddedTax to Modifier.", "created");
			if($orders = DataObject::get('Order', "\"AddedTax\" > 0")) {
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
			}
			else {
				DB::alteration_message("There are no orders with a AddedTax field greater than zero.");
			}
			$this->makeFieldObsolete("Order", "AddedTax");
		}
		else {
			DB::alteration_message("No need to update taxes.");
		}
	}


	function OrderShippingAddress_50(){
		DB::alteration_message("<h1>50. Order Shipping Address</h1>");
		if($this->hasTableAndField("Order", "ShippingAddress")) {
			if($this->hasTableAndField("Order", "UseShippingAddress")) {
				if($orders = DataObject::get('Order', "\"UseShippingAddress\" = 1 AND \"ShippingAddress\".\"ID\" IS NULL", "", " LEFT JOIN \"ShippingAddress\" ON \"ShippingAddress\".\"OrderID\" = \"Order\".\"ID\"")) {
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
					}
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


	function OrderBillingAddress_51(){
		DB::alteration_message("<h1>51. Order Billing Address</h1>");
		if($this->hasTableAndField("Order", "Address")) {
			if($this->hasTableAndField("Order", "City")) {
				if($orders = DataObject::get('Order', " AND \"BillingAddress\".\"ID\" IS NULL", "", " LEFT JOIN \"BillingAddress\" ON \"BillingAddress\".\"OrderID\" = \"Order\".\"ID\"")) {
					foreach($orders as $order) {
						if(!$order->BillingAddressID) {
							$obj = new BillingAddress();
							if(isset($order->Email)) {$obj->BillingEmail = $order->Email;}
							if(isset($order->Surname)) {$obj->BillingSurname = $order->Surname;}
							if(isset($order->FirstName)) {$obj->BillingFirstName = $order->FirstName;}
							if(isset($order->Address)) {$obj->BillingAddress = $order->Address;}
							if(isset($order->AddressLine2)) {$obj->BillingAddress2 = $order->AddressLine2;}
							if(isset($order->Address2)) {$obj->BillingAddress2 = $order->Address2;}
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
					}
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



	function MemberBillingAddress_52(){
		DB::alteration_message("<h1>52. Member Billing Address</h1>");
		if($this->hasTableAndField("Member", "Address")) {
			if($this->hasTableAndField("Member", "City")) {
				if($orders = DataObject::get('Order', "\"MemberID\" > 0 AND \"BillingAddress\".\"ID\" IS NULL", "", " LEFT JOIN \"BillingAddress\" ON \"BillingAddress\".\"OrderID\" = \"Order\".\"ID\"")) {
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
					}
				}
				//do not delete member fields, because they might be used in other places.
			}
			else {
				DB::alteration_message("There is no Address2 field, but there is an Address field in Member - this might be an issue.", "deleted");
			}
		}
		else {
			DB::alteration_message("Members do not have a billing address to migrate.");
		}
	}


	function MoveOrderStatus_60() {
		DB::alteration_message("<h1>60. Move Order Status</h1>");
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
					return;
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
									singleton('OrderStep')->requireDefaultRecords();
								}
							}
							if($CartObject = DataObject::get_one("OrderStep", "\"Code\" = 'CREATED'")) {
								DB::query("UPDATE \"Order\" SET \"StatusID\" = ".$CartObject->ID." WHERE \"Order\".\"ID\" = ".$row["ID"]);
							}
							break;
						case "Query":
						case "Unpaid":
							if(!$UnpaidObject) {
								if(!($UnpaidObject = DataObject::get_one("OrderStep", "\"Code\" = 'SUBMITTED'"))) {
									singleton('OrderStep')->requireDefaultRecords();
								}
							}
							if($UnpaidObject = DataObject::get_one("OrderStep", "\"Code\" = 'SUBMITTED'")) {
								DB::query("UPDATE \"Order\" SET \"StatusID\" = ".$UnpaidObject->ID." WHERE \"Order\".\"ID\" = ".$row["ID"]);
							}

							break;
						case "Processing":
						case "Paid":
							if(!$PaidObject) {
								if(!($PaidObject = DataObject::get_one("OrderStep", "\"Code\" = 'PAID'"))) {
									singleton('OrderStep')->requireDefaultRecords();
								}
							}
							if($PaidObject = DataObject::get_one("OrderStep", "\"Code\" = 'PAID'")) {
								DB::query("UPDATE \"Order\" SET \"StatusID\" = ".$PaidObject->ID." WHERE \"Order\".\"ID\" = ".$row["ID"]);
							}
							break;
						case "Sent":
						case "Complete":
							if(!$PaidObject) {
								if(!($SentObject = DataObject::get_one("OrderStep", "\"Code\" = 'SENT'"))) {
									singleton('OrderStep')->requireDefaultRecords();
								}
							}
							if($SentObject = DataObject::get_one("OrderStep", "\"Code\" = 'SENT'")) {
								DB::query("UPDATE \"Order\" SET \"StatusID\" = ".$SentObject->ID." WHERE \"Order\".\"ID\" = ".$row["ID"]);
							}
							break;
						case "AdminCancelled":
							if(!$AdminCancelledObject) {
								if(!($AdminCancelledObject  = DataObject::get_one("OrderStep", "\"Code\" = 'SENT'"))) {
									singleton('OrderStep')->requireDefaultRecords();
								}
							}
							if(!$adminID) {
								$adminID = Member::currentUserID();
								if(!$adminID) {
									$adminID = 1;
								}
							}
							DB::query("UPDATE \"Order\" SET \"StatusID\" = ".$AdminCancelledObject->ID." WHERE \"Order\".\"ID\" = ".$row["ID"].", \"CancelledByID\" = ".$adminID);
							break;
						case "MemberCancelled":
							if(!$MemberCancelledObject) {
								if(!($MemberCancelledObject = DataObject::get_one("OrderStep", "\"Code\" = 'SENT'"))) {
									singleton('OrderStep')->requireDefaultRecords();
								}
							}
							DB::query("UPDATE \"Order\" SET \"StatusID\" = ".$MemberCancelledObject->ID.", \"CancelledByID\" = \"MemberID\" WHERE \"Order\".\"ID\" = '".$row["ID"]."'");
							break;
					}
				}
			}
			$this->makeFieldObsolete("Order", "Status");
		}
		else {
			DB::alteration_message("There is no Status field in the Order Table.");
		}
	}

	function FixBadOrderStatus_68(){
		DB::alteration_message("<h1>68. Fix Bad Order Status</h1>");
		$firstOption = DataObject::get_one("OrderStep");
		if($firstOption) {
			$badOrders = DataObject::get("Order", "\"StatusID\" = 0 OR \"StatusID\" IS NULL OR \"OrderStep\".\"ID\" IS NULL", "", " LEFT JOIN \"OrderStep\" ON \"Order\".\"StatusID\" = \"OrderStep\".\"ID\"");
			if($badOrders) {
				foreach($badOrders as $order) {
					$order->StatusID = $firstOption->ID;
					$order->write();
					DB::alteration_message("No order status for order number #".$order->ID." reverting to: $firstOption->Name.","error");
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


	function UpdateProductGroups_110(){
		DB::alteration_message("<h1>110. Update Product Groups: Sets the product groups 'show products' to the default.</h1>");
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
				WHERE \"LevelOfProductsToShow\" = 0 OR \"LevelOfProductsToShow\" = IS NULL "
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

	function SetFixedPriceForSubmittedOrderItems_120() {
		DB::alteration_message("<h1>Set Fixed Price for Submitted Order Items: Migration taks to fix the price for submitted order items</h1>");
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
		$limit = 1000;
		$orderItems = DataObject::get(
			"OrderItem",
			"\"Quantity\" <> 0 AND \"OrderAttribute\".\"CalculatedTotal\" = 0",
			"\"Created\" ASC",
			"INNER JOIN
				\"Order\" ON \"Order\".\"ID\" = \"OrderAttribute\".\"OrderID\"",
			1000
		);
		$count = 0;
		if($orderItems) {
			foreach($orderItems as $orderItem) {
				if($orderItem->Order()) {
					if($orderItem->Order()->IsSubmitted()) {
						$orderItem->CalculatedTotal = $orderItem->UnitPrice(true) * $orderItem->Quantity;
						$orderItem->write();
						$count++;
						DB::alteration_message($orderItem->UnitPrice(true)." * ".$orderItem->Quantity  ." = ".$orderItem->CalculatedTotal, "created");
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


}

