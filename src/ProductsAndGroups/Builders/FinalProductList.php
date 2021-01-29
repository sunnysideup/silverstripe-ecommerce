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

    /**
     * returns the associated BaseProductList
     * @return BaseProductList
     */
    public function getBaseProductList()
    {
        return $this->baseProductList;
    }

    public function apply(string $classNameOrType, string $key, $params = null): self
    {
        $obj = $this->getApplyer($classNameOrType);

        $obj
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

    /**
     * required for SubGroups
     * @return array
     */
    public function getParentGroupIds(): array
    {
        return $this->baseProductList->getParentGroupIds();
    }

    /**
     * required for SubGroups
     * @return array
     */
    public function getAlsoShowProductsIds(): array
    {
        return $this->baseProductList->getAlsoShowProductsIds();
    }

    /**
     * required for SubGroups
     * @return array
     */
    public function getAlsoShowProducts(): DataList
    {
        return $this->baseProductList->getAlsoShowProducts();
    }

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
}
