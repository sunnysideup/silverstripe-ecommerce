<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use SilverStripe\Assets\Image;

/**
 * Selects all products without an image.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportProductGroupsNoImage extends Report
{
    use EcommerceProductGroupReportTrait;
    protected $dataClass = ProductGroup::class;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.PRODUCT_GROUPS_NOIMAGE', 'E-commerce: Categories: without image');
    }

    /**
     * @param mixed $params
     */
    protected function getEcommerceWhere($params = null): string
    {
        $ok = ProductGroup::get()->filter(['ImageID:GreaterThan' => 0])
            ->innerJoin('File', '"File"."ID" = "ProductGroup"."ImageID"')
            ->columnUnique();
        if (empty($ok)) {
            return '"ProductGroup"."ID" NOT IN (0)';
        }
        return '"ProductGroup"."ID" NOT IN (' . implode(',', $ok) . ')';
    }
}
