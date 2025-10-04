<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Control;

use DateTimeImmutable;
use DateTimeInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Model\Process\Referral;

/**
 * Class \Sunnysideup\Ecommerce\Control\QuickUpdates
 *
 */
class ReferralSummary extends Controller
{
    private static $url_segment = 'admin/ecommerce/referral-summary';

    private static $allowed_actions = [
        'prepdata' => 'ADMIN',
        'showdata' => 'ADMIN',
    ];



    private static $max_days_of_interest = 720;
    private static $recalculate_days_for_prep_data = 180;

    public function index($request) {}

    protected function init()
    {
        parent::init();
        Environment::increaseTimeLimitTo(600);
        $allowedActions = $this->Config()->get('allowed_actions');
        $securityCheck = $allowedActions['index'] ?? 'ADMIN';
        if (!Permission::check($securityCheck)) {
            return Security::permissionFailure($this);
        }
        $this->printMenu();
    }

    public function prepdata($request)
    {
        $daysAgo = $this->config()->get('recalculate_days_for_prep_data');
        $refs = Referral::get()->filterAny(
            [
                "Created:GreaterThan" => date('Y-m-d', strtotime($daysAgo . ' days ago')) . ' 23:59:59',
                'Processed' => 0,
            ]
        );
        foreach ($refs as $ref) {
            $ref->AttachData();
        }
        return $this->redirect($this->Link('showdata'));
    }

    public function showdata($request)
    {
        echo $this->renderReportForm($this->request->getVars());
        echo $this->renderResults($this->request->getVars());
    }



    protected function renderResults(array $getVars): void
    {
        // --- read & validate inputs -------------------------------------------
        $isYmd = static fn(string $s): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);

        $today = new DateTimeImmutable('today');
        $defaultDateFrom  = $today->modify('-3 months')->format('Y-m-d');
        $defaultDateUntil = $today->modify('-1 week')->format('Y-m-d');

        $dateFrom  = isset($getVars['DateFrom'])  && is_string($getVars['DateFrom'])  && $isYmd($getVars['DateFrom'])  ? $getVars['DateFrom']  : $defaultDateFrom;
        $dateUntil = isset($getVars['DateUntil']) && is_string($getVars['DateUntil']) && $isYmd($getVars['DateUntil']) ? $getVars['DateUntil'] : $defaultDateUntil;

        $orderType = $getVars['OrderType'] ?? 'Completed';
        if (!in_array($orderType, ['Uncompleted', 'Completed', 'All'], true)) {
            $orderType = 'Completed';
        }

        $includeFrom     = (($getVars['ShowFrom'] ?? 'No') === 'Yes');
        $includeSource    = (($getVars['ShowSource'] ?? 'No') === 'Yes');
        $includeMedium    = (($getVars['ShowMedium'] ?? 'No') === 'Yes');
        $includeCampaign   = (($getVars['ShowCampaign'] ?? 'No') === 'Yes');
        if ($includeCampaign) {
            $includeFrom = true; // preserve your original rule
        }

        $breakdownBy = $getVars['BreakdownBy'] ?? 'Week';
        if (!in_array($breakdownBy, ['Day', 'Week', 'Quarter', 'Year', 'AllTime'], true)) {
            $breakdownBy = 'Week';
        }

        // --- date key formatter ------------------------------------------------
        $formatKey = static function (DateTimeInterface $dt, string $mode): array {
            return match ($mode) {
                'Day'      => [$dt->format('Y-m-d'),         $dt->format('Y-m-d')],
                'Week'     => [$dt->format('o-\WW'),         $dt->format('o-\WW')],
                'Quarter'  => [
                    $dt->format('Y') . '-Q' . (int) ceil(((int) $dt->format('n')) / 3),
                    $dt->format('Y') . '-Q' . (int) ceil(((int) $dt->format('n')) / 3),
                ],
                'Year'     => [$dt->format('Y'),             $dt->format('Y')],
                'AllTime'  => ['AllTime',                    'AllTime'],
            };
        };

        // --- base query --------------------------------------------------------
        $filters = [
            'Created:GreaterThanOrEqual' => $dateFrom . ' 00:00:00',
            'Created:LessThanOrEqual'    => $dateUntil . ' 23:59:59',
        ];
        if ($orderType === 'Completed') {
            $filters['IsSubmitted'] = 1;
        } elseif ($orderType === 'Uncompleted') {
            $filters['IsSubmitted'] = 0;
        }

        /** @var \SilverStripe\ORM\DataList $refs */
        $refs = Referral::get()
            ->filter($filters)
            ->sort(['ID' => 'DESC'])
            ->limit(999999);

        // --- aggregation -------------------------------------------------------
        $list = [];

