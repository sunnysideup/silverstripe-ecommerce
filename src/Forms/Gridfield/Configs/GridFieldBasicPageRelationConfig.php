<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield\Configs;

use GridFieldConfig_RelationEditor;



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
            ->removeComponentsByType('GridFieldEditButton')
            ->removeComponentsByType('GridFieldAddNewButton');
    }
}

