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
        $check = ProductGroup::get()->filter(['ImageID:GreaterThan' => 0])->columnUnique('ImageID');
        foreach ($check as $id) {
            $image = Image::get()->byID($id);
            if ($image && $image->exists()) {
                $alwaysInclude[] = $id;
            }
        }
        return '"ProductGroup"."ID" NOT IN (' . implode(',', $alwaysInclude) . ')';
    }
}
