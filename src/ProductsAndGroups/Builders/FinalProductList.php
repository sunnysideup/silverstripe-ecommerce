<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Builders;

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
        $this->setRootGroup($rootGroup);

        $this->rootGroupController = $rootGroupController;
        ClassHelpers::check_for_instance_of($rootGroupController, ProductGroupController::class, true);
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

    /**
     * returns the associated BaseProductList
     * @return BaseProductList
     */
    public function getBaseProductList()
    {
        return $this->baseProductList;
    }

    public function apply(string $className, $param = null): self
    {
        $obj = $this->getApplyer($className);

        $obj
            ->apply($param)
            ->getProducts();

        return $this;
    }

    /**
     * @param  array|string $param optional additional filter
     * @return self
     */
    public function applyFilter($param = null): self
    {
        return $this->apply($this->getApplyerClassName('FILTER'), $param);
    }

    public function applySorter($param = null): self
    {
        return $this->apply($this->getApplyerClassName('SORT'), $param);
    }

    public function applyDisplayer($param = null): self
    {
        return $this->apply($this->getApplyerClassName('DISPLAY'), $param);
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
     * @param  string $type
     * @return string
     */
    protected function getApplyerClassName(string $type): string
    {
        return $this->getTemplateForProductsAndGroups()->getApplyerClassName($type);
    }

    /**
     * @param  string $className
     * @return BaseApplyer
     */
    protected function getApplyer(string $className)
    {
        return $this->getTemplateForProductsAndGroups()->getApplyer($className);
    }
}
