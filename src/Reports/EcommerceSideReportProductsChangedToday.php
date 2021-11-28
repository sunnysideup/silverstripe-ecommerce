<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Selects all products.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportProductsChangedToday extends Report
{
    use EcommerceProductReportTrait;

    protected $dataClass = Product::class;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.ALLPRODUCTS', 'E-commerce: Products changed in the last three days');
    }

    /**
     * @return int - for sorting reports
     */
    public function sort()
    {
        return 7000;
    }

    protected function getEcommerceWhere($params = null): string
    {
        return '"LastEdited" >= DATE_ADD(CURDATE(), INTERVAL -3 DAY)';
    }
}
