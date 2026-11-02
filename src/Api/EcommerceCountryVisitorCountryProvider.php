<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use Sunnysideup\Ecommerce\Interfaces\EcommerceGEOipProvider;
use Sunnysideup\OneTimeCode\ClientIp;
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
    use Configurable;

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
        if ($ip === '' || $ip === '0') {
            $ip = $this->getIP();
        }
        $class = $this->Config()->get('country_provider');
        if (class_exists($class)) {
            return $class::visitor_country($ip);
        }

        return Config::inst()->get(EcommerceCountry::class, 'default_country_code');
    }

    /**
     * returns string of IP address of the visitor (empty if it is not known, e.g. on the command line).
     */
    public function getIP()
    {
        return self::visitor_ip();
    }

    /**
     * The IP address of the visitor. This is the ONE place where the address is worked out,
     * all other code should get it from EcommerceCountry::get_ip().
     *
     * It is the real address of the visitor, also behind CloudFlare, and it can not be faked
     * with headers: see Sunnysideup\OneTimeCode\ClientIp.
     * The answer is always a single, valid IP address, or an empty string.
     */
    public static function visitor_ip(): string
    {
        $ip = ClientIp::get();

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }
}
