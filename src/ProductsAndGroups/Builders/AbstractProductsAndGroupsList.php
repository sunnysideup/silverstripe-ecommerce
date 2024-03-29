<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Builders;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\SS_List;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Api\EcommerceCache;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\BaseApplyer;
use Sunnysideup\Ecommerce\ProductsAndGroups\ProductGroupSchema;
use Sunnysideup\Vardump\DebugTrait;

abstract class AbstractProductsAndGroupsList
{
    use Configurable;
    use Injectable;
    use Extensible;
    use DebugTrait;

    //#########################################
    // PRODUCTS: basics
    //#########################################

    /**
     * @var DataList
     */
    protected $products;

    /**
     * @var ProductGroup
     */
    protected $rootGroup;

    /**
     * Set the root {@link ProductGroup} to display the products from.
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
     * @param SS_List $products
     */
    final public function setProducts($products): self
    {
        $this->products = $products;

        return $this;
    }

    //#########################################
    // PRODUCTS: basics
    //#########################################

    /**
     * @return ProductGroup
     */
    final public function getRootGroup()
    {
        return $this->rootGroup;
    }

    /**
     * Key Method!
     * Returns a raw list of all the matching products without any pagination.
     *
     * @return DataList
     */
    final public function getProducts()
    {
        return $this->products;
    }

    /**
     * IDs of all the products.
     * count how many times this is called.
     *
     * @todo: EcommerceCache candidate
     */
    final public function getProductIds(): array
    {
        return ArrayMethods::filter_array($this->products->columnUnique());
    }

    //#########################################
    // PRODUCTS: Counts
    //#########################################

    /**
     * Returns the total number of products available before pagination is
     * applied.
     *
     * @todo: EcommerceCache candidate
     */
    final public function getRawCount(): int
    {
        return $this->products->count();
    }

    /**
     * Is there more than x products.
     *
     * @param int $greaterThan
     */
    final public function hasMoreThanOne(?int $greaterThan = 1): bool
    {
        if ($this->hasMethod('getRawCountCached')) {
            return $this->getRawCountCached() > $greaterThan;
        }

        return $this->getRawCount() > $greaterThan;
    }

    //#########################################
    // PRODUCTS: Direct from the root group
    //#########################################

    final public function getDirectProducts(): DataList
    {
        return $this->products
            ->filter(['ParentID' => $this->rootGroup->ID])
        ;
    }

    final public function getDirectProductsExclusingAlsoShow(): DataList
    {
        return $this->products
            ->exclude(['ID' => $this->getAlsoShowProductsIds()])
        ;
    }

    final public function getDirectProductsWithAlsoShow(): DataList
    {
        return $this->products
            ->filterAny(['ParentID' => $this->rootGroup->ID, 'ID' => $this->rootGroup->getProductsToBeIncludedFromOtherGroupsArray()])
        ;
    }

    final public function getAlsoShowProductsFromRootGroupExclusive(): DataList
    {
        return $this->getDirectProductsWithAlsoShow()->exclude(['ParentID' => $this->rootGroup->ID]);
    }

    //#########################################
    // PRODUCTS: Children -from hierarchy
    //#########################################

    /**
     * child products (including indirect children).
     */
    final public function getChildProductsInclusive(): DataList
    {
        return $this->products
            ->filter(['ParentID' => $this->getParentGroupIds()])
        ;
    }

    /**
     * child products (including indirect children, but also show excluded).
     */
    final public function getChildProductsExclusive(): DataList
    {
        return $this->getChildProductsInclusive()
            ->exclude(['ID' => $this->getAlsoShowProductsIds()])
        ;
    }

    //#########################################
    // PRODUCTS: Also show
    //#########################################

    /**
     * @todo: EcommerceCache candidate
     */
    abstract public function getAlsoShowProductsIds(): array;

    abstract public function getAlsoShowProducts(): DataList;

    /**
     * like getAlsoShowProductsInclusive, but then without the Children from all groups
     * i.e. exclude ones that have one of the groups as Parent.
     */
    final public function getAlsoShowProductsExclusive(): DataList
    {
        return $this->getAlsoShowProducts()
            ->exclude(['ParentID' => $this->getParentGroupIds()])
        ;
    }

    //#########################################
    // GROUPS - smart
    //#########################################

    /**
     * @todo: EcommerceCache candidate
     */
    abstract public function getFilterForCandidateCategoryIds(): array;

    abstract public function getFilterForCandidateCategories(): DataList;

    //#########################################
    // GROUPS - ALL - based on products
    //#########################################

    /**
     * @todo: EcommerceCache candidate
     */
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
     */
    final public function getParentGroupsBasedOnProducts(): DataList
    {
        return $this->turnIdListIntoProductGroups($this->getProducts()->columnUnique('ParentID'));
    }

