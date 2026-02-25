<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield\Configs;

use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;

/**
 * ensures that order items can not be auto-linked or deleted
 * but retains all the other features of the GridFieldConfig_RelationEditor settings.
 */
class GridFieldConfigForCustomLists extends GridFieldConfig_RelationEditor
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
        $ac = $this->getComponentByType(GridFieldAddExistingAutocompleter::class);
        if ($ac) {
            $ac->setSearchFields(['Title']);
            $ac->setResultsFormat('$Title ($ProductCount)');
        }
        // $this->removeComponentsByType(GridFieldAddNewButton::class);
    }
}
