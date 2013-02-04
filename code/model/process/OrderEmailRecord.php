<?php

/**
 * @Description: DataObject recording all order emails sent.
 *
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderEmailRecord extends DataObject {

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $db = array(
		"From" => "Varchar(255)",
		"To" => "Varchar(255)",
		"Subject" => "Varchar(255)",
		"Content" => "HTMLText",
		"Result" => "Boolean"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $has_one = array(
		"Order" => "Order",
		"OrderStep" => "OrderStep"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $casting = array(
		"OrderStepNice" => "Varchar",
		"ResultNice" => "Varchar"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $summary_fields = array(
		"Created" => "Send",
		"OrderStepNice" => "What",
		"From" => "From",
		"To" => "To",
		"Subject" => "Subject",
		"ResultNice" => "Sent Succesfully"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $searchable_fields = array(
		'OrderID' => array(
			'field' => 'NumericField',
			'title' => 'Order Number'
		),
		"From" => "PartialMatchFilter",
		"To" => "PartialMatchFilter",
		"Subject" => "PartialMatchFilter",
		"Result" => true
	);

	/**
	 * casted Variable
	 * @var String
	 */
	function ResultNice() {return $this->getResultNice();}
	function getResultNice() {
		if($this->Result) {
			return _t("OrderEmailRecord.YES", "Yes");
		}
		return _t("OrderEmailRecord.NO", "No");
	}

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $singular_name = "Customer Email";
		function i18n_singular_name() { return _t("OrderEmailRecord.CUSTOMEREMAIL", "Customer Email");}

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $plural_name = "Customer Emails";
		function i18n_plural_name() { return _t("OrderEmailRecord.CUSTOMEREMAILS", "Customer Emails");}

	/**
	 * standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canCreate($member = null) {return false;}

	/**
	 * standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canEdit($member = null) {return false;}

	/**
	 * standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {return false;}
	//defaults

	/**
	 * standard SS variable
	 * @return String
	 */
	public static $default_sort = "\"Created\" DESC";

	/**
	 * standard SS method
	 * @return FieldSet
	 */
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->replaceField("OrderID", $fields->dataFieldByName("OrderID")->performReadonlyTransformation());
		$fields->replaceField("OrderStep", new ReadonlyField("OrderStepNice", "Order Step", $this->OrderStepNice()));
		return $fields;
	}

	/**
	 * standard SS method
	 * @return FieldSet
	 */
	function scaffoldSearchFields(){
		$fields = parent::scaffoldSearchFields();
		$fields->replaceField("OrderID", new NumericField("OrderID", "Order Number"));
		return $fields;
	}

	/**
	 * casted variable
	 *@ return String
	 **/
	function OrderStepNice() {return $this->getOrderStepNice();}
	function getOrderStepNice() {
		if($this->OrderStepID) {
			$orderStep = DataObject::get_by_id("OrderStep", $this->OrderStepID);
			if($orderStep) {
				return $orderStep->Name;
			}
		}
	}


	/**
	 * Debug helper method.
	 * Can be called from /shoppingcart/debug/
	 * @return String
	 */
	public function debug() {
		$html =  "
			<h2>".$this->ClassName."</h2><ul>";
		$fields = Object::get_static($this->ClassName, "db");
		foreach($fields as  $key => $type) {
			$html .= "<li><b>$key ($type):</b> ".$this->$key."</li>";
		}
		$fields = Object::get_static($this->ClassName, "casting");
		foreach($fields as  $key => $type) {
			$method = "get".$key;
			$html .= "<li><b>$key ($type):</b> ".$this->$method()." </li>";
		}
		$fields = Object::get_static($this->ClassName, "has_one");
		foreach($fields as  $key => $type) {
			$field = $type."ID";
			$html .= "<li><b>$key ($type):</b> ".$this->$field." </li>";
		}
		$html .= "</ul>";
		return $html;
	}

}
