<?php

namespace Sunnysideup\Ecommerce\Search\Filters;

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\Filters\ExactMatchFilter;

/**
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 **/
class OrderFiltersFromDateFilter extends ExactMatchFilter
{
    /**
     * @return DataQuery
     **/
    public function apply(DataQuery $query)
    {
        $value = $this->getValue();

        $date = new DBDate();
        $date->setValue(strtotime($value));

        $formattedDate = $date->format('y-MM-d');

        $query->where("\"Order\".\"Created\" >= '{$formattedDate}'");
        return $query;
    }
}
