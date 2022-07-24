<?php

namespace Sunnysideup\Ecommerce\Search\Filters;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\ExactMatchFilter;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Pages\Product;

use Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogSubmitted;

/**
 * Filter that searches the Two Addresses (billing + shipping)
 * and the member. It searches all the relevant fields.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: search
 */
class OrderItemProductFilter extends ExactMatchFilter
{
    /**
     * @return DataQuery
     */
    public function apply(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $value = $this->getValue();
        $product = Product::get()->filter(['InternalItemID' => Convert::raw2sql($value)])->first();
        if ($product) {
            $query->where("BuyableClassName = '" . addslashes($product->ClassName) . '\' AND "BuyableID" = ' . $product->ID);
        } else {
            $logs = OrderStatusLogSubmitted::get()
                ->filterAny(['OrderAsHTML:PartialMatch' => $value, 'OrderAsString:PartialMatch' => $value]);
            $orderIds = $logs->column('OrderID');
            $query->where('OrderID IN ('.implode(',', $orderIds).') OR TableTitle LIKE "%'.$value.'%"');
        }

        return $query;
    }
}
