<?php

namespace Sunnysideup\Ecommerce\Model\Address;



use Sunnysideup\Ecommerce\Model\Address\EcommerceRegion;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ViewableData;




/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD:  extends Object (ignore case)
  * NEW:  extends ViewableData (COMPLEX)
  * EXP: This used to extend Object, but object does not exist anymore. You can also manually add use Extensible, use Injectable, and use Configurable
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
class EcommerceRegionVisitorRegionProvider extends ViewableData
{
    /**
     * @return int - region ID
     */
    public function getRegion()
    {
        $region = DataObject::get_one(EcommerceRegion::class);
        if ($region) {
            return $region->ID;
        }
    }
}

