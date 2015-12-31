<?php

/**
 * Provides the entry point to editing a single record presented by the
 * {@link GridField}.
 *
 * Doesn't show an edit view on its own or modifies the record, but rather
 * relies on routing conventions established in {@link getColumnContent()}.
 *
 * The default routing applies to the {@link GridFieldDetailForm} component,
 * which has to be added separately to the {@link GridField} configuration.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldEditButtonOriginalPage extends GridFieldEditButton implements GridField_ColumnProvider
{


    /**
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     *
     * @return string - the HTML for the column
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        // No permission checks, handled through GridFieldDetailForm,
        // which can make the form readonly if no edit permissions are available.
        if ($record->hasMethod("CMSEditLink")) {
            $data = new ArrayData(array(
                'Link' => Controller::join_links($record->CMSEditLink())
            ));
            return $data->renderWith('GridFieldEditButtonInSiteTree');
        } else {
            return parent::getColumnContent($gridField, $record, $columnName);
        }
    }
}
