<?php

namespace Sunnysideup\Ecommerce\Forms\Gridfield;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridField_URLHandler;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\OrderItem;

/**
 * Adds an "Export list" button to the bottom of a {@link GridField}.
 */
class GridFieldExportSalesButton extends GridFieldExportButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler
{
    /**
     * Array of fields to be exporoted.
     *
     * @var array
     */
    private static $fields_and_methods_to_be_exported = [
        'OrderID',
        'InternalItemID',
        'TableTitle',
        'TableSubTitleNOHTML',
        'UnitPrice',
        'Quantity',
        'CalculatedTotal',
    ];

    private $isFirstRow = true;

    /**
     * export is an action button.
     *
     * @param mixed $gridField
     */
    public function getActions($gridField)
    {
        return ['exportsales'];
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ('exportsales' === $actionName) {
            return $this->handleSales($gridField);
        }
    }

    /**
     * it is also a URL.
     *
     * @param mixed $gridField
     */
    public function getURLHandlers($gridField)
    {
        return [
            'exportsales' => 'handleSales',
        ];
    }

    /**
     * Handle the export, for both the action button and the URL.
     *
     * @param mixed      $gridField
     * @param null|mixed $request
     */
    public function handleSales($gridField, $request = null)
    {
        if ($fileData = $this->generateExportFileData($gridField)) {
            $now = date('d-m-Y-H-i');
            $fileName = "sales-{$now}.csv";

            return HTTPRequest::send_file($fileData, $fileName, 'text/csv');
        }
    }

    /**
     * Place the export button in a <p> tag below the field.
     *
     * @param mixed $gridField
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

        return [
            $this->targetFragment => '<p class="grid-csv-button">' . $button->Field() . '</p>',
        ];
    }

    /**
     * Generate export fields for CSV.
     *
     * @param GridField $gridField
     *
     * @return null|string
     */
    public function generateExportFileData($gridField)
    {
        //reset time limit
        set_time_limit(1200);

        $idArray = [];

        $items = $gridField->getManipulatedList();

        foreach ($items->limit(null) as $item) {
            if (! $item->hasMethod('canView') || $item->canView()) {
                $idArray[$item->ID] = $item->ID;
            }
        }

        //data object variables
        // $orderStatusSubmissionLog = EcommerceConfig::get(OrderStatusLog::class, 'order_status_log_class_used_for_submitting_order');
        $fileData = '';
        $offset = 0;
        $count = 50;
        $orders = $this->getMyOrders($idArray, $count, $offset);

        while ($orders->exists()) {
            $offset += $count;
            foreach ($orders as $order) {
                if ($order->IsSubmitted()) {
                    $memberIsOK = false;
                    if (! $order->MemberID) {
                        $memberIsOK = true;
                    } elseif (! $order->Member()) {
                        $memberIsOK = true;
                    } elseif ($member = $order->Member()) {
                        $memberIsOK = true;
                        if ($member->IsShopAdmin()) {
                            $memberIsOK = false;
                        }
                    }
                    if ($memberIsOK) {
                        $items = OrderItem::get()->filter(['OrderID' => $order->ID]);
                        if ($items->exists()) {
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
        }

        return null;
    }

    public function generateExportFileDataDetails($email, $date, $orderItems)
    {
        $separator = $this->csvSeparator;
        $fileData = '';
        $columnData = [];
        $exportFields = Config::inst()->get(GridFieldExportSalesButton::class, 'fields_and_methods_to_be_exported');
        if ($this->isFirstRow) {
            $fileData = '"Email"' . $separator . '"SubmittedDate"' . $separator . '"' . implode('"' . $separator . '"', $exportFields) . '"' . "\n";
            $this->isFirstRow = false;
        }
        if ($orderItems) {
            foreach ($orderItems as $item) {
                $columnData = [];
                $columnData[] = '"' . $email . '"';
                $columnData[] = '"' . $date . '"';
                foreach ($exportFields as $field) {
                    $value = $item->hasMethod($field) ? $item->{$field}() : $item->{$field};
                    $value = preg_replace('#\s+#', ' ', $value);
                    $value = preg_replace('#\s+#', ' ', $value);
                    $value = str_replace(["\r", "\n"], "\n", $value);
                    $value = str_replace(["\r", "\n"], "\n", $value);
                    $tmpColumnData = '"' . str_replace('"', '\"', $value) . '"';
                    $columnData[] = $tmpColumnData;
                }
                $fileData .= implode($separator, $columnData);
                $fileData .= "\n";
                $item->destroy();
                unset($item, $columnData);
            }

            return $fileData;
        }

        return '';
    }

    /**
     * @param array $idArray Order IDs
     * @param int   $count
     * @param int   $offset
     *
     * @return \SilverStripe\ORM\DataList
     */
    protected function getMyOrders($idArray, $count, $offset)
    {
        return Order::get()
            ->sort('"Order"."ID" ASC')
            ->filter(['ID' => $idArray])
            ->leftJoin('Member', '"Member"."ID" = "Order"."MemberID"')
            ->limit($count, $offset)
        ;
    }
}
