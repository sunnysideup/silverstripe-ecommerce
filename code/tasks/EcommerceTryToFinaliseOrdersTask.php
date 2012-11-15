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

	/**
	 * Standard SS Variable
	 * TODO: either remove or add to all tasks
	 */
	static $allowed_actions = array(
		'*' => 'ADMIN',
		'*' => 'SHOPADMIN'
	);

	protected $title = 'Try to finalise all orders WITHOUT SENDING EMAILS.';

	protected $description = "This task can be useful in moving a bunch of orders through the latest order step. It will only move orders if they can be moved through order steps.  You may need to run this task several times to move all orders.";


	/**
	 *@return Integer - number of carts destroyed
	 **/
	public function run($request){
		//IMPORTANT!
		Email::send_all_emails_to("no-one@lets-hope-this-goes-absolutely-no-where.co.nz");
		Email::set_mailer("EcommerceTryToFinaliseOrdersTask_Mailer");
		$orderStatusLogClassName = "OrderStatusLog";
		$submittedOrderStatusLogClassName = EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order");
		if($submittedOrderStatusLogClassName) {
			$submittedStatusLog = DataObject::get_one($submittedOrderStatusLogClassName);
			if($submittedStatusLog) {
				$lastOrderStep = DataObject::get_one("OrderStep", "", "\"Sort\" DESC");
				if($lastOrderStep) {
					$joinSQL = "INNER JOIN \"$orderStatusLogClassName\" ON \"$orderStatusLogClassName\".\"OrderID\" = \"Order\".\"ID\"";
					$whereSQL = "\"StatusID\" <> ".$lastOrderStep->ID." AND \"$orderStatusLogClassName\".ClassName = '$submittedOrderStatusLogClassName'";
					if(isset($_GET["count"])) {
						$count = intval($_GET["count"]);
					}
					if(!$count) {
						$count = 50;
					}
					if(isset($_GET["last"])) {
						$last = intval($_GET["last"]);
					}
					else {
						$last = intval(Session::get("EcommerceTryToFinaliseOrdersTask"));
						if(!$last) {$last = 0;}
						$orders = DataObject::get("Order", $whereSQL, "\"LastEdited\" ASC", $joinSQL, "$last, $count");
						$last += $count;
						Session::set("EcommerceTryToFinaliseOrdersTask", $last);
					}
					if($orders) {
						foreach($orders as $order) {
							$stepBefore = DataObject::get_by_id("OrderStep", $order->StatusID);
							$stepAfter = DataObject::get_by_id("OrderStep", $order->StatusID);
							if($stepAfter->ID == $stepAfter->ID) {
								DB::alteration_message("could not move Order #".$order->ID);
							}
							else {
								DB::alteration_message("Moving Order #".$order->ID." from <strong>".$stepBefore->Name."</strong> to <strong>".$stepAfter->Name."</strong>", "created");
							}
						}
					}
					else {
						DB::alteration_message("No orders to move");
					}
				}
				else {
					DB::alteration_message("NO last order step", "deleted");
				}
			}
			else {
				DB::alteration_message("NO submitted order status log", "deleted");
			}
		}
		else {
			DB::alteration_message("NO EcommerceConfig::get(\"OrderStatusLog\", \"order_status_log_class_used_for_submitting_order\")", "deleted");
		}
		//echo "<script type=\"text/javascript\">location.reload();</script>";
	}

}

class EcommerceTryToFinaliseOrdersTask_Mailer extends mailer {
	/**
	 * Send a plain-text email.
	 *
	 * @param string $to
	 * @param string $from
	 * @param string §subject
	 * @param string $plainContent
	 * @param bool $attachedFiles
	 * @param array $customheaders
	 * @return bool
	 */
	function sendPlain($to, $from, $subject, $plainContent, $attachedFiles = false, $customheaders = false) {
		return true;
	}

	/**
	 * Send a multi-part HTML email.
	 *
	 * @return bool
	 */
	function sendHTML($to, $from, $subject, $htmlContent, $attachedFiles = false, $customheaders = false, $plainContent = false, $inlineImages = false) {
		return true;
	}
}
