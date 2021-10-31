<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Builders;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Pages\ProductGroupController;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\BaseApplyer;
use Sunnysideup\Vardump\Vardump;

/**
 * A wrapper for a paginated of products which can be filtered and sorted.
 *
 * This list is linked to a controller and can be changed (the base group list is usually the same)
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @subpackage: Builders
 */
class FinalProductList extends AbstractProductsAndGroupsList
{
    /**
     * @var BaseProductList
     */
    protected $baseProductList;

    /**
     * @var ProductGroupController
     */
    protected $rootGroupController;

    /**
     * @var int[]
     */
    protected $filterForCandidateCategoryIdsFiltered = [];

    /**
     * @var int[]
     */
    protected $alsoShowParentIdsFiltered = [];

    /**
     * @var int[]
     */
    protected $alsoShowProductsIdsFiltered = [];

    /**
     * singleton_cache.
     *
     * @var self
     */
    protected static $singleton_cache;

    private static $group_filter_candidates_sort = [
        'ClassName' => 'DESC',
        'Title' => 'ASC',
    ];

    /**
     * @param ProductGroupController $rootGroupController
     * @param ProductGroup           $rootGroup
     */
    public function __construct($rootGroupController, $rootGroup)
    {
        if (false === self::$singleton_cache) {
            user_error('Use ::inst to create me!');
        }
        $this->setRootGroupController($rootGroupController);
        $this->setRootGroup($rootGroup);

        $this->baseProductList = $rootGroup->getBaseProductList();
        ClassHelpers::check_for_instance_of($this->baseProductList, BaseProductList::class, true);
        $this->products = $this->baseProductList->getProducts();
    }

    /**
     * create instances.
     *
     * @param ProductGroupController $rootGroupController
     * @param ProductGroup           $rootGroup
     *
     * @return FinalProductList
     */
    public static function inst($rootGroupController, $rootGroup)
    {
        if (! isset(self::$singleton_cache)) {
            self::$singleton_cache = new FinalProductList($rootGroupController, $rootGroup);
        }

        return self::$singleton_cache;
    }

    //#################################################
    // SETTERS / GETTERS
    //#################################################

    /**
     * Set the root {@link ProductGroup} to display the products from.
     *
     * @param ProductGroup $rootGroupController
     */
    public function setRootGroupController($rootGroupController): self
    {
        $this->rootGroupController = $rootGroupController;
        ClassHelpers::check_for_instance_of($rootGroupController, ProductGroupController::class, true);

        return $this;
    }

    public function getBuyableClassName() : string
    {
        return $this->getBaseProductList()->getBuyableClassName();
    }

    /**
     *
     * @param  array|string $filter
     * @return self
     */
    public function setExtraFilter($filter): self
    {
        if ($filter) {
            $this->products = $this->products->filter($filter);
        }

        return $this;
    }

    /**
     *
     * @param  array|string $sort
     * @return self
     */
    public function setAlternativeSort($sort): self
    {
        if ($sort) {
            $this->products = $this->products->sort($sort);
        }

        return $this;
    }

    /**
     * returns the associated BaseProductList.
     *
     * @return BaseProductList
     */
    public function getBaseProductList()
    {
        return $this->baseProductList;
    }

    //#################################################
    // APPLYERS
    //#################################################

    public function apply(string $classNameOrType, string $key, $params = null): self
    {
        $applyer = $this->getApplyer($classNameOrType);
        //Vardump::now(get_class($obj));

        $this->products = $applyer
            ->apply($key, $params)
            ->getProducts()
        ;
        //Vardump::now($this->products);

        return $this;
    }

    /**
     * @param array|string $params optional additional filter
     */
    public function applySearchFilter(string $key, $params = null): self
    {
        return $this->apply('SEARCHFILTER', $key, $params);
    }

    /**
     * @param array|string $params optional additional filter
     */
    public function applyGroupFilter(string $key, $params = null): self
    {
        return $this->apply('GROUPFILTER', $key, $params);
    }

    /**
     * @param array|string $params optional additional filter
     */
    public function applyFilter(string $key, $params = null): self
    {
        return $this->apply('FILTER', $key, $params);
    }

    /**
     * @param array|string $params optional additional filter
     */
    public function applySorter(string $key, $params = null): self
    {
        return $this->apply('SORT', $key, $params);
    }

