<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups;

use Sunnysideup\Ecommerce\Api\ArrayMethods;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\ORM\SS_List;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\ViewableData;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Pages\BaseClass;
use Sunnysideup\Ecommerce\ProductsAndGroups\Traits\SubGroups;;


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

    private static $default_filter_class_name = ProductFilter::class;

    private static $default_sorter_class_name = ProductSorter::class;

    private static $default_displayer_class_name = ProductDisplayer::class;

    private static $default_user_preference_class_name = ProductListUserPreference::class;

    protected $baseProductList = null;

    protected $rootGroup = null;

    protected static $cache = [];

    public static function inst($rootGroup, ?string $baseProductListClassName = '', ?string $buyableClassName = '', ?int $levelOfProductsToShow = 0)
    {
        $cacheKey = implode('_', array_filter([$rootGroup->ID, $baseProductListClassName, $buyableClassName, $levelOfProductsToShow]));
        if(! isset(self::$cache[$cacheKey])) {
            self::$cache[$cacheKey] = new FinalProductList($rootGroup, $baseProductListClassName, $buyableClassName, $levelOfProductsToShow);
        }
        return self::$cache[$cacheKey];
    }


    /**
     * @param ProductGroup $rootGroup
     * @param string       $buyableClassName
     */
    public function __construct($rootGroup, ?string $baseProductListClassName = '', ?string $buyableClassName = '', ?int $levelOfProductsToShow = 0)
    {
        $this->rootGroup = $rootGroup;
        if(! $baseProductListClassName) {
            $baseProductListClassName = $rootGroup->getBaseProductListClassName();
        }
        $this->baseProductList = $baseProductListClassName::inst($rootGroup, $buyableClassName, $levelOfProductsToShow);
        $this->products = $this->baseProductList->getProducts();
    }

    public function getBaseProductList()
    {
        return $this->baseProductList;
    }

    /**
     * @var SS_List
     */
    protected $products;

    public function applyFilter($filter = null)
    {
        $this->apply($this->Config()->get('default_filter_class_name'), $filter);
    }


    public function applySorter($sort = null)
    {
        $this->apply($this->Config()->get('default_sorter_class_name'), $sort);
    }


    public function applyDisplayer($param = null)
    {
        $this->apply($this->Config()->get('default_displayer_class_name'), $param);
    }


    public function applyUserPreference($param = null)
    {
        $this->apply($this->Config()->get('default_user_preference_class_name'), $param);
    }

    public function apply(string $className, $param = null)
    {
        $obj = $this->getApplyer($className);
        $this->products = $obj->apply($param);
    }




    public function getFilterOptions() : array
    {
        return $this->getOptionsMap($this->Config()->get('default_filter_class_name'));
    }

    public function getSorterOptions(): array
    {
        return $this->getOptionsMap($this->Config()->get('default_sorter_class_name'));
    }

    public function getDisplayerOptions(): array
    {
        return $this->getOptionsMap($this->Config()->get('default_displayer_class_name'));
    }

    public function getOptionsMap(string $className): array
    {
        $obj = $this->getApplyer($className);

        return $obj->getOptionsMap();
    }


    public function getFilterList(?string $currentKey ='', ?bool $ajaxify = true) : ArrayList
    {
        return $this->getOptionsList($this->Config()->get('default_filter_class_name'), $currentKey, $ajaxify);
    }

    public function getSorteList(?string $currentKey ='', ?bool $ajaxify = true): ArrayList
    {
        return $this->getOptionsList($this->Config()->get('default_sorter_class_name'), $currentKey, $ajaxify);
    }

    public function getDisplayerList(?string $currentKey ='', ?bool $ajaxify = true): ArrayList
    {
        return $this->getOptionsList($this->Config()->get('default_displayer_class_name'), $currentKey, $ajaxify);
    }

    public function getOptionsList(string $className, ?string $currentKey ='', ?bool $ajaxify = true): ArrayList
    {
        $obj = $this->getOptionsList($className, $currentKey, $ajaxify);

        return $obj->getOptionsMap();
    }



    public function getFilterTitle() : array
    {
        return $this->getTitle($this->Config()->get('default_filter_class_name'));
    }

    public function getSorterTitle(): array
    {
        return $this->getTitle($this->Config()->get('default_sorter_class_name'));
    }

    public function getDisplayerTitle(): array
    {
        return $this->getTitle($this->Config()->get('default_displayer_class_name'));
    }

    public function getTitle(string $className): string
    {
        $obj = $this->getApplyer($className);

        return $obj->getTitle();
    }

    protected function getApplyer()
    {
        $obj = new $className($this);
        if(! $obj instanceof BaseClass) {
            user_error($className . ' needs to be an instance of ' . BaseClass::class);
        }

        return $obj;
    }


    /**
     * required for SubGroups
     * @return array
     */
    public function getParentGroupIds() : array
    {
        return $this->baseProductList->getParentGroupIds();
    }

    /**
     * required for SubGroups
     * @return array
     */
    public function getAlsoShowProductsIds() : array
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
    public function getGroups(int $maxRecursiveLevel, $filter = null) : ArrayList
    {
        return $this->baseProductList->getGroups($maxRecursiveLevel, $filter);
    }


}
