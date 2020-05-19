<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield;






use SilverStripe\Core\Convert;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\Forms\GridField\GridField_ActionProvider;




class GridFieldPrintPackingSlipButton implements GridField_ColumnProvider, GridField_ActionProvider
{
    /**
     * Add a column 'Delete'
     *
     * @param GridField $gridField
     * @param array $columns
     */
    public function augmentColumns($gridField, &$columns)
    {
        if (! in_array('Packing Slip', $columns, true)) {
            $columns[] = 'Packing Slip';
        }
    }

    /**
     * Return any special attributes that will be used for FormField::create_tag()
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return array
     */
    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return ['class' => 'col-buttons print'];
    }

    /**
     * Add the title
     *
     * @param GridField $gridField
     * @param string $columnName
     * @return array
     */
    public function getColumnMetadata($gridField, $columnName)
    {
        if ($columnName === 'Packing Slip') {
            return ['title' => 'Packing Slip'];
        }
    }

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
        $onclickStatement =
            "window.open(
                '" . Convert::raw2js($record->PackingSlipLink()) . "',
                'print_packing_slip',
                'width=600,height=300,location=0,menubar=0,scrollbars=1,status=0,toolbar=0,resizable=1'
            );";

        $field = GridField_FormAction::create(
            $gridField,
            'PrintPackingSlip' . $record->ID,
            false,
            'printpackingslip',
            ['RecordID' => $record->ID]
        )
            ->addExtraClass('gridfield-button-printpackingslip')
            ->setAttribute('title', _t('GridPacking Slip.PRINT_PACKING_SLIP', 'Packing Slip'))
            ->setAttribute('data-icon', 'download-csv')
            ->setAttribute('onclick', $onclickStatement)
            ->setDescription(_t('GridPacking Slip.PRINT_PACKING_SLIP_DESCRIPTION', 'Print Packing Slip'));
        return $field->FIeld();
    }

    /**
     * Which columns are handled by this component
     *
     * @param GridField $gridField
     * @return array
     */
    public function getColumnsHandled($gridField)
    {
        return ['Packing Slip'];
    }

    /**
     * Which GridField actions are this component handling
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        return ['printpackingslip'];
    }

    /**
     * Handle the actions and apply any changes to the GridField
     *
     * @param GridField $gridField$gridField->getList()->remove($item);eld
     * @param string $actionName
     * @param mixed $arguments
     * @param array $data - form data
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName === 'printpackingslip') {
            $itemID = intval($arguments['RecordID']);
            if (! $itemID) {
                return;
            }
            // $list = $gridField->getList();
            // $list = $list->exclude(array('ID' => $itemID));
            // $gridField->setList($list);
        }
    }
}

