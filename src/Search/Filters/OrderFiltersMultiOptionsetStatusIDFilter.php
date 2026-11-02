<?php

namespace Sunnysideup\Ecommerce\Search\Filters;

use Override;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\ExactMatchFilter;

/**
 * Allows you to filter orders for multiple statusIDs.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 */
class OrderFiltersMultiOptionsetStatusIDFilter extends ExactMatchFilter
{
    /**
     * @return DataQuery
     */
    #[Override]
    public function apply(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        // the values come from the browser: only whole numbers (IDs) are allowed
        // and they are passed to the database as parameters.
        $values = $this->getValue();
        if (! is_array($values)) {
            $values = [$values];
        }
        $values = array_values(
            array_unique(
                array_filter(
                    array_map('intval', array_filter($values, 'is_scalar'))
                )
            )
        );
        if ($values !== []) {
            $query = $query->where([
                '"StatusID" IN (' . DB::placeholders($values) . ')' => $values,
            ]);
        }

        return $query;
    }
}
