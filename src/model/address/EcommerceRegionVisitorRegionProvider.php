<?php

class EcommerceRegionVisitorRegionProvider extends Object
{
    /**
     * @return int - region ID
     */
    public function getRegion()
    {
        $region = DataObject::get_one('EcommerceRegion');
        if ($region) {
            return $region->ID;
        }
    }
}

