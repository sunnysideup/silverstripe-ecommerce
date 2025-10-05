<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Admin;

use DateTimeImmutable;
use DateTimeInterface;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Model\Process\Referral;

class ReferralSummaryAdmin extends LeftAndMain
{

    public static function do_data_prep(?int $limit = 3000): ?bool
    {
        $daysAgoDelete = (int) Config::inst()->get(ReferralSummaryAdmin::class, 'max_days_of_interest');

        $filter = [
            'Created:LessThan' => date('Y-m-d', strtotime('-' . $daysAgoDelete . ' days')) . ' 23:59:59',
            'Processed' => 0,
        ];
        $refs = Referral::get()->filterAny($filter)
            ->limit($limit);
        foreach ($refs as $ref) {
            $ref->delete();
        }

        $daysAgoStale = (int) Config::inst()->get(ReferralSummaryAdmin::class, 'recalculate_days_for_prep_data');
        $filter = [
            'Created:GreaterThan' => date('Y-m-d', strtotime('-' . $daysAgoStale . ' days')) . ' 23:59:59',
            'Processed' => 0,
        ];
        $refs = Referral::get()->filterAny($filter)
            ->sort('ID', 'DESC')
            ->limit($limit);
        foreach ($refs as $ref) {
            $ref->AttachData($daysAgoStale);
        }
        // old items more than six months old should be processed.
        $filter2 = [
            'Created:LessThan' => date('Y-m-d', strtotime('-' . $daysAgoStale . ' days')) . ' 23:59:59',
            'Processed' => 0,
        ];
        $refsCount = Referral::get()->filter($filter2)->count();
        if ($refsCount < $limit) {
            return true;
        }
        return false;
    }


    private static string $url_segment = 'referral-summary';
    private static string $menu_title = 'Referral Summary';
    private static int $menu_priority = -9999; // adjust if needed
    private static string $menu_icon_class = 'font-icon-chart-line';
    private static array $required_permission_codes = ['ADMIN'];

    private static $reporting_periods = [
        'Day' => 'Day',
        'Week' => 'Week',
        'Month' => 'Month',
        'Quarter' => 'Quarter',
        'Year' => 'Year',
        'AllTime' => 'AllTime',
    ];

    private static $stats_to_report_on = [
        'NumberOfClicks' => 'Number Of Clicks',
        'NumberOfClicksIntoOrders' => 'Clicks → Orders',
        'TotalOrderAmountInvoiced' => 'Total Invoiced',
        'TotalOrderAmountPaid' => 'Total Paid',
        'AverageClicksIntoOrders' => 'Avg Clicks → Orders',
        'AverageOrderAmountPaidPerClick' => 'Avg Paid / Order',
    ];

    private static $formatting_rules = [
        'NumberOfClicks' => 'Number',
        'NumberOfClicksIntoOrders' => 'Number',
        'TotalOrderAmountInvoiced' => 'Currency',
        'TotalOrderAmountPaid' => 'Currency',
        'AverageClicksIntoOrders' => 'Number',
        'AverageOrderAmountPaidPerClick' => 'Currency',
    ];

    private static $default_form_values = [
        'DateFrom' => '',
        'DateUntil' => '',
        'Keyword' => '',
        'OrderType' => 'Completed',
        'BreakdownBy' => 'Week',
        'ShowFrom' => 'No',
        'ShowSource' => 'No',
        'ShowMedium' => 'No',
        'ShowCampaign' => 'No',
        'ShowTerm' => 'No',
        'ShowContent' => 'No',
        'Statistic' => 'TotalOrderAmountPaid',
    ];

    protected array $myFormData = [];

    /** route actions */
    private static array $allowed_actions = [
        'EditForm' => 'ADMIN',
        'doRunReport' => 'ADMIN',
        'doPrepData' => 'ADMIN',
    ];

    /** config */
    private static int $max_days_of_interest = 1080;
    private static int $recalculate_days_for_prep_data = 180;

