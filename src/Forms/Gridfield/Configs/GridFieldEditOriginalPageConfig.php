<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield\Configs;

use GridFieldConfig_RecordEditor;
use GridFieldAddNewButtonOriginalPage;
use GridFieldEditButtonOriginalPage;



/**
 * @author nicolaas <github@sunnysideup.co.nz>
 */
class GridFieldEditOriginalPageConfig extends GridFieldConfig_RecordEditor
{
    /**
     * @param int $itemsPerPage - How many items per page should show up
     */
    public function __construct($itemsPerPage = null)
    {
        parent::__construct($itemsPerPage);
        $this
            ->removeComponentsByType('GridFieldEditButton')
            ->removeComponentsByType('GridFieldDeleteAction')
            ->removeComponentsByType('GridFieldAddNewButton')
            ->removeComponentsByType('GridFieldAddExistingAutocompleter')
            ->addComponent(new GridFieldAddNewButtonOriginalPage())
            ->addComponent(new GridFieldEditButtonOriginalPage());
    }
}

