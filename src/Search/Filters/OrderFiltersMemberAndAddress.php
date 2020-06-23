<?php

namespace Sunnysideup\Ecommerce\Search\Filters;

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\ExactMatchFilter;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Model\Address\BillingAddress;
use Sunnysideup\Ecommerce\Model\Address\ShippingAddress;

/**
 * Filter that searches the Two Addresses (billing + shipping)
 * and the member. It searches all the relevant fields.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search

 **/
class OrderFiltersMemberAndAddress extends ExactMatchFilter
{
    /**
     * @return DataQuery
     **/
    public function apply(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $value = $this->getValue();
        $billingAddressesIDs = [-1 => -1];
        $billingAddresses = BillingAddress::get()->where("
            \"FirstName\" LIKE '%${value}%' OR
            \"Surname\" LIKE '%${value}%' OR
            \"Email\" LIKE '%${value}%' OR
            \"Address\" LIKE '%${value}%' OR
            \"Address2\" LIKE '%${value}%' OR
            \"City\" LIKE '%${value}%' OR
            \"PostalCode\" LIKE '%${value}%' OR
            \"Phone\" LIKE '%${value}%'
        ");

        if ($billingAddresses->count()) {
            $billingAddressesIDs = $billingAddresses->map('ID', 'ID')->toArray();
        }
        $where[] = '"BillingAddressID" IN (' . implode(',', $billingAddressesIDs) . ')';
        $shippingAddressesIDs = [-1 => -1];
        $shippingAddresses = ShippingAddress::get()->where("
            \"ShippingFirstName\" LIKE '%${value}%' OR
            \"ShippingSurname\" LIKE '%${value}%' OR
            \"ShippingAddress\" LIKE '%${value}%' OR
            \"ShippingAddress2\" LIKE '%${value}%' OR
            \"ShippingCity\" LIKE '%${value}%' OR
            \"ShippingPostalCode\" LIKE '%${value}%' OR
            \"ShippingPhone\" LIKE '%${value}%'
        ");
        if ($shippingAddresses->count()) {
            $shippingAddressesIDs = $shippingAddresses->map('ID', 'ID')->toArray();
        }
        $where[] = '"ShippingAddressID" IN (' . implode(',', $shippingAddressesIDs) . ')';
        $memberIDs = [-1 => -1];
        $members = Member::get()->where("
            \"FirstName\" LIKE '%${value}%' OR
            \"Surname\" LIKE '%${value}%' OR
            \"Email\" LIKE '%${value}%'
        ");
        if ($members->count()) {
            $memberIDs = $members->map('ID', 'ID')->toArray();
        }
        $where[] = '"MemberID" IN (' . implode(',', $memberIDs) . ')';
        return $query->where('(' . implode(') OR (', $where) . ')');
    }
}
