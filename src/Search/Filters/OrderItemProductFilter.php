<?php

namespace Sunnysideup\Ecommerce\Search\Filters;

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
        // no need for Convert::raw2sql: the ORM and prepared queries take care of escaping.
        $value = is_scalar($this->getValue()) ? (string) $this->getValue() : '';
        $product = Product::get()->filter(['InternalItemID' => $value])->first();
        if ($product) {
            $query->where(['BuyableClassName = ? AND "BuyableID" = ?' => [$product->ClassName, (int) $product->ID]]);
        } else {
            $done = false;
            $rows = DB::prepared_query(
                '
                SELECT SiteTree_Versions.RecordID, SiteTree_Versions.ClassName
                FROM Product_Versions
                    INNER JOIN SiteTree_Versions
                        ON SiteTree_Versions.RecordID = Product_Versions.RecordID AND SiteTree_Versions.Version = Product_Versions.Version
                WHERE InternalItemID = ? LIMIT 1;',
                [$value]
            );
            foreach ($rows as $row) {
                $query->where(['BuyableClassName = ? AND "BuyableID" = ?' => [$row['ClassName'], (int) $row['RecordID']]]);
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
                $query->where('OrderID IN (' . implode(',', array_map('intval', $orderIds)) . ')');
            }
        }

        return $query;
    }
}
