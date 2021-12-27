<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Selects all products without an InternalID.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportNoInternalIDProducts extends Report
{
    use EcommerceProductReportTrait;

    protected $dataClass = Product::class;


    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.NOINTERNALID', 'E-commerce: Products: without Internal ID');
    }

    /**
     * @param null|mixed $params
     */
    protected function getEcommerceWhere($params = null): string
    {
        return "\"Product\".\"InternalItemID\" IS NULL OR \"Product\".\"InternalItemID\" = '' OR \"Product\".\"InternalItemID\" = '0' ";
    }

}
