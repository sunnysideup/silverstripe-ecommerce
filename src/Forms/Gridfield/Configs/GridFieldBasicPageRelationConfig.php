<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield\Configs;

use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldEditButton;

/**
 * @author nicolaas <github@sunnysideup.co.nz>
 */
class GridFieldBasicPageRelationConfig extends GridFieldConfig_RelationEditor
{
    /**
     * @param int $itemsPerPage - How many items per page should show up
     */
    public function __construct($itemsPerPage = null)
    {
        parent::__construct($itemsPerPage);
        $this
            ->removeComponentsByType(GridFieldEditButton::class)
            ->removeComponentsByType(GridFieldAddNewButton::class)
        ;
    }
}
