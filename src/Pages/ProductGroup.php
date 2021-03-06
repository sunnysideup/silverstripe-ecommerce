<?php

namespace Sunnysideup\Ecommerce\Pages;

use Page;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\Tab;
use SilverStripe\ORM\DataList;

use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Permission;

use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Cms\ProductsAndGroupsModelAdmin;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Forms\Fields\ProductProductImageUploadField;
use Sunnysideup\Ecommerce\Forms\Gridfield\Configs\GridFieldBasicPageRelationConfig;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\BaseApplyer;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\BaseProductList;
use Sunnysideup\Ecommerce\ProductsAndGroups\Template;

use Sunnysideup\Vardump\Vardump;

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
     * @var array
     */
    protected $recursiveValues = null;

    protected $baseProductList = null;

    private static $template_for_selection_of_products = Template::class;

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
        'DefaultSortOrder' => BaseApplyer::DEFAULT_NAME,
        'DefaultFilter' => BaseApplyer::DEFAULT_NAME,
        'DisplayStyle' => BaseApplyer::DEFAULT_NAME,
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
        'NumberOfProducts' => 'Direct Products',
        'AlsoShowProducts.Count' => 'Also Show Products',
        'Children.Count' => 'Child Categories',
    ];

    private static $casting = [
        'NumberOfProducts' => 'Int',
    ];

    private static $default_child = Product::class;

    private static $icon = 'sunnysideup/ecommerce:client/images/icons/productgroup-file.gif';

    private static $singular_name = 'Product Category';

    private static $plural_name = 'Product Categories';

    private static $description = 'A page the shows a bunch of products, based on your selection. By default it shows products linked to it (children)';

    private static $count = 0;

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

        return parent::canEdit($member);
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

        $calculatedNumberOfProductsPerPage = $this->getProductsPerPage();
        $numberOfProductsPerPageExplanation = $calculatedNumberOfProductsPerPage !== $this->NumberOfProductsPerPage ? _t('ProductGroup.CURRENTLVALUE', 'Current value: ') . $calculatedNumberOfProductsPerPage . ' ' . _t('ProductGroup.INHERITEDFROMPARENTSPAGE', ' (inherited from parent page because the current page is set to zero)') : '';
        $fields->addFieldToTab(
            'Root',
            Tab::create(
                'ProductDisplay',
                _t('ProductGroup.DISPLAY', 'Display'),
                $productsToShowField = DropdownField::create('LevelOfProductsToShow', _t('ProductGroup.PRODUCTSTOSHOW', 'Products to show'), $this->getShowProductLevelsArray()),
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
            if (! ClassHelpers::check_for_instance_of($this, ProductGroupSearchPage::class, false)) {
                $fields->addFieldsToTab(
                    'Root.OtherProductsShown',
                    [
                        HeaderField::create('ProductGroupsHeader', _t('ProductGroup.OTHERPRODUCTSTOSHOW', 'Other products to show ...')),
                        $this->getProductGroupsTable(),
                    ]
                );
            }
        }

        $fields->addFieldsToTab(
            'Root.Advanced',
            ReadonlyField::create(
                'DebugLink',
                'Debug Products and Links',
                DBField::create_field('HTMLText', '<a href="' . $this->Link() . '?showdebug=1">show debug information</a>')
            )
        );

        return $fields;
    }

    /**
     * used if you install lumberjack
     * @return string
     */
    public function getLumberjackTitle(): string
    {
        return _t('ProductGroup.BUYABLES', 'Products');
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

    /**
     * returns the template for providing related groups and products
     * @return Template
     */
    public function getTemplateForProductsAndGroups()
    {
        $className = $this->Config()->get('template_for_selection_of_products');

        return Injector::inst()->get($className);
    }

    /**
     * @param int $default, optional 10
     * @return int
     **/
    public function getProductsPerPage(?int $default = 10): int
    {
        return intval($this->recursiveValue('NumberOfProductsPerPage', $default));
    }

    /**
     * work out the recursive value in the Database for SORT / FILTER / DISPLAY
     * @param string $type SORT|FILTER|DISPLAY
     * @return string
     **/
    public function getListConfigCalculated(string $type): string
    {
        $field = $this->getSortFilterDisplayValues($type, 'dbFieldName');
        if ($field) {
            return $this->recursiveValue($field, BaseApplyer::DEFAULT_NAME);
        }
        return BaseApplyer::DEFAULT_NAME;
    }

    /**
     * Returns the number of product groups (children) to show in the current
     * product list based on the user setting for this page.
     *
     * @return int
     */
    public function getMyLevelOfProductsToShow(?int $defauult = 99): int
    {
        $value = $this->recursiveValue('LevelOfProductsToShow', 99);

        return intval($value);
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
        return 'filterforgroup/' . $this->URLSegment . ',' . $this->ID . '/';
    }

    /**
     * Link to the search results.
     *
     * @return string
     */
    public function SearchResultLink(?string $hash = ''): string
    {
        if ($hash) {
            $hash .= '/';
        }
        return $this->Link('searchresults/' . $hash);
    }

    /**
     * Retrieve the base list of products for this group
     *
     * @return BaseProductList
     */
    public function getBaseProductList()
    {
        if (! $this->baseProductList) {
            $className = $this->getTemplateForProductsAndGroups()->getBaseProductListClassName();
            $this->baseProductList = $className::inst(
                $this,
                $this->getBuyableClassName(),
                $this->recursiveValue('LevelOfProductsToShow', 99)
            );
        }
        return $this->baseProductList;
    }

    /**
     * @return DataList
     */
    public function getProducts()
    {
        return $this->getBaseProductList()->getProducts();
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
            $array = $this->AlsoShowProducts()->columnUnique();
        }
        return ArrayMethods::filter_array($array);
    }

    public function IDForSearchResults(): int
    {
        return $this->ID;
    }

    /**
     * Returns the parent page, but only if it is an instance of Product Group.
     *
     * @return ProductGroup|null
     */
    public function MainParentGroup(): ?ProductGroup
    {
        return $this->ParentGroup();
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
     * Returns the parent page, but only if it is an instance of Product Group.
     *
     * @return ProductGroup
     */
    public function TopParentGroup(): ProductGroup
    {
        $parent = $this->ParentGroup();
        if ($parent && $parent->exists()) {
            return $parent->TopParentGroup();
        }

        return $this;
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

    /**
     * Returns the full sortFilterDisplayNames set, a subset, or one value
     * by either type (e.g. FILTER) or variable (e.g dbFieldName)
     * or both.
     *
     * @param string $typeOrVariable    optional GROUPFILTER | FILTER | SORT | DISPLAY OR variable
     * @param string $variable:         optional getVariable, etc...
     *
     * @return array | String
     */
    public function getSortFilterDisplayValues(?string $typeOrVariable = '', ?string $variable = '')
    {
        return $this->getTemplateForProductsAndGroups()->getSortFilterDisplayValues($typeOrVariable, $variable);
    }

    /**
     * Returns the class we are working with.
     *
     * @return string
     */
    public function getBuyableClassName(): string
    {
        return EcommerceConfig::get(ProductGroup::class, 'base_buyable_class');
    }

    /**
     * Do products occur in more than one group.
     *
     * @return bool
     */
    public function getProductsAlsoInOtherGroups(): bool
    {
        return EcommerceConfig::inst()->ProductsAlsoInOtherGroups;
    }

    /**
     * @todo: add fitlerforgroup reverse
     * @return bool
     */
    public function CurrentOrSection()
    {
        $outcome = $this->LinkingMode();
        if ($outcome !== 'link') {
            $action = Controller::curr()->getRequest()->param('Action');
            if ($outcome === 'current' && in_array($action, ['filterforgroup', 'searchresults'], true)) {
                return 'section';
            }
            return $outcome;
        }
    }

    /**
     * Returns children ProductGroup pages of this group.
     *
     * @param int            $maxRecursiveLevel  - maximum depth , e.g. 1 = one level down - so no Child Child Groups are returned...
     *
     * @return \SilverStripe\ORM\SS_List (ProductGroups)
     */
    public function ChildCategoriesBasedOnProducts()
    {
        return $this->getBaseProductList()->getParentGroupsBasedOnProductsExcludingRootGroup();
    }

    public function ChildCategories(): DataList
    {
        return ProductGroup::get()->filter(['ParentID' => $this->ID]);
    }

    /**
     * @return array
     */
    public function getShowProductLevelsArray(): array
    {
        return $this->getBaseProductList()->getShowProductLevelsArray();
    }

    public function DebugMe(string $method)
    {
        if (Vardump::inst()->isSafe()) {
            return Vardump::inst()->vardumpMe($this->{$method}(), $method, static::class);
        }
    }

    protected function addDropDownForListConfig(FieldList $fields, string $type, string $title)
    {
        // display style
        $options = $this->getOptionsForDropdown($type);
        if (count($options) > 2) {
            $field = $this->getSortFilterDisplayValues($type, 'dbFieldName');
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
     * GROUPFILTER:
     * not available
     *
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
     * most likely values called: getDefaultFilterOptions,getDefaultSortOrderOptions, getDisplayStyleOptions
     *
     * @param string $type - FILTER | SORT | DISPLAY
     * @param bool   $withInherit - optional
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
        $method = 'get' . ucwords(strtolower($type)) . 'OptionsMap';
        $options = $this->getTemplateForProductsAndGroups()->{$method}();

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
        if (! isset($this->recursiveValues[$fieldNameOrMethod])) {
            $value = null;
            $fieldNameOrMethodWithGet = 'get' . $fieldNameOrMethod;
            $methodWorks = false;
            foreach ([$fieldNameOrMethod, $fieldNameOrMethodWithGet] as $method) {
                if ($this->hasMethod($method)) {
                    $methodWorks = true;
                    $outcome = $this->{$method}();
                    if ($outcome instanceof DataObject && $outcome->exists()) {
                        $value = $outcome;
                    } elseif ($outcome) {
                        $value = $outcome;
                    } else {
                        print_r($outcome);
                        user_error($fieldNameOrMethod . ' is empty');
                    }
                }
            }
            if ($methodWorks === false) {
                $value = $this->{$fieldNameOrMethod} ?? null;
            }
            if (! $value || $value === 'inherit') {
                $parent = $this->ParentGroup();
                if ($parent && $parent->exists() && $parent->ID !== $this->ID) {
                    $value = $parent->recursiveValue($fieldNameOrMethod, $default);
                } else {
                    $value = EcommerceConfig::inst()->recursiveValue($fieldNameOrMethod, $default);
                }
            }
            if (! $value) {
                $value = $default;
            }
            $this->recursiveValues[$fieldNameOrMethod] = $value;
        }

        return $this->recursiveValues[$fieldNameOrMethod];
    }
}
