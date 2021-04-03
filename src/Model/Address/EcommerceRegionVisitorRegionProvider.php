<?php

namespace Sunnysideup\Ecommerce\Model\Address;

use SilverStripe\ORM\DataObject;

class EcommerceRegionVisitorRegionProvider
{
    /**
     * @return int - region ID
     */
    public function getRegion() : int
    {
        $region = DataObject::get_one(EcommerceRegion::class);
        if ($region) {
            return $region->ID;
        }
        return 0;
    }
}
