<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield;

use SilverStripe\Core\Convert;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\ORM\DataObject;

class GridFieldPrintInvoiceButton implements GridField_ColumnProvider, GridField_ActionProvider
{
    /**
     * Add a column 'Delete'.
     *
     * @param GridField $gridField
     * @param array     $columns
     */
    public function augmentColumns($gridField, &$columns)
    {
        if (! in_array('Print', $columns, true)) {
            $columns[] = 'Print';
        }
    }

    /**
     * Return any special attributes that will be used for FormField::create_tag().
     *
     * @param GridField  $gridField
     * @param DataObject $record
     * @param string     $columnName
     *
     * @return array
     */
    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return ['class' => 'col-buttons print'];
    }

    /**
     * Add the title.
     *
     * @param GridField $gridField
     * @param string    $columnName
     *
     * @return array
     */
    public function getColumnMetadata($gridField, $columnName)
    {
        if ('Print' === $columnName) {
            return ['title' => 'Invoice'];
        }

        return [];
    }

    /**
     * @param GridField  $gridField
     * @param DataObject $record
     * @param string     $columnName
     *
     * @return string - the HTML for the column
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        if ($record->IsSubmitted()) {
            // No permission checks, handled through GridFieldDetailForm,
            // which can make the form readonly if no edit permissions are available.
            $onclickStatement =
                "window.open(
                    '" . Convert::raw2js($record->PrintLink()) . "',
                    'print_invoice',
                    'width=600,height=300,location=0,menubar=0,scrollbars=1,status=0,toolbar=0,resizable=1'
                );";

            GridField_FormAction::create(
                $gridField,
                'PrintInvoice' . $record->ID,
                false,
                'printinvoice',
                ['RecordID' => $record->ID]
            )
                ->addExtraClass('gridfield-button-printinvoice action btn btn-secondary no-ajax font-icon-down-circled action_export ')
                ->setAttribute('title', _t('GridAction.PRINT_INVOICE', 'Invoice'))
                ->setAttribute('onclick', $onclickStatement)
                ->setDescription(_t('GridAction.PRINT_INVOICE_DESCRIPTION', 'Print Invoice'))
                ->Field()
            ;
        }

        return '';
    }

    /**
     * Which columns are handled by this component.
     *
     * @param GridField $gridField
     *
     * @return array
     */
    public function getColumnsHandled($gridField)
    {
        return ['Print'];
    }

    /**
     * Which GridField actions are this component handling.
     *
     * @param GridField $gridField
     *
     * @return array
     */
    public function getActions($gridField)
    {
        return ['printinvoice'];
    }

    /**
     * Handle the actions and apply any changes to the GridField.
     *
     * @param string $actionName
     * @param mixed  $arguments
     * @param array  $data       - form data
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ('printinvoice' === $actionName) {
            /** @var DataList $list */
            $list = $gridField->getList();
            $item = $list->byID($arguments['RecordID']);
            if (! $item) {
                return;
            }
            // $list = $gridField->getList();
            // $list = $list->exclude(array('ID' => $itemID));
            // $gridField->setList($list);
        }
    }
}
