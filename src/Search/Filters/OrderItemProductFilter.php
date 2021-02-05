<?php

namespace Sunnysideup\Ecommerce\Search\Filters;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\ExactMatchFilter;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Model\Address\BillingAddress;
use Sunnysideup\Ecommerce\Model\Address\ShippingAddress;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Filter that searches the Two Addresses (billing + shipping)
 * and the member. It searches all the relevant fields.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search

 **/
class OrderItemProductFilter extends ExactMatchFilter
{
    /**
     * @return DataQuery
     **/
    public function apply(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $value = $this->getValue();
        $product = Product::get()->filter(['ID' => Convert::raw2sql($value)])->first();
        if($product) {
            $query->where('BuyableClassName = \''.addslashes($product->ClassName).'\' AND "BuyableID" = '.$product->ID);
        }
        return $query;

    }
}
