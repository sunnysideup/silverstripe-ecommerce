<?php


/**
 * This is the first Order Step.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderStep_Created extends OrderStep implements OrderStepInterface  {

	private static $defaults = array(
		"CustomerCanEdit" => 1,
		"CustomerCanPay" => 1,
		"CustomerCanCancel" => 1,
		"Name" => "Create",
		"Code" => "CREATED",
		"ShowAsUncompletedOrder" => 1
	);

	/**
		*initStep:
		* makes sure the step is ready to run.... (e.g. check if the order is ready to be emailed as receipt).
		* should be able to run this function many times to check if the step is ready
		* @see Order::doNextStatus
		* @param Order object
		* @return Boolean - true if the current step is ready to be run...
		**/
	public function initStep(Order $order) {
		return true;
	}

	/**
	 * Add the member to the order, in case the member is not an admin.
	 *
	 * @param DataObject - $order Order
	 * @return Boolean
	 **/
	public function doStep(Order $order) {
		if(!$order->MemberID) {
			$member = Member::currentUser();
			if($member) {
				if(!$member->IsShopAdmin()) {
					$order->MemberID = $member->ID();
					$order->write();
				}
			}
		}
		return true;
	}

	/**
	 * We can run the next step, once any items have been added.
	 * @see Order::doNextStatus
	 * @param Order $order
	 * @return OrderStep | Null (next step OrderStep object)
	 **/
	public function nextStep(Order $order) {
		if($order->TotalItems($recalculate = true)) {
			return parent::nextStep($order);
		}
		return null;
	}

	/**
	 * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
	 *@param FieldList $fields
	 *@param Order $order
	 *@return FieldList
	 **/
	function addOrderStepFields(FieldList $fields, Order $order) {
		$fields = parent::addOrderStepFields($fields, $order);
		if(!$order->IsSubmitted()) {
			//LINE BELOW IS NOT REQUIRED
			$header = _t("OrderStep.SUBMITORDER", "Submit Order");
			$label = _t("OrderStep.SUBMITNOW", "Submit Now");
			$msg = _t("OrderStep.MUSTDOSUBMITRECORD", "<p>Tick the box below to submit this order.</p>");
			$problems = array();
			if(!$order->getTotalItems()) {
				$problems[] = "There are no --- Order Items (products) --- associated with this order.";
			}
			if(!$order->MemberID) {
				$problems[] = "There is no --- Customer --- associated with this order.";
			}
			if(!$order->BillingAddressID) {
				$problems[] = "There is no --- Billing Address --- associated with this order.";
			}
			elseif($billingAddress = $order->BillingAddress()) {
				$requiredBillingFields = $billingAddress->getRequiredFields();
				if($requiredBillingFields && is_array($requiredBillingFields) && count($requiredBillingFields)) {
					foreach($requiredBillingFields as $requiredBillingField) {
						if(!$billingAddress->$requiredBillingField) {
							$problems[] = "There is no --- $requiredBillingField --- recorded in the billing address.";
						}
					}
				}
			}
			if(count($problems)) {
				$msg = "<p>You can not submit this order because:</p> <ul><li>".implode("</li><li>", $problems)."</li></ul>";
			}
			$fields->addFieldToTab("Root.Next", new HeaderField("CreateSubmitRecordHeader", $header, 3), "ActionNextStepManually");
			$fields->addFieldToTab("Root.Next", new LiteralField("CreateSubmitRecordMessage", $msg), "ActionNextStepManually");
			if(!$problems) {
				$fields->addFieldToTab("Root.Next", new CheckboxField("SubmitOrderViaCMS", $label), "ActionNextStepManually");
			}
		}
		return $fields;
	}

	/**
	 * Explains the current order step.
	 * @return String
	 */
	protected function myDescription(){
		return _t("OrderStep.CREATED_DESCRIPTION", "During this step the customer creates her or his order. The shop admininistrator does not do anything during this step.");
	}

}

