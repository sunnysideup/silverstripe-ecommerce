<?php

namespace Sunnysideup\Ecommerce\Model\Address;

class EcommerceRegionVisitorRegionProvider
{
    /**
     * @return int - region ID
     */
    public function getRegion(): int
    {
        $region = EcommerceRegion::get()->setUseCache(true)->first();
        if ($region) {
            return $region->ID;
        }

        return 0;
    }
}
