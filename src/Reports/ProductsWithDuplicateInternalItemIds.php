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
class ProductsWithDuplicateInternalItemIds extends Report
{
    use EcommerceProductReportTrait;

    protected $dataClass = Product::class;

    /**
     * @return int - for sorting reports
     */
    public function sort()
    {
        return 7001;
    }

    /**
     * @return string
     */
    public function title()
    {
        return 'E-commerce: Products: with duplicate internal item IDs (product codes / skus)';
    }

    /**
     * @param mixed $list
     */
    protected function updateEcommerceList($list)
    {
        return $list
            ->where('
                (TheOtherProductTable.ID IS NOT NULL AND TheOtherProductTable.ID <> Product.ID)

                OR RIGHT(Product.InternalItemID,2) = \'_2\'
                OR RIGHT(Product.InternalItemID,2) = \'_3\'
                OR RIGHT(Product.InternalItemID,2) = \'_4\'
            ')
            ->sort('Title', 'ASC')
            ->leftJoin(
                'Product',
                '"Product"."InternalItemID" = TheOtherProductTable.InternalItemID',
                'TheOtherProductTable'
            )
        ;
    }
}
