<?php

namespace Sunnysideup\Ecommerce\Search\Filters;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\ExactMatchFilter;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Model\Address\BillingAddress;
use Sunnysideup\Ecommerce\Model\Address\ShippingAddress;

/**
 * Filter that searches the Two Addresses (billing + shipping)
 * and the member. It searches all the relevant fields.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 */
class OrderFiltersMemberAndAddressFilter extends ExactMatchFilter
{
    /**
     * @return DataQuery
     */
    public function apply(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $value = Convert::raw2sql($this->getValue());
        $billingAddressesIDs = [];
        $billingAddresses = BillingAddress::get()->filterAny([
            'FirstName:PartialMatch' => $value,
            'Surname:PartialMatch' => $value,
            'Email:PartialMatch' => $value,
            'CompanyName:PartialMatch' => $value,
            'Address:PartialMatch' => $value,
            'Address2:PartialMatch' => $value,
            'City:PartialMatch' => $value,
            'PostalCode:PartialMatch' => $value,
            'Phone:PartialMatch' => $value,
        ]);

        if ($billingAddresses->exists()) {
            $billingAddressesIDs = $billingAddresses->columnUnique();
        }
        $billingAddressesIDs = ArrayMethods::filter_array($billingAddressesIDs);
        $where[] = '"BillingAddressID" IN (' . implode(',', $billingAddressesIDs) . ')';
        $shippingAddressesIDs = [];
        $shippingAddresses = ShippingAddress::get()->filterAny([
            'ShippingFirstName:PartialMatch' => $value,
            'ShippingSurname:PartialMatch' => $value,
            'ShippingCompanyName:PartialMatch' => $value,
            'ShippingAddress:PartialMatch' => $value,
            'ShippingAddress2:PartialMatch' => $value,
            'ShippingCity:PartialMatch' => $value,
            'ShippingPostalCode:PartialMatch' => $value,
            'ShippingPhone:PartialMatch' => $value,
        ]);
        if ($shippingAddresses->exists()) {
            $shippingAddressesIDs = $shippingAddresses->columnUnique();
        }
        $shippingAddressesIDs = ArrayMethods::filter_array($shippingAddressesIDs);
        $where[] = '"ShippingAddressID" IN (' . implode(',', $shippingAddressesIDs) . ')';
        $memberIDs = [];
        $members = Member::get()->filterAny([
            'FirstName:PartialMatch' => $value,
            'Surname:PartialMatch' => $value,
            'Email:PartialMatch' => $value,
        ]);
        if ($members->exists()) {
            $memberIDs = $members->columnUnique();
        }
        $memberIDs = ArrayMethods::filter_array($memberIDs);
        $where[] = '"MemberID" IN (' . implode(',', $memberIDs) . ')';

        return $query->where('(' . implode(') OR (', $where) . ')');
    }
}
