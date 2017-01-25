<?php

/**
 * Allows you to filter orders that are within three days of a specific date.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderFilters_AroundDateFilter extends ExactMatchFilter
{
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
     * @var int
     */
    private $divider = 3;

    /**
     *@return SQLQuery
     **/
    public function apply(DataQuery $query)
    {
        $value = $this->getValue();
        $date = new Date();
        $date->setValue($value);
        $distanceFromToday = time() - strtotime($value);
        $maxDays = round($distanceFromToday / (($this->divider * 2) * 86400)) + 1;
        $formattedDate = $date->format('Y-m-d');
        $db = DB::getConn();
        if ($db instanceof PostgreSQLDatabase) {
            // don't know whether functions should be used, hence the following code using an interval cast to an integer
            $query->where("(\"Order\".\"LastEdited\"::date - '$formattedDate'::date)::integer > -".$maxDays." AND (\"Order\".\"Created\"::date - '$formattedDate'::date)::integer < ".$maxDays);
        } else {
            // default is MySQL DATEDIFF() function - broken for others, each database conn type supported must be checked for!
            $query->where("(DATEDIFF(\"Order\".\"LastEdited\", '$formattedDate') > -".$maxDays." AND DATEDIFF(\"Order\".\"Created\", '$formattedDate') < ".$maxDays.')');
        }

        return $query;
    }

    /**
     *@return bool
     **/
    public function isEmpty()
    {
        $val = $this->getValue();

        return $val == null || $val === '' || $val === 0 || $val === array();
    }
}