<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield\Configs;




use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldAddNewButtonOriginalPage;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldEditButtonOriginalPage;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;




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
            ->removeComponentsByType(GridFieldEditButton::class)
            ->removeComponentsByType(GridFieldDeleteAction::class)
            ->removeComponentsByType(GridFieldAddNewButton::class)
            ->removeComponentsByType(GridFieldAddExistingAutocompleter::class)
            ->addComponent(new GridFieldAddNewButtonOriginalPage())
            ->addComponent(new GridFieldEditButtonOriginalPage());
    }
}

