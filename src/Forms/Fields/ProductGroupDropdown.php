<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use Override;
use SilverStripe\Forms\DropdownField;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

class ProductGroupDropdown extends DropdownField
{
    #[Override]
    public function getHasEmptyDefault()
    {
        return true;
    }

    #[Override]
    public function getSource()
    {
        $idList = Product::get()->filter(['AllowPurchase' => 1])->columnUnique('ParentID');

        return ProductGroup::get()
            ->Sort(['Title' => 'ASC'])
            ->filter(['ID' => $idList])
            ->map('ID', 'Title')
            ->toArray()
        ;
    }
}
