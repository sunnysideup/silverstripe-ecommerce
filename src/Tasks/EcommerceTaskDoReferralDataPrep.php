<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Model\Process\Referral;
use Sunnysideup\Ecommerce\Model\Process\ReferralProcessLog;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @description: see description
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskDoReferralDataPrep extends BuildTask
{
    protected string $title = 'Prepare e-commerce Referral Data';

    protected static string $description = 'Prepares all Referral Data for processing.';

    protected static string $commandName = 'ecommerce-prepare-referral-data';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $limit = (int) $input->getOption('limit') ?: 99999999;
        $start = (int) $input->getOption('start') ?: 0;

        $finished = $this->doDataPrep($limit, $start, false, $output);

        if ($finished) {
            $output->writeln('Referral data preparation completed');
        } else {
            $output->writeln('Referral data preparation in progress. Run again with --start=' . ($start + $limit));
        }

        return Command::SUCCESS;
    }

    private array $messages = [];

    private bool $retainMessages = false;

    /**
     * config
     */
    private static int $max_days_of_interest = 1825; // about 5 years

    private static int $recalculate_days_for_prep_data = 365;

    public function doDataPrep(?int $limit = 99999999, ?int $start = 0, ?bool $retainMessages = false, ?PolyOutput $output = null): bool
    {
        $this->retainMessages = $retainMessages;
        $this->messages = [];

        $filter = ['Completed' => 0];
        $obj = ReferralProcessLog::get()->filter($filter)->first();
        if (! $obj) {
            $obj = ReferralProcessLog::create();
            $obj->write();
        }

        $this->deleteOldReferrals($limit, $output);
        $this->recalculateReferrals($limit, $start, $output);
        $this->deleteStaleReferrals($limit, $start, $output);

        $count = Referral::get()->count();
        $finished = ($count <= ($start + $limit));
        if ($obj) {
            $obj->Completed = $finished;
            $obj->write();
        }

        return $finished;
    }

    protected function deleteOldReferrals(int $limit, ?PolyOutput $output = null)
    {

        $daysAgoDelete = (int) Config::inst()->get(self::class, 'max_days_of_interest') ?: (5 * 365);

        $filter = [
            'Created:LessThan' => date('Y-m-d', strtotime('-' . $daysAgoDelete . ' days')) . ' 23:59:59',
        ];
        $refs = Referral::get()->filter($filter)
            ->limit($limit);
        foreach ($refs as $ref) {
            $this->log('Deleting old referral ID = ' . $ref->ID, 'deleted', $output);
            $ref->delete();
        }
    }

    protected function recalculateReferrals(int $limit, int $start, ?PolyOutput $output = null)
    {
        // less than 180 days old items that have not been processed should be processed.
        $daysAgoStale = (int) Config::inst()->get(self::class, 'recalculate_days_for_prep_data') ?: self::$max_days_of_interest;
        $refs = Referral::get()
            ->filter(['Processed' => 0])->sort(['ID' => 'ASC'])
            ->limit($limit, $start);
        foreach ($refs as $ref) {
            $ref->ProcessReferral($daysAgoStale);
            $this->log('Recalculating referral ID = ' . $ref->ID, 'changed', $output);
        }
    }

    protected function deleteStaleReferrals(int $limit, int $start, ?PolyOutput $output = null)
    {
        // less than 180 days old items that have not been processed should be processed.
        $daysAgoStale = (int) Config::inst()->get(self::class, 'recalculate_days_for_prep_data') ?: self::$max_days_of_interest;
        $refs = Referral::get()
            ->filterAny(['AmountInvoiced' => 0, 'OrderID' => 0])
            ->filter(['Created:LessThan' => date('Y-m-d', strtotime('-' . $daysAgoStale . ' days')) . ' 23:59:59'])->sort(['ID' => 'ASC'])
            ->limit($limit, $start);
        foreach ($refs as $ref) {
            if ($ref->IsStaleWithoutOrder($daysAgoStale)) {
                $this->log('Deleting stale referral ID = ' . $ref->ID, 'deleted', $output);
                // by now we should have an order so even if we dont have an order it should still be marked as processed
                $ref->delete();
            }
        }
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    protected function log(string $message, ?string $type = 'changed', ?PolyOutput $output = null)
    {
        if ($this->retainMessages) {
            $this->messages[] = $message;
        } elseif ($output) {
            $output->writeln($message);
        } else {
            DB::alteration_message($message, $type);
        }
    }

    public function getOptions(): array
    {
        return [
            new InputOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Maximum number of items to process', 99999999),
            new InputOption('start', 's', InputOption::VALUE_OPTIONAL, 'Starting offset for processing', 0),
        ];
    }
}
