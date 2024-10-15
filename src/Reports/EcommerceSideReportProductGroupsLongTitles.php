<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Selects all products.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportProductGroupsLongTitles extends Report
{
    use EcommerceProductGroupReportTrait;

    private static $min_length = 100;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.PRODUCT_GROUPS_LONG_TITLES', 'E-commerce: Product Groups: Long Titles');
    }

    protected function getEcommerceWhere($params = null): string
    {
        return 'CHAR_LENGTH("Title") > ' . $this->Config()->get('min_length');
    }
}
