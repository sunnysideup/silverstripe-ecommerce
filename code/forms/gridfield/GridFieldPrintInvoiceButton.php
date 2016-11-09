<?php


class GridFieldPrintInvoiceButton implements  GridField_ColumnProvider, GridField_ActionProvider
{

    /**
     * Add a column 'Delete'
     *
     * @param GridField $gridField
     * @param array $columns
     */
    public function augmentColumns($gridField, &$columns) {
        if(!in_array('Print', $columns)) {
            $columns[] = 'Print';
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
    public function getColumnAttributes($gridField, $record, $columnName) {
        return array('class' => 'col-buttons print');
    }

    /**
     * Add the title
     *
     * @param GridField $gridField
     * @param string $columnName
     * @return array
     */
    public function getColumnMetadata($gridField, $columnName) {
        if($columnName == 'Print') {
            return array('title' => 'Invoice');
        }
    }


    /**
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     *
     * @return string - the HTML for the column
     */
    public function getColumnContent($gridField, $record, $columnName) {
        if($record->IsSubmitted()) {
            // No permission checks, handled through GridFieldDetailForm,
            // which can make the form readonly if no edit permissions are available.
            $onclickStatement =
                "window.open(
                    '".Convert::raw2js($record->PrintLink())."',
                    'print_invoice',
                    'width=600,height=300,location=0,menubar=0,scrollbars=1,status=0,toolbar=0,resizable=1'
                );";

            $field = GridField_FormAction::create(
                $gridField,
                'PrintInvoice'.$record->ID,
                false,
                "printinvoice",
                array('RecordID' => $record->ID)
            )
                ->addExtraClass('gridfield-button-printinvoice')
                ->setAttribute('title', _t('GridAction.PRINT_INVOICE', "Invoice"))
                ->setAttribute('data-icon', 'grid_print')
                ->setAttribute('onclick', $onclickStatement)
                ->setDescription(_t('GridAction.PRINT_INVOICE_DESCRIPTION','Print Invoice'));
            return $field->Field();
        }
    }


    /**
     * Which columns are handled by this component
     *
     * @param GridField $gridField
     * @return array
     */
    public function getColumnsHandled($gridField) {
        return array('Print');
    }



    /**
     * Which GridField actions are this component handling
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField) {
        return array('printinvoice');
    }


    /**
     * Handle the actions and apply any changes to the GridField
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param mixed $arguments
     * @param array $data - form data
     * @return void
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
        if($actionName == 'printinvoice') {
            $item = $gridField->getList()->byID($arguments['RecordID']);
            if(!$item) {
                return;
            }
            // $list = $gridField->getList();
            // $list = $list->exclude(array('ID' => $itemID));
            // $gridField->setList($list);
        }
    }
}
