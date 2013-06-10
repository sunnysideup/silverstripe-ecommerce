<?php


/**
 * @description: provides a bunch of filters for search in ModelAdmin (CMS)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderFilters_AroundDateFilter extends ExactMatchFilter {

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
		$this->model = $query->applyRelation($this->relation);
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
			$query->where("(\"Order\".\"LastEdited\"::date - '$formattedDate'::date)::integer > -".$maxDays." AND (\"Order\".\"Created\"::date - '$formattedDate'::date)::integer < ".$maxDays);
		}
		else {
			// default is MySQL DATEDIFF() function - broken for others, each database conn type supported must be checked for!
			$query->where("(DATEDIFF(\"Order\".\"LastEdited\", '$formattedDate') > -".$maxDays." AND DATEDIFF(\"Order\".\"Created\", '$formattedDate') < ".$maxDays.")");
		}
		return $query;
	}

	/**
	 *
	 *@return Boolean
	 **/
	public function isEmpty() {
		$val = $this->getValue();
		return $val == null || $val === '' || $val === 0 || $val === array();
	}

}

/**
 * Filter that searches the Two Addresses (billing + shipping)
 * and the member. It searches all the relevant fields.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderFilters_MemberAndAddress extends ExactMatchFilter {

	/**
	 *
	 *@return SQLQuery
	 **/
	public function apply(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$value = $this->getValue();
		$billingAddressesIDs = array(-1 => -1);
		$billingAddresses = BillingAddress::get()->where("
			\"FirstName\" LIKE '%$value%' OR
			\"Surname\" LIKE '%$value%' OR
			\"Email\" LIKE '%$value%' OR
			\"Address\" LIKE '%$value%' OR
			\"Address2\" LIKE '%$value%' OR
			\"City\" LIKE '%$value%' OR
			\"PostalCode\" LIKE '%$value%' OR
			\"Phone\" LIKE '%$value%' OR
			\"MobilePhone\" LIKE '%$value%'

		");

		if($billingAddresses->count()) {
			$billingAddressesIDs = $billingAddresses->map("ID", "ID")->toArray();
		}
		$where[] = "\"BillingAddressID\" IN (".implode(",", $billingAddressesIDs).")";
		$shippingAddressesIDs = array(-1 => -1);
		$shippingAddresses = ShippingAddress::get()->where("
			\"ShippingFirstName\" LIKE '%$value%' OR
			\"ShippingSurname\" LIKE '%$value%' OR
			\"ShippingAddress\" LIKE '%$value%' OR
			\"ShippingAddress2\" LIKE '%$value%' OR
			\"ShippingCity\" LIKE '%$value%' OR
			\"ShippingPostalCode\" LIKE '%$value%' OR
			\"ShippingPhone\" LIKE '%$value%' OR
			\"ShippingMobilePhone\" LIKE '%$value%'

		");
		if($shippingAddresses->count()) {
			$shippingAddressesIDs = $shippingAddresses->map("ID", "ID")->toArray();
		}
		$where[] = "\"ShippingAddressID\" IN (".implode(",", $shippingAddressesIDs).")";
		$memberIDs = array(-1 => -1);
		$members = Member::get()->where("
			\"FirstName\" LIKE '%$value%' OR
			\"Surname\" LIKE '%$value%' OR
			\"Email\" LIKE '%$value%'
		");
		if($members->count()) {
			$memberIDs = $members->map("ID", "ID")->toArray();
		}
		$where[] = "\"MemberID\" IN (".implode(",", $memberIDs).")";
		$query = $query->where("(".implode(") OR (", $where).")");
		return $query;

	}

	/**
	 *
	 *@return Boolean
	 **/
	public function isEmpty() {
		$val = $this->getValue();
		return $val == null || $val === '' || $val === 0 || $val === array();
	}

}


/**
 * Allows you to filter orders for multiple statusIDs
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderFilters_MultiOptionsetStatusIDFilter extends ExactMatchFilter {

	/**
	 *
	 *@return SQLQuery
	 **/
	public function apply(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$values = $this->getValue();
		if(is_array($values) && count($values)) {
			$query->where("\"StatusID\" IN (".implode(", ", $values).")");
		}
		return $query;
	}

	/**
	 *
	 *@return Boolean
	 **/
	public function isEmpty() {
		$val = $this->getValue();
		return $val == null || $val == '' || $val === 0 || $val === array();
	}
}


/**
 * Allows you to filter for orders that have been cancelled.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderFilters_HasBeenCancelled extends ExactMatchFilter {

	/**
	 *
	 *@return SQLQuery
	 **/
	public function apply(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$value = $this->getValue();
		if($value == 1) {
			$query->where("\"CancelledByID\" IS NOT NULL AND \"CancelledByID\" > 0");
		}
		return $query;
	}

	/**
	 *
	 *@return Boolean
	 **/
	public function isEmpty() {
		$val = $this->getValue();
		return $val == null || $val == '' || $val === 0 || $val === array();
	}
}


/**
 * Allows you to filter for orders that have at leat one payment
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderFilters_MustHaveAtLeastOnePayment extends ExactMatchFilter {

	/**
	 *
	 *@return SQLQuery
	 **/
	public function apply(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$value = $this->getValue();
		if($value && in_array($value, array(0,1))) {
			$query->innerJoin(
				$table = "Payment", // framework already applies quotes to table names here!
				$onPredicate = "\"Payment\".\"OrderID\" = \"Order\".\"ID\"",
				$tableAlias=null
			);
		}
		return $query;
	}

	/**
	 *
	 *@return Boolean
	 **/
	public function isEmpty() {
		$val = $this->getValue();
		return $val == null || $val == '' || $val === 0 || $val === array();
	}
}
