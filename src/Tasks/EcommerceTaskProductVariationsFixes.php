<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Pages\Product;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * see description in class.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskProductVariationsFixes extends BuildTask
{
    protected string $title = 'Fix Product Variations';

    protected static string $description = 'Fixes a bunch of links between Products and their Variations';

    protected static string $commandName = 'ecommerce:fix-product-variations';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $stagingArray = ['Live', 'Stage'];
        foreach ($stagingArray as $stage) {
            $products = Versioned::get_by_stage(Product::class, $stage);
            $count = 0;
            if ($products) {
                foreach ($products as $product) {
                    if ($product->hasExtension('ProductWithVariationDecorator')) {
                        $product->cleaningUpVariationData($verbose = true);
                        if ($product) {
                            ++$count;
                        }
                    }
                }
            }

            $output->writeln(sprintf('Updated %d Products (', $count) . $products->count() . sprintf(' products on %s)', $stage));
        }

        return Command::SUCCESS;
    }
}
