<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Reports;

use Override;
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
    #[Override]
    public function title()
    {
        return _t('EcommerceSideReport.ALLPRODUCTS', 'E-commerce: Products: All');
    }
}
