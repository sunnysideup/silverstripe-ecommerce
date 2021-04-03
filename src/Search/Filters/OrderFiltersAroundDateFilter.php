<?php

namespace Sunnysideup\Ecommerce\Search\Filters;

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\Filters\ExactMatchFilter;

/**
 * Allows you to filter orders that are within three days of a specific date.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 */
class OrderFiltersAroundDateFilter extends ExactMatchFilter
{
    /**
     * Additional days to add each month to make the filter for fuzzy as
     * you go further back in time.
     *
     * @var float
     */
    protected $additionalDaysPerMonth = 1;

    /**
     *  @return DataQuery
     */
    public function apply(DataQuery $query)
    {
        $value = $this->getValue();

        $date = new DBDate();
        $date->setValue($value);

        $formattedDate = $date->format('Y-MM-d');

        $distanceFromToday = time() - strtotime($value);
        $distanceFromTodayInDays = $distanceFromToday / 86400;
        $maxDays = 1;
        $maxDays += round($distanceFromTodayInDays / 30.5) * $this->additionalDaysPerMonth;
        $query->where("(ABS(DATEDIFF(\"LastEdited\", '{$formattedDate}')) < " . $maxDays . " OR ABS(DATEDIFF(\"Created\", '{$formattedDate}')) < " . $maxDays . ')');

        return $query;
    }
}
