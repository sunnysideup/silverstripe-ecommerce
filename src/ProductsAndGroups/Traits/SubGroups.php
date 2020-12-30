<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Traits;

use SilverStripe\ORM\DataList;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\ProductGroupList;

trait SubGroups
{
    ##########################################
    # PRODUCTS: basics
    ##########################################

    /**
     * Returns a raw list of all the matching products without any pagination.
     *
     * @return SS_List
     */
    public function getProducts()
    {
        return $this->products;
    }

    public function getProductsPaginated()
    {
    }

    public function getProductIds()
    {
        $this->products->columnUnique('ID');
    }

    ##########################################
    # PRODUCTS: Counts
    ##########################################

    /**
     * Returns the total number of products available before pagination is
     * applied.
     *
     * @return int
     */
    public function getRawCount(): int
    {
        return $this->products->count();
    }

    /**
     * Is there more than x products.
     *
     * @param int $greaterThan
     *
     * @return bool
     */
    public function hasMoreThanOne($greaterThan = 1): bool
    {
        return $this->getRawCount() > $greaterThan;
    }

    ##########################################
    # PRODUCTS: Direct
    ##########################################

    public function getDirectProducts(): DataList
    {
        return $this->products
            ->filter(['ParentID' => $this->rootGroup->ID]);
    }

    ##########################################
    # PRODUCTS: Children
    ##########################################

    public function getChildProductsInclusive(): DataList
    {
        return $this->products
            ->filter(['ParentID' => $this->getParentGroupIds()]);
    }

    public function getChildProductsExclusive(): DataList
    {
        return $this->products
            ->exclude(['ID' => $this->getAlsoShowProductsIds()]);
    }

    ##########################################
    # PRODUCTS: Also show
    ##########################################

    public function getAlsoShowProductsFromRootGroupOnly(): DataList
    {
        return $this->rootGroup->AlsoShowProducts()
            ->filter(['ID' => $this->getProducts()->column('ID')]);
    }

    public function getAlsoShowProductsInclusive(): DataList
    {
        return $this->products
            ->filter(['ID' => $this->getAlsoShowProductsIds()]);
    }

    public function getAlsoShowProductsExclusive(): DataList
    {
        return $this->products
            ->filter(['ID' => $this->getAlsoShowProductsIds()])
            ->exclude(['ParentID' => $this->getParentGroupIds()]);
    }

    ##########################################
    # GROUPS - ALL
    ##########################################

    /**
     * KEY METHOD!
     *
     * Applies default filter for groups
     *
     * With the current product list, return all the {@link ProductGroup}
     * instances that the products are displayed under. This only returns the
     * direct parents.
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function getParentGroups()
    {
        $ids = $this->getProducts()->columnUnique('ParentID');
        $ids = ArrayMethods::filter_array($ids);

        $groups = ProductGroup::get()->filter(['ID' => $ids]);

        return ProductGroupList::apply_default_filter_to_groups($groups);
    }

    /**
     * Given the products for this page, retrieve the parent groups excluding
     * the current one.
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function getProductGroupsExcludingRootGroup(): DataList
    {
        return $this->getParentGroups()->exclude(['ID' => $this->rootGroup->ID]);
    }

    ##################################################
    # GROUPS: Also Show Products
    ##################################################

    /**
     * List of All Also Show Product Parents
     * Excluding the Root Group
     * INCLUDING any other Direct Parent Groups
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function getAlsoShowProductsProductGroupInclusive()
    {
        $parentIDs = $this->getAlsoShowProducts()->columnUnique('ParentID');
        $parentIDs = ArrayMethods::filter_array($parentIDs);

        $filter = array_diff($parentIDs, [$this->rootGroup->ID]);
        return $this->getParentGroups()
            ->filter(['ID' => $filter]);
    }

    /**
     * List of All Also Show Product Parents Excluding the Root Group
     * AND EXCLUDING any Parent Groups
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function getAlsoShowProductsProductGroupsExclusive()
    {
        $excludeFilter = array_unique(array_merge($this->getParentGroupsIds(), [$this->rootGroup->ID]));
        $excludeFilter = ArrayMethods::filter_array($excludeFilter);

        return $this->getAlsoShowProductsProductGroupInclusive()
            ->exclude(['ID' => $excludeFilter]);
    }

    ##################################################
    # GROUPS: Parents
    ##################################################

    /**
     * This is the inverse of ProductGroupsFromAlsoShowProducts
     *
     * That is, it list the product groups that a product is primarily listed
     * under (exact parents only) from a "AlsoShow" product List.
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function getDirectParentGroupsInclusive()
    {
        return $this->getParentGroups()::get()
            ->filter(['ID' => $this->getParentGroupsIds()]);
    }

    /**
     * With the current product list, return all the {@link ProductGroup}
     * instances that the products are displayed under. This only returns the
     * direct parents.
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function getDirectParentGroupsExclusive(): DataList
    {
        return $this->getDirectParentGroupsInclusive()->exclude(['ID' => $this->getAlsoShowProductsProductGroupsExclusive()]);
    }
}
