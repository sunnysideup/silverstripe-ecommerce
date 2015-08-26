<?php


/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderStep_Submitted extends OrderStep implements OrderStepInterface  {

	private static $db = array(
		"SaveOrderAsHTML" => "Boolean",
		"SaveOrderAsSerializedObject" => "Boolean"
	);

	private static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanPay" => 1,
		"CustomerCanCancel" => 0,
		"Name" => "Submit",
		"Code" => "SUBMITTED",
		"ShowAsInProcessOrder" => 1,
		"SaveOrderAsHTML" => 1,
		"SaveOrderAsSerializedObject" => 0
	);

	/**
	 * The OrderStatusLog that is relevant to the particular step.
	 * @var String
	 */
	protected $relevantLogEntryClassName = "OrderStatusLog_Submitted";

	/**
	 * @return string
	 */
	public function getRelevantLogEntryClassName(){
		return EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order");
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Main", new HeaderField("HowToSaveSubmittedOrder", _t("OrderStep.HOWTOSAVESUBMITTEDORDER", "How would you like to make a backup of your order at the moment it is submitted?"), 3), "SaveOrderAsHTML");
		return $fields;
	}

	/**
	 * Can run this step once any items have been submitted.
	 * makes sure the step is ready to run.... (e.g. check if the order is ready to be emailed as receipt).
	 * should be able to run this function many times to check if the step is ready
	 * @see Order::doNextStatus
	 * @param Order object
	 * @return Boolean - true if the current step is ready to be run...
	 **/
	public function initStep(Order $order) {
		return (bool) $order->TotalItems($recalculate = true);
	}


	private static $test = 0;
	/**
	 * Add a member to the order - in case he / she is not a shop admin.
	 *
	 * @param Order object
	 * @return Boolean - true if run correctly.
	 **/
	 public function doStep(Order $order) {
		if(!$order->IsSubmitted()) {
			$className = $this->getRelevantLogEntryClassName();
			if(class_exists($className)) {


				//add currency if needed.
				$order->getHasAlternativeCurrency();

				$obj = $className::create();
				if(is_a($obj, Object::getCustomClass("OrderStatusLog"))) {
					//save versions
					//@todo: test and implement
					/*
					if($this->Attributes()->exists()){
						foreach($this->Attributes() as $attribute){
							if(is_a($attribute, Object::getCustomClass("OrderItem"))){
								$buyable = $attribute->Buyable();
								if(isset($buyable->Version)) {
									$attribute->Version = $buyable->Version;
									$attribute->write();
								}
							}
						}
          }
          */
					$obj->OrderID = $order->ID;
					$obj->Title = $this->Name;
					//it is important we add this here so that we can save the 'submitted' version.
					//this is particular important for the Order Item Links.
					$obj->write();
					$obj = OrderStatusLog::get()->byID($obj->ID);
					$saved = false;
					if($this->SaveOrderAsSerializedObject)       {$obj->OrderAsString  = $order->ConvertToString(); $saved = true; }
					if($this->SaveOrderAsHTML || !$saved)        {$obj->OrderAsHTML    = Convert::raw2sql($order->ConvertToHTML());}
					$obj->write();
				}
				else {
					user_error('EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order") refers to a class that is NOT an instance of OrderStatusLog');
				}
			}
			else {
				user_error('EcommerceConfig::get("OrderStatusLog", "order_status_log_class_used_for_submitting_order") refers to a non-existing class');
			}
			$order->LastEdited = "'".SS_Datetime::now()->Rfc2822()."'";

			//add member if needed...
			if(!$order->MemberID) {
				//lets see if we can find a member
				$memberOrderID = Session::get("Ecommerce_Member_For_Order");
				Session::clear("Ecommerce_Member_For_Order");
				Session::set("Ecommerce_Member_For_Order", 0);
				Session::save();
				if($memberOrderID) {
					$order->MemberID = $memberOrderID;
				}
			}
			$order->write($showDebug = false, $forceInsert = false, $forceWrite = true);

		}
		return true;
	}

	/**
	 * go to next step if order has been submitted.
	 * @param Order $order
	 * @return OrderStep | Null	(next step OrderStep)
	 **/
	public function nextStep(Order $order) {
		if($order->IsSubmitted()) {
			return parent::nextStep($order);
		}
		return null;
	}


	/**
	 * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
	 * @param FieldList $fields
	 * @param Order $order
	 * @return FieldList
	 **/
	function addOrderStepFields(FieldList $fields, Order $order) {
		$fields = parent::addOrderStepFields($fields, $order);
		$title = _t("OrderStep.CANADDGENERALLOG", " ... if you want to make some notes about this step then do this here...");
		$fields->addFieldToTab("Root.Next", $order->getOrderStatusLogsTableField("OrderStatusLog", $title),"ActionNextStepManually");
		return $fields;
	}

	/**
	 * Explains the current order step.
	 * @return String
	 */
	protected function myDescription(){
		return _t("OrderStep.SUBMITTED_DESCRIPTION", "The official moment the order gets submitted by the customer. The hand-shake for a commercial transaction.");
	}

}
