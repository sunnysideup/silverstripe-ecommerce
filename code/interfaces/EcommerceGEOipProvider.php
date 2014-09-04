<?php


interface EcommerceGEOipProvider {

	/**
	 * returns a country code of the current user...
	 * @return String
	 */
	public function getCountry();

}