        foreach ($refs as $ref) {
            /** @var Referral $ref */
            $createdTs = strtotime((string) $ref->Created);
            $created   = (new DateTimeImmutable())->setTimestamp($createdTs);

            [$dateKey, $dateLabel] = $breakdownBy === 'AllTime'
                ? ['AllTime', $dateFrom . ' .. ' . $dateUntil]
                : $formatKey($created, $breakdownBy);

            $from     = $ref->From ?: 'none';
            $source   = $ref->Source ?: 'none';
            $medium   = $ref->Medium ?: 'none';
            $campaign = $ref->Campaign ?: 'none';

            $key = $dateKey;
            if ($includeFrom) {
                $key .= '|' . $from;
            }
            if ($includeSource) {
                $key .= '|' . $source;
            }
            if ($includeMedium) {
                $key .= '|' . $medium;
            }
            if ($includeCampaign) {
                $key .= '|' . $campaign;
            }

            if (!isset($list[$key])) {
                $row = ['Date' => $dateLabel];
                if ($includeFrom) {
                    $row['Company'] = $from;
                }
                if ($includeSource) {
                    $row['Source'] = $source;
                }
                if ($includeMedium) {
                    $row['Medium'] = $medium;
                }
                if ($includeCampaign) {
                    $row['Campaign'] = $campaign;
                }
                $row += [
                    'NumberOfClicks' => 0,
                    'NumberOfClicksIntoOrders' => 0,
                    'TotalOrderAmountInvoiced' => 0.0,
                    'TotalOrderAmountPaid' => 0.0,
                    'AverageClicksIntoOrders' => 0.0,
                    'AverageOrderAmountPaidPerClick' => 0.0,
                ];
                $list[$key] = $row;
            }

            $list[$key]['NumberOfClicks']++;

            $hasSubmittedOrder = (bool) $ref->IsSubmitted;
            if ($hasSubmittedOrder) {
                $list[$key]['NumberOfClicksIntoOrders']++;
                $list[$key]['TotalOrderAmountInvoiced'] += (float) $ref->AmountInvoiced;
                $list[$key]['TotalOrderAmountPaid']     += (float) $ref->AmountPaid;
            }

            // running averages
            $clicks    = (int) $list[$key]['NumberOfClicks'];
            $intoOrder = (int) $list[$key]['NumberOfClicksIntoOrders'];

            $list[$key]['AverageClicksIntoOrders']        = $clicks > 0    ? round($intoOrder / $clicks, 2) : 0.0;
            $list[$key]['AverageOrderAmountPaidPerClick'] = $intoOrder > 0 ? round($list[$key]['TotalOrderAmountInvoiced'] / $intoOrder, 2) : 0.0;
        }

        ksort($list, SORT_NATURAL);

