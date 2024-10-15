<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Selects all products.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportProductsMismatchSearchAllowPurchase extends Report
{
    use EcommerceProductReportTrait;
    protected $dataClass = Product::class;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.MISMATCH_SEARCH_ALLOW_PURCHASE', 'E-commerce: Products: not for sale but shown in search or vice versa.');
    }

    protected function getEcommerceWhere($params = null): string
    {
        return '(ShowInSearch = 1 AND AllowPurchase = 0) OR (ShowInSearch = 0 AND AllowPurchase = 1) ';
    }
}