    public function getEditForm($id = null, $fields = null): Form
    {

        if ($this->getRequest()->getSession()->get('ReferralSummaryAdminDataPrepped')) {
            $today = new DateTimeImmutable('today');
            $defaultFrom  = $this->myFormData['DateFrom'] ?? $today->modify('-3 months')->format('Y-m-d');
            $defaultUntil = $this->myFormData['DateUntil'] ?? $today->modify('-1 week')->format('Y-m-d');

            $fields = FieldList::create(
                HeaderField::create('Heading', 'Sales Referrals', 3),
                LiteralField::create(
                    'Instructions',
                    '<p class="message warning">Use this report to see how well your marketing campaigns are doing.
                    These are raw numbers only so take them with a grain of salt.
                    They require interpretation and common sense.
                    </p>'
                ),
                HeaderField::create('DataSelectionHeader', 'Select Data', 3),
                CompositeField::create(
                    DateField::create('DateFrom', 'Date From')->setValue($defaultFrom),
                    DateField::create('DateUntil', 'Date Until')->setValue($defaultUntil),
                ),
                TextField::create(
                    'Keyword',
                    'Keyword (in From, Source, Medium, Campaign - optional)',
                    $this->myFormData['Keyword'] ?? ''
                ),
                DropdownField::create(
                    'OrderType',
                    'Order Type',
                    [
                        'Uncompleted' => 'Uncompleted',
                        'Completed' => 'Completed',
                        'All' => 'All',
                    ]
                )->setValue($this->myFormData['OrderType'] ?? $this->getDefaultFormValue('OrderType')),
                DropdownField::create(
                    'BreakdownBy',
                    'Reporting Period',
                    $this->config()->get('reporting_periods')
                )->setValue($this->myFormData['BreakdownBy'] ?? $this->getDefaultFormValue('BreakdownBy')),
                DropdownField::create(
                    'ShowFrom',
                    'Breakdown By Company',
                    ['No' => 'No', 'Yes' => 'Yes']
                )->setValue($this->myFormData['ShowFrom'] ?? $this->getDefaultFormValue('ShowFrom')),
                DropdownField::create(
                    'ShowSource',
                    'Breakdown By Source',
                    ['No' => 'No', 'Yes' => 'Yes']
                )->setValue($this->myFormData['ShowSource'] ?? $this->getDefaultFormValue('ShowSource')),
                DropdownField::create(
                    'ShowMedium',
                    'Breakdown By Medium',
                    ['No' => 'No', 'Yes' => 'Yes']
                )->setValue($this->myFormData['ShowMedium'] ?? $this->getDefaultFormValue('ShowMedium')),
                DropdownField::create(
                    'ShowCampaign',
                    'Breakdown By Campaign',
                    ['No' => 'No', 'Yes' => 'Yes']
                )->setValue($this->myFormData['ShowCampaign'] ?? $this->getDefaultFormValue('ShowCampaign')),
                DropdownField::create(
                    'ShowTerm',
                    'Breakdown By Term',
                    ['No' => 'No', 'Yes' => 'Yes']
                )->setValue($this->myFormData['ShowTerm'] ?? $this->getDefaultFormValue('ShowTerm')),
                DropdownField::create(
                    'ShowContent',
                    'Breakdown By Content',
                    ['No' => 'No', 'Yes' => 'Yes']
                )->setValue($this->myFormData['ShowContent'] ?? $this->getDefaultFormValue('ShowContent')),
                DropdownField::create(
                    'Statistic',
                    'Statistic (single focus)',
                    $this->config()->get('stats_to_report_on')

                )->setValue($this->myFormData['Statistic'] ?? $this->getDefaultFormValue('Statistic'))

            );
            $actions = FieldList::create(
                FormAction::create('doRunReport', 'Create report')
                    ->setUseButtonTag(true)
                    ->addExtraClass('btn-outline-primary'),
            );
        } else {
            $actions = FieldList::create(
                FormAction::create('doPrepData', 'Prepare Data (you may need to click this more than once)')
                    ->setUseButtonTag(true)
                    ->addExtraClass('btn-outline-warning')
            );
            $fields = FieldList::create(
                CompositeField::create(
                    HeaderField::create('Heading', 'Referral Summary - Data Preparation', 3),
                    LiteralField::create(
                        'Instructions',
                        '<p class="message warning">To speed up the reporting process, we need to prepare the data first.
                        This is a one-off process that may take a while depending on how much data you have.
                        Please click the button below to start the process.
                        If you have a lot of data, you may need to click it more than once.
                        </p>'
                    )
                )
            );
        }


        $form = Form::create($this, 'EditForm', $fields, $actions)
            ->addExtraClass('panel panel--padded panel--scrollable cms-content-view');
        // $form->setTemplate('LeftAndMain_EditForm');
        $form->setFormMethod('get');

        // if we have posted, render results below the form
        if ($this->getRequest()->httpMethod() === 'GET' && $this->getRequest()->getVar('action_doRunReport')) {
            $resultsHtml = $this->buildResultsHtml($form->getData());
            $form->Fields()->insertBefore(
                'DataSelectionHeader',
                LiteralField::create('Results', $resultsHtml)
            );
        }

        return $form;
    }

