<?php

namespace Sunnysideup\Ecommerce\Interfaces;

interface EcommerceGEOipProvider
{
    /**
     * returns a country code of the current user...
     *
     * @return string
     */
    public function getCountry();

    /**
     * returns the IP address of the current user: a single, valid IP address, or an empty string.
     * Do not trust request headers for this (they can be faked): see EcommerceCountryVisitorCountryProvider::visitor_ip().
     *
     * @return string
     */
    public function getIP();
}
