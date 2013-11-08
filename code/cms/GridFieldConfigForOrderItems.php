<?php

class GridFieldConfigForOrderItems extends GridFieldConfig_RelationEditor {

	/**
	 * @param int $itemsPerPage - How many items per page should show up
	 */
	public function __construct($itemsPerPage=null) {
		parent::__construct(10000);
		$this->removeComponentsByType("GridFieldAddExistingAutocompleter");
		//$this->removeComponentsByType("GridFieldButtonRow");
		//$this->removeComponentsByType("GridFieldAddNewButton");
		//$this->removeComponentsByType("GridFieldToolbarHeader");
		//$this->removeComponentsByType("GridFieldSortableHeader");
		//$this->removeComponentsByType("GridFieldFilterHeader");
		//$this->removeComponentsByType("GridFieldDataColumns");
		//$this->removeComponentsByType("GridFieldEditButton");
		$this->removeComponentsByType("GridFieldDeleteAction");
		//$this->removeComponentsByType("GridFieldPageCount");
		//$this->removeComponentsByType("GridFieldPaginator");
		//$this->removeComponentsByType("GridFieldDetailForm");
	}
}
