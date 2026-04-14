<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Reports;

use Override;
use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Selects all products.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportProductsNotNamed extends Report
{
    use EcommerceProductReportTrait;

    protected $dataClass = Product::class;

    private static $min_length = 100;

    /**
     * @return string
     */
    #[Override]
    public function title()
    {
        return _t('EcommerceSideReport.EcommerceSideReportProductsNotNamed', 'E-commerce: Products: Not Named');
    }

    protected function getEcommerceWhere($params = null): string
    {
        return 'CHAR_LENGTH("Title") = 0 OR "Title" IS NULL OR "Title" LIKE \'%Untitled%\' OR "Title" LIKE \'%New Product%\'';
    }
}
