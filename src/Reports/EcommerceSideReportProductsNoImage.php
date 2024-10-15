<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Selects all products without an image.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportProductsNoImage extends Report
{
    use EcommerceProductReportTrait;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.NOIMAGE', 'E-commerce: Products: without image');
    }

    /**
     * @param mixed $params
     */
    protected function getEcommerceWhere($params = null): string
    {
        return '"Product"."ImageID" IS NULL OR "Product"."ImageID" <= 0';
    }
}
