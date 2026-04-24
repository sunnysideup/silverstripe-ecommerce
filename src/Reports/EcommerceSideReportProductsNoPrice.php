<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Reports;

use Override;
use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Selects all products without a price.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportProductsNoPrice extends Report
{
    use EcommerceProductReportTrait;

    protected $dataClass = Product::class;

    /**
     * @return string
     */
    #[Override]
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
}
