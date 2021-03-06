<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Builders;

use SilverStripe\ORM\DataList;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Pages\ProductGroupController;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\BaseApplyer;

/**
 * A wrapper for a paginated of products which can be filtered and sorted.
 *
 * This list is linked to a controller and can be changed (the base group list is usually the same)
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @subpackage: Pages
 */
class FinalProductList extends AbstractProductsAndGroupsList
{
    /**
     * @var BaseProductList|null
     */
    protected $baseProductList = null;

    /**
     * @var ProductGroupController|null
     */
    protected $rootGroupController = null;

    /**
     * singleton_cache
     * @var self|null
     */
    protected static $singleton_cache = null;

    /**
     * @param ProductGroupController $rootGroupController
     * @param ProductGroup           $rootGroup
     */
    public function __construct($rootGroupController, $rootGroup)
    {
        $this->setRootGroupController($rootGroupController);
        $this->setRootGroup($rootGroup);

        $this->baseProductList = $rootGroup->getBaseProductList();
        ClassHelpers::check_for_instance_of($this->baseProductList, BaseProductList::class, true);
        $this->products = $this->baseProductList->getProducts();
    }

    /**
     * create instances
     * @param  ProductGroupController    $rootGroupController
     * @param  ProductGroup              $rootGroup
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

    ##################################################
    # SETTERS / GETTERS
    ##################################################

    /**
     * Set the root {@link ProductGroup} to display the products from.
     * @param ProductGroup $rootGroupController
     *
     * @return self
     */
    public function setRootGroupController($rootGroupController): self
    {
        $this->rootGroupController = $rootGroupController;
        ClassHelpers::check_for_instance_of($rootGroupController, ProductGroupController::class, true);

        return $this;
    }

    public function setExtraFilter($filter): self
    {
        if ($filter) {
            $this->products = $this->products->filter($filter);
        }
        return $this;
    }

    public function setAlternativeSort($sort): self
    {
        if ($sort) {
            $this->products = $this->products->sort($sort);
        }

        return $this;
    }

    /**
     * returns the associated BaseProductList
     * @return BaseProductList
     */
    public function getBaseProductList()
    {
        return $this->baseProductList;
    }

    ##################################################
    # APPLYERS
    ##################################################

    public function apply(string $classNameOrType, string $key, $params = null): self
    {
        $obj = $this->getApplyer($classNameOrType);

        $this->products = $obj
            ->apply($key, $params)
            ->getProducts();

        return $this;
    }

    /**
     * @param string         $key
     * @param array|string   $params optional additional filter
     *
     * @return self
     */
    public function applyGroupFilter(string $key, $params = null): self
    {
        return $this->apply('GROUPFILTER', $key, $params);
    }

    /**
     * @param string         $key
     * @param array|string   $params optional additional filter
     *
     * @return self
     */
    public function applyFilter(string $key, $params = null): self
    {
        return $this->apply('FILTER', $key, $params);
    }

    /**
     * @param string         $key
     * @param array|string   $params optional additional filter
     *
     * @return self
     */
    public function applySorter(string $key, $params = null): self
    {
        return $this->apply('SORT', $key, $params);
    }

    /**
     * @param string         $key
     * @param array|string   $params optional additional filter
     *
     * @return self
     */
    public function applyDisplayer(string $key, $params = null): self
    {
        return $this->apply('DISPLAY', $key, $params);
    }

    ##########################################
    # PRODUCTS: Also show
    ##########################################

    /**
     * @return array
     */
    public function getAlsoShowProductsIds(): array
    {
        return $this->baseProductList->getAlsoShowProductsIds();
    }

    /**
     * @return DataList
     */
    public function getAlsoShowProducts(): DataList
    {
        return $this->baseProductList->getAlsoShowProducts();
    }

    ##########################################
    # GROUPS - smart
    ##########################################

    public function getFilterForCandidateCategoryIds(): array
    {
        return $this->baseProductList->getFilterForCandidateCategoryIds();
    }

    public function getFilterForCandidateCategories(): DataList
    {
        return $this->baseProductList->getFilterForCandidateCategories();
    }

    ##################################################
    # GROUPS: Parents from natural hierachy
    ##################################################

    /**
     * required for SubGroups
     * @return array
     */
    public function getParentGroupIds(): array
    {
        return $this->baseProductList->getParentGroupIds();
    }

    public function getParentGroups(): DataList
    {
        return $this->baseProductList->getParentGroups();
    }

    ##################################################
    # GROUPS: Also Show Products, based on Products included through AlsoShow Show
    ##################################################

    public function getAlsoShowParentIds(): array
    {
        return $this->baseProductList->getAlsoShowParentIds();
    }

    public function getAlsoShowParents(): DataList
    {
        return $this->baseProductList->getAlsoShowParents();
    }

    ##################################################
    # HELPERS
    ##################################################

    /**
     * @param  string $type
     * @return string
     */
    protected function getApplyerClassName(string $type): string
    {
        return $this->getTemplateForProductsAndGroups()->getApplyerClassName($type);
    }

    /**
     * @param  string $classNameOrType
     * @return BaseApplyer
     */
    protected function getApplyer(string $classNameOrType)
    {
        return $this->getTemplateForProductsAndGroups()->getApplyer($classNameOrType, $this);
    }

    protected function getSiteTreeTableName(): string
    {
        return $this->baseProductList->getSiteTreeTableName();
    }

    protected function getBuyableTableNameName(): string
    {
        return $this->baseProductList->getBuyableTableNameName();
    }
}
