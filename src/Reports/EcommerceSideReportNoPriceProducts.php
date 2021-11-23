<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Forms\GridField\GridFieldExportButton;
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

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.NOPRICE', 'E-commerce: Products without Price');
    }

    /**
     * @param null|mixed $params
     *
     */
    protected function getEcommerceWhere($params = null): string
    {
        return '"Product"."Price" IS NULL OR "Product"."Price" = 0 ';
    }

    /**
     * @param null|mixed $params
     *
     */
    protected function getEcommerceSort($params = null) : array
    {
        return ['FullSiteTreeSort' => 'ASC'];
    }

}
