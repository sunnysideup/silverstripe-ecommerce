<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Builders;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\SS_List;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Api\EcommerceCache;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\BaseApplyer;

/**
 * The starting base of the Products.
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
     * keep the lists in memory.
     *
     * @var array
     */
    protected static $singleton_caches = [];

    /**
     * @var string
     */
    protected $buyableClassName = '';

    /**
     * @var int
     */
    protected $levelOfProductsToShow = 0;

    /**
     * @var string
     */
    protected $searchString = '';

    /**
     * @var string
     */
    protected $cacheKey = '';

    /**
     * @var RelatedProductGroups
     */
    protected $productGroupListProvider;

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
    protected $alsoShowParentIds = [];

    /**
     * @var int[]
     */
    protected $filterForCandidateCategoryIds = [];

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
     *
     * @var array'
     */
    private static $default_product_filter = [
        'ShowInSearch' => 1,
    ];

    private static $group_filter_candidates_sort = [
        'ClassName' => 'DESC',
        'Title' => 'ASC',
    ];

    /**
     * @param ProductGroup $rootGroup
     */
    public function __construct($rootGroup, ?string $buyableClassName = '', ?int $levelOfProductsToShow = 0, ?string $searchString = '')
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
            ->setLevelOfProductsToShow($levelOfProductsToShow)
            ->setSearchString($searchString)
        ;
    }

    public function init()
    {
        if ($this->hasCache()) {
            $this->loadCache();
        } else {
            $this->buildDefaultList()
                ->applyDefaultFilters()
                ->applyGroupOrSearchFilter()
                ->removeExcludedProducts()
                ->storeInCache()
            ;
        }
    }

    public static function apply_default_filter_to_products($list): SS_List
    {
        $filter = Config::inst()->get(self::class, 'default_product_filter');
        if (EcommerceConfig::inst()->OnlyShowProductsThatCanBePurchased) {
            $filter['AllowPurchase'] = 1;
        }

        return $list->filter($filter);
    }

    public static function inst($rootGroup, ?string $buyableClassName = '', ?int $levelOfProductsToShow = 0, ?string $searchString = '')
    {

        // low cost creation call to create cache key
        $cacheKey = self::generate_cache_key($rootGroup, $buyableClassName, $levelOfProductsToShow, $searchString);
        if (! isset(self::$singleton_caches[$cacheKey])) {
            $className = static::class;
            self::$singleton_caches[$cacheKey] = new $className($rootGroup, $buyableClassName, $levelOfProductsToShow, $searchString);
        }
        self::$singleton_caches[$cacheKey]
            ->setCacheKey($cacheKey)
            ->init();

        return self::$singleton_caches[$cacheKey];
    }

    public static function generate_cache_key($rootGroup, ?string $buyableClassName = '', ?int $levelOfProductsToShow = 0, ?string $searchString = '')
    {
        return implode(
            '_',
            [
                $rootGroup->ID,
                $rootGroup->ClassName,
                $buyableClassName,
                $levelOfProductsToShow,
                $searchString,
            ]
        );
    }

    public function generateCacheKey(): string
    {
        return self::generate_cache_key($this->rootGroup, $this->buyableClassName, $this->levelOfProductsToShow, $this->searchString);
    }

    //#########################################
    // Setters
    //#########################################

    /**
     * Override the class of buyable to display. Usually this is limited to
     * `Product` but can be tailored to display specific subclasses.
     */
    public function setBuyableClassName(string $buyableClassName): self
    {
        $this->buyableClassName = $buyableClassName;

        return $this;
    }

    public function getBuyableClassName(): string
    {
        return $this->buyableClassName;
    }

    public function getShowProductLevelsArray(): array
    {
        return $this->getProductGroupListProvider()->getShowProductLevelsArray();
    }

    public function setLevelOfProductsToShow(int $levelOfProductsToShow): self
    {
        $this->levelOfProductsToShow = $levelOfProductsToShow;

        return $this;
    }

    public function setSearchString(string $searchString): self
    {
        $this->searchString = $searchString;

        return $this;
    }

    /**
     * how many children, grand-children, etc.. levels do we provide?
     */
    public function getLevelOfProductsToShow(): int
    {
        return $this->levelOfProductsToShow;
    }


    public function setCacheKey(string $key): self
    {
        $this->cacheKey = $key;

        return $this;
    }

    //#########################################
    // PRODUCTS: Also show
    //#########################################

    public function getAlsoShowProductsIds(): array
    {
        return ArrayMethods::filter_array($this->alsoShowProductsIds);
    }

    public function getAlsoShowProducts(): DataList
    {
        return $this->products->filter(['ID' => $this->getAlsoShowProductsIds()]);
    }

    //#########################################
    // GROUPS - smart
    //#########################################

    public function getFilterForCandidateCategoryIds(): array
    {
        return ArrayMethods::filter_array($this->filterForCandidateCategoryIds);
    }

    public function getFilterForCandidateCategories(): DataList
    {
        if (empty($this->filterForCandidateCategoryIds)) {
            $ids1 = $this->getAlsoShowParents()->columnUnique();
            $ids2 = $this->getAlsoShowProductsProductGroupInclusive()->columnUnique();
            $this->filterForCandidateCategoryIds = array_merge($ids1, $ids2);
        }

        // print_r($idsAll);
        $list = $this->turnIdListIntoProductGroups($this->getFilterForCandidateCategoryIds(), true);

        return $list
            ->exclude(['ID' => $this->getParentGroupIds()])
            ->sort($this->Config()->get('group_filter_candidates_sort'))
        ;
    }

    //#################################################
    // GROUPS: Parents from natural hierachy
    //#################################################

    public function getParentGroupIds(): array
    {
        return ArrayMethods::filter_array($this->parentGroupIds);
    }

    public function getParentGroups(): DataList
    {
        return $this->turnIdListIntoProductGroups($this->getParentGroupIds());
    }

    //#################################################
    // GROUPS: Also Show Products, based on Products included through AlsoShow Show
    // NOTE: difference with below
    //#################################################

    public function getAlsoShowParentIds(): array
    {
        // we can do this here as filter_array always adds an entry.
        if ([] === $this->alsoShowParentIds) {
            $rows = DB::query('
                SELECT "ProductGroupID"
                FROM "Product_ProductGroups"
                WHERE "ProductID" IN (' . implode(', ', $this->getProductIds()) . ');')->column();

            $this->alsoShowParentIds = ArrayMethods::filter_array($rows);
        }

        return $this->alsoShowParentIds;
    }

    public function getAlsoShowParents(): DataList
    {
        $list = ProductGroup::get()->filter(['ID' => $this->getAlsoShowParentIds()]);

        return RelatedProductGroups::apply_default_filter_to_groups($list);
    }

    //#################################################
    // HELPERS
    //#################################################

    /**
     * Returns children ProductGroup pages of this group.
     * Make it more accesible for ProductGroup.
     *
     * @param int          $maxRecursiveLevel - maximum depth , e.g. 1 = one level down - so no Child Child Groups are returned...
     * @param array|string $filter            - additional filter to be added
     *
     * @return SS_List (ProductGroups)
     */
    public function getGroups(?int $maxRecursiveLevel = null, $filter = null)
    {
        if (null === $maxRecursiveLevel) {
            $maxRecursiveLevel = $this->getLevelOfProductsToShow();
        }

        $list = $this->getProductGroupListProvider()->getGroups($maxRecursiveLevel, $filter);
        ClassHelpers::check_for_instance_of($list, SS_List::class);

        return $list;
    }

    /**
     * Returns a list of {@link RelatedProductGroups}.
     *
     * @return RelatedProductGroups
     */
    public function getProductGroupListProvider()
    {
        if (! $this->productGroupListProvider) {
            $className = $this->rootGroup->getProductGroupSchema()->getProductGroupListClassName();
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


    //#################################################
    // BUILDERS
    //#################################################

    /**
     * key method for cache
     * create a starting point.
     * @return self
     */
    protected function buildDefaultList(): self
    {
        $buyableClassName = $this->buyableClassName;
        $this->products = $buyableClassName::get();

        return $this;
    }

    /**
     * key method for cache
     * add default filters.
     * @return self
     */
    protected function applyDefaultFilters(): self
    {
        $this->products = self::apply_default_filter_to_products($this->products);

        return $this;
    }

    /**
     * key method for cache
     * apply group filters to products.
     */
    protected function applyGroupOrSearchFilter(): self
    {
        if ($this->searchString) {
            $this->applySearchFilter();
        } else {
            $this->applyGroupFilterInner();
        }

        return $this;
    }

    /**
     * key method for cache (called from applyGroupOrSearchFilter)
     * @return self
     */
    protected function applySearchFilter(): self
    {
        $applyer = $this->getApplyer('SEARCHFILTER');
        //Vardump::now(get_class($obj));
        $this->products = $applyer
            ->apply(BaseApplyer::DEFAULT_NAME, $this->searchString)
            ->getProducts()
        ;
        //Vardump::now($this->products);

        return $this;
    }

    protected function applyGroupFilterInner()
    {
        $levelToShow = $this->getLevelOfProductsToShow();
        $groupFilter = '';
        //special cases
        if ($levelToShow < 0) {
            //no produts but if LevelOfProductsToShow = -1 then show all
            //note the smartness here when creating filters:
            // -1 == -1: show all -1 == -2: show none,
            // i.e. minus 1 is include all and minus -2 is include none.
            // ignore AlsoShow.
            $groupFilter = ' ' . $levelToShow . ' = -1 ';
        } elseif (0 === $levelToShow) {
            //backup - same as 1, but with also show!
            $groupFilter = '"' . $this->getBuyableTableNameName() . '"."ParentID" = ' . $this->rootGroup->ID;
            $this->alsoShowProductsIds = array_merge(
                $this->alsoShowProductsIds,
                $this->rootGroup->getProductsToBeIncludedFromOtherGroupsArray()
            );
        } else {
            $this->parentGroupIds[] = $this->rootGroup->ID;
            $this->alsoShowProductsIds = array_merge(
                $this->alsoShowProductsIds,
                $this->rootGroup->getProductsToBeIncludedFromOtherGroupsArray()
            );
            $childGroups = $this->getGroups();
            if ($childGroups->exists()) {
                foreach ($childGroups as $childGroup) {
                    $this->parentGroupIds[] = $childGroup->ID;
                    $this->alsoShowProductsIds = array_merge(
                        $this->alsoShowProductsIds,
                        $childGroup->getProductsToBeIncludedFromOtherGroupsArray()
                    );
                }
            }

            $groupFilter = '"' . $this->getBuyableTableNameName() . '"."ParentID" IN (' . implode(',', $this->getParentGroupIds()) . ')';
        }

        $alsoShowFilter = '"' . $this->getBuyableTableNameName() . '"."ID" IN (' . implode(',', $this->getAlsoShowProductsIds()) . ')';
        $fullFilter = '((' . $groupFilter . ') OR (' . $alsoShowFilter . '))';
        $this->products = $this->products->where($fullFilter);
    }

    /**
     * key method for cache
     * Generate Excluded products that can not be purchased.
     *
     * We all make a record of all the products that are in the current list
     * For efficiency sake, we do both these things at the same time.
     * @return self
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
                ->exclude(['ID' => self::$excluded_products])
            ;
        }

        return $this;
    }

    //#################################################
    // CACHE
    //#################################################

    /**
     * is there a cache available?
     */
    protected function hasCache(): bool
    {
        return EcommerceCache::inst()->hasCache($this->getCachekey('productids'));
    }

    /**
     * loads the following variables:
     * - products
     * - blockedProductsIds
     * - blockedProductsIds
     * - alsoShowProductsIds
     * - parentGroupIds.
     */
    protected function loadCache(): self
    {
        $this->buildDefaultList();
        // buildDefaultList, applyDefaultFilters, applySearchFilter, applyGroupFilterInner, removeExcludedProducts
        $this->products = $this->products->filter(['ID' => EcommerceCache::inst()->retrieve($this->getCachekey('productids'))]);
        $this->blockedProductsIds = EcommerceCache::inst()->retrieveAsIdList($this->getCachekey('blockedProductsIds')); // set in removeExcludedProducts
        $this->alsoShowProductsIds = EcommerceCache::inst()->retrieveAsIdList($this->getCachekey('alsoShowProductsIds')); // set in applyGroupFilterInner
        $this->parentGroupIds = EcommerceCache::inst()->retrieveAsIdList($this->getCachekey('parentGroupIds')); // set in applyGroupFilterInner
        $this->alsoShowParentIds = EcommerceCache::inst()->retrieveAsIdList($this->getCachekey('alsoShowParentIds')); // set in getAlsoShowParentIds

        return $this;
    }

    /**
     * sets the following variables:
     * - products
     * - blockedProductsIds
     * - alsoShowProductsIds
     * - parentGroupIds.
     */
    protected function storeInCache(): self
    {
        EcommerceCache::inst()->save($this->getCachekey('productids'), ArrayMethods::filter_array($this->products->columnUnique()));
        EcommerceCache::inst()->save($this->getCachekey('blockedProductsIds'), ArrayMethods::filter_array($this->blockedProductsIds));
        EcommerceCache::inst()->save($this->getCachekey('alsoShowProductsIds'), $this->getAlsoShowProductsIds());
        EcommerceCache::inst()->save($this->getCachekey('parentGroupIds'), $this->getParentGroupIds());
        EcommerceCache::inst()->save($this->getCachekey('alsoShowParentIds'), $this->getAlsoShowParentIds());

        return $this;
    }

    /**
     * @param string $add key to add
     */
    protected function getCachekey(?string $add = ''): string
    {
        return $this->cacheKey . $add;
    }
}
