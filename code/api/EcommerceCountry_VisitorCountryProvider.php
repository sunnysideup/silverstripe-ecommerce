<?php

/**
 * this is a very basic class with as its sole purpose providing
 * the country of the customer.
 * By default we are using the GEOIP class
 * but you can switch it to your own system by changing
 * the classname in the ecommerce.yml config file.
 */
class EcommerceCountry_VisitorCountryProvider extends Object implements EcommerceGEOipProvider
{
    /**
     * @return string (Country Code - e.g. NZ, AU, or AF)
     */
    public function getCountry()
    {
        if (class_exists('Geoip')) {
            return Geoip::visitor_country();
        } else {
            return Config::inst()->get('EcommerceCountry', 'default_country_code');
        }
    }

    /**
     * returns string of IP address.
     */
    public function getIP()
    {
        return Controller::curr()->getRequest()->getIP();
    }
}
