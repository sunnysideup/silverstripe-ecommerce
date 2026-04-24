<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Reports;

use Override;
use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Model\Order;

class EcommerceSideReportOrders extends Report
{
    use EcommerceOrderReportTrait;

    protected $dataClass = Order::class;

    #[Override]
    public function title()
    {
        return 'All Orders (show last months by default)';
    }
}
