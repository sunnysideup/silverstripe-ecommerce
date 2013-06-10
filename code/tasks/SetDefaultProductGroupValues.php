<?php


/**
 * @description: resets fields in the product group class to "inherit" in case their value does not exist.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/


class SetDefaultProductGroupValues extends BuildTask {


	protected $title = "Set Default Product Group Values";

	protected $description = "Set default product group values such as DefaultSortOrder.";

	protected $fieldsToCheck = array(
		"getSortOptionsForDropdown" => "DefaultSortOrder",
		"getFilterOptionsForDropdown" => "DefaultFilter",
		"getDisplayStyleForDropdown" => "DisplayStyle"
	);

	function run($request) {
		$productGroup = ProductGroup::get()->First();
		if($productGroup) {
			foreach($this->fieldsToCheck as $method => $fieldName) {
				$acceptableValuesArray = array_flip($productGroup->$method());
				$this->checkField($fieldName, $acceptableValuesArray, "inherit");
			}
		}
		else {
			DB::alteration_message("There are no ProductGroup pages to correct", 'created');
		}
	}

	protected function checkField($fieldName, $acceptableValuesArray, $resetValue) {
		$faultyProductGroups = ProductGroup::get()
			->exclude(array($fieldName =>  $acceptableValuesArray));
		if($faultyProductGroups->count()) {
			foreach($faultyProductGroups as $faultyProductGroup) {
				$faultyProductGroup->$fieldName = $resetValue;
				$faultyProductGroup->writeToStage('Stage');
				$faultyProductGroup->publish('Stage', 'Live');
				DB::alteration_message("Reset $fieldName for ".$faultyProductGroup->Title, 'created');
			}
		}
		else {
			DB::alteration_message("Could not find any faulty records for ProductGroup.$fieldName");
		}
	}


}

