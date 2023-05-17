<?php

namespace Sunnysideup\Ecommerce\Search\Filters;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\Filters\ExactMatchFilter;

/**
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 */
class OrderFiltersFromDateFilter extends ExactMatchFilter
{
    /**
     * @return DataQuery
     */
    public function apply(DataQuery $query)
    {
        $value = Convert::raw2sql($this->getValue());

        $date = new DBDate();
        $date->setValue(strtotime((string) $value));
        if($date->getTimestamp() > 0) {

            $formattedDate = $date->format('y-MM-d');
            if($formattedDate) {
                $query->where("\"Order\".\"Created\" >= '{$formattedDate}'");
            }
        }

        return $query;
    }
}