    public function doPrepData(array $data, Form $form): \SilverStripe\Control\HTTPResponse
    {
        if (self::do_data_prep()) {
            $this->getRequest()->getSession()->set('ReferralSummaryAdminDataPrepped', true);
            $message = 'Data preparation completed successfully.';
            $type = 'good';
        } else {
            $message = 'Data preparation in progress. Please click the button again if needed.';
            $type = 'warning';
        }
        $form->sessionMessage($message, $type);
        return $this->redirectBack();
    }

    public function doRunReport(array $data, Form $form)
    {
        // simply re-render form with results block
        $this->myFormData = $data;
        $form->loadDataFrom($data);
        return [];
    }

    /** ---------- helpers ---------- */

    protected function buildResultsHtml(array $vars): string
    {
        $isYmd = static fn(string $s): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);

        $today = new DateTimeImmutable('today');
        $defaultFrom  = $today->modify('-3 months')->format('Y-m-d');
        $defaultUntil = $today->modify('-1 week')->format('Y-m-d');

        $dateFrom  = isset($vars['DateFrom']) && is_string($vars['DateFrom']) && $isYmd($vars['DateFrom']) ? $vars['DateFrom'] : $defaultFrom;
        $dateUntil = isset($vars['DateUntil']) && is_string($vars['DateUntil']) && $isYmd($vars['DateUntil']) ? $vars['DateUntil'] : $defaultUntil;

        $orderType = in_array(($vars['OrderType'] ?? $this->getDefaultFormValue('OrderType')), ['Uncompleted', 'Completed', 'All'], true)
            ? $vars['OrderType']
            : $this->getDefaultFormValue('OrderType');

        $includeFrom = (($vars['ShowFrom'] ?? 'No') === 'Yes');
        $includeSource = (($vars['ShowSource'] ?? 'No') === 'Yes');
        $includeMedium = (($vars['ShowMedium'] ?? 'No') === 'Yes');
        $includeCampaign = (($vars['ShowCampaign'] ?? 'No') === 'Yes');


        $periods = (array) $this->config()->get('reporting_periods');
        $breakdownBy = isset($periods[$vars['BreakdownBy'] ?? '']) ? $vars['BreakdownBy'] : $this->getDefaultFormValue('BreakdownBy');

        $stats = (array) $this->config()->get('stats_to_report_on');
        $statistic = isset($stats[$vars['Statistic'] ?? '']) ? $vars['Statistic'] : $this->getDefaultFormValue('Statistic');

        $formatKey = static function (DateTimeInterface $dt, string $mode): array {
            return match ($mode) {
                'Day' => [$dt->format('Y-m-d'), $dt->format('Y-m-d')],
                'Week' => [$dt->format('o-\WW'), $dt->format('o-\WW')],
                'Month' => [$dt->format('Y-m'), $dt->format('Y-m')],
                'Quarter' => [
                    $dt->format('Y') . '-Q' . (int) ceil(((int) $dt->format('n')) / 3),
                    $dt->format('Y') . '-Q' . (int) ceil(((int) $dt->format('n')) / 3),
                ],
                'Year' => [$dt->format('Y'), $dt->format('Y')],
                'AllTime' => ['AllTime', 'AllTime'],
            };
        };

        $filters = [
            'Created:GreaterThanOrEqual' => $dateFrom . ' 00:00:00',
            'Created:LessThanOrEqual' => $dateUntil . ' 23:59:59',
        ];
        if ($orderType === 'Completed') {
            $filters['IsSubmitted'] = 1;
        } elseif ($orderType === 'Uncompleted') {
            $filters['IsSubmitted'] = 0;
        }
        if (!empty($vars['Keyword'])) {
            $keywordFilters = [];
            $keyword = $vars['Keyword'];
            $keywordFilters['From:PartialMatch'] = $keyword;
            $keywordFilters['Source:PartialMatch'] = $keyword;
            $keywordFilters['Medium:PartialMatch'] = $keyword;
            $keywordFilters['Campaign:PartialMatch'] = $keyword;
            $idList = Referral::get()
                ->filterAny($keywordFilters)
                ->column('ID');
            if (count($idList)) {
                $filters['ID'] = $idList;
            } else {
                $filters['ID'] = 0;
            }
        }

        $refs = Referral::get()
            ->filter($filters)
            ->sort(['ID' => 'DESC'])
            ->limit(999999);

        $list = [];

