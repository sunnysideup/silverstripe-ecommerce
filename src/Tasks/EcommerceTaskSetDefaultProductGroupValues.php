<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * @description: resets fields in the product group class to "inherit" in case their value does not exist.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskSetDefaultProductGroupValues extends BuildTask
{
    protected $title = 'Set Default Product Group Values';

    protected $description = 'Set default product group values such as DefaultSortOrder.';

    protected $fieldsToCheck = [
        'SORT' => 'DefaultSortOrder',
        'FILTER' => 'DefaultFilter',
        'DISPLAY' => 'DisplayStyle',
    ];

    public function run($request)
    {
        $productGroup = DataObject::get_one(ProductGroup::class);
        if ($productGroup) {
            foreach ($this->fieldsToCheck as $method => $fieldName) {
                $acceptableValuesArray = array_flip($productGroup->getUserPreferencesOptionsForDropdown($method));
                $this->checkOneField($fieldName, $acceptableValuesArray, 'inherit');
            }
        } else {
            DB::alteration_message('There are no ProductGroup pages to correct', 'created');
        }
    }

    protected function checkOneField($fieldName, $acceptableValuesArray, $resetValue)
    {
        $faultyProductGroups = ProductGroup::get()
            ->exclude([$fieldName => $acceptableValuesArray])
        ;
        if ($faultyProductGroups->exists()) {
            foreach ($faultyProductGroups as $faultyProductGroup) {
                $faultyProductGroup->{$fieldName} = $resetValue;
                $faultyProductGroup->writeToStage(Versioned::DRAFT);
                $faultyProductGroup->publishRecursive();
                DB::alteration_message("Reset {$fieldName} for " . $faultyProductGroup->Title, 'created');
            }
        } else {
            DB::alteration_message("Could not find any faulty records for ProductGroup.{$fieldName}");
        }
    }
}
