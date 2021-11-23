<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Selects all products that are not for sale.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportNotForSale extends Report
{

    use EcommerceProductReportTrait;

    protected $dataClass = Product::class;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.NOTFORSALE', 'E-commerce: Products not for sale');
    }

    /**
     *
     * @param null|mixed $params
     */
    public function getEcommerceFilter($params = null) : array
    {
        return ['AllowPurchase' => 0];
    }

    /**
     *
     * @param null|mixed $params
     */
    public function getEcommerceSort($params = null) : array
    {
        return ['FullSiteTreeSort' => 'ASC'];
    }

}
