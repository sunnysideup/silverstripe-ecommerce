<?php

namespace Sunnysideup\Ecommerce\Cms;

use Sunnysideup\Ecommerce\Api\OrderArchiver;
use Sunnysideup\Ecommerce\Model\Order;

/**
 * Sales to action, split into tabs by the day the order was placed:
 * one tab per day (today, yesterday, 2 days ago, ... up to `days_back` days ago),
 * then up to 2 weeks ago, 2 to 4 weeks ago (neither includes days already shown)
 * and finally everything older than that.
 *
 * The "older" tab has a button to archive all of its orders (without processing them).
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
     * How many days back to show one tab per day for (0 = today only, 5 = today + 5 previous days).
     *
     * @var int
     */
    private static $days_back = 5;

    /**
     * Days ago (inclusive) for the "up to 2 weeks ago" tab.
     *
     * @var int
     */
    private static $two_weeks_days = 14;

    /**
     * Days ago (inclusive) for the "2 to 4 weeks ago" tab.
     * Anything older than this goes into the "older" tab, which can be archived.
     *
     * @var int
     */
    private static $four_weeks_days = 28;

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
            $placedPerOrder = OrderArchiver::get_placed_dates($baseList->columnUnique());
            $today = strtotime(date('Y-m-d'));
            foreach ($baseList as $order) {
                $placed = $placedPerOrder[$order->ID] ?? $order->LastEdited;
                $daysAgo = (int) round(($today - strtotime(date('Y-m-d', strtotime((string) $placed)))) / 86400);
                if ($daysAgo < 0) {
                    $daysAgo = 0;
                }

                $key = $this->getBucketKey($daysAgo);
                $arrayOfTabs[$key]['IDs'][$order->ID] = $order->ID;
            }

            $this->buildTabs($brackets, $arrayOfTabs, $form);
            // Orders placed before this day are in the 'older' tab.
            $this->addArchiveOldOrdersButton(
                $form,
                'older',
                date('Y-m-d', strtotime('-' . $this->getFourWeeksDays() . ' days'))
            );
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

    protected function getMaxDays(): int
    {
        return max(0, (int) $this->Config()->get('days_back'));
    }

    protected function getTwoWeeksDays(): int
    {
        return max($this->getMaxDays() + 1, (int) $this->Config()->get('two_weeks_days'));
    }

    protected function getFourWeeksDays(): int
    {
        return max($this->getTwoWeeksDays() + 1, (int) $this->Config()->get('four_weeks_days'));
    }

    protected function getBucketKey(int $daysAgo): string
    {
        if ($daysAgo <= $this->getMaxDays()) {
            return $this->dayKey($daysAgo);
        }

        if ($daysAgo <= $this->getTwoWeeksDays()) {
            return 'twoweeks';
        }

        if ($daysAgo <= $this->getFourWeeksDays()) {
            return 'fourweeks';
        }

        return 'older';
    }

    protected function getBrackets(): array
    {
        $brackets = [];
        for ($i = 0; $i <= $this->getMaxDays(); ++$i) {
            $date = date('D j M', strtotime('-' . $i . ' days'));
            if (0 === $i) {
                $title = 'Today';
            } elseif (1 === $i) {
                $title = 'Yesterday';
            } else {
                $title = $i . ' days ago';
            }

            $brackets[$this->dayKey($i)] = $title . ': ' . $date;
        }

        $brackets['twoweeks'] = 'Up to 2 weeks ago';
        $brackets['fourweeks'] = '2 to 4 weeks ago';
        $brackets['older'] = 'Older than 4 weeks';

        return $brackets;
    }
}
