<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Builders;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\SS_List;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Api\EcommerceCache;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * The starting base of the Products
 *
 * This is basically a list of products for a product group where we take into consider:
 *
 * a. sub-groups
 * b. default filters (e.g. only show if AllowPurchase is true)
 *
 * Most of the time, you do not need to use this class at all, because the FinalProductList class
 * creates it for you and a FinalProductList is basically like this list but then ready to apply filters and sorts.
 *
 * That is, a BaseProduct List CAN NOT BE CHANGE
 * A final Product list is ALWAYS filtered and sorted.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @subpackage: Pages
 */
class BaseProductList extends AbstractProductsAndGroupsList
{
    /**
     * keep the lists in memory
     * @var array
     */
    protected static $singleton_caches = [];

    /**
     * @var string
     */
    protected $buyableClassName = Product::class;

    /**
     * @var RelatedProductGroups
     */
    protected $productGroupListProvider = null;

    /**
     * A list of relevant buyables that can not be purchased and therefore
     * should be excluded.
     *
     * @var int[]
     */
    protected $blockedProductsIds = [];

    /**
     * @var int[]
     */
    protected $alsoShowProductsIds = [];

    /**
     * @var int[]
     */
    protected $parentGroupIds = [];

    /**
     * @var int[]
     */
    protected static $excluded_products = [];

    /**
     * @var int[]
     */
    protected static $checked_products = [];

    /**
     * default filter for products: show in search and allow purchase are recommended.
     * @var array'
     */
    private static $default_product_filter = [
        'AllowPurchase' => 1,
    ];

    /**
     * @param ProductGroup $rootGroup
     * @param string       $buyableClassName
     */
    public function __construct($rootGroup, ?string $buyableClassName = '', ?int $levelOfProductsToShow = 0)
    {
        if (! $buyableClassName) {
            $buyableClassName = $rootGroup->getBuyableClassName();
        }
        if (! $levelOfProductsToShow) {
            $levelOfProductsToShow = $rootGroup->getLevelOfProductsToShow();
        }
        $this
            //set defaults
            ->setRootGroup($rootGroup)
            ->setBuyableClassName($buyableClassName)
            ->setLevelOfProductsToShow($levelOfProductsToShow);
        if ($this->hasCache()) {
            $this->loadCache();
        } else {
            $this->buildDefaultList()
                ->applyDefaultFilters()
                ->applyGroupFilter()
                ->removeExcludedProducts()
                ->storeInCache();
        }
    }

    public static function apply_default_filter_to_products($list): SS_List
    {
        $filter = Config::inst()->get(self::class, 'default_product_filter');

        return $list->filter($filter);
    }

    public static function inst($rootGroup, ?string $buyableClassName = '', ?int $levelOfProductsToShow = 0)
    {
        $cacheKey = implode('_', array_filter([$rootGroup->ID, $rootGroup->ClassName, $buyableClassName, $levelOfProductsToShow]));
        if (! isset(self::$singleton_caches[$cacheKey])) {
            self::$singleton_caches[$cacheKey] = new BaseProductList($rootGroup, $buyableClassName, $levelOfProductsToShow);
        }

        return self::$singleton_caches[$cacheKey];
    }

    /**
     * Override the class of buyable to display. Usually this is limited to
     * `Product` but can be tailored to display specific subclasses.
     *
     * @param string $buyableClassName
     *
     * @return self
     */
    public function setBuyableClassName(string $buyableClassName): self
    {
        $this->buyableClassName = $buyableClassName;

        return $this;
    }

    /**
     * @return string
     */
    public function getBuyableClassName(): string
    {
        return $this->buyableClassName;
    }

    /**
     * @return array
     */
    public function getShowProductLevelsArray(): array
    {
        return $this->getProductGroupListProvider()->getShowProductLevelsArray();
    }

    /**
     * @param int $levelOfProductsToShow
     *
     * @return self
     */
    public function setLevelOfProductsToShow(int $levelOfProductsToShow): self
    {
        $this->getProductGroupListProvider()->setLevelOfProductsToShow($levelOfProductsToShow);

        return $this;
    }

