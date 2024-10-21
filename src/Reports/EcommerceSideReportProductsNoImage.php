<?php

namespace Sunnysideup\Ecommerce\Reports;

use SilverStripe\Reports\Report;
use Sunnysideup\Ecommerce\Pages\Product;
use SilverStripe\Assets\Image;

/**
 * Selects all products without an image.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 */
class EcommerceSideReportProductsNoImage extends Report
{
    use EcommerceProductReportTrait;
    protected $dataClass = Product::class;

    /**
     * @return string
     */
    public function title()
    {
        return _t('EcommerceSideReport.NOIMAGE', 'E-commerce: Products: without image');
    }

    /**
     * @param mixed $params
     */
    protected function getEcommerceWhere($params = null): string
    {
        $check = Product::get()->filter(['ImageID:GreaterThan' => 0])->columnUnique('ImageID');
        foreach ($check as $id) {
            $image = Image::get()->byID($id);
            if ($image && $image->exists()) {
                $alwaysInclude[] = $id;
            }
        }
        return '"Product"."ID" NOT IN (' . implode(',', $alwaysInclude) . ')';
    }
}
