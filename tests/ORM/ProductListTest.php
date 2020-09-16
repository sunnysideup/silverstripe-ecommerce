<?php

namespace Sunnysideup\Ecommerce\Tests\ORM;

use SilverStripe\Dev\SapphireTest;
use Sunnysideup\Ecommerce\ORM\ProductList;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

class ProductListTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures.yml';

    public function testFilter()
    {
        $list = ProductList::create();

        $this->assertSame(3, $list->getPaginatedList()->TotalItems(), 'Get all products');

        $list = $list->filter([
            'ProductGroupID' => $this->objFromFixture(ProductGroup::class, 'rugby'),
        ]);

        $this->assertSame(1, $list->getPaginatedList()->TotalItems(), '1 product in rugby');

        $list = $list->filter([
            'ProductGroupID' => $this->objFromFixture(ProductGroup::class, 'sports'),
        ]);

        $this->assertSame(2, $list->getPaginatedList()->TotalItems(), '2 products in sports');
    }
}
