<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\DataList;
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
     * default filter for products: show in search and allow purchase are recommended.
     * @var array
     */
    private static $default_product_filter =  ['AllowPurchase' => 1, 'ShowInSearch' => 1];

    /**
     * default filter
     * @var array
     */
    private static $default_product_group_filter =  ['ShowInSearch' => 1];

    /**
     * @var string
     */
    protected $buyableClass;

    /**
     * @var SS_List
     */
    protected $products;

    /**
     * @var ProductGroupList
     */
    protected $productGroupListProvider;

    /**
     * @var ProductListOptions
     */
    protected $productListOptionsProvider;

    /**
     * A list of relevant buyables that can not be purchased and therefore
     * should be excluded.
     *
     * @var int[]
     */
    protected $blockedProductsIds = [];

    /**
     *
     * @var int[]
     */
    protected $alsoShowProductsIds = [];
    /**
     *
     * @var int[]
     */
    protected $childGroups = [];

    /**
     * @param ProductGroup $rootGroup
     * @param string       $buyableClass
     */
    public function __construct($rootGroup, $buyableClass = Product::class)
    {
        $this
            ->setRootGroup($rootGroup)
            ->setBuyableClass($buyableClass)
            ->setLevelOfProductsToShow($rootGroup->getLevelOfProductsToShow())
            ->buildDefaultList()
            ->applyDefaultFilters()
            ->applyGroupFilter()
            ->removeExcludedProducts()
            ->storeInCache();
    }

    /**
    * Set the root {@link ProductGroup} to display the products from.
    * @param ProductGroup $group
    *
    * @return self
    */
    public function setRootGroup(ProductGroup $rootGroup): ProductList
    {
        $this->rootGroup = $rootGroup;

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



    /**
     * @param int $levelOfProductsToShow
     *
     * @return self
     */
    public function setLevelOfProductsToShow(int $levelOfProductsToShow): ProductList
    {
        $this->getProductGroupList()->setLevelOfProductsToShow($levelOfProductsToShow);

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
     * Is there more than x products.
     *
     * @param int $greaterThan
     *
     * @return bool
     */
    public function CountGreaterThanOne($greaterThan = 1) : bool
    {
        return $this->getRawCount() > $greaterThan;
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


    public function getProductListOptionsProvider()
    {
        if(! $this->productListOptionsProvider) {
            $class = Config::inst()->get($this->rootGroup->ClassName, 'product_list_options_class');

            $this->productListOptionsProvider = Injector::inst()->get($class, $this->rootGroup);
        }

        return $this->productListOptionsProvider;
    }

    /**
     * Returns a list of {@link ProductGroupList}
     *
     * @return ProductGroupList
     */
    public function getProductGroupListProvider()
    {
        if(! $this->productGroupListProvider) {
            $class = Config::inst()->get($this->rootGroup->ClassName, 'product_group_list_class');

            $this->productGroupListProvider = Injector::inst()->get($class, $this->rootGroup);
        }

        return $this->productGroupListProvider;
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
     * @param string|array   $filter             - additional filter to be added
     *
     * @return \SilverStripe\ORM\ArrayList (ProductGroups)
     */
    public function getGroups(int $maxRecursiveLevel, $filter = null) : ArrayList
    {
        return $this->getProductGroupListProvider()->getGroups($maxRecursiveLevel, $filter);
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
            ['ID' => $productGroupIds,],
            $this->Config()->get('default_product_group_filter'),
        );
        return ProductGroup::get()
            ->filter($filter)
            ->exclude(['ID' => $this->childGroups,]);
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
        return ProductGroup::get()
            ->filter(['ID' => $this->childGroups, 'ShowInSearch' => 1,]);

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
        return ProductGroup::get()
            ->filter(['ID' => $productGroupIds, 'ShowInSearch' => 1,]);
    }

    public function getProductListConfigDefaultValue(string $type): string
    {
        return $this->getProductListOptionsProvider()->getProductListConfigDefaultValue($type);
    }



    protected function buildDefaultList()
    {
        $buyableClass = $this->buyableClass;
        $this->products = $buyableClass::get();

        return $this;
    }

    /**
     * @return self
     */
    protected function applyDefaultFilters(): ProductList
    {
        if (EcommerceConfig::inst()->OnlyShowProductsThatCanBePurchased) {
            $this->products = $this->products->filter($this->Config()->get('default_product_filter'));
        }

        return $this;
    }

    /**
     * @SEE: important notes at the top of this file / class
     *
     * IMPORTANT: Adjusts allProducts and returns it...
     *
     * @return self
     */
    protected function applyGroupFilter() : ProductList
    {
        $levelToShow = $this->getProductGroupListProvider()->getLevelOfProductsToShow();
        $groupFilter = '';
        $this->alsoShowProductsIds = [];
        $this->childGroups = [];
        //special cases
        if ($levelToShow < 0) {
            //no produts but if LevelOfProductsToShow = -1 then show all
            //note the smartness here -1 == 1 || -1 == -2
            $groupFilter = ' (' . $levelToShow . ' = -1) ';
        } elseif ($levelToShow === 0) {
            $groupFilter = '"ParentID" < 0';
        } else {
            $this->childGroups[$this->rootGroup->ID] = $this->rootGroup->ID;
            $this->alsoShowProductsIds = array_merge(
                $this->alsoShowProductsIds,
                $this->rootGroup->getProductsToBeIncludedFromOtherGroupsArray()
            );
            $childGroups = $this->rootGroup->ChildGroups();
            if ($childGroups && $childGroups->count()) {
                foreach ($childGroups as $childGroup) {
                    $this->childGroups[$childGroup->ID] = $childGroup->ID;
                    $this->alsoShowProductsIds = array_merge(
                        $this->alsoShowProductsIds,
                        $childGroup->getProductsToBeIncludedFromOtherGroupsArray()
                    );
                }
            }
            $obj = Injector::inst()->get($this->buyableClass);
            $tablename = $obj->baseTable().$this->getStage();
            $siteTreeTable = 'SiteTree'.$this->getStage();
            $groupFilter = '
                "'.$siteTreeTable.'"."ParentID" IN (' . implode(',', $groupIDs) . ')
                OR
                "'.$tableName.'"."ID" IN (' . implode($this->alsoShowProductsIds) . ')
            ';
        }
        $this->products = $this->products->where($groupFilter);

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
    protected function removeExcludedProducts(): ProductList
    {
        foreach ($this->products as $buyable) {
            if (! $buyable->canPurchase()) {
                $this->blockedProductsIds[$buyable->ID] = $buyable->ID;
            }
        }

        if (! empty($this->blockedProductsIds)) {
            $this->products = $this->products
                ->exclude(['ID' => $this->blockedProductsIds,]);
        }

        return $this;
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


}
