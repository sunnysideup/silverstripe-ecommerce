<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Forms\Fields\EcommerceSearchHistoryFormField;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskReviewSearches extends BuildTask
{
    /**
     * number of days shown.
     *
     * @int
     */
    protected $defaultMaxRows = 999;

    /**
     * number of days shown.
     *
     * @int
     */
    protected $defaultDays = 100;

    /**
     * minimum number of searches for
     * a particular keyword in order to show it at all.
     *
     * @int
     */
    protected $defaultMinimum = 5;

    /**
     * show up to XXX days ago.
     *
     * @int
     */
    protected $endingDaysBack = 0;

    /**
     * Standard (required) SS variable for BuildTasks.
     *
     * @var string
     */
    protected string $title = 'Search Statistics';

    /**
     * Standard (required) SS variable for BuildTasks.
     *
     * @var string
     */
    protected static string $description = 'What did people search for on the website, you can use the options to query different sets.';

    protected static string $commandName = 'ecommerce-review-searches';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        // @TODO (SS6 upgrade) - This task was designed for HTTP with form generation
        // It has been converted to CLI but may need additional work for optimal UX

        $maxRows = (int) $input->getOption('maxrows');
        if ($maxRows === 0) {
            $maxRows = $this->defaultMaxRows;
        }

        $days = (int) $input->getOption('days');
        if ($days === 0) {
            $days = $this->defaultDays;
        }

        $countMin = (int) $input->getOption('min');
        if ($countMin === 0) {
            $countMin = $this->defaultMinimum;
        }

        $endingDaysBack = (int) $input->getOption('ago');
        if ($endingDaysBack === 0) {
            $endingDaysBack = $this->endingDaysBack;
        }

        $field = EcommerceSearchHistoryFormField::create('stats', $this->title)
            ->setNumberOfDays($days)
            ->setMinimumCount($countMin)
            ->setMaxRows($maxRows)
            ->setEndingDaysBack($endingDaysBack)
        ;
        $output->writeForHtml($field->forTemplate());

        return Command::SUCCESS;
    }

    public function getOptions(): array
    {
        return [
            new InputOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Number of days to query', $this->defaultDays),
            new InputOption('maxrows', 'm', InputOption::VALUE_OPTIONAL, 'Maximum number of rows to display', $this->defaultMaxRows),
            new InputOption('ago', 'a', InputOption::VALUE_OPTIONAL, 'Up to how many days ago', $this->endingDaysBack),
            new InputOption('min', 'i', InputOption::VALUE_OPTIONAL, 'Minimum count threshold', $this->defaultMinimum),
        ];
    }
}
