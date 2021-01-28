<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Builders;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\SS_List;

use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\ProductsAndGroups\Template;

abstract class AbstractProductsAndGroupsList
{
    use Configurable;
    use Injectable;
    use Extensible;

    ##########################################
    # PRODUCTS: basics
    ##########################################

    /**
     * @var SS_List|null
     */
    protected $products = null;

    /**
     * @var ProductGroup|null
     */
    protected $rootGroup = null;

    /**
     * Set the root {@link ProductGroup} to display the products from.
     * @param ProductGroup $rootGroup
     *
     * @return self
     */
    public function setRootGroup(ProductGroup $rootGroup): self
    {
        $this->rootGroup = $rootGroup;
        ClassHelpers::check_for_instance_of($rootGroup, ProductGroup::class, true);

        return $this;
    }

    /**
     * Key Method!
     * Returns a raw list of all the matching products without any pagination.
     *
     * @return SS_List
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Key Method!
     * Returns a raw list of all the matching products without any pagination.
     *
     * @return SS_List
     */
    public function setProducts($products): self
    {
        $this->products = $products;

        return $this;
    }

    /**
     * IDs of all the products.
     * @return array
     */
    public function getProductIds() : array
    {
        return ArrayMethods::filter_array($this->products->columnUnique());
    }

    abstract public function getAlsoShowProductsIds(): array;

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
    public function hasMoreThanOne(?int $greaterThan = 1): bool
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

    public function getDirectProductsWithAlsoShow(): DataList
    {
        return $this->products
            ->filter(['ParentID' => $this->rootGroup->ID]);
    }

    ##########################################
    # PRODUCTS: Children
    ##########################################

    /**
     * child products (including indirect children)
     * @return DataList
     */
    public function getChildProductsInclusive(): DataList
    {
        return $this->products
            ->filter(['ParentID' => $this->getParentGroupIds()]);
    }

    /**
     * child products (including indirect children, but also show excluded)
     * @return DataList [description]
     */
    public function getChildProductsExclusive(): DataList
    {
        return $this->products
            ->exclude(['ID' => $this->getAlsoShowProductsIds()]);
    }

    ##########################################
    # PRODUCTS: Also show
    ##########################################

    /**
     * all Also Show products
     * @return DataList
     */
    public function getAlsoShowProductsFromRootGroupOnly(): DataList
    {
        return $this->rootGroup->AlsoShowProducts()
            ->filter(['ID' => $this->getProducts()->columnUnique()]);
    }

    /**
     * products from Also Show from all product groups
     * @return DataList
     */
    public function getAlsoShowProductsInclusive(): DataList
    {
        return $this->products
            ->filter(['ID' => $this->getAlsoShowProductsIds()]);
    }

    /**
     * like getAlsoShowProductsInclusive, but then without the Children from all groups
     * i.e. exclude ones that have one of the groups as Parent.
     * @return DataList [description]
     */
    public function getAlsoShowProductsExclusive(): DataList
    {
        return $this->getAlsoShowProductsInclusive()
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

        return RelatedProductGroups::apply_default_filter_to_groups($groups);
    }

    public function getParentGroupIds() : array
    {
        return ArrayMethods::filter_array($this->getParentGroups()->columnUnique());
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
    public function getAlsoShowProductsProductGroupInclusive() : DataList
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
    public function getAlsoShowProductsProductGroupsExclusive() : DataList
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
    public function getDirectParentGroupsInclusive() : DataList
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

    /**
     * @return Template
     */
    protected function getTemplateForProductsAndGroups()
    {
        $obj = $this->rootGroup->getTemplateForProductsAndGroups();
        ClassHelpers::check_for_instance_of($obj, Template::class, true);
        return $obj;
    }
}
