<?php

namespace Sunnysideup\Ecommerce\Api;



use Geoip;


use SilverStripe\Core\Config\Config;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;
use SilverStripe\Control\Controller;
use SilverStripe\View\ViewableData;
use Sunnysideup\Ecommerce\Interfaces\EcommerceGEOipProvider;



/**
 * this is a very basic class with as its sole purpose providing
 * the country of the customer.
 * By default we are using the GEOIP class
 * but you can switch it to your own system by changing
 * the classname in the ecommerce.yml config file.
 */

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD:  extends Object (ignore case)
  * NEW:  extends ViewableData (COMPLEX)
  * EXP: This used to extend Object, but object does not exist anymore. You can also manually add use Extensible, use Injectable, and use Configurable
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
class EcommerceCountryVisitorCountryProvider extends ViewableData implements EcommerceGEOipProvider
{
    /**
     * @return string (Country Code - e.g. NZ, AU, or AF)
     */
    public function getCountry()
    {
        if (class_exists('Geoip')) {
            return Geoip::visitor_country();
        }
        return Config::inst()->get(EcommerceCountry::class, 'default_country_code');
    }

    /**
     * returns string of IP address.
     */
    public function getIP()
    {
        return Controller::curr()->getRequest()->getIP();
    }
}

