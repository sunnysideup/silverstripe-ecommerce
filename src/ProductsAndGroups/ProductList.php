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
use Sunnysideup\Ecommerce\Api\EcommerceCache;
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
    protected $buyableClass = '';

    /**
     * @var SS_List
     */
    protected $products = null;

    /**
     * @var ProductGroupList
     */
    protected $productGroupListProvider = null;

    /**
     * @var ProductListOptions
     */
    protected $productListOptionsProvider = null;

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
    protected $childGroupsIds = [];

    /**
     * @param ProductGroup $rootGroup
     * @param string       $buyableClass
     */
    public function __construct($rootGroup, ?string $buyableClass = '', ?int $levelOfProductsToShow = 0)
    {
        if(! $levelOfProductsToShow) {
            $levelOfProductsToShow = $rootGroup->getLevelOfProductsToShow();
        }
        if(! $buyableClass) {
            $buyableClass = $rootGroup->getBuyableClassName();
        }
        $this
            //set defaults
            ->setRootGroup($rootGroup)
            ->setBuyableClass($buyableClass)
            ->setLevelOfProductsToShow($levelOfProductsToShow);
        if($this->hasCache()) {
            $this->loadCache();
        } else {
            $this
                //create list
                ->buildDefaultList()
                ->applyDefaultFilters()
                ->applyGroupFilter()
                ->removeExcludedProducts()
                ->storeInCache();
        }
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


    public function getProductListOptionsProvider()
    {
        if(! $this->productListOptionsProvider) {
            $class = Config::inst()->get($this->rootGroup->ClassName, 'product_list_options_class');

            $this->productListOptionsProvider = Injector::inst()->get($class, $this->rootGroup);
        }

        return $this->productListOptionsProvider;
    }


    public function getProductListConfigDefaultValue(string $type): string
    {
        return $this->getProductListOptionsProvider()->getProductListConfigDefaultValue($type);
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
        $this->products = $this->products->filter($this->Config()->get('default_product_filter'));

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
        $this->childGroupIds = [];
        //special cases
        if ($levelToShow < 0) {
            //no produts but if LevelOfProductsToShow = -1 then show all
            //note the smartness here -1 == 1 || -1 == -2
            $groupFilter = ' (' . $levelToShow . ' = -1) ';
        } elseif ($levelToShow === 0) {
            $groupFilter = '"ParentID" < 0';
        } else {
            $this->childGroupIds[$this->rootGroup->ID] = $this->rootGroup->ID;
            $this->alsoShowProductsIds = array_merge(
                $this->alsoShowProductsIds,
                $this->rootGroup->getProductsToBeIncludedFromOtherGroupsArray()
            );
            $childGroups = $this->rootGroup->childGroupIds();
            if ($childGroups && $childGroups->count()) {
                foreach ($childGroups as $childGroup) {
                    $this->childGroupIds[$childGroup->ID] = $childGroup->ID;
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
        if (EcommerceConfig::inst()->OnlyShowProductsThatCanBePurchased) {
            foreach ($this->products as $buyable) {
                if (! $buyable->canPurchase()) {
                    $this->blockedProductsIds[$buyable->ID] = $buyable->ID;
                }
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


    protected function hasCache() : bool
    {
        return EcommerceCache::inst()->get($this->getCachekey());
    }


    protected function loadCache()
    {
        $productIds = EcommerceCache::inst()->retrieve($this->getCachekey());
        if(empty($productIds) || ! is_array($productIds)) {
            $productIds = [-1 => -1,];
        }
        $this->buildDefaultList();
        $this->products = $this->products->filter(['ID' => $productIds]);
    }

    protected function storeInCache()
    {
        EcommerceCache::inst()->retrieve($this->getCachekey(), $this->products->column('ID'));
    }

    protected function getCachekey() : string
    {
        return implode(
            '_',
            [
                $this->rootGroup->ID,
                $this->buyableClass,
                $this->getProductGroupListProvider()->getLevelOfProductsToShow(),
            ]
        );
    }


}
