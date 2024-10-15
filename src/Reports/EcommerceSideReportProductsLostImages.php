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
class EcommerceSideReportProductsLostImages extends Report
{
    use EcommerceProductReportTrait;
    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.NOIMAGE', 'E-commerce: Products: missing images');
    }

    public function updateEcommerceList($list)
    {
        return $list
            ->leftJoin('File', '"File"."ID" = "Product"."ImageID"')
            ->where('"File"."ID" IS NULL AND "ImageID" > 0')
            ->sort(['Title' => 'ASC'])
        ;
    }
}
