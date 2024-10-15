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
class EcommerceSideReportProductGroupsNoImage extends Report
{
    use EcommerceProductReportTrait;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.PRODUCT_GROUPS_NOIMAGE', 'E-commerce: Product Groups: without image');
    }

    /**
     * @param mixed $params
     */
    protected function getEcommerceWhere($params = null): string
    {
        return '"Product"."ImageID" IS NULL OR "Product"."ImageID" <= 0';
    }
}
