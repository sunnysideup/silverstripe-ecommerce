<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield\Configs;



use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldEditButtonOriginalPage;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;




/**
 * @author nicolaas <github@sunnysideup.co.nz>
 */
class GridFieldEditOriginalPageConfigWithAddExisting extends GridFieldConfig_RelationEditor
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
            ->addComponent(new GridFieldEditButtonOriginalPage());
    }
}