    /**
     * @param array|string $params optional additional filter
     */
    public function applyDisplayer(string $key, $params = null): self
    {
        return $this->apply('DISPLAY', $key, $params);
    }

    //#########################################
    // PRODUCTS: Also show
    //#########################################

    public function getAlsoShowProductsIds(): array
    {
        return ArrayMethods::filter_array($this->alsoShowProductsIdsFiltered);
    }

    public function getAlsoShowProductsIdsFiltered(): array
    {
        return ArrayMethods::filter_array($this->alsoShowProductsIdsFiltered);
    }

    public function getAlsoShowProducts(): DataList
    {
        return $this->baseProductList->getAlsoShowProducts();
    }

    public function getAlsoShowProductsFiltered(): DataList
    {
        return $this->products->filter(['ID' => $this->getAlsoShowProductsIdsFiltered()]);
    }

    //#########################################
    // GROUPS - smart
    //#########################################

    public function getFilterForCandidateCategoryIds(): array
    {
        return $this->baseProductList->getFilterForCandidateCategoryIds();
    }

    public function getFilterForCandidateCategoryIdsFiltered(): array
    {
        return ArrayMethods::filter_array($this->filterForCandidateCategoryIdsFiltered);
    }

    public function getFilterForCandidateCategories(): DataList
    {
        return $this->baseProductList->getFilterForCandidateCategories();
    }

    /**
     *
     * @todo use EcommerceCache
     */
    public function getFilterForCandidateCategoriesFiltered()
    {
        if (empty($this->filterForCandidateCategoryIdsFiltered)) {
            $ids1 = $this->getAlsoShowParentsFiltered()->columnUnique();
            $ids2 = $this->getAlsoShowProductsProductGroupInclusiveFiltered()->columnUnique();
            $ids3 = $this->getParentGroupsBasedOnProductsFiltered()->columnUnique();
            $this->filterForCandidateCategoryIdsFiltered = array_merge($ids1, $ids2, $ids3);
        }
        $list = $this->turnIdListIntoProductGroups($this->getFilterForCandidateCategoryIdsFiltered(), true);

        return $list->exclude(['ID' => $this->getParentGroupIds()])
            ->Sort($this->Config()->get('group_filter_candidates_sort'))
        ;
    }

    /**
     *
     * @todo use EcommerceCache
     */
    public function getAlsoShowParentIdsFiltered(): array
    {
        if (! count($this->alsoShowParentIdsFiltered)) {
            $rows = DB::query('
                SELECT "ProductGroupID"
                FROM "Product_ProductGroups"
                WHERE "ProductID" IN (' . implode(', ', $this->getProductIds()) . ');')->column();

            $this->alsoShowParentIdsFiltered = ArrayMethods::filter_array($rows);
        }

        return $this->alsoShowParentIdsFiltered;
    }

    public function getAlsoShowProductsProductGroupInclusiveFiltered(): DataList
    {
        return $this->turnIdListIntoProductGroups($this->getAlsoShowProductsFiltered()->columnUnique('ParentID'));
    }

    public function getParentGroupsBasedOnProductsFiltered(): DataList
    {
        return $this->turnIdListIntoProductGroups($this->getProducts()->columnUnique('ParentID'));
    }

    //#################################################
    // GROUPS: Parents from natural hierachy
    //#################################################

    /**
     * required for SubGroups.
     */
    public function getParentGroupIds(): array
    {
        return $this->baseProductList->getParentGroupIds();
    }

    public function getParentGroups(): DataList
    {
        return $this->baseProductList->getParentGroups();
    }

    //#################################################
    // GROUPS: Also Show Products, based on Products included through AlsoShow Show
    //#################################################

    public function getAlsoShowParentIds(): array
    {
        return $this->baseProductList->getAlsoShowParentIds();
    }

    public function getAlsoShowParents(): DataList
    {
        return $this->baseProductList->getAlsoShowParents();
    }

    public function getAlsoShowParentsFiltered(): DataList
    {
        $list = ProductGroup::get()->filter(['ID' => $this->getAlsoShowParentIdsFiltered()]);

        return RelatedProductGroups::apply_default_filter_to_groups($list);
    }

    protected $rawCountCachedCache = null;

    public function getRawCountCached(): int
    {
        if(null === $this->rawCountCachedCache) {
            $this->rawCountCachedCache = $this->products->count();
        }
        return $this->rawCountCachedCache;

    }


}
