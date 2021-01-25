<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductDisplayer;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductFilter;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductSorter;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\FinalProductList;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\RelatedProductGroups;
use Sunnysideup\Ecommerce\ProductsAndGroups\Settings\UserPreference;

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
            'isFullListVariable' => null,
            'defaultApplyer' => ProductSorter::class,
        ],
        'SORT' => [
            'value' => 'default',
            'configName' => 'sort_options',
            'getVariable' => 'sort',
            'dbFieldName' => 'DefaultSortOrder',
            'translationCode' => 'SORT_BY',
            'isFullListVariable' => null,
            'defaultApplyer' => ProductFilter::class,
        ],
        'DISPLAY' => [
            'value' => 'default',
            'configName' => 'display_styles',
            'getVariable' => 'display',
            'dbFieldName' => 'DisplayStyle',
            'translationCode' => 'DISPLAY_STYLE',
            'isFullListVariable' => 'IsShowFullList',
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
    public function getSortFilterDisplayNames(?string $typeOrVariable = '', ?string $variable = '')
    {
        $data = $this->getSortFilterDisplayNamesData();
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
        $data = $this->getSortFilterDisplayNamesData();
        if (isset($data[$type])) {
            return true;
        } elseif ($showError) {
            user_error('Invalid type supplied: ' . $type . 'Please use: SORT / FILTER / DISPLAY');
        }
        return false;
    }

    /**
     * cache of all the data associated with a type
     * @param  string $type
     * @return array
     */
    public function getConfigOptionsCache(string $type): array
    {
        if (! isset($this->configOptionsCache[self::class])) {
            $this->configOptionsCache[$type] = EcommerceConfig::get(self::class, 'options');
        }
        return $this->configOptionsCache[$type];
    }
}
