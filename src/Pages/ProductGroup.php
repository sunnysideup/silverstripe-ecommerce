<?php

namespace Sunnysideup\Ecommerce\Pages;

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
use Sunnysideup\Ecommerce\ProductsAndGroups\BaseProductList;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\ProductGroupList;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\ProductList;
use Sunnysideup\Ecommerce\ProductsAndGroups\FinalProductList;

/**
 * Product Group is a 'holder' for Products within the CMS
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @subpackage: Pages
 */
class ProductGroup extends Page
{
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
     * @var array
     *            List of options to show products.
     *            With it, we provide a bunch of methods to access and edit the options.
     *            NOTE: we can not have an option that has a zero key ( 0 => "none"), as this does not work
     *            (as it is equal to not completed yet - not yet entered in the Database).
     */
    protected $showProductLevels = [
        99 => 'All Child Products (default)',
        -2 => 'None',
        -1 => 'All products',
        1 => 'Direct Child Products',
        2 => 'Two Levels Down Products',
        3 => 'Three Levels Down Products',
        4 => 'Four Levels Down Product',
    ];

    /**
     * @var array
     */
    protected $recursiveValues = null;

    /**
     * @var ProductList
     */
    protected $productList;

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
    private static $base_buyable_class = Product::class;

    private static $table_name = 'ProductGroup';

    private static $db = [
        'NumberOfProductsPerPage' => 'Int',
        'LevelOfProductsToShow' => 'Int',
        'DefaultSortOrder' => 'Varchar(20)',
        'DefaultFilter' => 'Varchar(20)',
        'DisplayStyle' => 'Varchar(20)',
    ];

    private static $has_one = [
        'Image' => Image::class,
    ];

    private static $owns = [
        'Image',
    ];

    private static $belongs_many_many = [
        'AlsoShowProducts' => Product::class,
    ];

    private static $defaults = [
        'DefaultSortOrder' => 'default',
        'DefaultFilter' => 'default',
        'DisplayStyle' => 'default',
        'LevelOfProductsToShow' => 99,
    ];

    private static $indexes = [
        'LevelOfProductsToShow' => true,
        'DefaultSortOrder' => true,
        'DefaultFilter' => true,
        'DisplayStyle' => true,
    ];

    private static $summary_fields = [
        'Image.CMSThumbnail' => 'Image',
        'Title' => 'Category',
        'NumberOfProducts' => 'Direct Product Count',
    ];

    private static $casting = [
        'NumberOfProducts' => 'Int',
    ];

    private static $default_child = Product::class;

    private static $icon = 'sunnysideup/ecommerce:client/images/icons/productgroup-file.gif';

    private static $singular_name = 'Product Category';

    private static $plural_name = 'Product Categories';

    private static $description = 'A page the shows a bunch of products, based on your selection. By default it shows products linked to it (children)';

    /**
     * @var string
     */
    private static $product_group_list_class_name = ProductGroupList::class;

    public function SummaryFields()
    {
        return Config::inst()->get(ProductGroup::class, 'summary_fields', Config::UNINHERITED);
    }

    public function i18n_singular_name()
    {
        return _t('ProductGroup.SINGULARNAME', 'Product Category');
    }

