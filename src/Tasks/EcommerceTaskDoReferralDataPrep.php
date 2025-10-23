<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Model\Process\Referral;
use Sunnysideup\Ecommerce\Model\Process\ReferralProcessLog;

/**
 * @description: see description
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskDoReferralDataPrep extends BuildTask
{
    protected $title = 'Prepare e-commerce Referral Data';

    protected $description = 'Prepares all Referral Data for processing.';

    private static $segment = 'doreferraldataprep';

    public function run($request)
    {
        return $this->doDataPrep();
    }

    private array $messages = [];
    private bool $retainMessages = false;

    /** config */
    private static int $max_days_of_interest = 1825; // about 5 years
    private static int $recalculate_days_for_prep_data = 365;

    public function doDataPrep(?int $limit = 99999999, ?int $start = 0, ?bool $retainMessages = false): bool
    {
        $this->retainMessages = $retainMessages;
        $this->messages = [];

        $filter = ['Completed' => 0];
        $obj = ReferralProcessLog::get()->filter($filter)->first();
        if (! $obj) {
            $obj = ReferralProcessLog::create()->write();
        }

        $this->deleteOldReferrals($limit);
        $this->recalculateReferrals($limit, $start);
        $this->deleteStaleReferrals($limit, $start);


        $count = Referral::get()->count();
        $finished = ($count <=  ($start + $limit) ? true : false);
        if ($obj) {
            $obj->Completed = $finished;
            $obj->write();
        }
        return $finished;
    }

    protected function deleteOldReferrals(int $limit)
    {

        $daysAgoDelete = (int) Config::inst()->get(self::class, 'max_days_of_interest') ?: (5 * 365);

        $filter = [
            'Created:LessThan' => date('Y-m-d', strtotime('-' . $daysAgoDelete . ' days')) . ' 23:59:59',
        ];
        $refs = Referral::get()->filter($filter)
            ->limit($limit);
        foreach ($refs as $ref) {
            DB::alteration_message('Deleting old referral ID = ' . $ref->ID, 'deleted');
            $ref->delete();
        }
    }

    protected function recalculateReferrals(int $limit, int $start)
    {
        // less than 180 days old items that have not been processed should be processed.
        $daysAgoStale = (int) Config::inst()->get(self::class, 'recalculate_days_for_prep_data') ?: self::$max_days_of_interest;
        $refs = Referral::get()
            ->filter(['Processed' => 0])
            ->sort('ID', 'ASC')
            ->limit($limit, $start);
        foreach ($refs as $ref) {
            $ref->ProcessReferral($daysAgoStale);
            $this->log('Recalculating referral ID = ' . $ref->ID, 'changed');
        }
    }

    protected function deleteStaleReferrals(int $limit, int $start)
    {
        // less than 180 days old items that have not been processed should be processed.
        $daysAgoStale = (int) Config::inst()->get(self::class, 'recalculate_days_for_prep_data') ?: self::$max_days_of_interest;
        $refs = Referral::get()
            ->filterAny(['AmountInvoiced' => 0, 'OrderID' => 0])
            ->filter(['Created:LessThan' => date('Y-m-d', strtotime('-' . $daysAgoStale . ' days')) . ' 23:59:59',])
            ->sort('ID', 'ASC')
            ->limit($limit, $start);
        foreach ($refs as $ref) {
            if ($ref->IsStaleWithoutOrder($daysAgoStale)) {
                $this->log('Deleting stale referral ID = ' . $ref->ID, 'deleted');
                // by now we should have an order so even if we dont have an order it should still be marked as processed
                $ref->delete();
            }
        }
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    protected function log(string $message, ?string $type = 'changed')
    {
        if ($this->retainMessages) {
            $this->messages[] = $message;
        } else {
            DB::alteration_message($message, $type);
        }
    }
}
