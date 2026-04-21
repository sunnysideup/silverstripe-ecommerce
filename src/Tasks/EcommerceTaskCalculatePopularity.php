<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

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
    protected string $title = 'Create popularity for all products';

    protected static string $description = 'Goes through all the products and calculates their popularity.';

    protected static string $commandName = 'ecommerce-calculate-product-popularity';

    /**
     * this is the decay rate for the popularity. The higher the number, the faster the decay.
     * 0.003 is a good number for a product that is popular for 3 months.
     * 0.001 is a good number for a product that is popular for 1 year.
     * 0.0001 is a good number for a product that is popular for 10 years.
     */
    private static float $decay_rate = 0.003;

    /**
     * if true, the popularity will be set to the rank of the product
     * (1 = most popular, 2 = second most popular, etc).
     */
    private static bool $set_to_rank = true;

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->resetPopularity($output);
        $this->calculatePopularity($output);
        $this->setPopularityToRank($output);
        $output->writeln('Popularity for all products calculated.');

        return Command::SUCCESS;
    }

    protected function resetPopularity(PolyOutput $output)
    {
        foreach (['', '_Live'] as $suffix) {
            DB::query('UPDATE "Product' . $suffix . '" SET "Popularity" = 0, PopularityRank = 0');
        }
        $output->writeln('Reset popularity for all products');
    }

    protected function calculatePopularity(PolyOutput $output)
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
                $output->write('.');
            }
        }

        // one last time
        $this->updatePopularityForOneProduct($currentProductID, $totalPointsForProduct);
        $output->writeln('');
        $output->writeln('Popularity calculated for all products');
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

    protected function setPopularityToRank(PolyOutput $output)
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
                $output->write('.');
            }

            foreach (['', '_Live'] as $suffix) {
                DB::query(
                    '
                    UPDATE "Product' . $suffix . '"
                    SET "PopularityRank" = ' . $count . '
                    WHERE ID = ' . $productID
                );
                if ($count % 100 === 0) {
                    $output->write('.');
                }
            }
        }

        $output->writeln('');
        $output->writeln('Popularity rank set for all products.');
    }
}
