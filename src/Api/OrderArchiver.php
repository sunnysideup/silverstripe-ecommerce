<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLUpdate;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Sunnysideup\Ecommerce\Model\Process\OrderSteps\OrderStepArchived;

/**
 * Moves orders straight to the Archived step WITHOUT writing the orders.
 *
 * Writing an order (or running its steps) can trigger emails, stock updates,
 * logs, etc... Here we only change the StatusID with a direct SQL update,
 * nothing else about the order (not even LastEdited) is touched.
 *
 * Class \Sunnysideup\Ecommerce\Api\OrderArchiver
 */
class OrderArchiver
{
    private const CHUNK_SIZE = 500;

    /**
     * THE definition of an "old" order: orders placed before this
     * (strtotime format, relative to now) can be archived from the sales admin screens.
     *
     * Change it in one place, e.g. in a yml file:
     *   Sunnysideup\Ecommerce\Api\OrderArchiver:
     *     archive_orders_older_than: '-30 days'
     *
     * @var string
     */
    private static $archive_orders_older_than = '-3 months';

    /**
     * @return string Y-m-d - orders placed before this day count as "old"
     */
    public static function get_cutoff_date(): string
    {
        $when = (string) Config::inst()->get(self::class, 'archive_orders_older_than');

        return date('Y-m-d', strtotime($when) ?: strtotime('-3 months'));
    }

    /**
     * The placement date is the creation date of the order submission log
     * (same logic as Order::OrderDate()), loaded in a few queries for all orders.
     * Orders without a submission log fall back to LastEdited.
     *
     * @return array<int, string> OrderID => date time string
     */
    public static function get_placed_dates(array $orderIDs): array
    {
        $orderIDs = self::clean_ids($orderIDs);
        $result = [];
        if ([] === $orderIDs) {
            return $result;
        }

        foreach (array_chunk($orderIDs, self::CHUNK_SIZE) as $chunk) {
            foreach (Order::get()->filter(['ID' => $chunk])->map('ID', 'LastEdited')->toArray() as $id => $lastEdited) {
                $result[(int) $id] = (string) $lastEdited;
            }

            $className = EcommerceConfig::get(OrderStatusLog::class, 'order_status_log_class_used_for_submitting_order');
            $logs = $className::get()
                ->filter(['OrderID' => $chunk])
                ->sort(['ID' => 'ASC']);
            foreach ($logs as $log) {
                // last one wins, same as Order::SubmissionLog()
                $result[(int) $log->OrderID] = (string) $log->Created;
            }
        }

        return $result;
    }

    /**
     * @param string $cutoffDate Y-m-d, orders placed BEFORE this day are returned
     *
     * @return int[]
     */
    public static function filter_ids_placed_before(array $orderIDs, string $cutoffDate): array
    {
        $result = [];
        foreach (self::get_placed_dates($orderIDs) as $id => $placed) {
            if (date('Y-m-d', strtotime($placed)) < $cutoffDate) {
                $result[] = $id;
            }
        }

        return $result;
    }

    /**
     * Archives the orders that were placed before the cutoff date.
     *
     * @param string $cutoffDate Y-m-d
     *
     * @return int[] IDs of the orders that were archived
     */
    public static function archive_placed_before(array $orderIDs, string $cutoffDate): array
    {
        return self::archive_without_writing(self::filter_ids_placed_before($orderIDs, $cutoffDate));
    }

    /**
     * Only orders that are in an admin manageable step are moved
     * (so not cancelled, not already archived, not incomplete carts).
     *
     * @return int[] IDs of the orders that were archived
     */
    public static function archive_without_writing(array $orderIDs): array
    {
        $orderIDs = self::clean_ids($orderIDs);
        $archivedStep = OrderStepArchived::get()->first();
        if ([] === $orderIDs || ! $archivedStep || ! $archivedStep->exists()) {
            return [];
        }

        $archivedStepID = (int) $archivedStep->ID;
        $manageableStepIDs = ArrayMethods::filter_array(OrderStep::admin_manageable_steps()->columnUnique());
        $archived = [];
        foreach (array_chunk($orderIDs, self::CHUNK_SIZE) as $chunk) {
            $ids = Order::get()
                ->filter(['ID' => $chunk, 'StatusID' => $manageableStepIDs, 'CancelledByID' => 0])
                ->exclude(['StatusID' => $archivedStepID])
                ->columnUnique();
            $ids = self::clean_ids($ids);
            if ([] === $ids) {
                continue;
            }

            SQLUpdate::create('"Order"')
                ->assign('"StatusID"', $archivedStepID)
                ->addWhere(['"ID" IN (' . DB::placeholders($ids) . ')' => $ids])
                ->execute();
            $archived = array_merge($archived, $ids);
        }

        return $archived;
    }

    /**
     * @return int[]
     */
    protected static function clean_ids(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }
}
