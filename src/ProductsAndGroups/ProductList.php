<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\ORM\SS_List;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\ViewableData;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * A wrapper for a paginated list of products which can be filtered and sorted.
 *
 * What configuation can be provided
 * 1. levels to show
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @subpackage: Pages
 */
class ProductList extends ViewableData
{



    /**
     * @var SS_List
     */
    protected $products;

    /**
     * @var ProductGroupList
     */
    protected $productGroups;

    /**
     * A list of relevant buyables that can not be purchased and therefore
     * should be excluded.
     *
     * @var int[]
     */
    protected $blockedProductsIds = [];

    /**
     * @param ProductGroup $productGroup
     * @param string       $buyableClass
     */
    public function __construct($productGroup, $buyableClass = Product::class)
    {
        $this
            ->setRootGroup($productGroup)
            ->setBuyableClass($buyableClass)
            ->buildDefaultList();
    }

    /**
    * Set the root {@link ProductGroup} to display the products from.
    * @param ProductGroup $group
    *
    * @return self
    */
    public function setRootGroup(ProductGroup $group): ProductList
    {
        $this->rootGroup = $group;

        return $this;
    }

    /**
    * Override the class of buyable to display. Usually this is limited to
    * `Product` but can be tailored to display specific subclasses.
    *
    * @param string $buyableClass
    *
    * @return self
    */
    public function setBuyableClass(string $buyableClass): ProductList
    {
        $this->buyableClass = $buyableClass;

        return $this;
    }

    protected function buildDefaultList()
    {
        $buyableClass = $this->buyableClass;
        $this->products = $buyableClass::get();

        $this->applyDefaultFilters();

        return $this;
    }


    /**
     * @param int $depth
     *
     * @return self
     */
    public function setLevelOfProductsToShow(int $depth): ProductList
    {
        $this->getProductGroupList()->setLevelOfProductsToShow($depth);

        return $this;
    }



    /**
     * Returns a raw list of all the matching products without any pagination.
     *
     * To retrieve a paginated list, use {@link getPaginatedList()}
     *
     * @return SS_List
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Returns a list of {@link ProductGroup}
     *
     * @return ProductGroupList
     */
    public function getProductGroupList()
    {
        return $this->productGroupList;
    }

    /**
     * @return SilverStripe\ORM\PaginatedList
     */
    public function getPaginatedList(): PaginatedList
    {
        return PaginatedList::create($this->products);
    }

    /**
     * Returns the total number of products available before pagination is
     * applied.
     *
     * @return int
     */
    public function getRawCount()
    {
        return count($this->products);
    }

    /**
     * Filter the list of products
     *
     * @param array|string $filter
     *
     * @return self
     */
    public function applyFilter($filter = null): ProductList
    {
        if (is_array($filter) && count($filter)) {
            $this->products = $this->products->filter($filter);
        } elseif ($filter) {
            $this->products = $this->products->where(Convert::raw2sql($filter));
        }

        return $this;
    }

    /**
     * @return self
     */
    public function applyDefaultFilters(): ProductList
    {
        if (EcommerceConfig::inst()->OnlyShowProductsThatCanBePurchased) {
            $this->products = $this->products->filter([
                'AllowPurchase' => 1,
            ]);
        }

        $this->extend('onAfterApplyDefaultFilters');

        return $this;
    }

    /**
     * Sort the list of products
     *
     * @param array|string $sort
     *
     * @return self
     */
    public function applySort($sort = null): ProductList
    {
        if (is_array($sort) && count($sort)) {
            $this->products = $this->products->sort($sort);
        } elseif ($sort) {
            $this->products = $this->products->sort(Convert::raw2sql($sort));
        }
        // @todo

        return $this;
    }

    /**
     * Generate Excluded products that can not be purchased.
     *
     * We all make a record of all the products that are in the current list
     * For efficiency sake, we do both these things at the same time.
     *
     * @return self
     */
    public function removeExcludedProducts(): ProductList
    {
        foreach ($this->products as $buyable) {
            if (! $buyable->canPurchase()) {
                $this->blockedProductsIds[] = $buyable->ID;
            }
        }

        if ($this->blockedProductsIds) {
            $this->products->exclude([
                'ID' => $this->blockedProductsIds,
            ]);
        }

        return $this;
    }

