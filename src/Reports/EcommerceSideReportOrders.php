<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Model\Order;

class EcommerceSideReportOrders extends Report
{
    use EcommerceOrderReportTrait;

    protected $dataClass = Order::class;

    public function title()
    {
        return 'All Orders (show last months by default)';
    }
}
