<?php

namespace Sunnysideup\Ecommerce\Tests;

use SilverStripe\Dev\FunctionalTest;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

class ProductGroupTest extends FunctionalTest
{
    protected static $fixture_file = 'fixtures.yml';

    public function testIndex()
    {
        $group = $this->objFromFixture(ProductGroup::class, 'mountainbiking');
        $response = $this->get($group->Link());

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetProductGroupsFromAlsoShowProducts()
    {
        $group = $this->objFromFixture(ProductGroup::class, 'mountainbiking');

        $related = $group->getProductGroupsFromAlsoShowProducts();

        $this->assertEquals(1, $related->count());
        $this->assertListEquals([
            'Title' => 'Fox Sports'
        ], $related, 'Fox sports is a related group to mountain biking');
    }
}
