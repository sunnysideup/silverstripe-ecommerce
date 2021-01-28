<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\BaseApplyer;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductDisplayer;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductFilter;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductSorter;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\BaseProductList;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\FinalProductList;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\RelatedProductGroups;
use Sunnysideup\Ecommerce\ProductsAndGroups\Settings\UserPreference;

/**
 * In terms of ProductAndGroupsLists, this class knows all about
 * the classes being used and the settings associated with it.
 * It is linked to the ProductGroup and so it does not know about user preferences
 * and other settings that are set in run-time.
 */
class Template
{
    use Configurable;
    use Injectable;

    /**
     * list of sort / filter / display variables.
     *
     * @var array
     */
    protected const SORT_DISPLAY_NAMES = [
        'FILTER' => [
            'value' => 'default',
            'configName' => 'filter_options',
            'getVariable' => 'filter',
            'dbFieldName' => 'DefaultFilter',
            'defaultApplyer' => ProductSorter::class,
        ],
        'SORT' => [
            'value' => 'default',
            'configName' => 'sort_options',
            'getVariable' => 'sort',
            'dbFieldName' => 'DefaultSortOrder',
            'translationCode' => 'SORT_BY',
            'defaultApplyer' => ProductFilter::class,
        ],
        'DISPLAY' => [
            'value' => 'default',
            'configName' => 'display_styles',
            'getVariable' => 'display',
            'dbFieldName' => 'DisplayStyle',
            'translationCode' => 'DISPLAY_STYLE',
            'defaultApplyer' => ProductDisplayer::class,
        ],
    ];

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

    public function getData()
    {
        return self::SORT_DISPLAY_NAMES;
    }

    public function getBaseProductListClassName(): string
    {
        return $this->Config()->get('base_product_list_class_name');
    }

    public function getFinalProductListClassName(): string
    {
        return $this->Config()->get('final_product_list_class_name');
    }

    public function getProductGroupListClassName(): string
    {
        return $this->Config()->get('product_group_list_class_name');
    }

    public function getUserPreferencesClassName(): string
    {
        return $this->Config()->get('user_preferences_class_name');
    }

    /**
     * Returns the full sortFilterDisplayNames set, a subset, or one value
     * by either type (e.g. FILER) or variable (e.g dbFieldName)
     * or both.
     *
     * @param string $typeOrVariable    FILTER | SORT | DISPLAY OR variable
     * @param string $variable:         getVariable, etc...
     *
     * @return array | String
     */
    public function getSortFilterDisplayValues(?string $typeOrVariable = '', ?string $variable = '')
    {
        $data = $this->getData();
        if ($variable) {
            return $data[$typeOrVariable][$variable];
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

    public function getShowProductLevels(): array
    {
        $className = $this->getProductGroupListClassName();

        return Injector::inst()->get($className)->getShowProductLevels();
    }

    /**
     * @param  string  $type      FILTER|SORT|DISPLAY
     * @param  boolean $showError optional
     * @return bool
     */
    public function IsSortFilterDisplayNamesType(string $type, ?bool $showError = true): bool
    {
        $data = $this->getData();
        if (isset($data[$type])) {
            return true;
        } elseif ($showError) {
            user_error('Invalid type supplied: ' . $type . 'Please use: SORT / FILTER / DISPLAY');
        }
        return false;
    }

    /**
     * cache of all the data associated with a type
     * @param  string $classNameOrType
     * @return array
     */
    public function getOptions(string $classNameOrType): array
    {
        $obj = $this->getApplyer($classNameOrType);

        return $obj->getOptions();
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
    public function getUserPreferencesTitle(string $type, ?string $value): string
    {
        $method = 'get' . $this->getSortFilterDisplayValues($type, 'dbFieldName') . 'Title';
        $value = $this->{$method}($value);
        if ($value) {
            return $value;
        }

        return _t('ProductGroup.UNKNOWN', 'UNKNOWN USER SETTING');
    }

    /**
     * returns a dropdown like list of options for a BaseClass class name
     * @param  string $classNameOrType
     *
     * @return array
     */
    public function getOptionsMap(string $classNameOrType): array
    {
        $obj = $this->getApplyer($classNameOrType);

        return $obj->getOptionsMap();
    }

    /**
     * returns a dropdown like list of options for a filters
     * @param  string $className
     *
     * @return array
     */
    public function getDefaultFilterOptions(): array
    {
        return $this->getOptionsMap('FILTER');
    }

    /**
     * returns a dropdown like list of options for a sorters
     * @param  string $className
     *
     * @return array
     */
    public function getDefaultSortOrderOptions(): array
    {
        return $this->getOptionsMap('SORT');
    }

    /**
     * returns a dropdown like list of options for a display styles
     * @param  string $className
     *
     * @return array
     */
    public function getDisplayStyleOptions(): array
    {
        return $this->getOptionsMap('DISPLAY');
    }

    public function getDefaultFilterList(string $linkTemplate, ?string $currentKey = '', ?bool $ajaxify = true): ArrayList
    {
        return $this->getOptionsList('FILTER', $linkTemplate, $currentKey, $ajaxify);
    }

    public function getDefaultSortOrderList(string $linkTemplate, ?string $currentKey = '', ?bool $ajaxify = true): ArrayList
    {
        return $this->getOptionsList('SORT', $linkTemplate, $currentKey, $ajaxify);
    }

    public function getDisplayStyleList(string $linkTemplate, ?string $currentKey = '', ?bool $ajaxify = true): ArrayList
    {
        return $this->getOptionsList('DISPLAY', $linkTemplate, $currentKey, $ajaxify);
    }

    public function getDefaultFilterTitle(?string $value = ''): array
    {
        return $this->getTitle('FILTER', $value);
    }

    public function getDefaultSortOrderTitle(?string $value = ''): array
    {
        return $this->getTitle('SORT', $value);
    }

    public function getDisplayStyleTitle(?string $value = ''): array
    {
        return $this->getTitle('DISPLAY', $value);
    }

    public function getTitle(string $className, ?string $value = ''): string
    {
        $obj = $this->getApplyer($className);

        return $obj->getTitle($value);
    }

    /**
     * todo: CHECK!
     * @param  string $type
     * @return string
     */
    public function getApplyerClassName(string $type): string
    {
        if ($this->IsSortFilterDisplayNamesType($type)) {
            return $this->getSortFilterDisplayValues($type, 'defaultApplyer');
        }
        return '';
    }

    /**
     * you can provide type or class name
     * @param  string $classNameOrType
     * @return BaseApplyer
     */
    public function getApplyer(string $classNameOrType, $finalProductList = null)
    {
        $className = $classNameOrType;
        if ($this->IsSortFilterDisplayNamesType($classNameOrType, false)) {
            $className = $this->getApplyerClassName($classNameOrType);
        }
        $obj = new $className($finalProductList);
        ClassHelpers::check_for_instance_of($obj, BaseApplyer::class);

        return $obj;
    }
}
