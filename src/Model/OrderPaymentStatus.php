<?php

namespace Sunnysideup\Ecommerce\Model;

use SilverStripe\ORM\DataObject;
use Sunnysideup\Ecommerce\Model\Money\EcommerceCurrency;

class OrderPaymentStatus extends DataObject
{
    private static $table_name = 'OrderPaymentStatus';

    private static $db = [
        'IsPaid' => 'Boolean',
        'SubTotal' => 'Currency',
        'Total' => 'Currency',
        'TotalPaid' => 'Currency',
        'TotalOutstanding' => 'Currency',
    ];

    private static $has_one = [
        'Order' => Order::class,
        'CurrencyUsed' => EcommerceCurrency::class,
    ];

    private static $summary_fields = [
        'CurrencyUsed.Title' => 'Currency',
        'IsPaid' => 'Is Paid',
        'SubTotal' => 'Sub Total',
        'Total' => 'Total Charge',
        'TotalPaid' => 'Total Paid',
        'TotalOutstanding' => 'Total Outstanding',
    ];

    public function canEdit($member = null)
    {
        return false;
    }

    public function canDelete($member = null)
    {
        return false;
    }
    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    public function createOrUpdateRecord(Order $order): OrderPaymentStatus
    {
        $filter = ['OrderID' => $order->ID];
        $obj = self::get()->filter($filter)->first();
        if (! $obj) {
            $obj = self::create($filter);
        }
        $obj->IsPaid = $order->getIsPaid();
        $obj->SubTotal = $order->getSubTotal();
        $obj->Total = $order->getTotal();
        $obj->TotalPaid = $order->getTotalPaid();
        $obj->TotalOutstanding = $order->getTotalOutstanding();
        $obj->CurrencyUsedID = $order->CurrencyUsedID;
        $obj->write();
        return $obj;
    }
}