    /**
     * how many children, grand-children, etc.. levels do we provide?
     * @return int
     */
    public function getLevelOfProductsToShow(): int
    {
        return $this->getProductGroupListProvider()->getLevelOfProductsToShow();
    }

    /**
     * @return array
     */
    public function getParentGroupIds(): array
    {
        return ArrayMethods::filter_array($this->parentGroupIds);
    }

    /**
     * @return array
     */
    public function getAlsoShowProductsIds(): array
    {
        return ArrayMethods::filter_array($this->alsoShowProductsIds);
    }

    /**
     * @return SS_List
     */
    public function getAlsoShowProducts(): DataList
    {
        return $this->products->filter(['ID' => $this->getAlsoShowProductsIds()]);
    }

    /**
     * Returns children ProductGroup pages of this group.
     *
     * @param int            $maxRecursiveLevel  - maximum depth , e.g. 1 = one level down - so no Child Child Groups are returned...
     * @param string|array   $filter             - additional filter to be added
     *
     * @return SS_List (ProductGroups)
     */
    public function getGroups(?int $maxRecursiveLevel = null, $filter = null)
    {
        if ($maxRecursiveLevel === null) {
            $maxRecursiveLevel = $this->getLevelOfProductsToShow();
        }
        $list = $this->getProductGroupListProvider()->getGroups($maxRecursiveLevel, $filter);
        ClassHelpers::check_for_instance_of($list, SS_List::class);

        return $list;
    }

    /**
     * Returns a list of {@link RelatedProductGroups}
     *
     * @return RelatedProductGroups
     */
    public function getProductGroupListProvider()
    {
        if (! $this->productGroupListProvider) {
            $className = $this->rootGroup->getTemplateForProductsAndGroups()->getProductGroupListClassName();
            //note, CAN NOT BE A SINGLETON if we want to pass it variables!
            $this->productGroupListProvider = Injector::inst()->get($className, false, [$this->rootGroup]);
        }

        return $this->productGroupListProvider;
    }

    public function getExcludedProducts(): array
    {
        return self::$excluded_products;
    }

    public function getBlockedProductIds(): array
    {
        return $this->blockedProductsIds;
    }

    /**
     * create a starting point
     */
    protected function buildDefaultList(): self
    {
        $buyableClassName = $this->buyableClassName;
        $this->products = $buyableClassName::get();

        return $this;
    }

    /**
     * add default filters.
     */
    protected function applyDefaultFilters(): self
    {
        $this->products = self::apply_default_filter_to_products($this->products);

        return $this;
    }

    /**
     * apply group filters to products
     */
    protected function applyGroupFilter(): self
    {
        $levelToShow = $this->getLevelOfProductsToShow();
        $groupFilter = '';
        $this->alsoShowProductsIds = [];
        $this->parentGroupIds = [];
        //special cases
        if ($levelToShow < 0) {
            //no produts but if LevelOfProductsToShow = -1 then show all
            //note the smartness here -1 == 1 || -1 == -2
            $groupFilter = ' (' . $levelToShow . ' = -1) ';
        } elseif ($levelToShow === 0) {
            //backup - same as 1, but without also show!
            $groupFilter = '"ParentID" = ' . $this->rootGroup->ID;
        } else {
            $this->parentGroupIds[$this->rootGroup->ID] = $this->rootGroup->ID;
            $this->alsoShowProductsIds = array_merge(
                $this->alsoShowProductsIds,
                $this->rootGroup->getProductsToBeIncludedFromOtherGroupsArray()
            );
            $childGroups = $this->getProductGroupListProvider()->getGroups();
            if ($childGroups && $childGroups->count()) {
                foreach ($childGroups as $childGroup) {
                    $this->parentGroupIds[$childGroup->ID] = $childGroup->ID;
                    $this->alsoShowProductsIds = array_merge(
                        $this->alsoShowProductsIds,
                        $childGroup->getProductsToBeIncludedFromOtherGroupsArray()
                    );
                }
            }
            $obj = Injector::inst()->get($this->buyableClassName);
            $stage = $this->getStage();
            $tableName = $obj->baseTable() . $stage;
            $siteTreeTable = 'SiteTree' . $stage;
            $groupFilter = '(
                "' . $siteTreeTable . '"."ParentID" IN (' . implode(',', $this->parentGroupIds) . ')
                OR
                "' . $tableName . '"."ID" IN (' . implode($this->alsoShowProductsIds) . ')
            )';
        }
        $this->alsoShowProductsIds = ArrayMethods::filter_array($this->alsoShowProductsIds);
        $this->products = $this->products->where($groupFilter);