    public function i18n_plural_name()
    {
        return _t('ProductGroup.PLURALNAME', 'Product Categories');
    }

    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canCreate($member, $context);
    }

    /**
     * Shop Admins can edit.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member, $context, $context);
    }

    /**
     * Standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canDelete($member = null, $context = [])
    {
        if (is_a(Controller::curr(), EcommerceConfigClassNames::getName(ProductsAndGroupsModelAdmin::class))) {
            return false;
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return $this->canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canPublish($member = null)
    {
        return parent::canEdit($member);
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Images', ProductProductImageUploadField::create('Image', _t('Product.IMAGE', 'Product Group Image')));

        $calculatedNumberOfProductsPerPage = $this->getNumberOfProductsPerPage();
        $numberOfProductsPerPageExplanation = $calculatedNumberOfProductsPerPage !== $this->NumberOfProductsPerPage ? _t('ProductGroup.CURRENTLVALUE', 'Current value: ') . $calculatedNumberOfProductsPerPage . ' ' . _t('ProductGroup.INHERITEDFROMPARENTSPAGE', ' (inherited from parent page because the current page is set to zero)') : '';
        $fields->addFieldToTab(
            'Root',
            Tab::create(
                'ProductDisplay',
                _t('ProductGroup.DISPLAY', 'Display'),
                $productsToShowField = DropdownField::create('LevelOfProductsToShow', _t('ProductGroup.PRODUCTSTOSHOW', 'Products to show'), $this->showProductLevels),
                HeaderField::create('WhatProductsAreShown', _t('ProductGroup.WHATPRODUCTSSHOWN', _t('ProductGroup.OPTIONSSELECTEDBELOWAPPLYTOCHILDGROUPS', 'Inherited options'))),
                $numberOfProductsPerPageField = NumericField::create('NumberOfProductsPerPage', _t('ProductGroup.PRODUCTSPERPAGE', 'Number of products per page'))
            )
        );
        $numberOfProductsPerPageField->setDescription($numberOfProductsPerPageExplanation);
        if ($calculatedNumberOfProductsPerPage && ! $this->NumberOfProductsPerPage) {
            $this->NumberOfProductsPerPage = 0;
            $numberOfProductsPerPageField->setAttribute('placeholder', $calculatedNumberOfProductsPerPage);
        }

        $this->addDropDownForListConfig($fields, 'FILTER', _t('ProductGroup.DEFAULTFILTER', 'Default Filter'));

        $this->addDropDownForListConfig($fields, 'SORT', _t('ProductGroup.DEFAULTSORTORDER', 'Default Sort Order'));

        $this->addDropDownForListConfig($fields, 'DISPLAY', _t('ProductGroup.DEFAULTDISPLAYSTYLE', 'Default Display Style'));

        $config = EcommerceConfig::inst();

        if ($config->ProductsAlsoInOtherGroups) {
            if (! $this instanceof ProductGroupSearchPage) {
                $fields->addFieldsToTab(
                    'Root.OtherProductsShown',
                    [
                        HeaderField::create('ProductGroupsHeader', _t('ProductGroup.OTHERPRODUCTSTOSHOW', 'Other products to show ...')),
                        $this->getProductGroupsTable(),
                    ]
                );
            }
        }

        return $fields;
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

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $urlSegments = ProductGroup::get()->column('URLSegment');

        foreach ($urlSegments as $urlSegment) {
            $counts = array_count_values($urlSegments);
            $hasDuplicates = $counts[$urlSegment] > 1 ? true : false;

            if ($hasDuplicates) {
                DB::alteration_message('found duplicates for ' . $urlSegment, 'deleted');
                $checkForDuplicatesURLSegments = ProductGroup::get()
                    ->filter(['URLSegment' => $urlSegment]);

                if ($checkForDuplicatesURLSegments->count()) {
                    $count = 0;
                    foreach ($checkForDuplicatesURLSegments as $productGroup) {
                        if ($count > 0) {
                            $oldURLSegment = $productGroup->URLSegment;
                            DB::alteration_message(' ... Correcting URLSegment for ' . $productGroup->Title . ' with ID: ' . $productGroup->ID, 'deleted');
                            $productGroup->writeToStage('Stage');
                            $productGroup->publishRecursive();

                            $newURLSegment = $productGroup->URLSegment;
                            DB::alteration_message(' ... .... from ' . $oldURLSegment . ' to ' . $newURLSegment, 'created');
                        }

                        $count++;
                    }
                }
            }
        }
    }

    public function getProductListConfigDefaultValue(string $type)
    {
        $method = 'get' . $this->getSortFilterDisplayNames($type, 'dbFieldName') . 'Calculated';
        if ($method) {
            return $this->{$method}();
        }

        return 'default';
    }

    /**
     * @return int
     **/
    public function getProductsPerPage(): int
    {
        return $this->recursiveValue('NumberOfProductsPerPage', 10);
    }

    /**
     * Returns the number of products to show per page
     * @alias of getProductsPerPage
     *
     * @return int
     */
    public function getNumberOfProductsPerPage(): int
    {
        return $this->getProductsPerPage();
    }

    /**
     * @return int
     * @alias of ProductsPerPage
     **/
    public function getListConfigCalculated(string $type): string
    {
        $field = $this->getSortFilterDisplayNames($type, 'dbFieldName');

        return $this->recursiveValue($field, 'default');
    }

    /**
     * @return string
     **/
    public function getDefaultFilterCalculated(): string
    {
        return $this->recursiveValue('DefaultFilter', 'default');
    }

    /**
     * @return string
     **/
    public function getDefaultSortOrderCalculated(): string
    {
        return $this->recursiveValue('DefaultSortOrder', 'default');
    }

    /**
     * @return int
     * @alias of ProductsPerPage
     **/
    public function getDisplayStyleCalculated(): string
    {
        return $this->recursiveValue('DisplayStyle', 'default');
    }

    /**
     * Returns the number of product groups (children) to show in the current
     * product list based on the user setting for this page.
     *
     * @return int
     */
    public function getLevelOfProductsToShow(): int
    {
        return $this->recursiveValue('LevelOfProductsToShow', 99);
    }

    /**
     * used if you install lumberjack
     * @return string
     */
    public function getLumberjackTitle(): string
    {
        return _t('ProductGroup.BUYABLES', 'Products');
    }

    /**
     * KEEP FOR LEGACY
     * add this segment to the end of a Product Group
     * link to create a cross-filter between the two categories.
     *
     * @return string
     */
    public function FilterForGroupLinkSegment(): string
    {
        return 'filterforgroup/' . $this->URLSegment . '/';
    }

    /**
     * Retrieve the base list of products for this group
     *
     * @return BaseProductList
     */
    public function getBaseProductList()
    {
        return $this->getFinalProductList()->getBaseProductList();
    }

    public function ProductsShowable($filter = null)
    {
        return $this->getFinalProductList()
            ->applyFilter($filter);
    }

    public function currentInitialProductsAsCachedArray(?string $filter = 'default'): array
    {
        return $this->ProductsShowable()
            ->getProductIds();
    }

    /**
     * Retrieve a list of products, based on the given parameters.
     *
     * This method is usually called by the various controller methods.
     *
     * The extraFilter helps you to select different products depending on the
     * method used in the controller.
     *
     * To paginate this
     *
     * @param array|string $extraFilter          Additional SQL filters to apply to the Product retrieval
     * @param array|string $alternativeSort      Additional SQL for sorting
     *
     * @return FinalProductList
     */
    public function getFinalProductList($extraFilter = null, $alternativeSort = null)
    {
        if (! $this->productList) {
            $className = $this->getFinalProductListClassName();
            $list = $className::inst($this);
        }

        if ($extraFilter) {
            $list = $list->applyFilter($extraFilter);
        }

        if ($alternativeSort) {
            $list = $list->applySorter($alternativeSort);
        }

        return $list;
    }

    /**
     * Returns children ProductGroup pages of this group.
     *
     * @param int            $maxRecursiveLevel  - maximum depth , e.g. 1 = one level down - so no Child Child Groups are returned...
     * @param string | Array $filter             - additional filter to be added
     *
     * @return \SilverStripe\ORM\ArrayList (ProductGroups)
     */
    public function ChildGroups(int $maxRecursiveLevel, $filter = null): ArrayList
    {
        return $this->getBaseProductList()->getGroups($maxRecursiveLevel, $filter);
    }

    /**
     * If products are show in more than one group then this returns an array for any products that are linked to this
     * product group.
     *
     * @return array
     */
    public function getProductsToBeIncludedFromOtherGroupsArray(): array
    {
        $array = [];
        if ($this->getProductsAlsoInOtherGroups() && $this->AlsoShowProducts()->count()) {
            $array = $this->AlsoShowProducts()->columnUnique('ID');
        }
        return ArrayMethods::filter_array($array);
    }

    /**
     * Returns the parent page, but only if it is an instance of Product Group.
     *
     * @return ProductGroup|null
     */
    public function ParentGroup(): ?ProductGroup
    {
        return ProductGroup::get()->byID($this->ParentID);
    }

    /**
     * Recursively generate a product menu.
     *
     * @param string $filter
     *
     * @return \SilverStripe\ORM\ArrayList (ProductGroups)
     */
    public function GroupsMenu($filter = 'ShowInMenus = 1')
    {
        if ($parent = $this->ParentGroup()) {
            return $parent->GroupsMenu($filter);
        }

        return $this->ChildGroups(1, $filter);
    }

    /**
     * returns a "BestAvailable" image if the current one is not available
     * In some cases this is appropriate and in some cases this is not.
     * For example, consider the following setup
     * - product A with three variations
     * - Product A has an image, but the variations have no images
     * With this scenario, you want to show ONLY the product image
     * on the product page, but if one of the variations is added to the
     * cart, then you want to show the product image.
     * This can be achieved bu using the BestAvailable image.
     *
     * @return Image|null
     */
    public function BestAvailableImage()
    {
        return $this->recursiveValue('Image', null);
    }

    /**
     * tells us if the current page is part of e-commerce.
     *
     * @return bool
     */
    public function IsEcommercePage(): bool
    {
        return true;
    }

    /**
     * the number of direct descendants
     * @return int
     */
    public function getNumberOfProducts(): int
    {
        return Product::get()->filter(['ParentID' => $this->ID])->count();
    }

    public function IsSortFilterDisplayNamesType(string $type, ?bool $showError = true): bool
    {
        $data = $this->getSortFilterDisplayNamesData();
        if (isset($data[$type])) {
            return true;
        } elseif ($showError) {
            user_error('Invalid type supplied: ' . $type . 'Please use: SORT / FILTER / DISPLAY');
            return false;
        }
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

    protected function addDropDownForListConfig(FieldList $fields, string $type, string $title)
    {
        // display style
        $options = $this->getOptionsForDropdown($type);
        if (count($options) > 2) {
            $field = $this->getSortFilterDisplayNames($type, 'dbFieldName');
            if ($this->{$field} === 'inherit') {
                $key = $this->getListConfigCalculated($type);
                $actualValue = ' (' . ($options[$key] ?? _t('ProductGroup.ERROR', 'ERROR')) . ')';
                $options['inherit'] = _t('ProductGroup.INHERIT', 'Inherit') . $actualValue;
            }
            $fields->addFieldToTab(
                'Root.ProductDisplay',
                $field = DropdownField::create($field, $title, $options)
            );
            $field->setDescription(
                _t(
                    'ProductGroup.INHERIT_RIGHT_TITLE',
                    "Inherit means that the parent page value is used - and if there is no relevant parent page then the site's default value is used."
                )
            );
        }
    }

    /**
     * SORT:
     * returns an array of Key => Title for sort options.
     *
     * FILTER:
     * Returns options for the dropdown of filter options.
     *
     * DISPLAY:
     * Returns the options for product display styles.
     * In the configuration you can set which ones are available.
     * If one is available then you must make sure that the corresponding template is available.
     * For example, if the display style is
     * MyTemplate => "All Details"
     * Then you must make sure MyTemplate.ss exists.
     *
     * @param string $type - FILTER | SORT | DISPLAY
     *
     * @return array
     */
    protected function getOptionsForDropdown(string $type, ?bool $withInherit = true)
    {
        $array = [];
        if ($withInherit) {
            $inheritTitle = _t('ProductGroup.INHERIT', 'Inherit');
            $array = ['inherit' => $inheritTitle];
        }
        $method = 'get' . $this->getSortFilterDisplayNames($type, 'dbFieldName') . 'Options';
        $options = $this->getFinalProductList()->{$method}();

        return array_merge($array, $options);
    }

    /**
     * Used in getCMCFields.
     *
     * @return GridField
     */
    protected function getProductGroupsTable()
    {
        return GridField::create(
            'AlsoShowProducts',
            _t('ProductGroup.OTHER_PRODUCTS_SHOWN_IN_THIS_GROUP', 'Other products shown in this group ...'),
            $this->AlsoShowProducts(),
            GridFieldBasicPageRelationConfig::create()
        );
    }

    /**
     * get recursive value for Product Group and check EcommerceConfig as last resort
     * @param  string $fieldNameOrMethod
     * @param  mixed  $default
     * @return mixed
     */
    protected function recursiveValue(string $fieldNameOrMethod, $default = null)
    {
        if (! isset($this->recursiveValues[$fieldName])) {
            $value = null;
            $fieldNameOrMethodWithGet = 'get' . $fieldNameOrMethod;
            if ($this->hasMethod($fieldNameOrMethod)) {
                $outcome = $this->{$fieldNameOrMethod}();
                if (is_object($value) && $value->exists()) {
                    $value = $outcome;
                }
            } elseif ($this->hasMethod($fieldNameOrMethodWithGet)) {
                $outcome = $this->{$fieldNameOrMethodWithGet}();
                if (is_object($value) && $value->exists()) {
                    $value = $outcome;
                }
            } else {
                $value = $this->{$fieldNameOrMethod} ?? null;
            }
            if (! $value || $value = 'inherit') {
                if ($parent = $this->ParentGroup()) {
                    $value = $parent->recursiveValue($fieldNameOrMethod, $default);
                } else {
                    $value = EcommerceConfig::inst()->{$fieldNameOrMethod};
                }
            }
            if (! $value) {
                $value = $default;
            }
            $this->recursiveValues[$fieldNameOrMethod] = $value;
        }

        return $this->recursiveValues[$fieldNameOrMethod];
    }

    /**
     * Do products occur in more than one group.
     *
     * @return bool
     */
    protected function getProductsAlsoInOtherGroups(): bool
    {
        return EcommerceConfig::inst()->ProductsAlsoInOtherGroups;
    }

    /**
     * Returns the class we are working with.
     *
     * @return string
     */
    protected function getBuyableClassName(): string
    {
        return EcommerceConfig::get(ProductGroup::class, 'base_buyable_class');
    }

    protected function getSortFilterDisplayNamesData(): array
    {
        $data = self::SORT_DISPLAY_NAMES;
        $outcome = $this->extend('updateSorterDisplayNamesData', $data);
        if ($outcome !== null) {
            $data = $outcome;
        }

        return $data;
    }
}
