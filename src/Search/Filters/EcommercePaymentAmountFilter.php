<?php

namespace Sunnysideup\Ecommerce\Search\Filters;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\ExactMatchFilter;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;

/**
 * Search the payments by amount, e.g. 129.50, 1129.5, $1,129.50 or 1 129.50.
 * The amount has to match to the cent.  Anything that is not a number finds nothing.
 *
 * Use it for the "Amount" in the searchable_fields of EcommercePayment.
 */
class EcommercePaymentAmountFilter extends ExactMatchFilter
{
    /**
     * Turns what a person types into a number (or null if it is not a number).
     */
    public static function parse_amount($value): ?float
    {
        if (! is_scalar($value)) {
            return null;
        }

        // keep digits, the decimal point and a minus sign (a refund): drop currency signs, spaces and thousand separators
        $clean = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', (string) $value));

        return (is_string($clean) && is_numeric($clean)) ? round((float) $clean, 2) : null;
    }

    protected function applyOne(DataQuery $query)
    {
        return $this->filterByAmount($query, true);
    }

    protected function excludeOne(DataQuery $query)
    {
        return $this->filterByAmount($query, false);
    }

    protected function filterByAmount(DataQuery $query, bool $inclusive): DataQuery
    {
        $amount = self::parse_amount($this->getValue());
        if (null === $amount) {
            $query->where($inclusive ? '1 = 0' : '1 = 1');

            return $query;
        }

        $column = '"' . DataObject::getSchema()->tableName(EcommercePayment::class) . '"."AmountAmount"';
        // the column has four decimals, so we compare with a margin of half a cent.
        $query->where([
            ($inclusive ? '' : 'NOT ') . '(ABS(' . $column . ' - ?) < 0.005)' => $amount,
        ]);

        return $query;
    }
}
