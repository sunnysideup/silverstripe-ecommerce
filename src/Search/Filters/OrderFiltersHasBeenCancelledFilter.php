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
 */
class OrderFiltersHasBeenCancelledFilter extends ExactMatchFilter
{
    /**
     *  @return DataQuery
     */
    public function apply(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $value = (int) $this->getValue();
        if (1 === $value) {
            $query->where('"CancelledByID" IS NOT NULL AND "CancelledByID" > 0');
        }

        return $query;
    }
}
