<?php


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
            ->removeComponentsByType('GridFieldEditButton')
            ->removeComponentsByType('GridFieldAddNewButton')
            ->addComponent(new GridFieldEditButtonOriginalPage());
    }
}
