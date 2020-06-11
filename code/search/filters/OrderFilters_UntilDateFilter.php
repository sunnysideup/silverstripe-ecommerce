<?php

/**
 * Allows you to filter orders that are within three days of a specific date.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderFilters_UntilDateFilter extends ExactMatchFilter
{
    /**
     *@return SQLQuery
     **/
    public function apply(DataQuery $query)
    {
        $value = $this->getValue();

        $date = new Date();
        $date->setValue($value);
        $formattedDate = $date->format('Y-m-d');

        $query->where("\"Order\".\"Created\" <= '$formattedDate'");
        return $query;
    }
}
