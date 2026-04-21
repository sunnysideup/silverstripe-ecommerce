<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;
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
class EcommerceTaskDeleteProducts extends BuildTask
{
    protected string $title = 'Delete e-commerce Buyables';

    protected static string $description = 'Removes all Buyables (Products) from the database.';

    protected static string $commandName = 'ecommerce:delete-products';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        if (!$input->getOption('confirm')) {
            $output->writeln('WARNING: This command will delete ALL products permanently!');
            $output->writeln('To confirm, run with --confirm flag:');
            $output->writeln('  sake ecommerce:delete-products --confirm');
            return Command::FAILURE;
        }

        $arrayOfBuyables = EcommerceConfig::get(EcommerceDBConfig::class, 'array_of_buyables');
        foreach ($arrayOfBuyables as $buyable) {
            $allproducts = $buyable::get();
            if ($allproducts->exists()) {
                foreach ($allproducts as $product) {
                    $output->writeln('Deleting ' . $product->ClassName . ' ID = ' . $product->ID);
                    if (is_a($product, EcommerceConfigClassNames::getName(SiteTree::class))) {
                        $product->deleteFromStage('Live');
                        $product->deleteFromStage('Draft');
                    } else {
                        $product->delete();
                    }

                    $product->destroy();
                    //TODO: remove versions
                }
            }
        }

        return Command::SUCCESS;
    }

    public function getOptions(): array
    {
        return [
            new InputOption('confirm', 'c', InputOption::VALUE_NONE, 'Confirm deletion of all products'),
        ];
    }
}
