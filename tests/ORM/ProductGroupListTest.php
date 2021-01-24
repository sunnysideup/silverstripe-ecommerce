<?php

namespace Sunnysideup\Ecommerce\Tests\ORM;

use SilverStripe\Dev\SapphireTest;
use Sunnysideup\Ecommerce\ORM\RelatedProductGroups;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

class ProductGroupListTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures.yml';

    public function testCreate()
    {
        $list = RelatedProductGroups::create();
        $this->assertSame(4, $list->getGroups()->TotalItems(), 'Get all product groups');

        $root = $this->objFromFixture(ProductGroup::class, 'rugby');
        $list = $list->setRootGroup($root);

        $this->assertSame(1, $list->getPaginatedList()->TotalItems(), '1 group (root)');
    }

    public function testFilter()
    {
        $list = RelatedProductGroups::create();
        $list = $list->filter([
            'ProductGroupID' => $this->objFromFixture(ProductGroup::class, 'sports')->ID,
        ]);

        $this->assertSame(2, $list->getPaginatedList()->TotalItems(), '2 sub groups ');
    }

    public function testGetGroups()
    {
        $list = RelatedProductGroups::create();
        $root = $this->objFromFixture(ProductGroup::class, 'sport');

        $list = $list->setRootGroup($root)->setIncludeRoot(false);

        $this->assertSame(3, $list->getGroups()->Count(), '3 subgroups under sport');

        // with a max depth of 1 we should only get the direct children
        $list = $list->setLevelOfProductsToShow(1);
        $this->assertSame(2, $list->getGroups()->Count(), '2 direct subgroups under sport');
    }
}
