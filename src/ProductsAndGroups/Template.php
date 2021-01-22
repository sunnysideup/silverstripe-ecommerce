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
use Sunnysideup\Ecommerce\ProductsAndGroups\FinalProductList;

class Template
{

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

}
