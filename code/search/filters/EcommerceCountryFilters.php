<?php


/**
 * @description: provides a bunch of filters for search in ModelAdmin (CMS)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceCountryFilters_AllowSales extends ExactMatchFilter {


	/**
	 *
	 *@return SQLQuery
	 **/
	public function apply(DataQuery $query) {
		$query->where("\"DoNotAllowSales\" = 0");
		return $query;
	}

	/**
	 *
	 *@return Boolean
	 **/
	public function isEmpty() {
		return $this->getValue() ? false : true;
	}

}
