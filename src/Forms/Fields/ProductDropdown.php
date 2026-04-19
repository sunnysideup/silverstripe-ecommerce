<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use Override;
use SilverStripe\Forms\DropdownField;
use Sunnysideup\Ecommerce\Pages\Product;

class ProductDropdown extends DropdownField
{
    #[Override]
    public function getHasEmptyDefault()
    {
        return true;
    }

    #[Override]
    public function getSource()
    {
        return Product::get()->Sort([
            'InternalItemID' => 'ASC',
            'Title' => 'ASC',
        ])->map('ID', 'FullName')->toArray();
    }
}
