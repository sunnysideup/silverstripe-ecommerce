<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Builders;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\SS_List;
use SilverStripe\Versioned\Versioned;

use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Dev\DebugTrait;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\ProductsAndGroups\Template;

abstract class AbstractProductsAndGroupsList
{
    use Configurable;
    use Injectable;
    use Extensible;
    use DebugTrait;

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
    final public function setRootGroup(ProductGroup $rootGroup): self
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
    final public function setProducts($products): self
    {
        $this->products = $products;

        return $this;
    }

    ##########################################
    # PRODUCTS: basics
    ##########################################

    /**
     * Key Method!
     * Returns a raw list of all the matching products without any pagination.
     *
     * @return SS_List
     */
    final public function getProducts()
    {
        return $this->products;
    }

    /**
     * IDs of all the products.
     * @return array
     */
    final public function getProductIds(): array
    {
        return ArrayMethods::filter_array($this->products->columnUnique());
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
    final public function getRawCount(): int
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
    final public function hasMoreThanOne(?int $greaterThan = 1): bool
    {
        return $this->getRawCount() > $greaterThan;
    }


    ##########################################
    # PRODUCTS: Direct from the root group
    ##########################################

    final public function getDirectProducts(): DataList
    {
        return $this->products
            ->filter(['ParentID' => $this->rootGroup->ID]);
    }

    final public function getDirectProductsExclusingAlsoShow(): DataList
    {
        return $this->products
            ->exclude(['ID' => $this->getAlsoShowProductsIds()]);
    }

    final public function getDirectProductsWithAlsoShow(): DataList
    {
        return $this->products
            ->filterAny(['ParentID' => $this->rootGroup->ID, 'ID' => $this->rootGroup->getProductsToBeIncludedFromOtherGroupsArray()]);
    }

    /**
     * @return DataList
     */
    final public function getAlsoShowProductsFromRootGroupExclusive(): DataList
    {
        return $this->getDirectProductsWithAlsoShow()->exclude(['ParentID' => $this->rootGroup->ID]);
    }


    ##########################################
    # PRODUCTS: Children -from hierarchy
    ##########################################

    /**
     * child products (including indirect children)
     * @return DataList
     */
    final public function getChildProductsInclusive(): DataList
    {
        return $this->products
            ->filter(['ParentID' => $this->getParentGroupIds()]);
    }

    /**
     * child products (including indirect children, but also show excluded)
     * @return DataList [description]
     */
    final public function getChildProductsExclusive(): DataList
    {
        return $this->getChildProductsInclusive()
            ->exclude(['ID' => $this->getAlsoShowProductsIds()]);
    }


    ##########################################
    # PRODUCTS: Also show
    ##########################################

    /**
     *
     * @return array
     */
    abstract public function getAlsoShowProductsIds(): array;

    /**
     *
     * @return DataList
     */
    abstract public function getAlsoShowProducts(): DataList;

    /**
     * like getAlsoShowProductsInclusive, but then without the Children from all groups
     * i.e. exclude ones that have one of the groups as Parent.
     * @return DataList [description]
     */
    final public function getAlsoShowProductsExclusive(): DataList
    {
        return $this->getAlsoShowProducts()
            ->exclude(['ParentID' => $this->getParentGroupIds()]);
    }


    ##########################################
    # GROUPS - ALL - based on products
    ##########################################

    final public function getParentGroupIdsBasedOnProducts(): array
    {
        return ArrayMethods::filter_array($this->getParentGroupsBasedOnProducts()->columnUnique());
    }

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
    final public function getParentGroupsBasedOnProducts() :DataList
    {
        return $this->turnIdListIntoProductGroups($this->getProducts()->columnUnique('ParentID'));
    }


    /**
     * Given the products for this page, retrieve the parent groups excluding
     * the current one.
     *
     * @return \SilverStripe\ORM\DataList
     */
    final public function getParentGroupsBasedOnProductsExcludingRootGroup(): DataList
    {
        return $this->getParentGroupsBasedOnProducts()->exclude(['ID' => $this->rootGroup->ID]);
    }


    ##################################################
    # GROUPS: DIRECT
    ##################################################

    /**
     * This is the inverse of ProductGroupsFromAlsoShowProducts
     *
     * That is, it list the product groups that a product is primarily listed
     * under (exact parents only) from a "AlsoShow" product List.
     *
     * @return \SilverStripe\ORM\DataList
     */
    final public function getDirectParentGroupsInclusive(): DataList
    {
        return $this->getParentGroupsBasedOnProducts()->filter(['ParentID' => $this->rootGroup->ID]);
    }

    /**
     * With the current product list, return all the {@link ProductGroup}
     * instances that the products are displayed under. This only returns the
     * direct parents.
     *
     * @return \SilverStripe\ORM\DataList
     */
    final public function getDirectParentGroupsExclusive(): DataList
    {
        return $this->getDirectParentGroupsInclusive()->exclude(['ID' => $this->getAlsoShowProductsProductGroupsExclusive()]);
    }


    ##################################################
    # GROUPS: Parents from natural hierachy
    ##################################################

    /**
     * ids for getParentGroups
     * @var array
     */
    abstract public function getParentGroupIds() : array;

    /**
     * parent groups that come from the natural hierarchy
     * the baselist knows about these
     * @var DataList
     */
    abstract public function getParentGroups() : DataList;


    /**
     * hierarchy parent groups excluding any parent groups that are included in AlsoShow
     * @var DataList
     */
    final public function getParentGroupsExclusive() : DataList
    {
        return $this->getParentGroups()
            ->exclude(['ID' => $this->getAlsoShowParentIds()]);
    }

    ##################################################
    # GROUPS: Also Show Products, based on Products included through AlsoShow Show
    # ie. from all the products, what ProductGroups are related through many-many (e.g. Brands)
    # NOTE: difference with below
    ##################################################

    abstract public function getAlsoShowParentIds(): array;

    abstract public function getAlsoShowParents(): DataList;


    ##################################################
    # GROUPS: Also Show Product Groups Based on Also Show Product ParentIDs
    # i.e. from the Also Show products, what are the natural parents?
    # NOTE: difference with above
    ##################################################

    /**
     * List of All Also Show Product Parents
     * Excluding the Root Group
     * INCLUDING any other Direct Parent Groups
     *
     * @return \SilverStripe\ORM\DataList
     */
    final public function getAlsoShowProductsProductGroupInclusive(): DataList
    {
        return $this->turnIdListIntoProductGroups($this->getAlsoShowProducts()->columnUnique('ParentID'));

    }

    /**
     * List of All Also Show Product Parents Excluding the Root Group
     * AND EXCLUDING any Parent Groups
     *
     * @return \SilverStripe\ORM\DataList
     */
    final public function getAlsoShowProductsProductGroupsExclusive(): DataList
    {
        $excludeFilter = $this->getParentGroupIds();

        return $this->getAlsoShowProductsProductGroupInclusive()
            ->exclude(['ID' => $excludeFilter]);
    }



    ##################################################
    # HELPERS
    ##################################################

    /**
     * @return Template
     */
    protected function getTemplateForProductsAndGroups()
    {
        $obj = $this->rootGroup->getTemplateForProductsAndGroups();
        ClassHelpers::check_for_instance_of($obj, Template::class, true);
        return $obj;
    }


    abstract protected function getSiteTreeTableName() : string;

    abstract protected function getBuyableTableNameName() : string;

    /**
     * Returns a versioned record stage table suffix (i.e "" or "_Live")
     *
     * @return string
     */
    protected function getStage()
    {
        $stage = '';

        if (Versioned::get_stage() === 'Live') {
            $stage = '_Live';
        }

        return $stage;
    }

    protected function turnIdListIntoProductGroups(array $ids) : DataList
    {
        $ids = ArrayMethods::filter_array($ids);

        $groups = ProductGroup::get()->filter(['ID' => $ids]);

        return RelatedProductGroups::apply_default_filter_to_groups($groups);
    }

}
