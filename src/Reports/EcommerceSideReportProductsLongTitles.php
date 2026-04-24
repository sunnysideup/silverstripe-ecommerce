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
class EcommerceSideReportProductsLongTitles extends Report
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
        return _t('EcommerceSideReport.PRODUCT_LONG_TITLES', 'E-commerce: Products: Long Titles');
    }

    protected function getEcommerceWhere($params = null): string
    {
        return 'CHAR_LENGTH("Title") > ' . $this->Config()->get('min_length');
    }
}
