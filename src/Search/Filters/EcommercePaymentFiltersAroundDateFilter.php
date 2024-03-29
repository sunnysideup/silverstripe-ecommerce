<?php

namespace Sunnysideup\Ecommerce\Search\Filters;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\Filters\ExactMatchFilter;

/**
 * @description: provides a bunch of filters for search in ModelAdmin (CMS)
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 */
class EcommercePaymentFiltersAroundDateFilter extends ExactMatchFilter
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
     *
     * If it is set to 90 this means that for every 90 day you can be one day off.
     *
     * All variables are in days.
     *
     * @var int
     */
    private $divider = 90;

    /**
     * @return DataQuery
     */
    protected function applyOne(DataQuery $query)
    {
        //$this->model = $query->applyRelation($this->relation);
        $value = Convert::raw2sql($this->getValue());
        $date = new DBDate();
        $date->setValue($value);

        $distanceFromToday = time() - strtotime((string) $value);
        $maxDays = round($distanceFromToday / (($this->divider * 2) * 86400)) + 1;

        $formattedDate = $date->format('Y-MM-d');

        // changed for PostgreSQL compatability
        // NOTE - we may wish to add DATEDIFF function to PostgreSQL schema, it's just that this would be the FIRST function added for SilverStripe
        // default is MySQL DATEDIFF() function - broken for others, each database conn type supported must be checked for!
        $query->where("(DATEDIFF(\"EcommercePayment\".\"Created\", '{$formattedDate}') > -" . $maxDays . " AND DATEDIFF(\"EcommercePayment\".\"Created\", '{$formattedDate}') < " . $maxDays . ')');

        return $query;
    }
}
