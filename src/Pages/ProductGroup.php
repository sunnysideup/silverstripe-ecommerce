<?php

namespace Sunnysideup\Ecommerce\Pages;

use Page;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\ArrayData;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Api\EcommerceCache;
use Sunnysideup\Ecommerce\Cms\ProductsAndGroupsModelAdmin;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Forms\Fields\ProductProductImageUploadField;
use Sunnysideup\Ecommerce\Forms\Gridfield\Configs\GridFieldBasicPageRelationConfig;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Search\ProductGroupSearchTable;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\BaseApplyer;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductSearchFilter;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\BaseProductList;
use Sunnysideup\Ecommerce\ProductsAndGroups\ProductGroupSchema;
use Sunnysideup\Vardump\Vardump;

/**
 * Product Group is a 'holder' for Products within the CMS.
 *
 * @property int $NumberOfProductsPerPage
 * @property int $LevelOfProductsToShow
 * @property string $DefaultSortOrder
 * @property string $DefaultFilter
 * @property string $DisplayStyle
 * @property bool $UseImageForProducts
 * @property int $ImageID
 * @method \SilverStripe\Assets\Image Image()
 * @method \Sunnysideup\Ecommerce\Model\Search\ProductGroupSearchTable ProductGroupSearchTable()
 * @method \SilverStripe\ORM\ManyManyList|\Sunnysideup\Ecommerce\Pages\Product[] AlsoShowProducts()
 * @mixin \SilverStripe\Lumberjack\Model\Lumberjack
 */
class ProductGroup extends Page
{
    protected $baseProductList;

    protected static $filterForCandidateCategoriesCache = [];

    /**
     * set by ID of RootGroup.
     *
     * @var array
     */
    protected static $searchStringCache = [];

    protected static $parentGroupCache;

    protected static $parentPageCache = [];

    protected static $topParentGroupCache = [];

    protected static $getProductCountCache = [];

    /**
     * @var array
     */
    protected static $recursiveValuesCache = [];

    private static $template_for_selection_of_products = ProductGroupSchema::class;

    /**
     * @var string
     */
    private static $base_buyable_class = Product::class;

    private static $maximum_number_of_products_to_list = 1000;

    private static $table_name = 'ProductGroup';

    private static $db = [
        'NumberOfProductsPerPage' => 'Int',
        'LevelOfProductsToShow' => 'Int',
        'DefaultSortOrder' => 'Varchar(20)',
        'DefaultFilter' => 'Varchar(20)',
        'DisplayStyle' => 'Varchar(20)',
        'UseImageForProducts' => 'Boolean',
        'AlternativeProductGroupNames' => 'Varchar(255)', //To ensure they are also find for other names in search
    ];

    private static $has_one = [
        'Image' => Image::class,
    ];

    private static $owns = [
        'Image',
    ];

    private static $cascade_deletes = [
        'Image',
    ];

