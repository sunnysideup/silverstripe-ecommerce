<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * Selects all products without an image.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportProductGroupsLostImages extends Report
{
    use EcommerceProductGroupReportTrait;
    protected $dataClass = ProductGroup::class;
    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.PRODCT_GROUPS_NOIMAGE', 'E-commerce: ProductsGroups: missing images');
    }

    public function updateEcommerceList($list)
    {
        return $list
            ->leftJoin('File', '"File"."ID" = "ProductGroup"."ImageID"')
            ->where('"File"."ID" IS NULL AND "ImageID" > 0')
            ->sort(['Title' => 'ASC'])
        ;
    }
}
