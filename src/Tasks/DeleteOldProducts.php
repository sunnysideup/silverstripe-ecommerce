<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Pages\Product;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Deletes products that are not for sale and have not been sold for a year.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class DeleteOldProducts extends BuildTask
{
    protected static string $commandName = 'ecommerce-delete-old-products';

    protected string $title = 'Delete products that are not for sale and have not been sold for a year';

    protected static string $description = 'Deletes products that are not for sale and have not been sold for a year.';

    private static $last_sold_days_ago = '365';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $cutOfTs = strtotime('-' . $this->Config()->get('last_sold_days_ago') . ' days');
        $output->writeForHtml('<h1>Deleting products that are not for sale, last sold since: ' . date('Y-m-d', $cutOfTs) . '</h1>');

        $products = Product::get()->filter(['ID' => $this->getListOfCandidates()]);
        foreach ($products as $product) {
            $output->writeln('Deleting ' . $product->FullName);
            $product->DeleteFromStage(Versioned::LIVE);
            $product->DeleteFromStage(Versioned::DRAFT);
        }

        return Command::SUCCESS;
    }

    public function getListOfCandidates(): array
    {
        $ids = [];
        strtotime('-' . $this->Config()->get('last_sold_days_ago') . ' days');
        $products = Product::get()->filter(['AllowPurchase' => false]);
        foreach ($products as $product) {
            if (! $product->hasBeenSold()) {
                $ids[$product->ID] = $product->ID;
            }
        }

        return ArrayMethods::filter_array($ids);
    }
}
