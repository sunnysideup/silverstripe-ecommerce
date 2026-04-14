<?php

namespace Sunnysideup\Ecommerce\Search\Filters;

use Override;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\ExactMatchFilter;

/**
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 */
class ProductMinimumPriceFilter extends ExactMatchFilter
{
    /**
     * @return DataQuery
     */
    #[Override]
    public function apply(DataQuery $query)
    {
        $value = floatval($this->getValue());
        if ($value !== 0.0) {
            $query->where('"Product"."Price" >= ' . $value);
        }

        return $query;
    }
}
