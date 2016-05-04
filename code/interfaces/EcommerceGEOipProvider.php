<?php


interface EcommerceGEOipProvider
{
    /**
     * returns a country code of the current user...
     *
     * @return string
     */
    public function getCountry();

    /**
     * returns a country code of the current user...
     *
     * @return string
     */
    public function getIP();
}
