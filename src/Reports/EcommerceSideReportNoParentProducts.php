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
class EcommerceSideReportNoParentProducts extends Report
{
    use EcommerceProductReportTrait;

    protected $dataClass = Product::class;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.NO_ALSO_SHOW', 'E-commerce: Products without parents');
    }

    public function updateEcommerceList($list)
    {
        return $list
            ->where('"AllowPurchase" = 1 AND "PhotographicProductGroup"."ID" IS NULL')
            ->sort('Title', 'ASC')
            ->leftJoin('PhotographicProductGroup', '"SiteTree"."ParentID" = "PhotographicProductGroup"."ID"')
        ;
    }
}
