<?php

namespace Sunnysideup\Ecommerce\ORM;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\DataList;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\ViewableData;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
* A wrapper for a paginated list of products which can be filtered and sorted.
*
* @author: Nicolaas [at] Sunny Side Up .co.nz
* @package: ecommerce
* @subpackage: Pages
*/
class ProductListBackup extends ViewableData
{




        /**
         * variable to speed up methods in this class.
         *
         * @var array
         */
        protected $configOptionsCache = [];

        /**
         * cache variable for default preference key.
         *
         * @var array
         */
        protected $productListConfigDefaultValueCache = [];

        /**
         * cache of all the data associated with a type
         * @param  string $type
         * @return array
         */
        public function getConfigOptionsCache(string $type) : array
        {
            if (! isset($this->configOptionsCache[self::class])) {
                $this->configOptionsCache[$type] = EcommerceConfig::get(self::class, 'options');
            }
            return $this->configOptionsCache[$type];

        }

        /**
         * return value for type x key x variable
         *
         * @param  string $type     e.g. SORT | FILTER
         * @param  string $key      e.g. best_match | price | lastest
         * @param  string $variable e.g. SQL | Title

         * @return mixed - empty if not found
         */
        public function getValueForProductListConfigType(string $type, string $key, string $variable)
        {
            $options = $this->getConfigOptionsCache($type);
            //check !!!
            if (isset($options[$key][$variable])) {
                return $options[$key][$variable];
                //all good
            } else {
                $userPreference = $this->getProductListConfigDefaultValue($type);
                if($key !== $userPreference) {
                    return $this->getValueForProductListConfigType($type, $userPreference, $variable);
                }
                if($userPreference !== 'default') {
                    return $this->getValueForProductListConfigType($type, 'default', $variable);
                }
                // //reset
                // // TODO: what is this for?
                // $this->getSortFilterDisplayNames($type, 'getVariable');
                // //clear bogus value from session ...
                // $sessionName = $this->getSortFilterDisplayNames($type, 'sessionName');
                // Controller::curr()->getRequest()->getSession()->set('ProductGroup_' . $sessionName, '');
            }

            return 'error';

        }


        /**
         * Checks for the most applicable user preferences for this page:
         *
         * 1. what is saved in Database for this page.
         * 2. what the parent product group has saved in the database
         * 3. what the standard default is.
         *
         * @param string $type - FILTER | SORT | DISPLAY
         *
         * @return string - returns the key
         */
        public function getProductListConfigDefaultValue(string $type): string
        {
            if (! isset($this->productListConfigDefaultValueCache[$type])) {
                $options = $this->getConfigOptionsCache($type);
                $dbVariableName = self::SORT_DISPLAY_NAMES[$type]['dbFieldName'];
                $dbValue = $this->rootGroup->{$dbVariableName};
                if ($dbValue === 'inherit' && $parent = $this->rootGroup->ParentGroup()) {
                    $this->productListConfigDefaultValueCache[$type] = $parent->getProductListConfigDefaultValue($type);
                } elseif ($dbValue && array_key_exists($dbValue, $options)) {
                    $this->productListConfigDefaultValueCache[$type] = $dbValue;
                } else {
                    $this->productListConfigDefaultValueCache[$type] = 'default';
                }
            }

            return $this->productListConfigDefaultValueCache[$type] ?? '';
        }

        /**
         * Returns the full sortFilterDisplayNames set, a subset, or one value
         * by either type (e.g. FILER) or variable (e.g dbFieldName)
         * or both.
         *
         * @param string $typeOrVariable FILTER | SORT | DISPLAY or sessionName, getVariable, etc...
         * @param string $variable:          sessionName, getVariable, etc...
         *
         * @return array | String
         */
        protected function getSortFilterDisplayNames(?string $typeOrVariable = '', ?string $variable = '')
        {
            if ($variable) {
                return self::SORT_DISPLAY_NAMES[$typeOrVariable][$variable];
            }

            $data = [];

            if (isset($this->sortFilterDisplayNames[$typeOrVariable])) {
                $data = self::SORT_DISPLAY_NAMES[$typeOrVariable];
            } elseif ($typeOrVariable) {
                foreach ($this->sortFilterDisplayNames as $group) {
                    $data[] = $group[$typeOrVariable] ?? 'error';
                }
            } else {
                $data = self::SORT_DISPLAY_NAMES;
            }

            return $data;
        }

