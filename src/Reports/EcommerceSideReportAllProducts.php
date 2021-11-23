<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Selects all products.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportAllProducts extends Report
{
    use EcommerceProductReportTrait;

    protected $dataClass = Product::class;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.ALLPRODUCTS', 'E-commerce: All products');
    }

    /**
     * @return int - for sorting reports
     */
    public function sort()
    {
        return 7000;
    }

    protected function getEcommerceSort($params = null) : array
    {
        return ['FullSiteTreeSort' => 'ASC'];
    }

}
