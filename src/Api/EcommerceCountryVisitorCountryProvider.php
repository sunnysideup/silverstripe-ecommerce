<?php

namespace Sunnysideup\Ecommerce\Api;

use Sunnysideup\Geoip\Geoip;


use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use Sunnysideup\Ecommerce\Interfaces\EcommerceGEOipProvider;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;

/**
 * this is a very basic class with as its sole purpose providing
 * the country of the customer.
 * By default we are using the GEOIP class
 * but you can switch it to your own system by changing
 * the classname in the ecommerce.yml config file.
 */
class EcommerceCountryVisitorCountryProvider implements EcommerceGEOipProvider
{
    /**
     * @return string (Country Code - e.g. NZ, AU, or AF)
     */
    public function getCountry()
    {
        if (class_exists(Geoip::class)) {
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
