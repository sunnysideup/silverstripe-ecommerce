<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderEmailRecord;
use Sunnysideup\Ecommerce\Reports\EcommerceOrderReportTrait;

class EcommerceSideReportOrders extends Report
{
    use EcommerceOrderReportTrait;
    protected $dataClass = Order::class;

    public function title()
    {
        return 'All Orders (show last months by default)';
    }
}
