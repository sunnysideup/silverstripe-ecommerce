<?php

/**
 * Adds an "Export list" button to the bottom of a {@link GridField}.
 *
 * @package forms
 * @subpackage fields-gridfield
 */

class GridFieldExportSalesButton extends GridFieldExportButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler
{

    /**
     * Array of fields to be exporoted
     * @var array
     */
    private static $fields_and_methods_to_be_exported = array(
        'OrderID',
        'InternalItemID',
        'TableTitle',
        'TableSubTitleNOHTML',
        'UnitPrice',
        'Quantity',
        'CalculatedTotal',
    );




    /**
     * export is an action button
     */
    public function getActions($gridField)
    {
        return array('exportsales');
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName == 'exportsales') {
            return $this->handleSales($gridField);
        }
    }

    /**
     * it is also a URL
     */
    public function getURLHandlers($gridField)
    {
        return array(
            'exportsales' => 'handleSales',
        );
    }

    /**
     * Handle the export, for both the action button and the URL
      */
    public function handleSales($gridField, $request = null)
    {
        $now = Date("d-m-Y-H-i");
        $fileName = "sales-$now.csv";

        if ($fileData = $this->generateExportFileData($gridField)) {
            return SS_HTTPRequest::send_file($fileData, $fileName, 'text/csv');
        }
    }


    /**
     * Place the export button in a <p> tag below the field
     */
    public function getHTMLFragments($gridField)
    {
        $button = new GridField_FormAction(
            $gridField,
            'exportsales',
            _t('TableListField.CSVEXPORT_SALES', 'Export Row Items'),
            'exportsales',
            null
        );
        $button->setAttribute('data-icon', 'download-csv');
        $button->addExtraClass('no-ajax action_export');
        $button->setForm($gridField->getForm());
        return array(
            $this->targetFragment => '<p class="grid-csv-button">' . $button->Field() . '</p>',
        );
    }

    /**
     * Generate export fields for CSV.
     *
     * @param GridField $gridField
     * @return array
     */
    public function generateExportFileData($gridField)
    {
        //reset time limit
        set_time_limit(1200);

        $idArray = array();

        //Remove GridFieldPaginator as we're going to export the entire list.
        $gridField->getConfig()->removeComponentsByType('GridFieldPaginator');

        $items = $gridField->getManipulatedList();

        foreach ($items->limit(null) as $item) {
            if (!$item->hasMethod('canView') || $item->canView()) {
                $idArray[$item->ID] = $item->ID;
            }
        }

        //file data
        $now = Date('d-m-Y-H-i');
        $fileName = "export-$now.csv";

        //data object variables
        $orderStatusSubmissionLog = EcommerceConfig::get('OrderStatusLog', 'order_status_log_class_used_for_submitting_order');
        $fileData = '';
        $offset = 0;
        $count = 50;
        $orders = $this->getMyOrders($idArray, $count, $offset);

        while (
            $orders->count()
        ) {
            $offset = $offset + $count;
            foreach ($orders as $order) {
                if ($order->IsSubmitted()) {
                    $memberIsOK = false;
                    if (!$order->MemberID) {
                        $memberIsOK = true;
                    } elseif (!$order->Member()) {
                        $memberIsOK = true;
                    } elseif ($member = $order->Member()) {
                        $memberIsOK = true;
                        if ($member->IsShopAdmin()) {
                            $memberIsOK = false;
                        }
                    }
                    if ($memberIsOK) {
                        $items = OrderItem::get()->filter(array('OrderID' => $order->ID));
                        if ($items && $items->count()) {
                            $fileData .= $this->generateExportFileDataDetails($order->getOrderEmail(), $order->SubmissionLog()->Created, $items);
                        }
                    }
                }
            }
            unset($orders);
            $orders = $this->getMyOrders($idArray, $count, $offset);
        }
        if ($fileData) {
            return $fileData;
        } else {
            return null;
        }
    }

    /**
     *
     *
     * @param  array    $idArray Order IDs
     * @param  int      $count
     * @param  int      $offset
     * @return DataList
     */
    protected function getMyOrders($idArray, $count, $offset)
    {
        return  Order::get()
            ->sort('"Order"."ID" ASC')
            ->filter(array('ID' => $idArray))
            ->leftJoin('Member', '"Member"."ID" = "Order"."MemberID"')
            ->limit($count, $offset);
    }

    private $isFirstRow = true;

    public function generateExportFileDataDetails($email, $date, $orderItems)
    {
        $separator = $this->csvSeparator;
        $fileData = '';
        $columnData = array();
        $exportFields = Config::inst()->get('GridFieldExportSalesButton', 'fields_and_methods_to_be_exported');
        if ($this->isFirstRow) {
            $fileData = '"Email"'.$separator.'"SubmittedDate"'.$separator.'"'.implode('"'.$separator.'"', $exportFields).'"'."\n";
            $this->isFirstRow = false;
        }
        if ($orderItems) {
            foreach ($orderItems as $item) {
                $columnData = array();
                $columnData[] = '"'.$email.'"';
                $columnData[] = '"'.$date.'"';
                foreach ($exportFields as $field) {
                    if ($item->hasMethod($field)) {
                        $value = $item->$field();
                    } else {
                        $value = $item->$field;
                    }
                    $value = preg_replace('/\s+/', ' ', $value);
                    $value = preg_replace('/\s+/', ' ', $value);
                    $value = str_replace(array("\r", "\n"), "\n", $value);
                    $value = str_replace(array("\r", "\n"), "\n", $value);
                    $tmpColumnData = '"'.str_replace('"', '\"', $value).'"';
                    $columnData[] = $tmpColumnData;
                }
                $fileData .= implode($separator, $columnData);
                $fileData .= "\n";
                $item->destroy();
                unset($item);
                unset($columnData);
            }

            return $fileData;
        } else {
            return '';
        }
    }
}
