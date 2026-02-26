<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield\Configs;

use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Versioned\GridFieldArchiveAction;

/**
 * @author nicolaas <github@sunnysideup.co.nz>
 */
class GridFieldBasicPageRelationConfigNoAddExisting extends GridFieldConfig_RelationEditor
{
    /**
     * @param int $itemsPerPage - How many items per page should show up
     */
    public function __construct($itemsPerPage = null)
    {
        parent::__construct($itemsPerPage);
        $this
            ->removeComponentsByType(GridFieldAddNewButton::class)
            ->removeComponentsByType(GridFieldAddExistingAutocompleter::class)
            // ->removeComponentsByType(GridFieldDeleteAction::class)
            ->removeComponentsByType(GridFieldArchiveAction::class)
        ;
    }
}
