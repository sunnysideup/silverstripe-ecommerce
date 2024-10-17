<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;

/** @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportProductsFeatured extends Report
{
    use EcommerceProductReportTrait;
    protected $dataClass = Product::class;
    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.FEATURED', 'E-commerce: Products: featured');
    }

    /**
     * working out the items.
     *
     * @param null|mixed $params
     */
    protected function getEcommerceFilter($params = null): array
    {
        return ['FeaturedProduct' => 1];
    }


}
