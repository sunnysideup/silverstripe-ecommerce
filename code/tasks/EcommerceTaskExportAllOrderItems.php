<?php

/**
 * set the order id number.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class EcommerceTaskExportAllOrderItems extends BuildTask{

	protected $title = "Export all order items to CSV";

	protected $description = "allows download of all sales items with all details as CSV. Excludes sales made by Admins";

	function run($request){
		//reset time limit
		set_time_limit(0);

		//file data
		$now = Date("d-m-Y-H-i");
		$fileName = "export-$now.csv";
		$fileLocation = Director::baseFolder().'/'.$fileName;

		//data object variables
		$orderStatusSubmissionLog = EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order");
		$fileData = "";
		$offset = 0;
		$count = 50;
		while(
			$orders = DataObject::get(
				"Order",
				"",
				"\"Order\".\"ID\" ASC",
				"	INNER JOIN \"OrderStatusLog\" ON \"Order\".\"ID\" = \"OrderStatusLog\".\"OrderID\"
					INNER JOIN \"$orderStatusSubmissionLog\" ON \"$orderStatusSubmissionLog\".\"ID\" = \"OrderStatusLog\".\"ID\"
					LEFT JOIN \"Member\" ON \"Member\".\"ID\" = \"Order\".\"MemberID\"
				",
				"$offset, $count"
		)) {
			$offset = $offset + $count;
			foreach($orders as $order) {
				if($order->IsSubmitted()) {
					$memberIsOK = false;
					if(!$order->MemberID) {
						$memberIsOK = true;
					}
					elseif(!$order->Member()) {
						$memberIsOK = true;
					}
					elseif($member = $order->Member()) {
						$memberIsOK = true;
						if($member->IsShopAdmin()) {
							$memberIsOK = false;
						}
					}
					if($memberIsOK) {
						if($items = DataObject::get("OrderItem", "OrderID = ".$order->ID)) {
							$fileData .= $this->generateExportFileData($order->getOrderEmail(), $order->SubmissionLog()->Created, $items);
						}
					}
				}
			}
			unset($orders);
		}
		if($fileData){
			file_put_contents($fileLocation, $fileData);
			Director::redirect("/".$fileName);
		}
		else{
			user_error("No records found", E_USER_ERROR);
		}
	}


	function generateExportFileData($email, $date, $orderItems) {
		$separator = ",";
		$fileData = '';
		$columnData = array();
		$exportFields = array(
			'Email',
			'OrderID',
			'InternalItemID',
			'TableTitle',
			'TableSubTitleNOHTML',
			'UnitPrice',
			'Quantity',
			'CalculatedTotal',
		);

		if($orderItems) {
			foreach($orderItems as $item) {
				$columnData = array();
				$columnData[] = '"'.$email.'"';
				$columnData[] = '"'.$date.'"';
				foreach($exportFields as $field) {
					$value = $item->$field;
					$value = preg_replace( '/\s+/', ' ', $value );
					$value = str_replace(array("\r", "\n"), "\n", $value);
					$tmpColumnData = '"' . str_replace('"', '\"', $value) . '"';
					$columnData[] = $tmpColumnData;
				}
				$fileData .= implode($separator, $columnData);
				$fileData .= "\n";
				$item->destroy();
				unset($item);
				unset($columnData);
			}
			return $fileData;
		}
		else {
			return "";
		}
	}


}
