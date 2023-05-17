<?php

namespace Sunnysideup\Ecommerce\Search\Filters;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\ExactMatchFilter;

/**
 * Allows you to filter orders for multiple statusIDs.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 */
class OrderFiltersMultiOptionsetStatusIDFilter extends ExactMatchFilter
{
    /**
     * @return DataQuery
     */
    public function apply(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $values = Convert::raw2sql($this->getValue());
        if (! is_array($values)) {
            $values = [$values];
        }
        if (is_array($values) && count($values)) {
            $query = $query->where('"StatusID" IN (' . implode(', ', $values) . ')');
        }

        return $query;
    }
}
