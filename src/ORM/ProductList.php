<?php

namespace Sunnysideup\Ecommerce\ORM;

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
     * @var string
     */
    protected $buyableClass = Product::class;

    /**
     * A list of relevant buyables that can not be purchased and therefore
     * should be excluded.
     *
     * @var int[]
     */
    protected $blockedProductsIds = [];

    /**
     * Root group to pull products from
     */

    /**
     * @param string $buyableClass
     */
    public function __construct($buyableClass = Product::class)
    {
        $this->setBuyableClass($buyableClass);
    }

    /**
     * @return string
     */
    public function getBuyableClass(): string
    {
        return $this->buyableClass;
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
        $this->products = $buyableClass::get();
        $this->productGroups = ProductGroupList::create();

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
        $this->productGroups->setMaxDepth($depth);

        return $this;
    }

    /**
     * Set the root {@link ProductGroup} to display the products from.
     * @param ProductGroup $group
     *
     * @return self
     */
    public function setRootGroup(ProductGroup $group): ProductList
    {
        $this->productGroups->setRootGroup($group);

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
    public function getProductGroups()
    {
        return $this->productGroups;
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

    /**
     *@todo: temporary method
     */
    public function getProductIds()
    {
        return $this->products->column('ID');
    }
}
