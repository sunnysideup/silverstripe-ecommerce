<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield\Configs;


use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;


/**
 * ensures that order items can not be auto-linked or deleted
 * but retains all the other features of the GridFieldConfig_RelationEditor settings.
 */
class GridFieldConfigForOrderItems extends GridFieldConfig_RelationEditor
{
    /**
     * @param int $itemsPerPage - How many items per page should show up
     */
    public function __construct($itemsPerPage = null)
    {
        if (! $itemsPerPage) {
            $itemsPerPage = 100;
        }
        parent::__construct($itemsPerPage);
        $this->removeComponentsByType(GridFieldAddExistingAutocompleter::class);
        //$this->removeComponentsByType("GridFieldButtonRow");
        //$this->removeComponentsByType("GridFieldAddNewButton");
        //$this->removeComponentsByType("GridFieldToolbarHeader");
        //$this->removeComponentsByType("GridFieldSortableHeader");
        //$this->removeComponentsByType("GridFieldFilterHeader");
        //$this->removeComponentsByType("GridFieldDataColumns");
        //$this->removeComponentsByType("GridFieldEditButton");
        $this->removeComponentsByType(GridFieldDeleteAction::class);
        //$this->removeComponentsByType("GridFieldPageCount");
        //$this->removeComponentsByType("GridFieldPaginator");
        //$this->removeComponentsByType("GridFieldDetailForm");
    }
}

