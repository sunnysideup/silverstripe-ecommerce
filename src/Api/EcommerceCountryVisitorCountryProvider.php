<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Control\Controller;


use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
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

    use SilverStripe\Core\Config\Configurable;

    private static $country_provider = '\\Sunnysideup\\Geoip\\Geoip';

    public static function ip2country(?string $ip = '')
    {
        return Injector::inst()->get(self::class)->getCountry($ip);
    }

    /**
     * @return string (Country Code - e.g. NZ, AU, or AF)
     */
    public function getCountry(string $ip = '')
    {
        if (! $ip) {
            $ip = $this->getIP();
        }
        $class = $this->Config()->get('country_provider');
        if (class_exists($class)) {
            return $class::visitor_country($ip);
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
