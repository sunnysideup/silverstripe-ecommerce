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

    private static $min_length = 100;

    protected $dataClass = Product::class;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.ALLPRODUCTS', 'E-commerce: Long Titles');
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
        return 'CHAR_LENGTH("Title") > '.$this->Config('min_length');
    }
}