    /**
     * Is there more than x products.
     *
     * @param int $greaterThan
     *
     * @return bool
     */
    public function CountGreaterThanOne($greaterThan = 1)
    {
        return $this->getRawCount() > $greaterThan;
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
            return PaginatedList::create(ProductGroup::get()->filter([
                'ID' => $ids,
            ]));
        }
    }

    /**
     * @SEE: important notes at the top of this file / class
     *
     * IMPORTANT: Adjusts allProducts and returns it...
     *
     * @return \SilverStripe\ORM\DataList
     */
    protected function getGroupFilter()
    {
        $levelToShow = $this->MyLevelOfProductsToShow();
        $cacheKey = 'GroupFilter_' . abs(intval($levelToShow + 999));
        if ($groupFilter = $this->retrieveObjectStore($cacheKey)) {
            $this->allProducts = $this->allProducts->where($groupFilter);
        } else {
            $groupFilter = '';
            $productFilterArray = [];
            //special cases
            if ($levelToShow < 0) {
                //no produts but if LevelOfProductsToShow = -1 then show all
                $groupFilter = ' (' . $levelToShow . ' = -1) ';
            } elseif ($levelToShow > 0) {
                $groupIDs = [$this->ID => $this->ID];
                $productFilterTemp = $this->getProductsToBeIncludedFromOtherGroups();
                $productFilterArray[$productFilterTemp] = $productFilterTemp;
                $childGroups = $this->ChildGroups($levelToShow);
                if ($childGroups && $childGroups->count()) {
                    foreach ($childGroups as $childGroup) {
                        $groupIDs[$childGroup->ID] = $childGroup->ID;
                        $productFilterTemp = $childGroup->getProductsToBeIncludedFromOtherGroups();
                        $productFilterArray[$productFilterTemp] = $productFilterTemp;
                    }
                }
                $groupFilter = ' ( "ParentID" IN (' . implode(',', $groupIDs) . ') ) ' . implode($productFilterArray) . ' ';
            } else {
                //fall-back
                $groupFilter = '"ParentID" < 0';
            }
            $this->allProducts = $this->allProducts->where($groupFilter);
            $this->saveObjectStore($groupFilter, $cacheKey);
        }

        return $this->allProducts;
    }

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

    /**
     * If products are show in more than one group yhen this returns a where phrase for any products that are linked to this
     * product group.
     *
     * @return string
     */
    protected function getProductsToBeIncludedFromOtherGroups()
    {
        //TO DO: this should actually return
        //Product.ID = IN ARRAY(bla bla)
        $array = [];
        if ($this->getProductsAlsoInOtherGroups()) {
            $array = $this->AlsoShowProducts()->map('ID', 'ID')->toArray();
        }
        if (count($array)) {
            return ' OR ("Product"."ID" IN (' . implode(',', $array) . ')) ';
        }

        return '';
    }

    protected function getConfigOptionsObject()
    {
        $class = Config::inst()->get($this->rootGroup->ClassName, 'product_list_options_class');

        return Injector::inst()->get($class);
    }


    /**
     *@todo: temporary method
     */
    public function getProductIds()
    {
        return $this->products->column('ID');
    }

    /**
     * Returns children ProductGroup pages of this group.
     *
     * @param int            $maxRecursiveLevel  - maximum depth , e.g. 1 = one level down - so no Child Child Groups are returned...
     * @param string | Array $filter             - additional filter to be added
     *
     * @return \SilverStripe\ORM\ArrayList (ProductGroups)
     */
    public function getGroupsRecursive(int $maxRecursiveLevel, $filter = null) : ArrayList
    {
        return $this->getProductList()->getGroupsRecursive($maxRecursiveLevel, $filter);
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
        $productGroups = $this->getProductList($this->getMyUserPreferencesDefault('FILTER'))
            ->getProducts()
            ->column('ParentID');

        if ($productGroups) {
            return ProductGroup::get()->filter([
                'ID' => $productGroups,
                'ShowInSearch' => 1,
            ])->exclude([
                'ID' => $this->ID,
            ]);
        }
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
        $alsoShowProductsArray = $this->AlsoShowProducts()
            ->filter($this->getUserSettingsOptionSQL('FILTER', $this->getMyUserPreferencesDefault('FILTER')))
            ->map('ID', 'ID')->toArray();

        if ($alsoShowProductsArray) {
            $parentIDs = Product::get()->filter([
                'ID' => $alsoShowProductsArray,
            ])->map('ParentID', 'ParentID')->toArray();

            if ($parentIDs) {
                return ProductGroup::get()->filter([
                    'ID' => $parentIDs,
                    'ShowInMenus' => 1,
                ])->exclude([
                    'ID' => $this->ID,
                ]);
            }
        }
    }

    /**
     * Given the products for this page, retrieve the parent groups excluding
     * the current one.
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function getProductGroupsParentGroups(): DataList
    {
        $list = $this->getProductList($this->getMyUserPreferencesDefault('FILTER'));

        return $list->getParentGroups()->exclude(['ID' => $this->ID]);
    }


}
