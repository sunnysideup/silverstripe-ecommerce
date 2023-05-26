<?php

namespace Sunnysideup\Ecommerce\Search\Filters;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Filters\ExactMatchFilter;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogSubmitted;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Filter that searches the Two Addresses (billing + shipping)
 * and the member. It searches all the relevant fields.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
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
        $value = Convert::raw2sql($this->getValue());
        $product = Product::get()->filter(['InternalItemID' => $value])->first();
        if ($product) {
            $query->where("BuyableClassName = '" . addslashes($product->ClassName) . '\' AND "BuyableID" = ' . $product->ID);
        } else {
            $done = false;
            $rows = DB::query(
                '
                SELECT SiteTree_Versions.RecordID, SiteTree_Versions.ClassName
                FROM Product_Versions
                    INNER JOIN SiteTree_Versions
                        ON SiteTree_Versions.RecordID = Product_Versions.RecordID AND SiteTree_Versions.Version = Product_Versions.Version
                WHERE InternalItemID = \'' . $value . '\' LIMIT 1;'
            );
            foreach ($rows as $row) {
                $query->where("BuyableClassName = '" . addslashes($row['ClassName']) . '\' AND "BuyableID" = ' . $row['RecordID']);
                $done = true;
            }
            if (! $done) {
                $logs = OrderStatusLogSubmitted::get()
                    ->filterAny(['OrderAsHTML:PartialMatch' => $value, 'OrderAsString:PartialMatch' => $value])
                ;
                $orderIds = [0 => 0];
                if ($logs->exists()) {
                    $orderIds = $logs->column('OrderID');
                }
                $query->where('OrderID IN (' . implode(',', $orderIds) . ')');
            }
        }

        return $query;
    }
}
