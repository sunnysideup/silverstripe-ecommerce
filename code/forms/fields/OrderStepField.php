<?php


/**
 * This field shows the admin (and maybe the customer) where the Order is at (which orderstep)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderStepField extends DatalessField {

	/**
	 * @var string $content
	 */
	protected $content;

	function __construct($name, $order, $member = null) {
		$where = "\"HideStepFromCustomer\" = 0";
		$currentStep = $order->CurrentStepVisibleToCustomer();
		if($member->IsShopAdmin) {
			$where = "";
			$currentStep = $order->MyStep();
		}
		$orderSteps = DataObject::get("OrderStep", $where);
		if($member)
		$future = false;
		$html = "
		<div class=\"orderStepField\">
			<ol>";
		foreach($orderSteps as $orderStep) {
			$description = "";
			if($member->IsShopAdmin()) {
				if($orderStep->Description) {
					$description =  " title=\"".Convert::raw2att($orderStep->Description)."\" " ;
				}
			}
			$class = "";
			if($orderStep->ID == $currentStep->ID) {
				$future = true;
				$class .= " current";
			}
			elseif($future) {
				$class .= " todo";
			}
			else {
				$class .= " done";
			}
			$html .= '<li class="'.$class.'" '.$description.'>'.$orderStep->Title.'</li>';
		}
		$html .= "</ol><div class=\"clear\"></div></div>";
		if($currentStep->Description) {
			$html .= "
				<p>".
				"<strong>".$currentStep->Title."</strong> ".
				_t("OrderStepField.STEP", "step").
				": ".
				"<i>".$currentStep->Description."</i>".
				"</p>";
		}
		$this->content = $html;
		Requirements::themedCSS("OrderStepField");
		parent::__construct($name);
	}

	function FieldHolder() {
		return is_object($this->content) ? $this->content->forTemplate() : $this->content;
	}

	function Field() {
		return $this->FieldHolder();
	}

	/**
	 * Sets the content of this field to a new value
	 * @param string $content
	 */
	function setContent($content) {
		$this->content = $content;
	}

	/**
	 * @return string
	 */
	function getContent() {
		return $this->content;
	}

	/**
	 * Synonym of {@link setContent()} so that LiteralField is more compatible with other field types.
	 * @param mixed
	 */
	function setValue($value) {
		return $this->setContent($value);
	}

	/**
	 * standard SS method
	 * @return Field
	 */
	function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->setReadonly(true);
		return $clone;
	}
}


