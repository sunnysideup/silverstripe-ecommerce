<?php

namespace PhotoWarehouse\App\Ecommerce\Reports;

use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Reports\EcommerceOrderReportTrait;

class EcommerceSideReportOrdersSimple extends Report
{
    use EcommerceOrderReportTrait;

    protected $dataClass = Order::class;

    public function title()
    {
        return 'Ecommerce - Simple List of Orders';
    }
}
