<?php

namespace Sunnysideup\Ecommerce\Search\Filters;

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\ExactMatchFilter;

/**
 * Allows you to filter for orders that have at leat one payment.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 */
class OrderFiltersMustHaveAtLeastOnePaymentFilter extends ExactMatchFilter
{
    /**
     * @return DataQuery
     */
    public function apply(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $value = $this->getValue();
        if ($value) {
            $query->innerJoin(
                $table = 'Payment', // framework already applies quotes to table names here!
                $onPredicate = '"Payment"."OrderID" = "Order"."ID"',
                $tableAlias = null
            );
        }

        return $query;
    }
}