        return $this;
    }

    /**
     * Generate Excluded products that can not be purchased.
     *
     * We all make a record of all the products that are in the current list
     * For efficiency sake, we do both these things at the same time.
     */
    protected function removeExcludedProducts(): self
    {
        if (EcommerceConfig::inst()->OnlyShowProductsThatCanBePurchased) {
            $productsThatNeedChecking = $this->products;
            if (is_array(self::$checked_products) && count(self::$checked_products)) {
                $productsThatNeedChecking = $productsThatNeedChecking->exclude(['ID' => self::$checked_products]);
            }
            foreach ($productsThatNeedChecking as $buyable) {
                self::$checked_products[$buyable->ID] = $buyable->ID;
                if (! $buyable->canPurchase()) {
                    $this->blockedProductsIds[$buyable->ID] = $buyable->ID;
                }
            }
        }

        if (! empty($this->blockedProductsIds)) {
            self::$excluded_products = array_unique(array_merge(
                self::$excluded_products,
                $this->blockedProductsIds
            ));
            $this->products = $this->products
                ->exclude(['ID' => self::$excluded_products]);
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

    /**
     * is there a cache available?
     * @return bool
     */
    protected function hasCache(): bool
    {
        return EcommerceCache::inst()->hasCache($this->getCachekey());
    }

    /**
     * loads the following variables:
     * - products
     * - blockedProductsIds
     * - blockedProductsIds
     * - alsoShowProductsIds
     * - parentGroupIds
     */
    protected function loadCache(): self
    {
        $productIds = EcommerceCache::inst()->retrieve($this->getCachekey());
        if (empty($productIds) || ! is_array($productIds)) {
            $productIds = ArrayMethods::filter_array([]);
        }
        $this->buildDefaultList();
        $this->products = $this->products->filter(['ID' => $productIds]);
        $this->blockedProductsIds = EcommerceCache::inst()->retrieve($this->getCachekey('blockedProductsIds'));
        $this->alsoShowProductsIds = EcommerceCache::inst()->retrieve($this->getCachekey('alsoShowProductsIds'));
        $this->parentGroupIds = EcommerceCache::inst()->retrieve($this->getCachekey('parentGroupIds'));

        return $this;
    }

    /**
     * sets the following variables:
     * - products
     * - blockedProductsIds
     * - blockedProductsIds
     * - alsoShowProductsIds
     * - parentGroupIds
     */
    protected function storeInCache(): self
    {
        EcommerceCache::inst()->save($this->getCachekey(), $this->products->columnUnique());
        EcommerceCache::inst()->save($this->getCachekey('blockedProductsIds'), $this->blockedProductsIds);
        EcommerceCache::inst()->save($this->getCachekey('alsoShowProductsIds'), $this->getAlsoShowProductsIds());
        EcommerceCache::inst()->save($this->getCachekey('parentGroupIds'), $this->getParentGroupIds());

        return $this;
    }

    /**
     * @param  string $add key to add
     *
     * @return string
     */
    protected function getCachekey(?string $add = ''): string
    {
        return implode(
            '_',
            [
                $this->rootGroup->ID,
                $this->rootGroup->ClassName,
                $this->buyableClassName,
                $this->getLevelOfProductsToShow(),
                $add,
            ]
        );
    }
}
