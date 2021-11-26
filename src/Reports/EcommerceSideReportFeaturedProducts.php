<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;

/** @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportFeaturedProducts extends Report
{
    use EcommerceProductReportTrait;

    protected $dataClass = Product::class;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.FEATUREDPRODUCTS', 'E-commerce: Featured products');
    }

    /**
     * working out the items.
     *
     * @param null|mixed $params
     *
     * @return \SilverStripe\ORM\DataList
     */
    protected function getEcommerceFilter($params = null): array
    {
        return ['FeaturedProduct' => 1];
    }

    /**
     * @param null|mixed $params
     */
    protected function getEcommerceSort($params = null): array
    {
        return ['FullSiteTreeSort' => 'ASC'];
    }
}
