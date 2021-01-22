<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups;

use Page;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\Tab;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Permission;

use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Cms\ProductsAndGroupsModelAdmin;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Forms\Fields\ProductProductImageUploadField;
use Sunnysideup\Ecommerce\Forms\Gridfield\Configs\GridFieldBasicPageRelationConfig;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductDisplayer;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductFilter;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductSorter;
use Sunnysideup\Ecommerce\ProductsAndGroups\BaseProductList;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\ProductGroupList;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\ProductList;
use Sunnysideup\Ecommerce\ProductsAndGroups\Templats\UserPreference;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\FinalProductList;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

class Template
{
    use Configurable;
    use Injectable;

    /**
     * @var string
     */
    private static $product_group_list_class_name = ProductGroupList::class;

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


}
