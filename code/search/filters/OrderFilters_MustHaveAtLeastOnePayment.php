<?php

/**
 * Allows you to filter for orders that have at leat one payment.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderFilters_MustHaveAtLeastOnePayment extends ExactMatchFilter
{
    /**
     *@return SQLQuery
     **/
    public function apply(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $value = $this->getValue();
        if ($value && in_array($value, [0, 1], true)) {
            $query->innerJoin(
                $table = 'Payment', // framework already applies quotes to table names here!
                $onPredicate = '"Payment"."OrderID" = "Order"."ID"',
                $tableAlias = null
            );
        }

        return $query;
    }
}
