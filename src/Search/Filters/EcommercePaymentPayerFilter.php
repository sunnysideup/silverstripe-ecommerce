<?php

namespace Sunnysideup\Ecommerce\Search\Filters;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\PartialMatchFilter;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Model\Address\BillingAddress;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
use Sunnysideup\Ecommerce\Model\Order;

/**
 * Search the payments by the name or the email address of the person that paid.
 *
 * It looks in the same places as EcommercePayment::getPayerDetails():
 *  - the member that is recorded on the payment,
 *  - the member of the order,
 *  - the billing address of the order (this also finds people without a member account).
 *
 * Every word that you type has to match the first name, the surname or the email address of ONE person,
 * so "john smith" and "smith john" and "john@example" all work.
 *
 * Use it for any field of the searchable_fields of EcommercePayment (it does not look at that field).
 */
class EcommercePaymentPayerFilter extends PartialMatchFilter
{
    private const MAX_WORDS = 6;

    private const NAME_FIELDS = ['FirstName', 'Surname', 'Email'];

    protected function applyOne(DataQuery $query)
    {
        return $this->filterByPayer($query, true);
    }

    protected function excludeOne(DataQuery $query)
    {
        return $this->filterByPayer($query, false);
    }

    protected function filterByPayer(DataQuery $query, bool $inclusive): DataQuery
    {
        $words = preg_split('/\s+/', trim((string) $this->getValue()), -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($words) || [] === $words) {
            return $query;
        }

        $words = array_slice($words, 0, self::MAX_WORDS);
        $schema = DataObject::getSchema();
        $payment = $schema->tableName(EcommercePayment::class);
        $order = $schema->tableName(Order::class);
        $member = $schema->tableName(Member::class);
        $billing = $schema->tableName(BillingAddress::class);

        $params = [];
        $memberSql = 'SELECT "' . $member . '"."ID" FROM "' . $member . '" WHERE ' . $this->peopleCondition($member, $words, $params);
        $memberParams = $params;

        $params = [];
        $billingSql = 'SELECT "' . $billing . '"."ID" FROM "' . $billing . '" WHERE ' . $this->peopleCondition($billing, $words, $params);
        $billingParams = $params;

        $sql = '(' .
            '"' . $payment . '"."PaidByID" IN (' . $memberSql . ')' .
            ' OR "' . $payment . '"."OrderID" IN (' .
                'SELECT "' . $order . '"."ID" FROM "' . $order . '"' .
                ' WHERE "' . $order . '"."MemberID" IN (' . $memberSql . ')' .
                ' OR "' . $order . '"."BillingAddressID" IN (' . $billingSql . ')' .
            ')' .
        ')';

        // the parameters have to be in the order of the question marks above
        $allParams = array_merge($memberParams, $memberParams, $billingParams);

        $query->where([($inclusive ? '' : 'NOT ') . $sql => $allParams]);

        return $query;
    }

    /**
     * (every word matches the first name OR the surname OR the email of the person) - for all words.
     */
    private function peopleCondition(string $table, array $words, array &$params): string
    {
        $perWord = [];
        foreach ($words as $word) {
            $like = '%' . addcslashes($word, '\\%_') . '%';
            $perField = [];
            foreach (self::NAME_FIELDS as $field) {
                $perField[] = '"' . $table . '"."' . $field . '" LIKE ?';
                $params[] = $like;
            }

            $perWord[] = '(' . implode(' OR ', $perField) . ')';
        }

        return '(' . implode(' AND ', $perWord) . ')';
    }
}
