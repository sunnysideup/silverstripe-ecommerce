<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Removes superfluous entries in Product_ProductGroups.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskRemoveSuperfluousLinksInProductProductGroups extends BuildTask
{
    protected static string $commandName = 'ecommerce:clean-product-links';

    protected string $title = 'Delete superfluous entries in Product_ProductGroups';

    protected static string $description = 'Look at all the links in Product_ProductGroups and removes non-existing entries.';

    protected $verbose = false;

    public function setVerbose(bool $b)
    {
        $this->verbose = $b;

        return $this;
    }

    public function getOptions(): array
    {
        return [
            new InputOption('verbose', 'v', InputOption::VALUE_NONE, 'Verbose output'),
        ];
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->verbose = $input->getOption('verbose');

        if ($this->verbose) {
            $output->writeln('Before: ' . DB::query('SELECT COUNT(ID) FROM Product_ProductGroups;')->value());
        }

        DB::query('
            DELETE T1 FROM Product_ProductGroups AS T1
                LEFT JOIN Product ON Product.ID = ProductID
                LEFT JOIN ProductGroup ON ProductGroup.ID = ProductGroupID
            WHERE Product.ID IS NULL OR ProductGroup.ID IS NULL
        ');
        
        if ($this->verbose) {
            $output->writeln('After: ' . DB::query('SELECT COUNT(ID) FROM Product_ProductGroups;')->value());
        }

        return Command::SUCCESS;
    }
}
