<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @description: resets fields in the product group class to "inherit" in case their value does not exist.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskSetDefaultProductGroupValues extends BuildTask
{
    protected string $title = 'Set Default Product Group Values';

    protected static string $description = 'Set default product group values such as DefaultSortOrder.';

    protected static string $commandName = 'ecommerce-set-default-product-group-values';

    protected $fieldsToCheck = [
        'SORT' => 'DefaultSortOrder',
        'FILTER' => 'DefaultFilter',
        'DISPLAY' => 'DisplayStyle',
    ];

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $productGroup = ProductGroup::get()->setUseCache(true)->first();
        if ($productGroup) {
            foreach ($this->fieldsToCheck as $method => $fieldName) {
                $acceptableValuesArray = array_flip($productGroup->getUserPreferencesOptionsForDropdown($method));
                $this->checkOneField($fieldName, $acceptableValuesArray, 'inherit', $output);
            }
        } else {
            $output->writeln('There are no ProductGroup pages to correct');
        }

        return Command::SUCCESS;
    }

    protected function checkOneField($fieldName, $acceptableValuesArray, $resetValue, PolyOutput $output)
    {
        $faultyProductGroups = ProductGroup::get()
            ->exclude([$fieldName => $acceptableValuesArray])
        ;
        if ($faultyProductGroups->exists()) {
            foreach ($faultyProductGroups as $faultyProductGroup) {
                $faultyProductGroup->{$fieldName} = $resetValue;
                $faultyProductGroup->writeToStage(Versioned::DRAFT);
                $faultyProductGroup->publishRecursive();
                $output->writeln(sprintf('Reset %s for ', $fieldName) . $faultyProductGroup->Title);
            }
        } else {
            $output->writeln('Could not find any faulty records for ProductGroup.' . $fieldName);
        }
    }
}
