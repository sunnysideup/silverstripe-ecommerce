<?php

namespace Sunnysideup\Ecommerce\Search\Filters;



use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\ExactMatchFilter;



/**
 * Allows you to filter for orders that have been cancelled.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search

 **/
class OrderFiltersHasBeenCancelled extends ExactMatchFilter
{
    /**
     *@return SQLQuery
     **/
    public function apply(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $value = $this->getValue();
        if ($value === 1) {
            $query->where('"CancelledByID" IS NOT NULL AND "CancelledByID" > 0');
        }

        return $query;
    }
}

