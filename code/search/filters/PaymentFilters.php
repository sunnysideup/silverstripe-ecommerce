<?php

/**
 * @description: provides a bunch of filters for search in ModelAdmin (CMS)
 *
 * @authors: Nicolaas
 *
 * @package: ecommerce
 * @sub-package: cms
 *
 **/


class PaymentFilter_AroundDateFilter extends ExactMatchFilter {

	/**
	 *
	 *@return SQLQuery
	 **/
	public function apply(SQLQuery $query) {
		$query = $this->applyRelation($query);
		$value = $this->getValue();
		$date = new Date();
		$date->setValue($value);
		$distanceFromToday = new Date() - $date;
		$maxDays = round($distanceFromToday/12)+1;
		$formattedDate = $date->format("Y-m-d");

		// changed for PostgreSQL compatability
		// NOTE - we may wish to add DATEDIFF function to PostgreSQL schema, it's just that this would be the FIRST function added for SilverStripe
		$db = DB::getConn();
		if( $db instanceof PostgreSQLDatabase ) {
			// don't know whether functions should be used, hence the following code using an interval cast to an integer
			$query->where("(\"Payment\".\"Created\"::date - '$formattedDate'::date)::integer > -".$maxDays." AND (\"Payment\".\"Created\"::date - '$formattedDate'::date)::integer < ".$maxDays);
		}
		else {
			// default is MySQL DATEDIFF() function - broken for others, each database conn type supported must be checked for!
			$query->where("(DATEDIFF(\"Payment\".\"Created\", '$formattedDate') > -".$maxDays." AND DATEDIFF(\"Payment\".\"Created\", '$formattedDate') < ".$maxDays.")");
		}
		return $query;

	}

	/**
	 *
	 *@return Boolean
	 **/
	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '';
	}

}
