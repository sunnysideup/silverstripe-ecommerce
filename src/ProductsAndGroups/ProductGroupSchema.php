<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\ArrayList;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Pages\ProductGroupController;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\BaseApplyer;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductDisplayer;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductFilter;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductSearchFilter;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductGroupFilter;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductSorter;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\BaseProductList;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\FinalProductList;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\RelatedProductGroups;
use Sunnysideup\Ecommerce\ProductsAndGroups\Settings\UserPreference;
use Sunnysideup\Vardump\DebugTrait;

/**
 * In terms of ProductAndGroupsLists, this class knows all about
 * the classes being used and the settings associated with it.
 * It is linked to the ProductGroup and so it does not know about user preferences
 * and other settings that are set in run-time.
 */
class ProductGroupSchema
{
    use Configurable;
    use Injectable;
    use DebugTrait;

    /**
     * list of sort / filter / display variables.
     *
     * @var array
     */
    protected const SORT_DISPLAY_NAMES = [
        'SEARCHFILTER' => [
            'getVariable' => 'searchfilter',
            'dbFieldName' => '',
            'translationCode' => 'SEARCH_RESULTS',
            'defaultApplyer' => ProductSearchFilter::class,
        ],
        'GROUPFILTER' => [
            'getVariable' => 'groupfilter',
            'dbFieldName' => '',
            'translationCode' => 'GROUPFILTER_BY',
            'defaultApplyer' => ProductGroupFilter::class,
        ],
        'FILTER' => [
            'getVariable' => 'filter',
            'dbFieldName' => 'DefaultFilter',
            'translationCode' => 'FILTER_BY',
            'defaultApplyer' => ProductFilter::class,
        ],
        'SORT' => [
            'getVariable' => 'sort',
            'dbFieldName' => 'DefaultSortOrder',
            'translationCode' => 'SORT_BY',
            'defaultApplyer' => ProductSorter::class,
        ],
        'DISPLAY' => [
            'getVariable' => 'display',
            'dbFieldName' => 'DisplayStyle',
            'translationCode' => 'DISPLAY_STYLE',
            'defaultApplyer' => ProductDisplayer::class,
        ],
    ];

    /**
     * @var array<null|string, mixed>|mixed
     */
    public $sortFilterDisplayNames;

    /**
     * @var string
     */
    private static $product_group_list_class_name = RelatedProductGroups::class;

    /**
     * @var string
     */
    private static $base_product_list_class_name = BaseProductList::class;

    /**
     * @var string
     */
    private static $final_product_list_class_name = FinalProductList::class;

    /**
     * @var string
     */
    private static $user_preferences_class_name = UserPreference::class;

    /**
     * @var string
     */
    private static $debug_provider_class_name = Debug::class;

    public function getData()
    {
        return self::SORT_DISPLAY_NAMES;
    }

    public function getBaseProductListClassName(): string
    {
        return Config::inst()->get(static::class, 'base_product_list_class_name');
    }

    public function getFinalProductListClassName(): string
    {
        return Config::inst()->get(static::class, 'final_product_list_class_name');
    }

    public function getProductGroupListClassName(): string
    {
        return Config::inst()->get(static::class, 'product_group_list_class_name');
    }

    public function getUserPreferencesClassName(): string
    {
        return Config::inst()->get(static::class, 'user_preferences_class_name');
    }

    public function getDebugProvider(): string
    {
        return Config::inst()->get(static::class, 'debug_provider_class_name');
    }

    /**
     * @param ProductGroupController $rootGroupController
     * @param ProductGroup           $rootGroup
     */
    public function getDebugProviderAsObject($rootGroupController, $rootGroup): Debug
    {
        $className = $this->getDebugProvider();

        return new $className($rootGroupController, $rootGroup);
    }

    /**
     * Returns the full sortFilterDisplayNames set, a subset, or one value
     * by either type (e.g. FILER) or variable (e.g dbFieldName)
     * or both.
     *
     * @param string $typeOrVariable FILTER | SORT | DISPLAY OR variable
     *
     * @return array|string
     */
    public function getSortFilterDisplayValues(?string $typeOrVariable = '', ?string $variable = '')
    {
        $data = $this->getData();
        if ($variable) {
            return $data[$typeOrVariable][$variable] ?? 'error';
        }

        $newData = [];

        if (isset($this->sortFilterDisplayNames[$typeOrVariable])) {
            $newData = $data[$typeOrVariable];
        } elseif ($typeOrVariable) {
            foreach ($this->sortFilterDisplayNames as $group) {
                $newData[] = $group[$typeOrVariable] ?? 'error';
            }
        } else {
            $newData = $data;
        }

        return $newData;
    }

    /**
     * @param string $type      FILTER|SORT|DISPLAY
     * @param bool   $showError optional
     */
    public function IsSortFilterDisplayNamesType(string $type, ?bool $showError = true): bool
    {
        $data = $this->getData();
        if (isset($data[$type])) {
            return true;
        }
        if ($showError) {
            user_error('Invalid type supplied: ' . $type . 'Please use: SORT / FILTER / DISPLAY / SEARCHFILTER');
        }

        return false;
    }

    public function getOptions(string $classNameOrType): array
    {
        $obj = $this->getApplyer($classNameOrType);

        return $obj->getOptions();
    }

    /**
     * returns a dropdown like list of options for a searches.
     */
    public function getSearchFilterOptionsMap(): array
    {
        return $this->getOptionsMap('SEARCHFILTER');
    }

    /**
     * returns a dropdown like list of options for a filters.
     */
    public function getGroupFilterOptionsMap(): array
    {
        return $this->getOptionsMap('GROUPFILTER');
    }

    /**
     * returns a dropdown like list of options for a filters.
     */
    public function getFilterOptionsMap(): array
    {
        return $this->getOptionsMap('FILTER');
    }

    /**
     * returns a dropdown like list of options for a sorters.
     */
    public function getSortOptionsMap(): array
    {
        return $this->getOptionsMap('SORT');
    }

    /**
     * returns a dropdown like list of options for a display styles.
     */
    public function getDisplayOptionsMap(): array
    {
        return $this->getOptionsMap('DISPLAY');
    }

    /**
     * todo: CHECK!
     */
    public function getApplyerClassName(string $type): string
    {
        if ($this->IsSortFilterDisplayNamesType($type)) {
            return $this->getSortFilterDisplayValues($type, 'defaultApplyer');
        }

        return '';
    }

    /**
     * you can provide type or class name.
     *
     * @param null|mixed $finalProductList
     *
     * @return BaseApplyer
     */
    public function getApplyer(string $classNameOrType, $finalProductList = null)
    {
        $className = $classNameOrType;
        $betterClassName = $this->getApplyerClassName($classNameOrType);
        if ($betterClassName) {
            $className = $betterClassName;
        }
        $obj = new $className($finalProductList);
        ClassHelpers::check_for_instance_of($obj, BaseApplyer::class);

        return $obj;
    }

    /**
     * returns a dropdown like list of options for a BaseClass class name.
     */
    protected function getOptionsMap(string $classNameOrType): array
    {
        $obj = $this->getApplyer($classNameOrType);

        return $obj->getOptionsMap();
    }
}
