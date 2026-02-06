<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use SilverStripe\Forms\DropdownField;
use Sunnysideup\Ecommerce\Pages\Product;

class ProductDropdown extends DropdownField
{
    public function getHasEmptyDefault()
    {
        return true;
    }

    public function getSource()
    {
        return Product::get()->Sort([
            'InternalItemID' => 'ASC',
            'Title' => 'ASC',
        ])->map('ID', 'FullName')->toArray();
    }
}
