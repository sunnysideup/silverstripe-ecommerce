<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield\Configs;

use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldAddNewButtonOriginalPage;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldEditButtonOriginalPage;

class GridFieldEditOriginalPageConfigWithDelete extends GridFieldConfig_RecordEditor
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
            ->addComponent(new GridFieldAddNewButtonOriginalPage())
            ->addComponent(new GridFieldEditButtonOriginalPage())
        ;
    }
}