        echo $this->arrayToTable($list);
    }

    protected function printMenu()
    {
        $function = $this->request->param('Action');
        $title = '';
        if ($function !== 'prepdata' || $function === 'showdata') {
            $title = $menuList[$function] ?? '- Please <a href="/' . $this->Link('prepdata') . '">prep data</a> and then select a report';
        }
        echo '<h1>Referral Summary' . $title . '</h1>';
    }

    protected function renderReportForm(array $getVars): string
    {
        $isYmd = static fn(string $s): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);

        $today = new DateTimeImmutable('today');
        $defaultDateFrom  = $today->modify('-3 months')->format('Y-m-d');
        $defaultDateUntil = $today->modify('-1 week')->format('Y-m-d');

        $dateFrom  = isset($getVars['DateFrom'])  && is_string($getVars['DateFrom'])  && $isYmd($getVars['DateFrom'])  ? $getVars['DateFrom']  : $defaultDateFrom;
        $dateUntil = isset($getVars['DateUntil']) && is_string($getVars['DateUntil']) && $isYmd($getVars['DateUntil']) ? $getVars['DateUntil'] : $defaultDateUntil;

        $orderType  = ($getVars['OrderType'] ?? 'Completed');
        if (!in_array($orderType, ['Uncompleted', 'Completed', 'All'], true)) {
            $orderType = 'Completed';
        }

        $showFrom = ($getVars['ShowFrom'] ?? 'No');
        if (!in_array($showFrom, ['Yes', 'No'], true)) {
            $showFrom = 'No';
        }

        $showSource = ($getVars['ShowSource'] ?? 'No');
        if (!in_array($showSource, ['Yes', 'No'], true)) {
            $showSource = 'No';
        }

        $showMedium = ($getVars['ShowMedium'] ?? 'No');
        if (!in_array($showMedium, ['Yes', 'No'], true)) {
            $showMedium = 'No';
        }

        $showCampaign = ($getVars['ShowCampaign'] ?? 'No'); // keeping your field name
        if (!in_array($showCampaign, ['Yes', 'No'], true)) {
            $showCampaign = 'No';
        }

        $breakdownBy = ($getVars['BreakdownBy'] ?? 'Week');
        if (!in_array($breakdownBy, ['Day', 'Week', 'Quarter', 'Year', 'AllTime'], true)) {
            $breakdownBy = 'Week';
        }

        $sel = static fn(string $cur, string $val): string => $cur === $val ? ' selected' : '';

        $hDateFrom  = htmlspecialchars($dateFrom, ENT_QUOTES);
        $hDateUntil = htmlspecialchars($dateUntil, ENT_QUOTES);

        return
            '<form id=\'reportForm\' method=\'get\'>' .

            '<label for=\'DateFrom\'>Date From</label>' .
            '<input type=\'date\' id=\'DateFrom\' name=\'DateFrom\' value=\'' . $hDateFrom . '\' required>' .
            '<hr />' .
            '<label for=\'DateUntil\'>Date Until</label>' .
            '<input type=\'date\' id=\'DateUntil\' name=\'DateUntil\' value=\'' . $hDateUntil . '\' required>' .
            '<hr />' .
            '<label for=\'OrderType\'>Order Type</label>' .
            '<select id=\'OrderType\' name=\'OrderType\'>' .
            '<option value=\'Uncompleted\'' . $sel($orderType, 'Uncompleted') . '>Uncompleted</option>' .
            '<option value=\'Completed\''   . $sel($orderType, 'Completed')   . '>Completed</option>' .
            '<option value=\'All\''         . $sel($orderType, 'All')         . '>All</option>' .
            '</select>' .
            '<hr />' .
            '<label for=\'ShowFrom\'>Breakdown By Company</label>' .
            '<select id=\'ShowFrom\' name=\'ShowFrom\'>' .
            '<option value=\'Yes\'' . $sel($showFrom, 'Yes') . '>Yes</option>' .
            '<option value=\'No\''  . $sel($showFrom, 'No')  . '>No</option>' .
            '</select>' .
            '<hr />' .
            '<label for=\'ShowSource\'>Breakdown By Source</label>' .
            '<select id=\'ShowSource\' name=\'ShowSource\'>' .
            '<option value=\'Yes\'' . $sel($showSource, 'Yes') . '>Yes</option>' .
            '<option value=\'No\''  . $sel($showSource, 'No')  . '>No</option>' .
            '</select>' .
            '<hr />' .
            '<label for=\'ShowMedium\'>Breakdown By Medium</label>' .
            '<select id=\'ShowMedium\' name=\'ShowMedium\'>' .
            '<option value=\'Yes\'' . $sel($showMedium, 'Yes') . '>Yes</option>' .
            '<option value=\'No\''  . $sel($showMedium, 'No')  . '>No</option>' .
            '</select>' .
            '<hr />' .
            '<label for=\'ShowCampaign\'>Breakdown By Campaign</label>' .
            '<select id=\'ShowCampaign\' name=\'ShowCampaign\'>' .
            '<option value=\'Yes\'' . $sel($showCampaign, 'Yes') . '>Yes</option>' .
            '<option value=\'No\''  . $sel($showCampaign, 'No')  . '>No</option>' .
            '</select>' .
            '<hr />' .
            '<label for=\'BreakdownBy\'>Reporting Period</label>' .
            '<select id=\'BreakdownBy\' name=\'BreakdownBy\'>' .
            '<option value=\'Day\''     . $sel($breakdownBy, 'Day')     . '>Day</option>' .
            '<option value=\'Week\''    . $sel($breakdownBy, 'Week')    . '>Week</option>' .
            '<option value=\'Quarter\'' . $sel($breakdownBy, 'Quarter') . '>Quarter</option>' .
            '<option value=\'Year\''    . $sel($breakdownBy, 'Year')    . '>Year</option>' .
            '<option value=\'AllTime\'' . $sel($breakdownBy, 'AllTime') . '>AllTime</option>' .
            '</select>' .
            '<hr />' .
            '<button type=\'submit\'>Run</button>' .
            '</form>';
    }

    protected function arrayToTable(array $array): string
    {
        if (count($array)) {
            $html = '
            <style>
                table {
                    border-collapse: collapse;
                    margin: 4rem auto;
                    width: 80%;

                }
                th, td {
                    padding: 5px;
                    text-align: right;
                    width: 16.666;
                }
                th {
                    background-color: #eee;
                    text-align: center;
                }
                td {
                    font-size: 10px;
                }
                td.string {
                    text-align: left;
                }
                h3 {
                    text-align: center;
                }
            </style>
            <table border="1">';

            // Header row
            $html .= '<thead><tr>';
            foreach ($array[array_key_first($array)] as $key => $value) {
                $html .= '<th colspan="1">' . $this->camelCaseToWords($key) . '</th>';
            }
            $html .= '</tr><thead><tbody>';

            // Data rows
            foreach ($array as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $isNumber = is_numeric($cell);
                    $html .= '<td class="' . ($isNumber ? 'number' : 'string') . '">' . str_replace('|', ' | ', (string) $cell) . '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        } else {
            $html = '<p class="message warning">no data</p>';
        }
        return $html;
    }

    public function camelCaseToWords(string $string): string
    {
        $string = preg_replace('/(?<!^)[A-Z]/', ' $0', $string);
        return $string;
    }
}
