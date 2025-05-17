<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * We calculate the popularity of a product based on the number of orders
 * We also include the products that are not for sale, because they may become for sale
 * again and then we want to their popularity to be correct immediately.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskCalculatePopularity extends BuildTask
{
    protected $title = 'Create popularity for all products';

    protected $description = 'Goes through all the products and calculates their popularity.';

    private static $segment = 'calculate-product-popularity';

    /**
     * this is the decay rate for the popularity. The higher the number, the faster the decay.
     * 0.003 is a good number for a product that is popular for 3 months.
     * 0.001 is a good number for a product that is popular for 1 year.
     * 0.0001 is a good number for a product that is popular for 10 years.
     * @var float
     */
    private static float $decay_rate = 0.003;

    /**
     *
     * if true, the popularity will be set to the rank of the product
     * (1 = most popular, 2 = second most popular, etc).
     *
     * @var bool
     */
    private static bool $set_to_rank = true;

    public function run($request)
    {
        $this->resetPopularity();
        $this->calculatePopularity();
        $this->setPopularityToRank();
        DB::alteration_message('Popularity for all products calculated.', 'created');
    }

    protected function resetPopularity()
    {
        foreach (['', '_Live'] as $suffix) {
            DB::query('UPDATE "Product' . $suffix . '" SET "Popularity" = 0, PopularityRank = 0');
        }
    }

    protected function calculatePopularity()
    {
        $lambda = $this->Config()->get('decay_rate') * -1;
        $excludedOrderIds = [];
        $orderSteps = OrderStep::get();
        foreach ($orderSteps as $orderStep) {
            if ($orderStep->ShowAsUncompletedOrder) {
                $ids = $orderStep->Orders()->columnUnique('ID');
                $excludedOrderIds = array_merge($excludedOrderIds, $ids);
            }
        }
        $rows = DB::query('
            SELECT BuyableID, DATEDIFF(NOW(), "Created") AS daysAgo
            FROM "OrderItem"
            INNER JOIN OrderAttribute ON OrderItem.ID = OrderAttribute.ID
            WHERE
                OrderID NOT IN (' . implode(',', $excludedOrderIds) . ')
            ORDER BY BuyableID, OrderItem.ID
        ');
        $currentProductID = 0;
        $totalPointsForProduct = 0;
        foreach ($rows as $count => $row) {
            $productID = (int) $row['BuyableID'];
            // update previous product
            if ($count > 0 && $productID !== $currentProductID) {
                $this->updatePopularityForOneProduct($currentProductID, $totalPointsForProduct);
                $totalPointsForProduct = 0;
            }
            $totalPointsForProduct += exp($lambda * $row['daysAgo']);
            // do last
            $currentProductID = $productID;
            if ($count % 100 === 0) {
                echo $count . ' ';
            }
        }
        // one last time
        $this->updatePopularityForOneProduct($currentProductID, $totalPointsForProduct);
        echo "\n";
        DB::alteration_message('Popularity calculated for all products', 'created');
    }

    protected function updatePopularityForOneProduct($productID, $totalPointsForProduct)
    {
        foreach (['', '_Live'] as $suffix) {
            DB::query(
                '
                UPDATE "Product' . $suffix . '"
                SET "Popularity" = ' . $totalPointsForProduct . '
                WHERE ID = ' . $productID
            );
        }
    }

    protected function setPopularityToRank()
    {
        $rows = DB::query(
            '
            SELECT "Product"."ID", "Product"."Popularity"
            FROM "Product"
            ORDER BY "Popularity" DESC
        '
        );
        $count = 0;
        foreach ($rows as $row) {
            $productID = (int) $row['ID'];
            $popularity = (float) $row['Popularity'];
            if ($popularity > 0) {
                $count++;
            } else {
                echo ". ";
            }
            foreach (['', '_Live'] as $suffix) {
                DB::query(
                    '
                    UPDATE "Product' . $suffix . '"
                    SET "PopularityRank" = ' . $count . '
                    WHERE ID = ' . $productID
                );
                if ($count % 100 === 0) {
                    echo '. ';
                }
            }
        }
        echo "\n";
        DB::alteration_message('Popularity rank set for all products.', 'created');
    }
}
