<?php


/**
 * @description: provides a bunch of filters for search in ModelAdmin (CMS)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class PaymentFilter_AroundDateFilter extends ExactMatchFilter {

	/**
	 * The divider is used to work out the
	 * maximum number of days we should be from the date.
	 * The Further back in time we go, the greater the margin of error.
	 *
	 * For example, if you search for a date that is one year ago,
	 * then the margin of error is 360/12 = 30 days.
	 * if we search for yesterdaty then the margin of error is one.
	 *
	 * The calculation works as follow: [today] - [searched day] / [divider].
	 * All variables are in days.
	 *
	 * @var Int
	 */
	private $divider = 12;

	/**
	 *
	 *@return SQLQuery
	 **/
	public function apply(DataQuery $query) {
		$query = $this->applyRelation($query);
		$value = $this->getValue();
		$date = new Date();
		$date->setValue($value);
		$distanceFromToday = time() - strtotime($value);
		$maxDays = round($distanceFromToday/($this->divider * 86400))+1;
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
