<?php

namespace Sunnysideup\Ecommerce\Search\Filters;

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\ExactMatchFilter;

/**
 * Allows you to filter orders for multiple statusIDs.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 */
class OrderEmailRecordFiltersMultiOptionsetStatusIDFilter extends ExactMatchFilter
{
    /**
     * @return DataQuery
     */
    public function apply(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $values = $this->getValue();
        if (is_array($values) && count($values)) {
            $query->where('"OrderStepID" IN (' . implode(', ', $values) . ')');
        }

        return $query;
    }
}
