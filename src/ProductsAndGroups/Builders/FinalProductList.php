<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Builders;

use SilverStripe\ORM\SS_List;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\BaseApplyer;
use Sunnysideup\Ecommerce\ProductsAndGroups\ProductsAndGroupsList;


/**
 * A wrapper for a paginated of products which can be filtered and sorted.
 *
 * What configuation can be provided
 * 1. levels to show
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @subpackage: Pages
 */
class FinalProductList extends ProductsAndGroupsList
{

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

    public function getDefaultFilterTitle(?string $value = ''): array
    {
        return $this->getTitle($this->getApplyerClassName('FILTER'), $value);
    }

    public function getDefaultSortOrderTitle(?string $value = ''): array
    {
        return $this->getTitle($this->getApplyerClassName('SORT'), $value);
    }

    public function getDisplayStyleTitle(?string $value = ''): array
    {
        return $this->getTitle($this->getApplyerClassName('DISPLAY'), $value);
    }

    public function getTitle(string $className, ?string $value = ''): string
    {
        $obj = $this->getApplyer($className);

        return $obj->getTitle($value);
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

    /**
     * todo: CHECK!
     * @param  string $type
     * @return string
     */
    protected function getApplyerClassName(string $type): string
    {
        if ($this->rootGroup->IsSortFilterDisplayNamesType($type)) {
            return $this->rootGroup->getSortFilterDisplayNames($type, 'defaultApplyer');
        }

        return user_error();
    }

    protected function getApplyer(string $className)
    {
        $obj = new $className($this);
        if (! $obj instanceof BaseApplyer) {
            user_error($className . ' needs to be an instance of ' . BaseApplyer::class);
        }

        return $obj;
    }

    /**
     * Returns the Title for a type key.
     *
     * If no key is provided then the default key is used.
     *
     * runs a method: getDefaultFilterTitle, getDefaultSortOrderTitle, or getDisplayStyleTitle
     * where DefaultFilter, DefaultSortOrder and DisplayStyle are the DB Fields...
     *
     * @param string $type - FILTER | SORT | DISPLAY
     *
     * @return string
     */
    public function getUserPreferencesTitle($type, $value)
    {
        $method = 'get' . $this->controller->getSortFilterDisplayNames($type, 'dbFieldName') . 'Title';
        $value = $this->{$method}($value);
        if ($value) {
            return $value;
        }

        return _t('ProductGroup.UNKNOWN', 'UNKNOWN USER SETTING');
    }
}
