<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Helpers;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

use Sunnysideup\Ecommerce\Config\EcommerceConfig;

use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * provides data on the user
 */
class RelatedGroupsAndProducts extends BaseClass
{


    public function __construct($products, $childGroupsIds, $alsoShowProductIds)
    {
        parent::__construct($products);
        $this->childGroupsIds = $childGroupsIds;
        $this->alsoShowProductIds = $alsoShowProductIds;
    }


    /**
     * Given the products for this page, retrieve the parent groups excluding
     * the current one.
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function getProductGroupsParentGroups(): DataList
    {
        return $this->filteredSortedProducts->getParentGroups()->exclude(['ID' => $this->ID]);
    }


    /**
     * This is the inverse of ProductGroupsFromAlsoShowProducts
     *
     * That is, it list the product groups that a product is primarily listed
     * under (exact parents only) from a "AlsoShow" product List.
     *
     * @return \SilverStripe\ORM\DataList|null
     */
    public function getProductGroupsFromAlsoShowProductsInverse()
    {
        $filter = $this->getValueForProductListConfigType(
            'FILTER',
            $this->getProductListConfigDefaultValue('FILTER'),
            'SQL'
        );
        $alsoShowProductsArray = $this->AlsoShowProducts()
            ->filter($filter)
            ->map('ID', 'ID')
            ->toArray();

        if ($alsoShowProductsArray) {
            $parentIDs = Product::get()
                ->filter(['ID' => $alsoShowProductsArray,])
                ->map('ParentID', 'ParentID')
                ->toArray();

            if ($parentIDs) {
                return ProductGroup::get()
                    ->filter(['ID' => $parentIDs,'ShowInMenus' => 1,])
                    ->exclude(['ID' => $this->ID,]);
            }
        }
    }

    /**
     * Returns a list of Product Groups that have the products for the CURRENT
     * product group listed as part of their AlsoShowProducts list.
     *
     * With the method below you can work out a list of brands that apply to the
     * current product group (e.g. socks come in three brands - namely A, B and C)
     *
     * @return \SilverStripe\ORM\DataList|null
     */
    public function getProductGroupsFromAlsoShowProducts()
    {
        $productGroups = $this->getProductList($this->getProductListConfigDefaultValue('FILTER'))
            ->getProducts()
            ->column('ParentID');

        if ($productGroups) {
            return ProductGroup::get()
                ->filter(['ID' => $productGroups,'ShowInSearch' => 1,])
                ->exclude(['ID' => $this->ID,]);
        }
    }


    /**
     * With the current product list, return all the {@link ProductGroup}
     * instances that the products are displayed under. This only returns the
     * direct parents.
     *
     * @return PaginatedList|null
     */
    public function getParentGroups()
    {
        $ids = $this->products->columnUnique('ParentID');

        if ($ids) {
            return PaginatedList::create(
                ProductGroup::get()->filter(['ID' => $ids,])
            );
        }
    }

    /**
     * Returns a list of Product Groups that have the products for the CURRENT
     * product group listed as part of their AlsoShowProducts list.
     *
     * With the method below you can work out a list of brands that apply to the
     * current product group (e.g. socks come in three brands - namely A, B and C)
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function getProductGroupsFromAlsoShowProducts()
    {
        $productGroupIds = $this->products->filter(['ID' => $this->alsoShowProductsIds])
            ->column('ParentID');

        if (empty($productGroupIds)) {
            $productGroupIds = [-1 => -1];
        }
        $filter = array_merge(
            $this->Config()->get('default_product_group_filter'),
            ['ID' => $productGroupIds,]
        );
        return ProductGroup::get()
            ->filter($filter)
            ->exclude(['ID' => $this->childGroupsIds,]);
    }


    /**
     * This is the inverse of ProductGroupsFromAlsoShowProducts
     *
     * That is, it list the product groups that a product is primarily listed
     * under (exact parents only) from a "AlsoShow" product List.
     *
     * @return \SilverStripe\ORM\DataList|null
     */
    public function getProductGroupsFromAlsoShowProductsInverse()
    {
        $filter = array_merge(
            $this->Config()->get('default_product_group_filter'),
            ['ID' => $this->childGroupsIds,]
        );
        return ProductGroup::get()
            ->filter($filter);

    }

    /**
     * Given the products for this page, retrieve the parent groups excluding
     * the current one.
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function getProductGroupsParentGroups(): DataList
    {
        $productGroupIds = $this->products->filter(['ID' => $this->alsoShowProductsIds])
            ->column('ParentID');

        if (empty($productGroupIds)) {
            $productGroupIds = [-1 => -1];
        }
        $filter = array_merge(
            $this->Config()->get('default_product_group_filter'),
            ['ID' => $productGroupIds,]
        );

        return ProductGroup::get()
            ->filter($filter);ProductSorter0
    }


            /**
             * With the current product list, return all the {@link ProductGroup}
             * instances that the products are displayed under. This only returns the
             * direct parents.
             *
             * @return DataList|null
             */
            public function getParentGroups()
            {
                $ids = $this->products->columnUnique('ParentID');

                if (empty($ids)) {
                    $ids = [-1 => -1,];
                }
                return ProductGroup::get()->filter(['ID' => $ids,]);
            }


}
