<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Selects all products.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportLongTitles extends Report
{
    use EcommerceProductReportTrait;

    protected $dataClass = Product::class;


    private static $min_length = 100;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.LONG_TITLES', 'E-commerce: Products: Long Titles');
    }

    /**
     * @return int - for sorting reports
     */
    public function sort()
    {
        return 7000;
    }

    protected function getEcommerceWhere($params = null): string
    {
        return 'CHAR_LENGTH("Title") > ' . $this->Config()->get('min_length');
    }
}
