<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\ORM\DB;
use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Selects all products without a price.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportNoAlsoShowLinkProducts extends Report
{
    use EcommerceProductReportTrait;

    protected $dataClass = Product::class;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.NO_ALSO_SHOW', 'E-commerce: Products: without Also Show Parent');
    }

    public function updateEcommerceList($list)
    {
        return $list
            ->where('"ProductGroup"."ID" IS NULL')
            ->sort('Title', 'ASC')
            ->innerJoin('Product_ProductGroups', '"Product_ProductGroups"."ProductID" = "SiteTree"."ID"')
            ->leftJoin('ProductGroup', '"ProductGroup"."ID" = "Product_ProductGroups"."ProductGroupID"')
        ;
    }
}
