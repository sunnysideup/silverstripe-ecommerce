<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\BuildTask;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\OrderItem;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;

/**
 * set the order id number.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskOrderItemsPerCustomer extends BuildTask
{
    protected $title = 'Export all order items to CSV per customer';

    protected $description = 'Allows download of all sales items with all details as CSV. Excludes sales made by Admins';

    public function run($request)
    {
        //reset time limit
        set_time_limit(1200);

        //file data
        $now = date('d-m-Y-H-i');
        $fileName = "export-{$now}.csv";

        //data object variables
        $orderStatusSubmissionLog = EcommerceConfig::get(OrderStatusLog::class, 'order_status_log_class_used_for_submitting_order');
        $orderStatusSubmissionLogTableName = $orderStatusSubmissionLog::getSchema()->tableName($orderStatusSubmissionLog);
        $fileData = '';
        $offset = 0;
        $count = 50;
        $orders = Order::get()
            ->sort(['Order.ID' => 'ASC'])
            ->innerJoin('OrderStatusLog', '"Order"."ID" = "OrderStatusLog"."OrderID"')
            ->innerJoin($orderStatusSubmissionLogTableName, "\"{$orderStatusSubmissionLogTableName}\".\"ID\" = \"OrderStatusLog\".\"ID\"")
            ->leftJoin('Member', '"Member"."ID" = "Order"."MemberID"')
            ->limit($count, $offset)
        ;
        $ordersCountExists = $orders->exists();
        $sanityCheck = 0;
        while ($ordersCountExists && $sanityCheck < 1000) {
            ++$sanityCheck;
            $offset += $count;
            foreach ($orders as $order) {
                if ($order->IsSubmitted()) {
                    $memberIsOK = false;
                    $member = $order->Member();
                    if ($member && $member->exists()) {
                        $memberIsOK = true;
                        if ($member->IsShopAssistant()) {
                            $memberIsOK = false;
                        }
                    } else {
                        $memberIsOK = true;
                    }
                    if ($memberIsOK) {
                        $items = OrderItem::get()->filter(['OrderID' => $order->ID]);
                        if ($items->exists()) {
                            $fileData .= $this->generateExportFileData($order->getOrderEmail(), $order->SubmissionLog()->Created, $items);
                        }
                    }
                }
            }
            $orders = Order::get()
                ->sort(['Order.ID' => 'ASC'])
                ->innerJoin('OrderStatusLog', '"Order"."ID" = "OrderStatusLog"."OrderID"')
                ->innerJoin($orderStatusSubmissionLogTableName, "\"{$orderStatusSubmissionLogTableName}\".\"ID\" = \"OrderStatusLog\".\"ID\"")
                ->leftJoin('Member', '"Member"."ID" = "Order"."MemberID"')
                ->limit($count, $offset)
            ;
            $ordersCountExists = $orders->exists();
        }
        unset($orders);
        if ($fileData !== '' && $fileData !== '0') {
            HTTPRequest::send_file($fileData, $fileName, 'text/csv');
        } else {
            user_error('No records found', E_USER_ERROR);
        }
    }

    public function generateExportFileData($email, $date, $orderItems)
    {
        $separator = ',';
        $fileData = '';
        $columnData = [];
        $exportFields = [
            'OrderID',
            'InternalItemID',
            'TableTitle',
            'TableSubTitleNOHTML',
            'UnitPrice',
            'Quantity',
            'CalculatedTotal',
        ];

        if ($orderItems) {
            foreach ($orderItems as $item) {
                $columnData = [];
                $columnData[] = '"' . $email . '"';
                $columnData[] = '"' . $date . '"';
                foreach ($exportFields as $field) {
                    $value = $item->{$field};
                    $value = preg_replace('#\s+#', ' ', (string) $value);
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
}
