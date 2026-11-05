<?php

namespace Sunnysideup\Ecommerce\Cms;

use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;

/**
 * Sales to action, split into one tab per day the order was placed
 * (today, yesterday, 2 days ago, ... up to `days_back` days ago).
 *
 * Class \Sunnysideup\Ecommerce\Cms\SalesAdminByDay
 */
class SalesAdminByDay extends SalesAdmin
{
    /**
     * Re-uses an existing permission code so that anyone who can see the
     * other "Sales to Action" screens can see this one too.
     */
    private static $required_permission_codes = 'CMS_ACCESS_SalesAdminByOrderStep';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $url_segment = 'sales-by-day';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_title = '... by Day';

    private static $menu_priority = 3.115;

    /**
     * How many days back to show (0 = today only, 14 = today + 14 previous days).
     *
     * @var int
     */
    private static $days_back = 14;

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $managed_models = [
        Order::class,
    ];

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $form->Fields();
        if (is_subclass_of($this->modelClass, Order::class) || Order::class === $this->modelClass) {
            $brackets = $this->getBrackets();
            $arrayOfTabs = array_fill_keys(array_keys($brackets), ['IDs' => []]);
            $baseList = $this->getList();
            $placedPerOrder = $this->getPlacedDatePerOrder($baseList);
            $today = strtotime(date('Y-m-d'));
            $maxDays = (int) $this->Config()->get('days_back');
            foreach ($baseList as $order) {
                $placed = $placedPerOrder[$order->ID] ?? $order->LastEdited;
                $daysAgo = (int) round(($today - strtotime(date('Y-m-d', strtotime((string) $placed)))) / 86400);
                if ($daysAgo < 0) {
                    $daysAgo = 0;
                }

                $key = $daysAgo <= $maxDays ? $this->dayKey($daysAgo) : 'older';
                $arrayOfTabs[$key]['IDs'][$order->ID] = $order->ID;
            }

            $this->buildTabs($brackets, $arrayOfTabs, $form);
        }

        return $form;
    }

    /**
     * Keys are strings, because buildTabs() skips falsy keys (and "today" would be 0).
     */
    protected function dayKey(int $daysAgo): string
    {
        return 'day' . $daysAgo;
    }

    protected function getBrackets(): array
    {
        $maxDays = (int) $this->Config()->get('days_back');
        $brackets = [];
        for ($i = 0; $i <= $maxDays; ++$i) {
            $date = date('D j M', strtotime('-' . $i . ' days'));
            if (0 === $i) {
                $title = 'Today';
            } elseif (1 === $i) {
                $title = 'Yesterday';
            } else {
                $title = $i . ' days ago';
            }

            $brackets[$this->dayKey($i)] = $title . ' (' . $date . ')';
        }

        $brackets['older'] = 'Older than ' . $maxDays . ' days';

        return $brackets;
    }

    /**
     * The placement date is the creation date of the order submission log
     * (same logic as Order::OrderDate()), loaded in one query for all orders.
     *
     * @return array<int, string> OrderID => date time string
     */
    protected function getPlacedDatePerOrder($baseList): array
    {
        $result = [];
        if ($baseList->exists()) {
            $className = EcommerceConfig::get(OrderStatusLog::class, 'order_status_log_class_used_for_submitting_order');
            $logs = $className::get()
                ->filter(['OrderID' => $baseList->columnUnique()])
                ->sort(['ID' => 'ASC']);
            foreach ($logs as $log) {
                // last one wins, same as Order::SubmissionLog()
                $result[$log->OrderID] = $log->Created;
            }
        }

        return $result;
    }
}
