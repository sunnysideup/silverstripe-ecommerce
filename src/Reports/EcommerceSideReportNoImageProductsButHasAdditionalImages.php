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
class EcommerceSideReportNoImageProductsButHasAdditionalImages extends Report
{
    use EcommerceProductReportTrait;

    protected $dataClass = Product::class;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.NOIMAGE_ADDITIONAL_IMAGES', 'E-commerce: Products: missing images - but has additional images');
    }

    public function updateEcommerceList($list)
    {
        $list = $list
            ->where('"Product"."ImageID" IS NULL OR "Product"."ImageID" <= 0')
            ->sort(['Title' => 'ASC'])
        ;
        $ids = [-1 => -1];
        foreach($list as $product) {
            if($product->AdditionalImages()->exists()) {
                $ids[] = $product->ID;
            }
        }
        return $list->filter(['ID' => $ids]);
    }
}