    private static $belongs_to = [
        'ProductGroupSearchTable' => ProductGroupSearchTable::class,
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

    private static $searchable_fields = [
        'ShowInMenus' => 'ExactMatchFilter',
        'ShowInSearch' => 'ExactMatchFilter',
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
        if (null !== $extended) {
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
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
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
    public function canDelete($member = null)
    {
        if (is_a(Controller::curr(), EcommerceConfigClassNames::getName(ProductsAndGroupsModelAdmin::class))) {
            return false;
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
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
        $fields->dataFieldByName('Title')->setTitle(_t('Category.NAME', 'Category Name'));
        $fields->addFieldsToTab(
            'Root.Main',
            [
                TextField::create('AlternativeProductGroupNames', _t('ProductGroup.ALTERNATIVEPRODUCTGROUPNAMES', 'Alternative Names (comma separated)')),
            ],
            'Content'
        );
        $fields->addFieldsToTab(
            'Root.Images',
            [
                ProductProductImageUploadField::create('Image', _t('Product.IMAGE', 'Product Group Image')),
                CheckboxField::create('UseImageForProducts', 'Child products can use this image as default image'),                CheckboxField::create('UseImageForProducts', 'Child products can use this image as default image'),
            ]
        );

        $calculatedNumberOfProductsPerPage = $this->getProductsPerPage();
        $numberOfProductsPerPageExplanation = $calculatedNumberOfProductsPerPage !== $this->NumberOfProductsPerPage ? _t('ProductGroup.CURRENTLVALUE', 'Current value: ') . $calculatedNumberOfProductsPerPage . ' ' . _t('ProductGroup.INHERITEDFROMPARENTSPAGE', ' (inherited from parent page because the current page is set to zero)') : '';
        if($this->exists()) {
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
            if ($calculatedNumberOfProductsPerPage && !$this->NumberOfProductsPerPage) {
                $this->NumberOfProductsPerPage = 0;
                $numberOfProductsPerPageField->setAttribute('placeholder', $calculatedNumberOfProductsPerPage);
            }
        }

        $this->addDropDownForListConfig($fields, 'FILTER', _t('ProductGroup.DEFAULTFILTER', 'Default Filter'));

        $this->addDropDownForListConfig($fields, 'SORT', _t('ProductGroup.DEFAULTSORTORDER', 'Default Sort Order'));

        $this->addDropDownForListConfig($fields, 'DISPLAY', _t('ProductGroup.DEFAULTDISPLAYSTYLE', 'Default Display Style'));

        $config = EcommerceConfig::inst();

        if ($config->ProductsAlsoInOtherGroups) {
            if (!ClassHelpers::check_for_instance_of($this, ProductGroupSearchPage::class, false)) {
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
        $mySearchDetail = $this->ProductGroupSearchTable();
        if ($mySearchDetail && $mySearchDetail->exists()) {
            $searchDetails = '
            <h2>Title recorded (prioritised in search)</h2>
            <p>
                ' . $mySearchDetail->Title . '
            </p>
            <h2>Keywords recorded</h2>
            <p>
                ' . $mySearchDetail->Data . '
            </p>
            <p>
                <a href="' . $mySearchDetail->CMSEditLink() . '">See Search Keywords Recorded</a>
            </p>';
        } else {
            $searchDetails = '
            <p class="message warning">
                No search data is recorded
            </p>';
        }
        $fields->addFieldsToTab(
            'Root.Search',
            [
                LiteralField::create(
                    'SearchDetails',
                    $searchDetails
                ),
            ]
        );

        return $fields;
    }

    public function FilterForGroupSegment(): string
    {
        return $this->URLSegment . '.' . $this->ID;
    }

    /**
     * @EcommerCache Candidate
     */
    public function getFilterForCandidateCategories(): DataList
    {
        if (!isset(self::$filterForCandidateCategoriesCache[$this->ID])) {
            self::$filterForCandidateCategoriesCache[$this->ID] =
                $this->getBaseProductList()->getFilterForCandidateCategories();
        }

        return self::$filterForCandidateCategoriesCache[$this->ID];
    }

    /**
     * used if you install lumberjack.
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
            $hasDuplicates = $counts[$urlSegment] > 1;

            if ($hasDuplicates) {
                DB::alteration_message('found duplicates for ' . $urlSegment, 'deleted');
                $checkForDuplicatesURLSegments = ProductGroup::get()
                    ->filter(['URLSegment' => $urlSegment])
                ;

                if ($checkForDuplicatesURLSegments->exists()) {
                    $count = 0;
                    foreach ($checkForDuplicatesURLSegments as $productGroup) {
                        if ($count > 0) {
                            $oldURLSegment = $productGroup->URLSegment;
                            DB::alteration_message(' ... Correcting URLSegment for ' . $productGroup->Title . ' with ID: ' . $productGroup->ID, 'deleted');
                            $productGroup->writeToStage(Versioned::DRAFT);
                            $productGroup->publishRecursive();

                            $newURLSegment = $productGroup->URLSegment;
                            DB::alteration_message(' ... .... from ' . $oldURLSegment . ' to ' . $newURLSegment, 'created');
                        }

                        ++$count;
                    }
                }
            }
        }
    }

    /**
     * returns the template for providing related groups and products.
     *
     * @return ProductGroupSchema
     */
    public function getProductGroupSchema()
    {
        return Injector::inst()->get($this->getTemplateForSelectionOfProducts());
    }

    public function getProductsPerPage(?int $default = 10): int
    {
        return (int) $this->recursiveValue('NumberOfProductsPerPage', $default);
    }

    /**
     * work out the recursive value in the Database for SORT / FILTER / DISPLAY.
     *
     * @param string $type SORT|FILTER|DISPLAY
     */
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
     */
    public function getMyLevelOfProductsToShow(?int $defauult = 99): int
    {
        $value = $this->recursiveValue('LevelOfProductsToShow', 99);

        return (int) $value;
    }

    /**
     * Link to the search results.
     */
    public function SearchResultLink(?string $hash = ''): string
    {
        if ($hash) {
            $hash .= '/';
        }

        return $this->Link('searchresults/' . $hash);
    }

    /**
     * This can be set from the controller
     * to create a filtered baselist.
     */
    public static function set_search_string_for_base_list(int $id, string $string)
    {
        self::$searchStringCache[$id] = $string;
    }

    /**
     * Retrieve the base list of products for this group.
     *
     * @EcommerceCache candidate?
     *
     * @return BaseProductList
     */
    public function getBaseProductList()
    {
        if (!$this->baseProductList) {
            $className = $this->getProductGroupSchema()->getBaseProductListClassName();
            $this->baseProductList = $className::inst(
                $this,
                $this->getBuyableClassName(),
                $this->getMyLevelOfProductsToShow(),
                self::$searchStringCache[$this->ID] ?? ''
            );
        }

        return $this->baseProductList;
    }

    /**
     * @EcommerceCache candidate?
     *
     * @return DataList
     */
    public function getProducts()
    {
        return $this->getBaseProductList()->getProducts();
    }

    public function hasProducts(?bool $includeAlsoShowProducts = false): bool
    {
        if($includeAlsoShowProducts) {
            return $this->getProducts()->exists() || $this->AlsoShowProducts()->exists();
        }
        return $this->getProducts()->exists();
    }

    /**
     * returns the parent Product Group that is the same type.
     * So that filters can be set as parent groups.
     *
     * @return ProductGroup
     */
    public function MyFilterParent()
    {
        if (empty(self::$parentGroupCache[$this->ID])) {
            self::$parentGroupCache[$this->ID] = $this;
            while (self::$parentGroupCache[$this->ID]) {
                $obj = self::$parentGroupCache[$this->ID];
                $nextParent = $obj->ParentGroup();
                if ($nextParent && $nextParent->ClassName === $this->ClassName) {
                    self::$parentGroupCache[$this->ID] = $nextParent;
                } else {
                    //important to return here...
                    return self::$parentGroupCache[$this->ID];
                }
            }
        }

        return self::$parentGroupCache[$this->ID];
    }

    /**
     * If products are shown in more than one group then this returns an array
     * for any products that are linked to this
     * product group.
     *
     * @EcommerceCache candidate?
     */
    public function getProductsToBeIncludedFromOtherGroupsArray(): array
    {
        $array = EcommerceCache::inst()->retrieve('AlsoShowProducts_' . $this->ID);
        if (null === $array) {
            $array = [];
            if ($this->getProductsAlsoInOtherGroups() && $this->AlsoShowProducts()->exists()) {
                $array = $this->AlsoShowProducts()->columnUnique();
            }
            EcommerceCache::inst()->save('AlsoShowProducts_' . $this->ID, $array);
        }

        return ArrayMethods::filter_array($array);
    }

    public function IDForSearchResults(): int
    {
        return $this->ID;
    }

    /**
     * Returns the parent page, but only if it is an instance of Product Group.
     */
    public function MainParentGroup(): ?ProductGroup
    {
        return $this->ParentGroup();
    }

    /**
     * Returns the parent page, but only if it is an instance of Product Group.
     */
    public function ParentGroup(): ?ProductGroup
    {
        if (!isset(self::$parentPageCache[$this->ID])) {
            self::$parentPageCache[$this->ID] = ProductGroup::get_by_id($this->ParentID);
        }

        return self::$parentPageCache[$this->ID];
    }

    /**
     * Returns the parent page, but only if it is an instance of Product Group.
     */
    public function TopParentGroup(): ProductGroup
    {
        $parent = $this->ParentGroup();
        self::$topParentGroupCache[$this->ID] = $parent && $parent->exists() ? $parent->TopParentGroup() : $this;

        return self::$topParentGroupCache[$this->ID];
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
     * @return null|Image
     */
    public function BestAvailableImage() :?Image
    {
        return $this->recursiveValue('Image');
    }

    /**
     * tells us if the current page is part of e-commerce.
     */
    public function IsEcommercePage(): bool
    {
        return true;
    }

    /**
     * the number of direct descendants.
     */
    public function getNumberOfProducts(): int
    {
        if (!isset(self::$getProductCountCache[$this->ID])) {
            self::$getProductCountCache[$this->ID] = Product::get()->filter(['ParentID' => $this->ID])->count();
        }

        return self::$getProductCountCache[$this->ID];
    }

    /**
     * Returns the full sortFilterDisplayNames set, a subset, or one value
     * by either type (e.g. FILTER) or variable (e.g dbFieldName)
     * or both.
     *
     * @param string $typeOrVariable optional SEARCHFILTER|SEARCHFILTER|GROUPFILTER|FILTER|SORT|DISPLAY OR variable
     *
     * @return array|string
     */
    public function getSortFilterDisplayValues(?string $typeOrVariable = '', ?string $variable = '')
    {
        return $this->getProductGroupSchema()->getSortFilterDisplayValues($typeOrVariable, $variable);
    }

    /**
     * Returns the class we are working with.
     */
    public function getBuyableClassName(): string
    {
        return EcommerceConfig::get(ProductGroup::class, 'base_buyable_class');
    }

    /**
     * Do products occur in more than one group.
     */
    public function getProductsAlsoInOtherGroups(): bool
    {
        return EcommerceConfig::inst()->ProductsAlsoInOtherGroups;
    }

    /**
     * Returns children ProductGroup pages of this group.
     *
     * @return \SilverStripe\ORM\SS_List (ProductGroups)
     */
    public function ChildCategoriesBasedOnProducts()
    {
        return $this->getBaseProductList()->getParentGroupsBasedOnProductsExcludingRootGroup();
    }

    public function ChildCategories(): DataList
    {
        return ProductGroup::get()
            ->filter(['ParentID' => $this->ID, 'ShowInSearch' => true])
        ;
    }

    public function getShowProductLevelsArray(): array
    {
        return $this->getBaseProductList()->getShowProductLevelsArray();
    }

    public function VardumpMe(string $method)
    {
        return Vardump::inst()->vardumpMe($this->{$method}(), $method, static::class);
    }

    public function onAfterPublish()
    {
        parent::onAfterPublish();
        if (Config::inst()->get(ProductSearchFilter::class, 'use_product_search_table')) {
            ProductGroupSearchTable::add_product_group(
                $this,
                $this->getProductSearchTableDataValues()
            );
        }
    }

    public function onBeforeUnpublish()
    {
        ProductGroupSearchTable::remove_product_group($this);
    }

    public function onBeforeDelete()
    {
        parent::onBeforeDelete();
        ProductGroupSearchTable::remove_product_group($this);
    }

    /**
     * returns a URL without the -2 or -3, at the end,
     * so that the URLSegment can be used as a code.
     *
     * @return string [description]
     */
    public function CleanURLSegment(): string
    {
        $urlSegment = $this->URLSegment;
        $x = 2;
        while ($x < 10) {
            if (substr((string) $urlSegment, -2) === '-' . $x) {
                return substr((string) $urlSegment, 0, -2);
            }
            ++$x;
        }

        return $urlSegment;
    }

    protected function getTemplateForSelectionOfProducts(): string
    {
        return $this->Config()->get('template_for_selection_of_products');
    }

    protected function addDropDownForListConfig(FieldList $fields, string $type, string $title)
    {
        // display style
        $options = $this->getOptionsForDropdown($type);
        if (count($options) > 2) {
            $field = $this->getSortFilterDisplayValues($type, 'dbFieldName');
            if ('inherit' === $this->{$field}) {
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
     * not available.
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
     * @param string $type        - FILTER | SORT | DISPLAY
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
        $options = $this->getProductGroupSchema()->{$method}();

        return array_merge($array, $options);
    }

    /**
     * Used in getCMCFields.
     *
     * @return GridField
     */
    protected function getProductGroupsTable()
    {
        $field = GridField::create(
            'AlsoShowProducts',
            _t('ProductGroup.OTHER_PRODUCTS_SHOWN_IN_THIS_GROUP', 'Other products shown in this group ...'),
            $this->AlsoShowProducts(),
            $config = GridFieldBasicPageRelationConfig::create()
        );
        /** @var GridFieldAddExistingAutocompleter $ac */
        $ac = $config->getComponentByType(GridFieldAddExistingAutocompleter::class);
        if ($ac) {
            $ac->setSearchFields(['Title']);
            $ac->setResultsFormat('$Breadcrumbs');
            $ac->setSearchList(Product::get()->filter(['AllowPurchase' => 1]));
        }

        return $field;
    }

    /**
     * get recursive value for Product Group and check EcommerceConfig as last resort.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    protected function recursiveValue(string $fieldNameOrMethod, $default = null)
    {
        $key = $fieldNameOrMethod . '_' . $this->ID;
        if (!isset(self::$recursiveValuesCache[$key])) {
            $value = null;
            $fieldNameOrMethodWithGet = 'get' . $fieldNameOrMethod;
            $methodWorks = false;
            foreach ([$fieldNameOrMethod, $fieldNameOrMethodWithGet] as $method) {
                if ($this->hasMethod($method)) {
                    $methodWorks = true;
                    $outcome = $this->{$method}();
                    if ($outcome && $outcome instanceof DataObject && $outcome->exists()) {
                        $value = $outcome;
                    } elseif ($outcome) {
                        $value = $outcome;
                    } else {
                        print_r($outcome);
                        user_error($fieldNameOrMethod . ' is empty');
                    }
                }
            }
            if (false === $methodWorks) {
                $value = $this->{$fieldNameOrMethod} ?? null;
            }
            if (!$value || 'inherit' === $value) {
                $parent = $this->ParentGroup();
                if ($parent && $parent->exists() && $parent->ID !== $this->ID) {
                    $value = $parent->recursiveValue($fieldNameOrMethod, $default);
                } else {
                    $value = EcommerceConfig::inst()->recursiveValue($fieldNameOrMethod, $default);
                }
            }
            if (!$value) {
                $value = $default;
            }
            self::$recursiveValuesCache[$key] = $value;
        }

        return self::$recursiveValuesCache[$key];
    }

    protected function getProductSearchTableDataValues(): array
    {
        return [
            $this->Title,
            $this->Content,
        ];
    }

    public function addToSearchTable()
    {
        if (Config::inst()->get(ProductSearchFilter::class, 'use_product_search_table')) {
            ProductGroupSearchTable::add_product_group(
                $this,
                $this->getProductSearchTableDataValues()
            );
        }
    }

    public function AlternativeNames(): ?ArrayList
    {
        if($this->AlternativeProductGroupNames) {
            $list = array_filter(array_filter(explode(',', $this->AlternativeProductGroupNames), 'trim'));
            $al = ArrayList::create();
            foreach($list as $name) {
                $al->push(ArrayData::create(['Title' => DBField::create_field('Varchar', $name)]));
            }
            return $al;
        }
        return null;
    }

}
