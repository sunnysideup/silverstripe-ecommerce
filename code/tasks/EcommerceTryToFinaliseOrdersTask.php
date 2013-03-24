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

class EcommerceTryToFinaliseOrdersTask extends BuildTask {

	protected $title = 'Try to finalise all orders WITHOUT SENDING EMAILS';

	protected $description = "This task can be useful in moving a bunch of orders through the latest order step. It will only move orders if they can be moved through order steps.  You may need to run this task several times to move all orders.";

	/**
	 *@return Integer - number of carts destroyed
	 **/
	public function run($request){
		//IMPORTANT!
		Email::send_all_emails_to("no-one@localhost");
		Email::set_mailer( new EcommerceTryToFinaliseOrdersTask_Mailer() );
		$orderStatusLogClassName = "OrderStatusLog";
		$submittedOrderStatusLogClassName = EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order");
		if($submittedOrderStatusLogClassName) {
			$submittedStatusLog = DataObject::get_one($submittedOrderStatusLogClassName);
			if($submittedStatusLog) {
				$orderSteps = DataObject::get("OrderStep", "", "\"Sort\" DESC", "", 1);
				$lastOrderStep = $orderSteps->First();
				if($lastOrderStep) {
					$joinSQL = "INNER JOIN \"$orderStatusLogClassName\" ON \"$orderStatusLogClassName\".\"OrderID\" = \"Order\".\"ID\"";
					$whereSQL = "\"StatusID\" <> ".$lastOrderStep->ID."";
					$count = null;
					if(isset($_GET["count"])) {
						$count = intval($_GET["count"]);
					}
					if(!intval($count)) {
						$count = 50;
					}
					$last = null;
					if(isset($_GET["last"])) {
						$last = intval($_GET["last"]);
					}
					if(!intval($last)) {
						$last = intval(Session::get("EcommerceTryToFinaliseOrdersTask"));
						if(!$last) {$last = 0;}
					}
					$orders = DataObject::get("Order", $whereSQL, "\"Order\".\"ID\" ASC", $joinSQL, "$last, $count");
					if($orders) {
						DB::alteration_message("<h1>Moving $count Orders (starting from $last)</h1>");
						foreach($orders as $order) {
							$last++;
							Session::set("EcommerceTryToFinaliseOrdersTask", $last);
							$stepBefore = DataObject::get_by_id("OrderStep", $order->StatusID);
							try{
								$order->tryToFinaliseOrder();
							}
							catch(Exception $e) {
								DB::alteration_message($e, "deleted");
							}
							$stepAfter = DataObject::get_by_id("OrderStep", $order->StatusID);
							if($stepBefore) {
								if($stepBefore->ID == $stepAfter->ID) {
									DB::alteration_message("could not move Order ".$order->getTitle().", remains at <strong>".$stepBefore->Name."</strong>");
								}
								else {
									DB::alteration_message("Moving Order #".$order->getTitle()." from <strong>".$stepBefore->Name."</strong> to <strong>".$stepAfter->Name."</strong>", "created");
								}
							}
							else {
								DB::alteration_message("Moving Order ".$order->getTitle()." from <strong>unknown step</strong> to <strong>".$stepAfter->Name."</strong>", "created");
							}
						}
					}
					else {
						Session::clear("EcommerceTryToFinaliseOrdersTask");
						DB::alteration_message("<br /><br /><br /><br /><h1>COMPLETED!</h1>All orders have been moved.", "created");
					}
				}
				else {
					DB::alteration_message("NO last order step.", "deleted");
				}
			}
			else {
				DB::alteration_message("NO submitted order status log.", "deleted");
			}
		}
		else {
			DB::alteration_message("NO EcommerceConfig::get(\"OrderStatusLog\", \"order_status_log_class_used_for_submitting_order\")", "deleted");
		}
		if(Session::get("EcommerceTryToFinaliseOrdersTask")) {
			DB::alteration_message("WAIT: we are still moving more orders ... this page will automatically load the next lot in 5 seconds.", "deleted");
			echo "<script type=\"text/javascript\">window.setTimeout(function() {location.reload();}, 5000);</script>";
		}
	}

}

class EcommerceTryToFinaliseOrdersTask_Mailer extends mailer {
	/**
	 * FAKE Send a plain-text email.
	 *
	 * @return bool
	 */
	function sendPlain($to, $from, $subject, $plainContent, $attachedFiles = false, $customheaders = false) {
		return true;
	}

	/**
	 * FAKE Send a multi-part HTML email.
	 *
	 * @return bool
	 */
	function sendHTML($to, $from, $subject, $htmlContent, $attachedFiles = false, $customheaders = false, $plainContent = false, $inlineImages = false) {
		return true;
	}
}
