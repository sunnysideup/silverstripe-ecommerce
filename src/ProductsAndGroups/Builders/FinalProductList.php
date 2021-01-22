<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups;

use SilverStripe\ORM\SS_List;
use SilverStripe\View\ViewableData;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\BaseClass;
use Sunnysideup\Ecommerce\ProductsAndGroups\Traits\SubGroups;

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
class FinalProductList extends ViewableData
{
    use SubGroups;

    protected $baseProductList = null;

    protected $rootGroup = null;

    protected static $cache = [];

    /**
     * @var SS_List
     */
    protected $products;

    /**
     * @param ProductGroup $rootGroup
     * @param string       $buyableClassName
     */
    public function __construct($rootGroup, ?string $baseProductListClassName = '', ?string $buyableClassName = '', ?int $levelOfProductsToShow = 0)
    {
        $this->rootGroup = $rootGroup;
        if (! $baseProductListClassName) {
            $baseProductListClassName = $rootGroup->getTemplateForProductsAndGroups()->getBaseProductListClassName();
        }
        $this->baseProductList = $baseProductListClassName::inst($rootGroup, $buyableClassName, $levelOfProductsToShow);
        $this->products = $this->baseProductList->getProducts();
    }

    public static function inst($rootGroup, ?string $baseProductListClassName = '', ?string $buyableClassName = '', ?int $levelOfProductsToShow = 0)
    {
        $cacheKey = implode('_', array_filter([$rootGroup->ID, $baseProductListClassName, $buyableClassName, $levelOfProductsToShow]));
        if (! isset(self::$cache[$cacheKey])) {
            self::$cache[$cacheKey] = new FinalProductList($rootGroup, $baseProductListClassName, $buyableClassName, $levelOfProductsToShow);
        }
        return self::$cache[$cacheKey];
    }

    public function getBaseProductList()
    {
        return $this->baseProductList;
    }

    /**
     *
     * @param  array|string $filter optional additional filter
     * @return self           [description]
     */

    public function applyFilter($filter = null): self
    {
        return $this->apply($this->getApplyerClassName('FILTER'), $filter);
    }

    public function applySorter($sort = null): self
    {
        return $this->apply($this->getApplyerClassName('SORT'), $sort);
    }

    public function applyDisplayer($param = null): self
    {
        return $this->apply($this->getApplyerClassName('DISPLAY'), $param);
    }

    public function apply(string $className, $param = null)
    {
        $obj = $this->getApplyer($className);

        $obj
            ->apply($param)
            ->getProducts();

        return $this;
    }

    public function getDefaultFilterOptions(): array
    {
        return $this->getOptionsMap($this->getApplyerClassName('FILTER'));
    }

    public function getDefaultSortOrderOptions(): array
    {
        return $this->getOptionsMap($this->getApplyerClassName('SORT'));
    }

    public function getDisplayStyleOptions(): array
    {
        return $this->getOptionsMap($this->getApplyerClassName('DISPLAY'));
    }

    public function getOptionsMap(string $className): array
    {
        $obj = $this->getApplyer($className);

        return $obj->getOptionsMap();
    }

    public function getDefaultFilterList(string $linkTemplate, ?string $currentKey = '', ?bool $ajaxify = true): ArrayList
    {
        return $this->getOptionsList($this->getApplyerClassName('FILTER'), $linkTemplate, $currentKey, $ajaxify);
    }

    public function getDefaultSortOrderList(string $linkTemplate, ?string $currentKey = '', ?bool $ajaxify = true): ArrayList
    {
        return $this->getOptionsList($this->getApplyerClassName('SORT'), $linkTemplate, $currentKey, $ajaxify);
    }

    public function getDisplayStyleList(string $linkTemplate, ?string $currentKey = '', ?bool $ajaxify = true): ArrayList
    {
        return $this->getOptionsList($this->getApplyerClassName('DISPLAY'), $linkTemplate, $currentKey, $ajaxify);
    }

    public function getOptionsList(string $className, string $linkTemplate, ?string $currentKey = '', ?bool $ajaxify = true): ArrayList
    {
        $obj = $this->getApplyer($className);

        return $obj->getOptionsList($linkTemplate, $currentKey, $ajaxify);
    }

    public function getDefaultFilterTitle(): array
    {
        return $this->getTitle($this->getApplyerClassName('FILTER'));
    }

    public function getDefaultSortOrderTitle(): array
    {
        return $this->getTitle($this->getApplyerClassName('SORT'));
    }

    public function getDisplayStyleTitle(): array
    {
        return $this->getTitle($this->getApplyerClassName('DISPLAY'));
    }

    public function getTitle(string $className): string
    {
        $obj = $this->getApplyer($className);

        return $obj->getTitle();
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
     * Returns children ProductGroup pages of this group.
     *
     * @param int            $maxRecursiveLevel  - maximum depth , e.g. 1 = one level down - so no Child Child Groups are returned...
     * @param string|array   $filter             - additional filter to be added
     *
     * @return \SilverStripe\ORM\ArrayList (ProductGroups)
     */
    public function getGroups(int $maxRecursiveLevel, $filter = null): ArrayList
    {
        return $this->baseProductList->getGroups($maxRecursiveLevel, $filter);
    }

    protected function getApplyerClassName(string $type): string
    {
        if ($this->rootGroup->IsSortFilterDisplayNamesType($type)) {
            return $this->rootGroup->getSortFilterDisplayNames($type, 'defaultApplyer');
        }

        return $obj;
    }

    protected function getApplyer(string $className)
    {
        $obj = new $className($this);
        if (! $obj instanceof BaseClass) {
            user_error($className . ' needs to be an instance of ' . BaseClass::class);
        }

        return $obj;
    }
}
