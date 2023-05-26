<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Selects all products without a price.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportNoParentProducts extends Report
{
    use EcommerceProductReportTrait;

    protected $dataClass = Product::class;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.NO_PARENT_PRODUCT', 'E-commerce: Products: without parents');
    }

    public function updateEcommerceList($list)
    {
        return $list
            ->where(' "ProductGroup"."ID" IS NULL')
            ->sort('Title', 'ASC')
            ->leftJoin('ProductGroup', '"SiteTree"."ParentID" = "ProductGroup"."ID"')
        ;
    }
}
