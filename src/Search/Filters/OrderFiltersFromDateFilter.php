<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Search\Filters;

use Override;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\Filters\ExactMatchFilter;

/**
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 */
class OrderFiltersFromDateFilter extends ExactMatchFilter
{
    /**
     * @return DataQuery
     */
    #[Override]
    public function apply(DataQuery $query)
    {
        $value = Convert::raw2sql($this->getValue());

        $date = DBDate::create();
        $date->setValue(strtotime((string) $value));
        if ($date->getTimestamp() > 0) {

            $formattedDate = $date->format('y-MM-d');
            if ($formattedDate) {
                $query->where(sprintf("\"Order\".\"Created\" >= '%s'", $formattedDate));
            }
        }

        return $query;
    }
}
