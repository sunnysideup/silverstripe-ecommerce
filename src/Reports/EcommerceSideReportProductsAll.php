<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Reports\Report;

/**
 * Selects all products.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportProductsAll extends Report
{
    use EcommerceProductReportTrait;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.ALLPRODUCTS', 'E-commerce: Products: All');
    }
}