    /**
    * @var SS_List
    */
    protected $products;

    /**
    * @var ProductGroupList
    */
    protected $productGroupList;

    /**
    * @var ProductListOptions
    */
    protected $productListOptions;

    /**
    * A list of relevant buyables that can not be purchased and therefore
    * should be excluded.
    *
    * @var int[]
    */
    protected $blockedProductsIds = [];

    /**
    * Root group to pull products from
    */
    protected $rootGroup = null;

    /**
    * @param string $buyableClass
    */
    public function __construct($productGroup, $buyableClass = Product::class)
    {
        $this
        ->setRootGroup($productGroup)
        ->setBuyableClass($buyableClass)
        ->buildDefaultList();
    }

    /**
    * @return string
    */
    public function getBuyableClass(): string
    {
        return $this->buyableClass;
    }


    /**
    * @param int $depth
    *
    * @return self
    */
    public function setLevelOfProductsToShow(int $depth): ProductList
    {
        $this->productGroups->setMaxDepth($depth);

        return $this;
    }

    public function getProductGroupList()
    {
        if(! $this->productGroupList) {
            $class = Config::inst()->get($this->rootGroup->ClassName, 'product_group_list_class');
            $this->productGroupList = Injector::inst()->get($class, $this->rootGroup);
        }

        return $this->productGroupList;
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

    /**
    * @return SilverStripe\ORM\PaginatedList
    */
    public function getPaginatedList(): PaginatedList
    {
        return PaginatedList::create($this->products);
    }

    /**
    * Returns the total number of products available before pagination is
    * applied.
    *
    * @return int
    */
    public function getRawCount()
    {
        return count($this->products);
    }

    /**
    * Filter the list of products
    *
    * @param array|string $filter
    *
    * @return self
    */
    public function applyFilter($filter = null): ProductList
    {
        if (is_array($filter) && count($filter)) {
            $this->products = $this->products->filter($filter);
        } elseif ($filter) {
            $this->products = $this->products->where(Convert::raw2sql($filter));
        }

        return $this;
    }

    /**
    * @return self
    */
    public function applyDefaultFilters(): ProductList
    {
        if (EcommerceConfig::inst()->OnlyShowProductsThatCanBePurchased) {
            $this->products = $this->products->filter([
                'AllowPurchase' => 1,
            ]);
        }

        $this->extend('onAfterApplyDefaultFilters');

        return $this;
    }

    /**
    * Sort the list of products
    *
    * @param array|string $sort
    *
    * @return self
    */
    public function applySort($sort = null): ProductList
    {
        if (is_array($sort) && count($sort)) {
            $this->products = $this->products->sort($sort);
        } elseif ($sort) {
            $this->products = $this->products->sort(Convert::raw2sql($sort));
        }
        // @todo

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
    public function removeExcludedProducts(): ProductList
    {
        foreach ($this->products as $buyable) {
            if (! $buyable->canPurchase()) {
                $this->blockedProductsIds[$buyable->ID] = $buyable->ID;
            }
        }

        if ($this->blockedProductsIds) {
            $this->products->exclude([
                'ID' => $this->blockedProductsIds,
            ]);
        }

        return $this;
    }

    /**
    * Is there more than x products.
    *
    * @param int $greaterThan
    *
    * @return bool
    */
    public function CountGreaterThanOne($greaterThan = 1) : bool
    {
        return $this->getRawCount() > $greaterThan;
    }

    /**
    * With the current product list, return all the {@link ProductGroup}
    * instances that the products are displayed under. This only returns the
    * direct parents.
    *
    * @return \SilverStripe\ORM\DataList|null
    */
    public function getParentGroups()
    {
        $ids = $this->products->columnUnique('ParentID');

        if ($ids) {
            return ProductGroup::get()->filter(['ID' => $ids]);
        }
    }

    /**
    * @SEE: important notes at the top of this file / class
    *
    * IMPORTANT: Adjusts allProducts and returns it...
    *
    * @return \SilverStripe\ORM\DataList
    */
    protected function getGroupFilter()
    {
        $levelToShow = $this->MyLevelOfProductsToShow();
        $cacheKey = 'GroupFilter_' . abs(intval($levelToShow + 999));
        if ($groupFilter = $this->retrieveObjectStore($cacheKey)) {
            $this->allProducts = $this->allProducts->where($groupFilter);
        } else {
            $groupFilter = '';
            $productFilterArray = [];
            //special cases
            if ($levelToShow < 0) {
                //no produts but if LevelOfProductsToShow = -1 then show all
                $groupFilter = ' (' . $levelToShow . ' = -1) ';
            } elseif ($levelToShow > 0) {
                $groupIDs = [$this->ID => $this->ID];
                $productFilterTemp = $this->getProductsToBeIncludedFromOtherGroups();
                $productFilterArray[$productFilterTemp] = $productFilterTemp;
                $childGroups = $this->ChildGroups($levelToShow);
                if ($childGroups && $childGroups->count()) {
                    foreach ($childGroups as $childGroup) {
                        $groupIDs[$childGroup->ID] = $childGroup->ID;
                        $productFilterTemp = $childGroup->getProductsToBeIncludedFromOtherGroups();
                        $productFilterArray[$productFilterTemp] = $productFilterTemp;
                    }
                }
                $groupFilter = ' ( "ParentID" IN (' . implode(',', $groupIDs) . ') ) ' . implode($productFilterArray) . ' ';
            } else {
                //fall-back
                $groupFilter = '"ParentID" < 0';
            }
            $this->allProducts = $this->allProducts->where($groupFilter);
            $this->saveObjectStore($groupFilter, $cacheKey);
        }

        return $this->allProducts;
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
    * If products are show in more than one group yhen this returns a where phrase for any products that are linked to this
    * product group.
    *
    * @return string
    */
    protected function getProductsToBeIncludedFromOtherGroups()
    {
        //TO DO: this should actually return
        //Product.ID = IN ARRAY(bla bla)
        $array = [];
        if ($this->getProductsAlsoInOtherGroups()) {
            $array = $this->AlsoShowProducts()->map('ID', 'ID')->toArray();
        }
        if (count($array)) {
            return ' OR ("Product"."ID" IN (' . implode(',', $array) . ')) ';
        }

        return '';
    }

    /**
    *@todo: temporary method$this->getProductList()-
    */
    public function getProductIds()
    {
        return $this->products->column('ID');
    }

    /**
    * Returns children ProductGroup pages of this group.
    *
    * @param int            $maxRecursiveLevel  - maximum depth , e.g. 1 = one level down - so no Child Groups are returned...
    * @param string | Array $filter             - additional filter to be added
    * @param int            $numberOfRecursions - current level of depth
    *
    * @return \SilverStripe\ORM\ArrayList (ProductGroups)
    */
    public function ChildGroups($maxRecursiveLevel, $filter = null, $numberOfRecursions = 0)
    {
        $arrayList = ArrayList::create();
        ++$numberOfRecursions;

        if ($numberOfRecursions < $maxRecursiveLevel) {
            if ($filter && is_string($filter)) {
                $filterWithAND = " AND ${filter}";
                $where = "\"ParentID\" = '{$this->ID}' ${filterWithAND}";
                $children = ProductGroup::get()->where($where);
            } elseif (is_array($filter) && count($filter)) {
                $filter += ['ParentID' => $this->ID];
                $children = ProductGroup::get()->filter($filter);
            } else {
                $children = ProductGroup::get()->filter([
                    'ParentID' => $this->ID,
                ]);
            }

            if ($children->count()) {
                foreach ($children as $child) {
                    $arrayList->push($child);
                    $arrayList->merge($child->ChildGroups($maxRecursiveLevel, $filter, $numberOfRecursions));
                }
            }
        }

        return $arrayList;
    }


}