        foreach ($refs as $ref) {
            $createdTs = strtotime((string) $ref->Created);
            $created = (new DateTimeImmutable())->setTimestamp($createdTs);

            [$dateKey, $dateLabel] = $breakdownBy === 'AllTime'
                ? ['AllTime', $dateFrom . ' ... ' . $dateUntil]
                : $formatKey($created, $breakdownBy);

            $from = $ref->From ?: 'none';
            $source = $ref->Source ?: 'none';
            $medium = $ref->Medium ?: 'none';
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

            if ((bool) $ref->IsSubmitted) {
                $list[$key]['NumberOfClicksIntoOrders']++;
                $list[$key]['TotalOrderAmountInvoiced'] += (float) $ref->AmountInvoiced;
                $list[$key]['TotalOrderAmountPaid'] += (float) $ref->AmountPaid;
            }

            $clicks = (int) $list[$key]['NumberOfClicks'];
            $intoOrder = (int) $list[$key]['NumberOfClicksIntoOrders'];

            $list[$key]['AverageClicksIntoOrders'] = $clicks > 0 ? round($intoOrder / $clicks, 2) : 0.0;
            $list[$key]['AverageOrderAmountPaidPerClick'] = $intoOrder > 0 ? round($list[$key]['TotalOrderAmountInvoiced'] / $intoOrder, 2) : 0.0;
        }

        ksort($list, SORT_NATURAL);

        return $this->arrayToTableWithBars($list, (string) $statistic);
    }

    protected function arrayToTableWithBars(array $array, string $statistic): string
    {
        $html = '<h2>Results</h2>';
        if (!count($array)) {
            $html .= '<p class=\'message warning\'>no data</p>';
            return $html;
        }

        // find max of selected statistic
        $max = 0.0;
        foreach ($array as $row) {
            $val = (float) ($row[$statistic] ?? 0);
            if ($val > $max) {
                $max = $val;
            }
        }
        $max = $max > 0 ? $max : 1.0;

        $escapeFN = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES);

        $html .= '
        <style>
            .ReferralSummaryAdmin .form__field-holder  {max-width: 100%!important;}
            .ref-table { border-collapse: collapse; margin-bottom: 4rem; width: 100%; }
            .ref-table th, .ref-table td { padding: 6px 8px; border: 1px solid #ddd; font-size: 12px; min-width: 150px; max-width: 400px; }
            .ref-table td, .ref-table td * { overflow-wrap: anywhere; }
            .ref-table th { background: #f5f5f5; text-align: center; }
            .ref-string { text-align: left; }
            .ref-num { text-align: right; white-space: nowrap; }
            .ref-bar { height: 6px; background: #9aa0a6; margin-top: 4px; border-radius: 3px; }
            .ref-stat-col { min-width: 180px; }
        </style>
        <table class=\'ref-table\'>';

        // header
        $first = reset($array);
        $html .= '<thead><tr>';
        $headerkeys = $this->config()->get('stats_to_report_on');
        $formattingRules = $this->config()->get('formatting_rules');
        foreach ($first as $key => $cell) {
            $isStat =  ((string) $key === $statistic);
            $isHeader = !$isStat && !isset($headerkeys[$key]);
            $label = $this->camelCaseToWords((string) $key);
            if ($isStat) {
                $extra = $isStat ? ' class=\'ref-stat-col\'' : '';
                $html .= '<th' . $extra . '>' . $escapeFN($label) . '</th>';
            } elseif ($isHeader) {
                $html .= '<th>' . $escapeFN((string) $label) . '</th>';
            }
        }
        $html .= '</tr></thead><tbody>';

        // rows
        foreach ($array as $row) {
            $html .= '<tr>';
            foreach ($row as $key => $cell) {
                $isStat =  ((string) $key === $statistic);
                $isHeader = !$isStat && !isset($headerkeys[$key]);
                if ($isStat) {
                    $format = $formattingRules[$key] ?? 'String';
                    $val = (float) $cell;
                    $width = (int) round(($val / $max) * 100, 0);
                    if ($format === 'Currency') {
                        $label = 'NZD' . number_format((float) $cell, 0);
                    } elseif ($format === 'Number') {
                        $label = number_format((float) $cell, 0);
                    } else {
                        $label = (string) $cell;
                    }
                    $html .= '<td class=\'ref-num\'>'
                        . $escapeFN((string) $label)
                        . '<div class=\'ref-bar\' style=\'width:' . $width . '%\'></div>'
                        . '</td>';
                } elseif ($isHeader) {
                    $html .= '<td class=\'ref-string\'>' . $escapeFN((string) $cell) . '</td>';
                }
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    public function camelCaseToWords(string $string): string
    {
        return (string) preg_replace('/(?<!^)[A-Z]/', ' $0', (string) $string);
    }

    protected function getDefaultFormValue(string $key): string
    {
        return $this->config()->get('default_form_values')[$key] ?? '';
    }
}