    /**
     * Given the products for this page, retrieve the parent groups excluding
     * the current one.
     */
    final public function getParentGroupsBasedOnProductsExcludingRootGroup(): DataList
    {
        return $this->getParentGroupsBasedOnProducts()->exclude(['ID' => $this->rootGroup->ID]);
    }

    //#################################################
    // GROUPS: DIRECT
    //#################################################

    /**
     * This is the inverse of ProductGroupsFromAlsoShowProducts.
     *
     * That is, it list the product groups that a product is primarily listed
     * under (exact parents only) from a "AlsoShow" product List.
     */
    final public function getDirectParentGroupsInclusive(): DataList
    {
        return $this->getParentGroupsBasedOnProducts()->filter(['ParentID' => $this->rootGroup->ID]);
    }

    /**
     * With the current product list, return all the {@link ProductGroup}
     * instances that the products are displayed under. This only returns the
     * direct parents.
     */
    final public function getDirectParentGroupsExclusive(): DataList
    {
        return $this->getDirectParentGroupsInclusive()->exclude(['ID' => $this->getAlsoShowProductsProductGroupsExclusive()]);
    }

    //#################################################
    // GROUPS: Parents from natural hierachy
    //#################################################

    /**
     * ids for getParentGroups.
     *
     * @todo: EcommerceCache candidate
     */
    abstract public function getParentGroupIds(): array;

    /**
     * parent groups that come from the natural hierarchy
     * the baselist knows about these.
     */
    abstract public function getParentGroups(): DataList;

    /**
     * hierarchy parent groups excluding any parent groups that are included in AlsoShow.
     */
    final public function getParentGroupsExclusive(): DataList
    {
        return $this->getParentGroups()
            ->exclude(['ID' => $this->getAlsoShowParentIds()])
        ;
    }

    //#################################################
    // GROUPS: Also Show Products, based on Products included through AlsoShow Show
    // ie. from all the products, what ProductGroups are related through many-many (e.g. Brands)
    // NOTE: difference with below
    //#################################################

    /**
     * @todo: EcommerceCache candidate
     */
    abstract public function getAlsoShowParentIds(): array;

    abstract public function getAlsoShowParents(): DataList;

    //#################################################
    // GROUPS: Also Show Product Groups Based on Also Show Product ParentIDs
    // i.e. from the Also Show products, what are the natural parents?
    // NOTE: difference with above
    //#################################################

    /**
     * List of All Also Show Product Parents
     * Excluding the Root Group
     * INCLUDING any other Direct Parent Groups.
     */
    final public function getAlsoShowProductsProductGroupInclusive(): DataList
    {
        return $this->turnIdListIntoProductGroups($this->getAlsoShowProducts()->columnUnique('ParentID'));
    }

    /**
     * List of All Also Show Product Parents Excluding the Root Group
     * AND EXCLUDING any Parent Groups.
     */
    final public function getAlsoShowProductsProductGroupsExclusive(): DataList
    {
        $excludeFilter = $this->getParentGroupIds();

        return $this->getAlsoShowProductsProductGroupInclusive()
            ->exclude(['ID' => $excludeFilter])
        ;
    }

    public function getApplyerClassName(string $type): string
    {
        return $this->getProductGroupSchema()->getApplyerClassName($type);
    }

    /**
     * @return BaseApplyer
     */
    public function getApplyer(string $classNameOrType)
    {
        return $this->getProductGroupSchema()
            ->getApplyer($classNameOrType, $this)
        ;
    }

    //#################################################
    // HELPERS
    //#################################################

    /**
     * @return ProductGroupSchema
     */
    protected function getProductGroupSchema()
    {
        $obj = $this->rootGroup->getProductGroupSchema();
        ClassHelpers::check_for_instance_of($obj, ProductGroupSchema::class, true);

        return $obj;
    }

    final protected function getBuyableTableBaseName(?string $baseClass = SiteTree::class): string
    {
        $singleton = DataObject::singleton($baseClass);
        $baseTable = $singleton->baseTable();
        return $singleton->stageTable($baseTable, Versioned::get_stage());
    }

    protected function turnIdListIntoProductGroups(array $ids, ?bool $useFilterParent = false): DataList
    {
        $ids = ArrayMethods::filter_array($ids);

        $groups = ProductGroup::get()->filter(['ID' => $ids]);
        // we need a way to find the FilterParent, which may be the parent of the
        // group listed.
        if ($useFilterParent) {
            $newArray = [];
            foreach ($groups as $group) {
                $filterParent = $group->MyFilterParent();
                if ($filterParent) {
                    $newArray[] = $filterParent->ID;
                }
            }
            $newGroups = ProductGroup::get()->filter(['ID' => ArrayMethods::filter_array($newArray)]);
        }

        return RelatedProductGroups::apply_default_filter_to_groups($groups);
    }
}
