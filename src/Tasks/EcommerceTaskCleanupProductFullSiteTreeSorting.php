<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Pages\Product;
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
class EcommerceTaskCleanupProductFullSiteTreeSorting extends BuildTask
{
    protected string $title = 'Cleanup Product Full SiteTree Sorting';

    protected static string $description = 'Resets all the sorting values in the Full Site Tree Sorting field in Products (not for the ProductVariations). This field includes the sorting number for the product at hand, as well as all the sorting number of its parent pages... Allowing you to keep the SiteTree sort order for a collection of random products.';

    protected static string $commandName = 'ecommerce-cleanup-product-sorting';

    protected $deleteFirst = true;

    public function setDeleteFirst(bool $b)
    {
        $this->deleteFirst = $b;
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        if ($input->getOption('no-delete')) {
            $this->deleteFirst = false;
        }

        $stagingArray = ['_Live', ''];
        foreach ($stagingArray as $extension) {
            if ($this->deleteFirst) {
                DB::query(sprintf("UPDATE Product%s SET \"FullSiteTreeSort\" = '';", $extension));
            } else {
                $output->writeln('updating Product' . $extension);
            }

            for ($i = 30; $i > 0; --$i) {
                $joinStatement = "
                    INNER JOIN SiteTree{$extension} AS UP0 ON UP0.ID = Product{$extension}.ID";
                $concatStatement = 'CONCAT(';
                for ($j = 1; $j < $i; ++$j) {
                    $concatStatement .= 'UP' . ($i - $j) . ".Sort,',',";
                    $joinStatement .= "
                        INNER JOIN SiteTree{$extension} AS UP{$j} ON UP{$j}.ID = UP" . ($j - 1) . '.ParentID';
                }

                $concatStatement .= 'UP0.Sort)';
                $sql = "
                    SELECT COUNT(\"Product{$extension}\".\"ID\")
                    FROM  \"Product{$extension}\"
                    {$joinStatement}
                    WHERE \"Product{$extension}\".\"FullSiteTreeSort\" IS NULL OR \"Product{$extension}\".\"FullSiteTreeSort\" = '';
                ";
                $count = DB::query($sql)->value();
                if ($count) {
                    $output->writeln(sprintf('We are about to update %s Products', $count));
                    $sql = "
                        UPDATE \"Product{$extension}\"
                        {$joinStatement}
                        SET \"Product{$extension}\".\"FullSiteTreeSort\" = {$concatStatement}
                        WHERE \"Product{$extension}\".\"FullSiteTreeSort\" IS NULL OR \"Product{$extension}\".\"FullSiteTreeSort\" = '';";
                    DB::query($sql);
                    $outcome = DB::query($sql);
                    $output->writeForHtml('<p style="font-size: 10px; color: grey;">' . $sql . ': ' . ($outcome ? 'SUCCESS' : 'ERROR') . '</p>');
                }
            }
        }

        $missedOnes = Product::get()
            ->where("\"FullSiteTreeSort\" IS NULL OR \"FullSiteTreeSort\" = ''");
        if ($missedOnes->exists()) {
            $output->writeln('ERROR: could not updated all Product.FullSiteTreeSort numbers!');
        } else {
            $output->writeln('All Product.FullSiteTreeSort have been updated');
        }

        $examples = Product::get()
            ->shuffle()
            ->limit(3);
        if ($examples->exists()) {
            foreach ($examples as $key => $example) {
                $output->writeForHtml(sprintf('EXAMPLE #%s: ', $key) . $example->Title . ': <strong>' . $example->FullSiteTreeSort . '</strong>');
            }
        }

        return Command::SUCCESS;
    }

    public function getOptions(): array
    {
        return [
            new InputOption('no-delete', 'd', InputOption::VALUE_NONE, 'Do not delete existing sort values first'),
        ];
    }
}
