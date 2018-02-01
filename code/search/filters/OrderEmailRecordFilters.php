<?php


/**
 * Allows you to filter orders for multiple statusIDs.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderEmailRecordFilters_MultiOptionsetStatusIDFilter extends ExactMatchFilter
{
    /**
     *@return SQLQuery
     **/
    public function apply(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $values = $this->getValue();
        if (is_array($values) && count($values)) {
            $query->where('"OrderStepID" IN ('.implode(', ', $values).')');
        }

        return $query;
    }
}
