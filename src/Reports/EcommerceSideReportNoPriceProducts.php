<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Selects all products without a price.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportNoPriceProducts extends Report
{
    use EcommerceProductReportTrait;

    protected $dataClass = Product::class;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.NO_PRICE', 'E-commerce: Products: without Price');
    }

    /**
     * @param null|mixed $params
     */
    protected function getEcommerceWhere($params = null): string
    {
        return '"Product"."Price" IS NULL OR "Product"."Price" = 0 ';
    }

    /**
     * @param null|mixed $params
     */
    protected function getEcommerceSort($params = null): array
    {
        return ['FullSiteTreeSort' => 'ASC'];
    }
}
