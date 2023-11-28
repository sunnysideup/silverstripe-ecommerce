<?php

namespace Sunnysideup\Ecommerce\Control;

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
        'perday' => 'ADMIN',
        'perdaypersource' => 'ADMIN',
        'perdaypercampaign' => 'ADMIN',
        'perweek' => 'ADMIN',
        'perweekpersource' => 'ADMIN',
        'perweekpercampaign' => 'ADMIN',
        'permonth' => 'ADMIN',
        'permonthpersource' => 'ADMIN',
        'permonthpercampaign' => 'ADMIN',
        'peryear' => 'ADMIN',
        'peryearpersource' => 'ADMIN',
        'peryearpercampaign' => 'ADMIN',
        'clearoldentries' => 'ADMIN',
    ];

    private static $menu_list = [
        'prepdata' => 'prepare data',
        'perday' => 'per day',
        'perdaypersource' => 'per day per source',
        'perdaypercampaign' => 'per day per campaign',
        'perweek' => 'per week',
        'perweekpersource' => 'per week per source',
        'perweekpercampaign' => 'per week per campaign',
        'permonth' => 'per month',
        'permonthpersource' => 'per month per source',
        'permonthpercampaign' => 'per month per campaign',
        'peryear' => 'per year',
        'peryearpersource' => 'per year per source',
        'peryearpercampaign' => 'per year per campaign',
        'clearoldentries' => 'delete old data',
    ];


    private static $max_days_of_interest = 720;
    private static $recalculate_days_for_prep_data = 30;

    public function index($request) {}

    protected function init()
    {
        parent::init();
        Environment::increaseTimeLimitTo(600);
        $allowedActions = $this->Config()->get('allowed_actions');
        $securityCheck = $allowedActions['index'] ?? 'ADMIN';
        if(!Permission::check($securityCheck)) {
            return Security::permissionFailure($this);
        }
        $this->printMenu();

    }

    public function clearoldentries($request)
    {
        $daysAgo = $this->config()->get('max_days_of_interest');
        $objects = Referral::get()->filter("Created:LessThan", date('Y-m-d', strtotime($daysAgo . ' days ago')) . ' 23:59:59');
        foreach($objects as $object) {
            $object->delete();
        }
        die('done');
    }

    public function prepdata($request)
    {
        $daysAgo = $this->config()->get('recalculate_days_for_prep_data');
        $refs = Referral::get()->filter("Created:GreaterThan", date('Y-m-d', strtotime($daysAgo . ' days ago')) . ' 23:59:59');
        foreach($refs as $ref) {
            if($ref->OrderID) {
                $order = $ref->Order();
                if($order && $order->exists() && $order->IsSubmitted()) {
                    DB::query('UPDATE Referrral SET IsSubmitted = 1, AmountPaid = \'' . $order->getTotalPaid() . '\', AmountInvoiced = \'' . $order->getTotal() . '\' WHERE ID = ' . $ref->ID);
                }
            }

        }
        die('done');
    }

    public function perday($request)
    {
        return $this->listInner('Y-m-d', false, false);
    }

    public function perdaypersource($request)
    {
        return $this->listInner('Y-m-d', true, false);
    }

    public function perdaypercampaign($request)
    {
        return $this->listInner('Y-m-d', false, true);
    }

    public function perweek($request)
    {
        return $this->listInner('Y-W', false, false);
    }

    public function perweekpersource($request)
    {
        return $this->listInner('Y-W', true, false);
    }

    public function perweekpercampaign($request)
    {
        return $this->listInner('Y-W', false, true);
    }

    public function permonth($request)
    {
        return $this->listInner('Y-m', false, false);
    }

    public function permonthpersource($request)
    {
        return $this->listInner('Y-m', true, false);
    }

    public function permonthpercampaign($request)
    {
        return $this->listInner('Y-m', false, true);
    }

    public function peryear($request)
    {
        return $this->listInner('Y', false, false);
    }

    public function peryearpersource($request)
    {
        return $this->listInner('Y', true, false);
    }

    public function peryearpercampaign($request)
    {
        return $this->listInner('Y', false, true);
    }

    protected function listInner(string $dateFormat, bool $includeFrom, bool $includeCampaign)
    {

        $refs = Referral::get()->sort(['ID' => 'DESC'])->limit(999999);
        $list = [];
        foreach($refs as $ref) {
            $date = date($dateFormat, strtotime($ref->Created));
            $campaign =  $ref->getFullCode();
            $from =  $ref->getFrom();
            $key = $date;
            if($includeFrom || $includeCampaign) {
                $key .= '|' . $from;
            }
            if($includeCampaign) {
                $key .= '|' . $campaign;
            }
            if (!isset($list[$key])) {
                $list[$key] = [
                    'Key' => $key,
                    'NumberOfClicks' => 0,
                    'NumberOfClicksIntoOrders' => 0,
                    'TotalOrderAmountPaid' => 0,
                    'AverageClicksIntoOrders' => 0,
                    'AverageOrderAmountPaidPerClick' => 0,
                ];
            }
            $hasOrder = $ref->IsSubmitted;
            $amount = $ref->AmountPaid;
            $list[$key]['NumberOfClicks']++;

            if ($hasOrder) {
                $list[$key]['NumberOfClicksIntoOrders']++;
                $list[$key]['TotalOrderAmountPaid'] += $amount;
            }

            // Update percentages and averages for each date
            $list[$key]['AverageClicksIntoOrders'] = ($list[$key]['NumberOfClicks'] > 0) ? round($list[$key]['NumberOfClicksIntoOrders'] / $list[$key]['NumberOfClicks'] * 100, 2) : 0;
            $list[$key]['AverageOrderAmountPaidPerClick'] = ($list[$key]['NumberOfClicksIntoOrders'] > 0) ? round($list[$key]['TotalOrderAmountPaid'] / $list[$key]['NumberOfClicksIntoOrders'], 2) : 0;

        }
        ksort($list);
        echo $this->arrayToTable($list);
    }

    protected function printMenu()
    {
        $menuList = $this->Config()->get('menu_list');
        $function = $this->request->param('Action');
        $title = $menuList[$function] ?? 'Please prep data and then select a report';
        echo '<h1>Referral Summary - ' . $title . '</h1><ul>';
        foreach($menuList as $key => $value) {
            echo '<li><a href="/' . $this->Config()->get('url_segment') . '/' . $key . '">' . $value . '</a></li>';
        }
        echo '</ul><h3>' . $title . '</h3>';

    }

    protected function arrayToTable(array $array): string
    {
        if(count($array)) {
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
                }
                th:first-child, td:first-child {
                    text-align: left;
                }
                td:first-child {
                    font-size: 10px;
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
                    $html .= '<td>' . str_replace('|', ' | ', $cell) . '</td>';
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
