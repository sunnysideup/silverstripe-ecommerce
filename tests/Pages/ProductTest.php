prodS<?php

namespace Sunnysideup\Ecommerce\Tests\Pages;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * @internal
 * @coversNothing
 */
class ProductTest extends SapphireTest
{
    public function testGetCMSFields()
    {
        $product = Product::create();
        $this->assertInstanceOf(FieldList::class, $product->getCMSFields());
    }
}
