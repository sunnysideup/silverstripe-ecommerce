<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield\Configs;

use GridFieldConfig_RecordEditor;
use GridFieldAddNewButtonOriginalPage;
use GridFieldEditButtonOriginalPage;



class GridFieldEditOriginalPageConfigWithDelete extends GridFieldConfig_RecordEditor
{
    /**
     * @param int $itemsPerPage - How many items per page should show up
     */
    public function __construct($itemsPerPage = null)
    {
        parent::__construct($itemsPerPage);
        $this
            ->removeComponentsByType('GridFieldEditButton')
            ->removeComponentsByType('GridFieldAddNewButton')
            ->addComponent(new GridFieldAddNewButtonOriginalPage())
            ->addComponent(new GridFieldEditButtonOriginalPage());
    }
}

