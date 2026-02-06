<?php

namespace Sunnysideup\Ecommerce\Pages;

use Bummzack\SortableFile\Forms\SortableUploadField;
use Exception;
use Page;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\UnsavedRelationList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\ArrayData;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Api\EcommerceCache;
use Sunnysideup\Ecommerce\Api\GetParentDetails;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Cms\ProductsAndGroupsModelAdmin;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigAjax;
use Sunnysideup\Ecommerce\Config\EcommerceConfigAjaxDefinitions;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Control\ShoppingCartController;
use Sunnysideup\Ecommerce\Dev\EcommerceCodeFilter;
use Sunnysideup\Ecommerce\Forms\Fields\EcomQuantityField;
use Sunnysideup\Ecommerce\Forms\Fields\ProductGroupDropdown;
use Sunnysideup\Ecommerce\Forms\Fields\YesNoDropDownField;
use Sunnysideup\Ecommerce\Forms\Gridfield\Configs\GridFieldConfigForProductGroups;
use Sunnysideup\Ecommerce\Interfaces\BuyableModel;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Money\EcommerceCurrency;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\OrderItem;
use Sunnysideup\Ecommerce\Model\ProductOrderItem;
use Sunnysideup\Ecommerce\Model\Search\ProductSearchTable;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductSearchFilter;
use Sunnysideup\Ecommerce\Tasks\EcommerceTaskDebugCart;
use Sunnysideup\Ecommerce\Tasks\EcommerceTaskLinkProductWithImages;
use Sunnysideup\Ecommerce\Tasks\EcommerceTaskRemoveSuperfluousLinksInProductProductGroups;
use Sunnysideup\Vardump\ArrayToTable;

/**
 * This is a standard Product page-type with fields like
 * Price, Weight, Model and basic management of
 * groups.
 *
 * It also has an associated ProductOrderItem class,
 * an extension of OrderItem, which is the mechanism
 * that links this page type class to the rest of the
 * eCommerce platform. This means you can add an instance
 * of this page type to the shopping cart.
 *
 * @property bool $HideFromShoppingFeed
 * @property string $MPN
 * @property float $Price
 * @property float $Weight
 * @property string $Model
 * @property string $Quantifier
 * @property bool $FeaturedProduct
 * @property bool $AllowPurchase
 * @property string $InternalItemID
 * @property float $FullSiteTreeSort
 * @property string $FullName
 * @property string $ProductBreadcrumb
 * @property string $ShortDescription
 * @property string $AlternativeProductNames
 * @property bool $UseImageForProducts
 * @property int $GoogleProductCategoryID
 * @property int $ImageID
 * @method \Sunnysideup\EcommerceGoogleShoppingFeed\Model\GoogleProductCategory GoogleProductCategory()
 * @method \SilverStripe\Assets\Image Image()
 * @method \Sunnysideup\Ecommerce\Model\Search\ProductSearchTable ProductSearchTable()
 * @method \SilverStripe\ORM\ManyManyList|\Sunnysideup\EcommerceTax\Model\GSTTaxModifierOptions[] ExcludedFrom()
 * @method \SilverStripe\ORM\ManyManyList|\Sunnysideup\EcommerceTax\Model\GSTTaxModifierOptions[] AdditionalTax()
 * @method \SilverStripe\ORM\ManyManyList|\Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions[] UnavailableDeliveryOptions()
 * @method \SilverStripe\ORM\ManyManyList|\Sunnysideup\Ecommerce\Pages\Product[] EcommerceRecommendedProducts()
 * @method \SilverStripe\ORM\ManyManyList|\Sunnysideup\Ecommerce\Pages\ProductGroup[] ProductGroups()
 * @method \SilverStripe\ORM\ManyManyList|\SilverStripe\Assets\File[] AdditionalFiles()
 * @method \SilverStripe\ORM\ManyManyList|\Sunnysideup\EcommerceDiscountCoupon\Model\DiscountCouponOption[] ApplicableDiscountCoupons()
 * @method \SilverStripe\ORM\ManyManyList|\Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierAdditional[] AdditionalDeliveryCosts()
 * @method \SilverStripe\ORM\ManyManyList|\Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions[] ExcludedFromDeliveryCosts()
 * @method \SilverStripe\ORM\ManyManyList|\Sunnysideup\Ecommerce\Pages\Product[] RecommendedFor()
 * @mixin \Sunnysideup\EcommerceGoogleShoppingFeed\Extensions\GoogleShoppingFeedExtension
 * @mixin \Sunnysideup\EcommerceAlsoRecommended\Model\EcommerceAlsoRecommendedDOD
 * @mixin \Sunnysideup\EcommerceDelivery\Extensions\ProductDeliveryExtension
 * @mixin \Sunnysideup\EcommerceDiscountCoupon\Model\Buyables\DiscountCouponProductDataExtension
 * @mixin \Sunnysideup\EcommerceTax\Decorator\GSTTaxDecorator
 */
class Product extends Page implements BuyableModel
{
    /**
     * @var string
     */
    protected $defaultClassNameForOrderItem = ProductOrderItem::class;

    protected static $parent_cache = [];

    private static $buyable_product_variation_class_name = 'Sunnysideup\\EcommerceProductVariation\\Model\\\Buyables\\ProductVariation';

    /**
     * @var string
     */
    private static $folder_name_for_images = 'ProductImages';

    /**
     * @var bool
     */
    private static $allow_price_caching = true;

    private static $table_name = 'Product';

    private static $db = [
        'Price' => 'Currency',
        'Weight' => 'Float',
        'HasPhysicalDispatch' => 'Boolean(1)',
        'UseParentImage' => 'Boolean(1)',
        'Model' => 'Varchar(30)',
        'Quantifier' => 'Varchar(30)',
        'FeaturedProduct' => 'Boolean',
        'AllowPurchase' => 'Boolean',
        'InternalItemID' => 'Varchar(30)', //ie SKU, ProductID etc (internal / existing recognition of product)
        'FullSiteTreeSort' => 'Decimal(64, 0)', //store the complete sort numbers from current page up to level 1 page, for sitetree sorting
        'FullName' => 'Varchar(255)', //Name for look-up lists
        'ProductBreadcrumb' => 'Varchar(255)', //Name for look-up lists
        'ShortDescription' => 'Varchar(600)', //For use in lists.
        'AlternativeProductNames' => 'Varchar(255)', //To ensure they are also find for other names in search
        'UseImageForProducts' => 'Boolean', //For use in lists.
        'Popularity' => 'Float', //For actual value
        'PopularityRank' => 'Int', //For fast sorting
        'SearchBoost' => 'Decimal(4,2)',
    ];

    private static $has_one = [
        'Image' => Image::class,
    ];

    private static $belongs_to = [
        'ProductSearchTable' => ProductSearchTable::class,
    ];

    private static $many_many = [
        'ProductGroups' => ProductGroup::class,
        'AdditionalImages' => Image::class,
        'AdditionalFiles' => File::class,
    ];

    private static $owns = [
        'Image',
        'AdditionalImages',
        'AdditionalFiles',
    ];

    private static $cascade_deletes = [
        'Image',
        'AdditionalImages',
        'AdditionalFiles',
    ];

    private static $casting = [
        'CalculatedPrice' => 'Currency',
        'CalculatedPriceAsMoney' => 'Money',
        'AllowPurchaseNice' => 'Varchar',
        'ProductType' => 'Varchar',
        'SoldCount' => 'Int',
        'InternalItemIDCalculated' => 'Varchar',
    ];

    private static $indexes = [
        'FullSiteTreeSort' => true,
        'ProductBreadcrumb' => true,
        'InternalItemID' => true,
        'AllowPurchase' => true,
        'FullName' => true,
        'Price' => true,
        'PopularityRank' => true,
    ];

    private static $many_many_extraFields = [
        'AdditionalImages' => [
            'ImageSort' => 'Int',
        ],
        'AdditionalFiles' => [
            'FileSort' => 'Int',
        ],
    ];

    /**
     * Standard SS variable.
     */
    private static $defaults = [
        'AllowPurchase' => 1,
    ];

    /**
     * Standard SS variable.
     */
    private static $searchable_fields = [
        'ParentID' => [
            'title' => 'Category',
            'field' => ProductGroupDropdown::class,
            'filter' => 'ExactMatchFilter',
        ],
        'Title' => [
            'title' => 'Keyword',
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
        ],
        'Price' => [
            'title' => 'Minimum Price',
            'field' => NumericField::class,
            'filter' => 'ProductMinimumPriceFilter',
        ],
        //todo: hack - to allow multiple search
        'Weight' => [
            'title' => 'Maximum Price',
            'field' => NumericField::class,
            'filter' => 'ProductMaximumPriceFilter',
        ],
        'InternalItemID' => [
            'title' => 'Internal Item ID',
            'filter' => 'PartialMatchFilter',
        ],
        'AllowPurchase' => [
            'title' => 'For Sale',
            'field' => YesNoDropDownField::class,
            'filter' => 'ExactMatchFilter',
        ],
        'FeaturedProduct' => [
            'title' => 'Featured',
            'field' => YesNoDropDownField::class,
            'filter' => 'ExactMatchFilter',
        ],
    ];

    /**
     * Standard SS variable.
     */
    private static $singular_name = 'Product';

    /**
     * Standard SS variable.
     */
    private static $plural_name = 'Products';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A product that is for sale in the shop.';

    /**
     * Standard SS variable.
     */
    private static $default_parent = ProductGroup::class;

    /**
     * Standard SS variable.
     */
    private static $icon = 'sunnysideup/ecommerce: client/images/icons/product-file.gif';

    public function AdditionalImages(): ManyManyList|UnsavedRelationList
    {
        $list = $this->getManyManyComponents('AdditionalImages');
        if ($list->exists()) {
            return $list->orderBy('Product_AdditionalImages.ImageSort ASC');
        }
        return $list;
    }

    public function SummaryFields()
    {
        return [
            'Image.CMSThumbnail' => 'Image',
            'InternalItemID' => 'Code',
            'Title' => 'Title',
            'ProductBreadcrumb' => 'Breadcrumb',
            'Price.Nice' => 'Price',
            'AllowPurchaseNice' => 'For Sale',
            'PopularityRank' => 'Popularity',
        ];
    }

    public static function is_product_variation($buyable): bool
    {
        $name = Config::inst()->get(Product::class, 'buyable_product_variation_class_name');

        return class_exists($name) && is_a($buyable, $name);
    }

    /**
     * By default we search for products that are allowed to be purchased only
     * standard SS method.
     *
     * @param null|mixed $_params
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function scaffoldSearchFields($_params = null)
    {
        $fields = parent::scaffoldSearchFields($_params);
        $fields->fieldByName('AllowPurchase')->setValue(1);

        return $fields;
    }

    public function i18n_singular_name()
    {
        return _t('Order.PRODUCT', 'Product');
    }

    public function i18n_plural_name()
    {
        return _t('Order.PRODUCTS', 'Products');
    }

    public function getFolderName(): string
    {
        return Config::inst()->get(static::class, 'folder_name_for_images') ?: 'ProductImages';
    }

    /**
     * Standard SS Method.
     */
    public function getCMSFields()
    {
        //prevent calling updateSettingsFields extend function too early
        //$siteTreeFieldExtensions = $this->get_static('SiteTree','runCMSFieldsExtensions');
        //$this->disableCMSFieldsExtensions();
        $fields = parent::getCMSFields();
        //if($siteTreeFieldExtensions) {
        //$this->enableCMSFieldsExtensions();
        //}
        $fields->dataFieldByName('Title')->setTitle(_t('Product.NAME', 'Product Name'));

        $config = EcommerceConfig::inst();
        // main fields
        $fields->addFieldsToTab(
            'Root.Main',
            [
                CurrencyField::create('Price', _t('Product.PRICE', 'Price')),
                new TextField('InternalItemID', _t('Product.CODE', 'Product Code'), '', 30),
                $allowPurchaseField = new CheckboxField('AllowPurchase', _t('Product.ALLOWPURCHASE', 'Allow product to be purchased')),
            ],
            'URLSegment'
        );
        if ($config && ! $config->AllowFreeProductPurchase) {
            $price = $this->getCalculatedPrice();
            if (0 === $price) {
                $link = $config->CMSEditLink();
                $allowPurchaseField->setDescription(
                    _t(
                        'Product.DO_NOT_ALLOW_FREE_PRODUCTS_TO_BE_PURCHASED',
                        "NB: Allow Purchase + zero price is not allowed.  Change the <a href=\"{$link}\">Shop Settings</a> to allow a zero price product purchases or set price on this product."
                    )
                );
            }
        }

        // images - dirty hack to show images!
        $fields->addFieldsToTab(
            'Root.Images',
            [
                UploadField::create('Image', _t('Product.IMAGE', 'Product Image'))
                    ->setFolderName($this->getFolderName()),
                CheckboxField::create('UseParentImage', 'Use parent category image as default image for product (if no image is uploaded)'),
                $this->getAdditionalImagesField(),
                $this->getAdditionalImagesMessage(),
                $this->getAdditionalFilesField(),
            ]
        );

        $parent = $this->getParent();
        if ($parent && $parent instanceof ProductGroup && ! (bool) $parent->UseImageForProducts) {
            $fields->removeByName('UseParentImage');
        }

        // details
        $fields->addFieldsToTab(
            'Root.Details',
            [
                new CheckboxField('FeaturedProduct', _t('Product.FEATURED', 'Featured Product')),
                new TextareaField('ShortDescription', _t('Product.SHORT_DESCRIPTION', 'Short Description')),
                HTMLEditorField::create('Content', _t('Product.DESCRIPTION', 'Product Description'))
                    ->setRows(3),
                new CheckboxField('HasPhysicalDispatch', _t('Product.HAS_PHYSICAL_DISPATCH', 'Has Physical Dispatch')),
            ]
        );

        if (EcommerceConfig::inst()->ProductsHaveWeight) {
            $fields->addFieldToTab(
                'Root.Details',
                NumericField::create('Weight', _t('Product.WEIGHT', 'Weight'))->setScale(3)
            );
        }

        if ($config->ProductsHaveModelNames) {
            $fields->addFieldToTab('Root.Details', new TextField('Model', _t('Product.MODEL', 'Model')));
        }

        if ($config->ProductsHaveQuantifiers) {
            $fields->addFieldToTab(
                'Root.Details',
                TextField::create('Quantifier', _t('Product.QUANTIFIER', 'Quantifier'))
                    ->setDescription(_t('Product.QUANTIFIER_EXPLANATION', 'e.g. per kilo, per month, per dozen, each'))
            );
        }

        if ($this->canPurchase()) {
            $fields->addFieldToTab(
                'Root.Main',
                new LiteralField(
                    'AddToCartLink',
                    '<p class="message good"><a href="' . $this->AddLink() . '" target="_parent">' . _t('Product.ADD_TO_CART', 'add to cart') . '</a></p>'
                )
            );
        } else {
            $fields->addFieldToTab(
                'Root.Main',
                new LiteralField(
                    'AddToCartLink',
                    '<p class="message warning">' . _t('Product.CAN_NOT_BE_ADDED_TO_CART', 'this product can not be added to cart') . '</p>'
                )
            );
        }

        $fields->addFieldsToTab(
            'Root.Under',
            [
                new ReadonlyField('FullName', _t('Product.FULLNAME', 'Full Name')),
                new ReadonlyField('ProductBreadcrumb', _t('Product.PRODUCT_BREADCRUMP', 'Breadcrumb')),
                (new ReadonlyField(
                    'FullSiteTreeSortNice',
                    _t('Product.FULLSITETREESORT', 'Full sort index'),
                    /// note use use string to avoid issues with int only supporting 19 digits
                    GetParentDetails::format_sort_numbers((string) $this->FullSiteTreeSort)
                )
                )
                    ->setDescription('This number is used to sort the product in a list of all products.'),
            ]
        );

        if ($config->ProductsAlsoInOtherGroups) {
            $fields->addFieldsToTab(
                'Root.Under',
                [
                    new HeaderField('ProductGroupsHeader', _t('Product.ALSOSHOWSIN', 'Also shows in ...')),
                    $this->getProductGroupsTableField(),
                ]
            );
        }

        $fields->addFieldsToTab(
            'Root.Orders',
            [
                ReadonlyField::create(
                    'Popularity',
                    _t('Product.POPULARITY', 'Popularity Score'),
                )
                    ->setDescription(_t('Product.POPULARITY_SCORE_DESCRIPTION', 'This is a calculated value based on the number of times this product has been sold with more weight given to more recently sold items.')),
                ReadonlyField::create(
                    'Popularity',
                    _t('Product.POPULARITY_RANK', 'Popularity Rank'),
                )
                    ->setDescription(_t('Product.POPULARITY_RANK_DESCRIPTION', 'This is the rank among all products. 1 = most popular, 2 = second most popular, etc.')),
            ]
        );

        if ($config->ShowFullDetailsForProducts && $this->exists()) {
            // $exportButton = Injector::inst()->createWithArgs(GridFieldExportButton::class, ['buttons-before-left']);
            // $exportButton->setExportColumns($this->getExportFieldsForOrder());
            $fields->addFieldsToTab(
                'Root.Orders',
                [
                    GridField::create(
                        'SalesOrderItems',
                        'Sales Record',
                        $this->SalesOrderItems()
                            ->sort(['ID' => 'DESC']),
                        GridFieldConfig_RecordViewer::create()
                        //->addComponent($exportButton)
                    )
                        ->setDescription('Includes unsold items in cart and cancelled orders, please check individual orders for details.'),
                ]
            );
            $fields->addFieldsToTab(
                'Root.History',
                [
                    LiteralField::create(
                        'ChangeHistory',
                        ArrayToTable::convert($this->getHistoryData()) .
                            '<p><a href="/admin/pages/history/show/' . $this->ID . '">Full History</a></p>'
                    ),
                ]
            );
            $mySearchDetail = $this->ProductSearchTable();
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
                        <a href="' . $mySearchDetail->CMSEditLink() . '">See Search Keywords Recorded (last updated: ' . $mySearchDetail->LastEdited . ')</a>
                    </p>';
            } else {
                $searchDetails = '
                    <p class="message warning">
                        No search data is recorded.
                    </p>';
            }
            $fields->addFieldsToTab(
                'Root.Search',
                [
                    NumericField::create('SearchBoost', 'Search Boost')
                        ->setDescription(
                            '
                                Higher boost means product will appear more up-front in search results.
                                Zero is the default.
                                Negative numbers make it less likely to appear.
                                Use sparingly! If you use it enter a number between -5 and 5 only.
                                For example 3 is a good number to get a badly peforming product near the front.
                                '
                        )
                        ->setScale(2),
                    LiteralField::create(
                        'SearchDetails',
                        $searchDetails
                    ),
                ]
            );
        }
        $fields->addFieldsToTab(
            'Root.Main',
            [
                TextField::create('AlternativeProductNames', _t('Product.ALTERNATIVEPRODUCTNAMES', 'Alternative Names (comma separated)')),
            ],
            'Price'
        );
        return $fields;
    }

    public function getHistoryData(?string $code = '', ?int $limit = 50): array
    {
        if (! $code) {
            $code = $this->InternalItemID;
        }
        $sql = '
            SELECT
                SiteTree_Versions.LastEdited,
                SiteTree_Versions.Title,
                SiteTree_Versions.Version,
                Product_Versions.Price,
                Product_Versions.AllowPurchase
            FROM Product_Versions
                INNER JOIN SiteTree_Versions
                ON
                    SiteTree_Versions.RecordID = Product_Versions.RecordID AND
                    SiteTree_Versions.Version = Product_Versions.Version
            WHERE InternalItemID = \'' . $code . '\'
            ORDER BY Product_Versions.ID DESC
            LIMIT ' . $limit . '; ';
        $array = [];
        $rows = DB::query($sql);
        $previousCheck = '';
        foreach ($rows as $row) {
            $check = $row['Price'] . '-' . ($row['AllowPurchase'] ? 'Y' : 'N') . '-' . $row['Title'];
            if ($previousCheck !== $check) {
                $array[] = [
                    'Code' => $code,
                    'Version' => $row['Version'],
                    'Changed' => $row['LastEdited'],
                    'Title' => $row['Title'],
                    'Price' => $row['Price'],
                    'For Sale' => $row['AllowPurchase'] ? 'YES' : 'NO',
                ];
            }
            $previousCheck = $check;
        }

        return $array;
    }

    /**
     * How to view using AJAX
     * e.g. if you want to load the produyct in a list - using AJAX
     * then use this link
     * Opening the link will return a HTML snippet.
     *
     * @return string
     */
    public function AjaxLink()
    {
        return $this->Link('ajaxview');
    }

    /**
     * sets the FullName and FullSiteTreeField to the latest values
     * This can be useful as you can compare it to the ones saved in the database.
     * Returns true if the value is different from the one in the database.
     */
    public function prepareFullFields()
    {
        //FullName
        $fullName = '';
        if ($this->InternalItemID) {
            $fullName .= $this->InternalItemID . ': ';
        }

        $fullName .= $this->Title;

        $obj = (GetParentDetails::create($this, ProductGroup::class))
            ->setNotAllowedClassNames(ProductGroupSearchPage::class)
            ->run();
        $parentsTitle = $obj->getParentsTitle();

        //setting fields with new values!
        $this->FullName = $fullName . ' (' . _t('product.IN', 'in') . ' ' . $parentsTitle . ')';
        $this->ProductBreadcrumb = $parentsTitle;
        $this->FullSiteTreeSort = (int) implode('', $obj->getParentSortArray());
    }

    /**
     * Returns all the parent groups for the product.
     *
     * @return null|\SilverStripe\ORM\DataList (ProductGroups)
     */
    public function AllParentGroups(?bool $cached = true): ?DataList
    {
        $otherGroupsArray = $cached ? $this->ProductGroupIDsCached() : $this->ProductGroups()->columnUnique();
        $ids = ArrayMethods::filter_array(array_merge([$this->ParentID], $otherGroupsArray));

        if ($ids !== []) {
            return ProductGroup::get()->filter([
                'ID' => $ids,
            ]);
        }

        return null;
    }

    /**
     * Returns all the parent groups for the product,
     * including the parent-parents, and so on.
     *
     * @return \SilverStripe\ORM\DataList (ProductGroups)
     */
    public function AllParentGroupsIncludingParents()
    {
        $directParents = $this->AllParentGroups();
        $allParentsArray = [];

        foreach ($directParents as $parent) {
            /**
             * @var Sitetree $obj
             */
            $obj = $parent;
            $allParentsArray[$obj->ID] = $obj->ID;

            while ($obj && $obj->ParentID) {
                if ($obj && ClassHelpers::check_for_instance_of($obj, ProductGroup::class, false)) {
                    $allParentsArray[$obj->ID] = $obj->ID;
                }
            }
        }

        return ProductGroup::get()->filter(['ID' => ArrayMethods::filter_array($allParentsArray)]);
    }

    /**
     * We have this so that Variations can link to products and products link
     * to themselves...
     *
     * @return self
     */
    public function getProduct()
    {
        return $this;
    }

    /**
     * Returns products in the same group.
     *
     * @return null|\SilverStripe\ORM\DataList (Products)
     */
    public function Siblings()
    {
        if ($this->ParentID) {
            return Product::get()
                ->filter([
                    'ShowInMenus' => 1,
                    'ParentID' => $this->ParentID,
                ])
                ->exclude(['ID' => $this->ID]);
        }

        return null;
    }

    //IMAGE

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
     */
    public function BestAvailableImage(): ?Image
    {
        if ($this->ImageID) {
            $image = Image::get_by_id($this->ImageID);
            if ($image) {
                return $image;
            }
        }
        if ($this->UseParentImage) {
            $parent = $this->ParentGroup();
            if ($parent && $parent->exists() && $parent->UseImageForProducts) {
                return $parent->BestAvailableImage();
            }
        }

        return null;
    }

    /**
     * Returns the direct parent group for the product.
     *
     * @return null|ProductGroup
     */
    public function ParentGroup()
    {
        if (! isset(self::$parent_cache[$this->ID])) {
            self::$parent_cache[$this->ID] = ProductGroup::get_by_id($this->ParentID);
        }

        return self::$parent_cache[$this->ID];
    }

    /**
     * Returns the parent page, but only if it is an instance of Product Group.
     */
    public function MainParentGroup(): ?ProductGroup
    {
        return $this->ParentGroup();
    }

    /**
     * Returns the top parent group of the product (in the hierarchy).
     *
     * @return null|ProductGroup
     */
    public function TopParentGroup()
    {
        $parent = $this->ParentGroup();
        if ($parent && $parent->exists()) {
            return $parent->TopParentGroup();
        }

        return null;
    }

    /**
     * Little hack to show thumbnail in summary fields in modeladmin in CMS.
     *
     * @return string (HTML = formatted image)
     */
    public function CMSThumbnail()
    {
        /** @var Image $image */
        $image = $this->Image();
        if ($image && $image->exists()) {
            return $image->StripThumbnail();
        }

        return '[' . _t('product.NOIMAGE', 'no image') . ']';
    }

    /**
     * Returns a link to a default image.
     *
     * If a default image is set in the site config then this link is returned
     * Otherwise, a standard link is returned.
     *
     * @return string
     */
    public function DefaultImageLink()
    {
        return EcommerceConfig::inst()->DefaultImageLink();
    }

    /**
     * returns the default image of the product.
     *
     * @return null|Image
     */
    public function DefaultImage()
    {
        return EcommerceConfig::inst()->DefaultImage();
    }

    /**
     * Action to return specific version of a specific product.
     * This can be any product to enable the retrieval of deleted products.
     * This is really useful for sold products where you want to retrieve the actual version that you sold.
     * If the version can not be found then we retrieve the current one.
     *
     * @param int $id
     * @param int $version
     *
     * @return null|\SilverStripe\ORM\DataObject
     */
    public function getVersionOfBuyable($id = 0, $version = 0)
    {
        if (! $id) {
            $id = $this->ID;
        }

        if (! $version) {
            $version = $this->Version;
        }

        //not sure why this is running via OrderItem...

        $obj = OrderItem::get_version($this->ClassName, $id, $version);
        if (! $obj) {
            $className = $this->ClassName;
            $obj = $className::get_by_id($id);
        }

        return $obj;
    }

    /**
     * Returns the order item associated with the buyable. ALWAYS returns one,
     * even if there is none in the cart.
     *
     * Does not write to database.
     *
     * @return OrderItem (no kidding)
     */
    public function OrderItem()
    {
        $filterArray = [];
        $extendedFilter = $this->extend('updateItemFilter', $filterArray);

        if (null !== $extendedFilter && is_array($extendedFilter) && count($extendedFilter)) {
            $filterArray = $extendedFilter;
        }

        //make the item and extend
        $item = ShoppingCart::singleton()->findOrMakeItem($this, $filterArray);

        $this->extend('updateDummyItem', $item);

        return $item;
    }

    /**
     * you can overwrite this function in your buyable items (such as Product).
     *
     * @return string
     */
    public function classNameForOrderItem()
    {
        $className = $this->defaultClassNameForOrderItem;
        $updateClassName = $this->extend('updateClassNameForOrderItem', $className);
        if (null !== $updateClassName && is_array($updateClassName) && count($updateClassName)) {
            $className = $updateClassName[0];
        }

        return $className;
    }

    /**
     * You can set an alternative class name for order item using this method.
     *
     * @param string $className
     */
    public function setAlternativeClassNameForOrderItem($className): static
    {
        $this->defaultClassNameForOrderItem = $className;
        return $this;
    }

    /**
     * This is used when you add a product to your cart.
     *
     * If you set it to 1 then you can add 0.1 product to cart.
     *
     * If you set it to -1 then you can add 10, 20, 30, etc.. products to cart.
     *
     * @return int
     */
    public function QuantityDecimals()
    {
        return 0;
    }

    public function HasBeenSold(): bool
    {
        return $this->getHasBeenSold();
    }

    public function getHasBeenSold(): bool
    {
        return (bool) $this->SalesRecord()->exists();
    }

    /**
     * includes unsold items - raw list!
     */
    public function SalesOrderItems(): DataList
    {
        return OrderItem::get()->filter(
            [
                'BuyableID' => $this->ID,
                'BuyableClassName' => $this->ClassName,
            ]
        );
    }

    public function SoldCount(?array $filter = []): int
    {
        if ($filter && is_array($filter) && count($filter)) {
            return (int) $this->SalesRecord()->filter($filter)->sum('Quantity');
        }
        return (int) $this->SalesRecord()->sum('Quantity');
    }

    /**
     * is used to work out if a product has been sold!
     */
    public function SalesRecord(): DataList
    {
        $dataList = Order::get_datalist_of_orders_with_submit_record(true, false); // only submitted, cancalled false
        $dataList = $dataList->innerJoin('OrderAttribute', '"OrderAttribute"."OrderID" = "Order"."ID"');
        $dataList = $dataList->innerJoin('OrderItem', '"OrderAttribute"."ID" = "OrderItem"."ID"');

        return $dataList->filter(
            [
                'BuyableID' => $this->ID,
                'buyableClassName' => $this->ClassName,
            ]
        );
    }

    //LINKS

    /**
     * Tells us the link to select variations
     * If ajaxified, this controller method (selectvariation)
     * Will return a html snippet for selecting the variation.
     * This is useful in the Product Group where you can both
     * non-variation and variation products to have the same
     * "add to cart" button.  Using this link you can provide a
     * pop-up select system for selecting a variation.
     *
     * @return string
     */
    public function AddVariationsLink()
    {
        return $this->Link('selectvariation');
    }

    /**
     * useful for Product Variations as they return the parent Product.
     */
    public function Product()
    {
        return $this;
    }

    /**
     * alias.
     *
     * @return string
     */
    public function AbsoluteLinkNoStage()
    {
        return str_replace('?stage=Stage', '', $this->AbsoluteLink());
    }

    /**
     * passing on shopping cart links ...is this necessary?? ...why not just pass the cart?
     *
     * @return string
     */
    public function AddLink()
    {
        return ShoppingCartController::add_item_link($this->ID, $this->ClassName, $this->linkParameters('add'));
    }

    /**
     * link use to add (one) to cart.
     *
     * @return string
     */
    public function IncrementLink()
    {
        return ShoppingCartController::add_item_link($this->ID, $this->ClassName, $this->linkParameters('increment'));
    }

    /**
     * Link used to remove one from cart
     * we can do this, because by default remove link removes one.
     *
     * @return string
     */
    public function DecrementLink()
    {
        return ShoppingCartController::remove_item_link($this->ID, $this->ClassName, $this->linkParameters('decrement'));
    }

    /**
     * remove one buyable's orderitem from cart.
     *
     * @return string (Link)
     */
    public function RemoveLink()
    {
        return ShoppingCartController::remove_item_link($this->ID, $this->ClassName, $this->linkParameters('remove'));
    }

    /**
     * remove all of this buyable's orderitem from cart.
     *
     * @return string (Link)
     */
    public function RemoveAllLink()
    {
        return ShoppingCartController::remove_all_item_link($this->ID, $this->ClassName, $this->linkParameters('removeall'));
    }

    /**
     * remove all of this buyable's orderitem from cart and go through to this buyble to add alternative selection.
     *
     * @return string (Link)
     */
    public function RemoveAllAndEditLink()
    {
        return ShoppingCartController::remove_all_item_and_edit_link($this->ID, $this->ClassName, $this->linkParameters('removeallandedit'));
    }

    /**
     * set new specific new quantity for buyable's orderitem.
     *
     * @param float $quantity
     *
     * @return string (Link)
     */
    public function SetSpecificQuantityItemLink($quantity): string
    {
        return ShoppingCartController::set_quantity_item_link($this->ID, $this->ClassName, array_merge($this->linkParameters('setspecificquantityitem'), ['quantity' => $quantity]));
    }

    /**
     * @return string
     */
    public function AddToCartAndGoToCheckoutLink()
    {
        $array = $this->linkParameters();
        $array['BackURL'] = urlencode(CheckoutPage::find_link());

        return ShoppingCartController::add_item_link($this->ID, $this->ClassName, $array);
    }

    /**
     * @return string
     */
    public function VersionedLink()
    {
        return Controller::join_links(
            Director::baseURL(),
            EcommerceConfig::get(ShoppingCartController::class, 'url_segment'),
            'submittedbuyable',
            ClassHelpers::sanitise_class_name($this->ClassName),
            $this->ID,
            $this->Version
        );
    }

    public function RemoveFromSaleLink()
    {
        return ShoppingCartController::remove_from_sale_link($this->ID, $this->ClassName);
    }

    //TEMPLATE STUFF

    /**
     * @return bool
     */
    public function IsInCart()
    {
        return $this->OrderItem() && $this->OrderItem()->Quantity > 0;
    }

    /**
     * @return EcomQuantityField
     */
    public function EcomQuantityField()
    {
        return EcomQuantityField::create($this);
    }

    /**
     * returns the instance of EcommerceConfigAjax for use in templates.
     * In templates, it is used like this:
     * $EcommerceConfigAjax.TableID.
     */
    public function AJAXDefinitions(): EcommerceConfigAjaxDefinitions
    {
        return EcommerceConfigAjax::get_one($this);
    }

    /**
     * Is it a variation?
     */
    public function IsProductVariation(): bool
    {
        return false;
    }

    /**
     * tells us if the current page is part of e-commerce.
     */
    public function IsEcommercePage(): bool
    {
        return true;
    }

    public function ProductType(): string
    {
        return $this->getProductType();
    }

    public function getProductType(): string
    {
        return $this->singular_name();
    }

    public function AllowPurchaseNice()
    {
        return $this->obj('AllowPurchase')->Nice();
    }

    /**
     * Products have a standard price, but for specific situations they have a calculated price.
     * The Price can be changed for specific member discounts, etc...
     *
     * @return float
     */
    public function CalculatedPrice()
    {
        return $this->getCalculatedPrice();
    }

    public function AllowPriceCaching(): bool
    {
        return $this->Config()->allow_price_caching;
    }

    /**
     * Products have a standard price, but for specific situations they have a calculated price.
     * The Price can be changed for specific member discounts, etc...
     *
     * We add three "hooks" / "extensions" here... so that you can update prices
     * in a logical order (e.g. firstly change to forex and then apply discount)
     *
     * @return float
     */
    public function getCalculatedPrice(?bool $forceRecalculation = false)
    {
        $cacheKey = '';
        $price = null;
        if ($this->AllowPriceCaching() && $forceRecalculation === false) {
            $cacheKey = 'ppcpw_' . $this->getPriceCacheKey();
            if (EcommerceCache::inst()->hasCache($cacheKey)) {
                $price = (float) EcommerceCache::inst()->retrieve($cacheKey, true);
            }
        }
        if (null === $price) {
            $price = $this->Price;
            $updatedPrice = $this->extend('updateBeforeCalculatedPrice', $price);
            if (null !== $updatedPrice && is_array($updatedPrice) && count($updatedPrice)) {
                $price = $updatedPrice[0];
            }
            $updatedPrice = $this->extend('updateCalculatedPrice', $price);
            if (null !== $updatedPrice && is_array($updatedPrice) && count($updatedPrice)) {
                $price = $updatedPrice[0];
            }

            $updatedPrice = $this->extend('updateAfterCalculatedPrice', $price);
            if (null !== $updatedPrice && is_array($updatedPrice) && count($updatedPrice)) {
                $price = $updatedPrice[0];
            }
            if ($cacheKey !== '' && $cacheKey !== '0') {
                EcommerceCache::inst()->save($cacheKey, $price, true);
            }
        }

        return $price;
    }

    public function getPriceCacheKey(): string
    {
        return $this->ID . '_' . $this->Version . '_' . (Security::getCurrentUser() ? Security::getCurrentUser()->ID : 0);
    }

    /**
     * How do we display the price?
     *
     * @return \SilverStripe\ORM\FieldType\DBMoney
     */
    public function CalculatedPriceAsMoney(?bool $forceRecalculation = false)
    {
        return $this->getCalculatedPriceAsMoney($forceRecalculation);
    }

    public function getCalculatedPriceAsMoney(?bool $forceRecalculation = false)
    {
        return EcommerceCurrency::get_money_object_from_order_currency($this->getCalculatedPrice($forceRecalculation));
    }

    //CRUD SETTINGS

    /**
     * Is the product for sale?
     *
     * @param bool $checkPrice
     *
     * @return bool
     */
    public function canPurchase(Member $member = null, $checkPrice = true)
    {
        $config = EcommerceConfig::inst();

        // shop closed
        if ($config->ShopClosed) {
            return false;
        }

        // not sold at all
        if (! $this->AllowPurchase) {
            return false;
        }

        // check country
        if (! $member instanceof \SilverStripe\Security\Member) {
            $member = Security::getCurrentUser();
        }

        $extended = $this->extendedCan('canPurchaseByCountry', $member);

        if (null !== $extended) {
            return $extended;
        }

        if (! EcommerceCountry::allow_sales()) {
            return false;
        }

        if ($checkPrice) {
            $price = $this->getCalculatedPrice();
            if (0 === $price && ! $config->AllowFreeProductPurchase) {
                return false;
            }
        }

        // Standard mechanism for accepting permission changes from decorators
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }

        return $this->AllowPurchase;
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

        return parent::canCreate($member);
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
        return $this->canEdit($member);
    }

    public function IDForSearchResults(): int
    {
        return $this->ID;
    }

    public function debug()
    {
        $config = EcommerceConfig::inst();

        $html = EcommerceTaskDebugCart::debug_object($this);
        $html .= '<ul>';
        $html .= '<li><hr />Links<hr /></li>';
        $html .= '<li><b>Link:</b> <a href="' . $this->Link() . '">' . $this->Link() . '</a></li>';
        $html .= '<li><b>Ajax Link:</b> <a href="' . $this->AjaxLink() . '">' . $this->AjaxLink() . '</a></li>';
        $html .= '<li><b>AddVariations Link:</b> <a href="' . $this->AddVariationsLink() . '">' . $this->AddVariationsLink() . '</a></li>';
        $html .= '<li><b>Add to Cart Link:</b> <a href="' . $this->AddLink() . '">' . $this->AddLink() . '</a></li>';
        $html .= '<li><b>Increment Link:</b> <a href="' . $this->IncrementLink() . '">' . $this->IncrementLink() . '</a></li>';
        $html .= '<li><b>Decrement Link:</b> <a href="' . $this->DecrementLink() . '">' . $this->DecrementLink() . '</a></li>';
        $html .= '<li><b>Remove Link:</b> <a href="' . $this->RemoveAllLink() . '">' . $this->RemoveLink() . '</a></li>';
        $html .= '<li><b>Remove All Link:</b> <a href="' . $this->RemoveAllLink() . '">' . $this->RemoveAllLink() . '</a></li>';
        $html .= '<li><b>Remove All and Edit Link:</b> <a href="' . $this->RemoveAllAndEditLink() . '">' . $this->RemoveAllAndEditLink() . '</a></li>';
        $html .= '<li><b>Set Specific Quantity Item Link (e.g. 77):</b> <a href="' . $this->SetSpecificQuantityItemLink(77) . '">' . $this->SetSpecificQuantityItemLink(77) . '</a></li>';

        $html .= '<li><hr />Cart<hr /></li>';
        $html .= '<li><b>Allow Purchase (DB Value):</b> ' . $this->AllowPurchaseNice() . ' </li>';
        $html .= '<li><b>Can Purchase (overal calculation):</b> ' . ($this->canPurchase() ? 'YES' : 'NO') . ' </li>';
        $html .= '<li><b>Shop Open:</b> ' . $config->ShopClosed !== '' ? 'NO' : 'YES </li>';
        $html .= '<li><b>Extended Country Can Purchase:</b> ' . (null === $this->extendedCan('canPurchaseByCountry', null) ? 'no applicable' : ($this->extendedCan('canPurchaseByCountry', null) ? 'CAN PURCHASE' : 'CAN NOT PURCHASE')) . ' </li>';
        $html .= '<li><b>Allow sales to this country (' . EcommerceCountry::get_country() . '):</b> ' . (EcommerceCountry::allow_sales() ? 'YES' : 'NO') . ' </li>';
        $html .= '<li><b>Class Name for OrderItem:</b> ' . $this->classNameForOrderItem() . ' </li>';
        $html .= '<li><b>Quantity Decimals:</b> ' . $this->QuantityDecimals() . ' </li>';
        $html .= '<li><b>Is In Cart:</b> ' . ($this->IsInCart() ? 'YES' : 'NO') . ' </li>';
        $html .= '<li><b>Has Been Sold:</b> ' . ($this->HasBeenSold() ? 'YES' : 'NO') . ' </li>';
        $html .= '<li><b>Calculated Price:</b> ' . $this->CalculatedPrice() . ' </li>';
        $html .= '<li><b>Calculated Price as Money:</b> ' . $this->getCalculatedPriceAsMoney()->Nice() . ' </li>';

        $html .= '<li><hr />Location<hr /></li>';
        $html .= '<li><b>Main Parent Group:</b> ' . $this->ParentGroup()->Title . '</li>';
        $html .= '<li><b>All Others Parent Groups:</b> ' . ($this->AllParentGroups()->exists() ? '<pre>' . print_r($this->AllParentGroups()->map()->toArray(), 1) . '</pre>' : 'none') . '</li>';

        $html .= '<li><hr />Image<hr /></li>';
        $html .= '<li><b>Image:</b> ' . ($this->BestAvailableImage() instanceof \SilverStripe\Assets\Image ? '<img src=' . $this->BestAvailableImage()->Link() . ' />' : 'no image') . ' </li>';
        $productGroup = ProductGroup::get_by_id($this->ParentID);
        if ($productGroup) {
            $html .= '<li><hr />Product Example<hr /></li>';
            $html .= '<li><b>Product Group View:</b> <a href="' . $productGroup->Link() . '">' . $productGroup->Title . '</a> </li>';
            $html .= '<li><b>Product Group Debug:</b> <a href="' . $productGroup->Link('debug') . '">' . $productGroup->Title . '</a> </li>';
            $html .= '<li><b>Product Group Admin:</b> <a href="/admin/pages/edit/show/' . $productGroup->ID . '">' . $productGroup->Title . ' Admin</a> </li>';
            $html .= '<li><b>Edit this Product:</b> <a href="/admin/pages/edit/show/' . $this->ID . '">' . $this->Title . ' Admin</a> </li>';
        }

        return $html . '</ul>';
    }

    /**
     * add data to search table if the.
     */
    public function onAfterPublish()
    {
        parent::onAfterPublish();
        $this->addToSearchTable();
    }

    public function addToSearchTable()
    {
        if (Config::inst()->get(ProductSearchFilter::class, 'use_product_search_table')) {
            ProductSearchTable::add_product(
                $this,
                $this->getProductSearchTableDataValues(),
                EcommerceConfig::inst()->OnlyShowProductsThatCanBePurchased
            );
        }
    }

    public function onBeforeUnpublish()
    {
        ProductSearchTable::remove_product($this);
    }

    public function onBeforeDelete()
    {
        parent::onBeforeDelete();
        ProductSearchTable::remove_product($this);
    }

    public function onAfterDelete()
    {
        parent::onAfterDelete();
        $obj = new EcommerceTaskRemoveSuperfluousLinksInProductProductGroups();
        $obj->setVerbose(false);
        $obj->run(null);
    }

    public function getArrayOfImages(?bool $deleteMissingImages = false): array
    {
        $arrayInner = [];
        if ($this->ImageID) {
            $image = Image::get()->byID($this->ImageID); //see Product::has_one()
            if ($image && $image->exists()) {
                $arrayInner[$image->ID] = $image;
            } elseif ($deleteMissingImages) {
                DB::query('UPDATE Product SET ImageID = 0 WHERE ID = ' . $this->ID);
                DB::query('UPDATE Product_Live SET ImageID = 0 WHERE ID = ' . $this->ID);
                $this->deleteImage($image);
            }
        }
        $otherImagesIDs = DB::query(
            '
            SELECT ImageID FROM
            Product_AdditionalImages
                INNER JOIN File
                    ON File.ID = ImageID
            WHERE ProductID = ' . $this->ID . '
            ORDER BY ImageSort'
        )
            ->column('ImageID');

        foreach ($otherImagesIDs as $otherImageID) {
            if ($otherImageID) {
                $image = Image::get()->byID($otherImageID);
                if ($image && $image->exists()) {
                    $arrayInner[$image->ID] = $image;
                } elseif ($deleteMissingImages) {
                    $this->AdditionalImages()->removeByID($otherImageID);
                    $this->deleteImage($image);
                }
            }
        }

        return $arrayInner;
    }

    private function deleteImage($image)
    {
        if ($image) {
            try {
                if ($image->canArchive()) {
                    $image->doArchive();
                }
            } catch (Exception $e) {
                //do nothing
            }
        }
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $filter = EcommerceCodeFilter::create();
        $filter->checkCode($this, 'InternalItemID');

        $this->prepareFullFields();
    }

    protected function getProductSearchTableDataValues(): array
    {
        return [
            (string) $this->Title,
            implode(' ', $this->AlternativeNamesUnique()),
            (string) $this->InternalItemID,
            (string) $this->ProductBreadcrumb,
            (string) $this->ShortDescription,
            (string) $this->Content,
        ];
    }

    /**
     * Used in getCSMFields.
     *
     * @return GridField
     */
    protected function getProductGroupsTableField()
    {
        return new GridField(
            'ProductGroups',
            _t('Product.THIS_PRODUCT_SHOULD_ALSO_BE_LISTED_UNDER', 'This product is also listed under ...'),
            $this->ProductGroups()->filter(['ShowInSearch' => 1]),
            GridFieldConfigForProductGroups::create()
        );
    }

    /**
     * Used in getCSMFields.
     *
     * @return LiteralField
     */
    protected function getAdditionalImagesMessage()
    {
        $msg = '';
        if ($this->InternalItemID) {
            $findImagesTask = EcommerceTaskLinkProductWithImages::create();
            $findImagesLink = $findImagesTask->Link();
            $findImagesLinkOne = $findImagesLink . '?productid=' . $this->ID;
            $msg .= '
                <h3>Batch Upload</h3>
                <p>
                To batch upload additional images and files, please go to the <a href="/admin/assets">Files section</a>, and upload them there.
                Files need to be named in the following way:
                An additional image for your product should be named &lt;Product Code&gt;_(00 to 99).(png/jpg/gif). <br />For example, you may name your image:
                <strong>' . $this->InternalItemID . "_08.jpg</strong>.
                <br /><br />You can <a href=\"{$findImagesLinkOne}\" target='_blank'>find images for <i>" . $this->Title . "</i></a> or
                <a href=\"{$findImagesLink}\" target='_blank'>images for all products</a> ...
            </p>";
        } else {
            $msg .= '
            <h3>Batch Upload</h3>
            <p>To batch upload additional images and files, you must first specify a product code.</p>';
        }

        return new LiteralField('ImageFileNote', $msg);
    }

    /**
     * Used in getCSMFields.
     *
     * @return SortableUploadField
     */
    protected function getAdditionalImagesField()
    {
        return (new SortableUploadField(
            'AdditionalImages',
            'More images'
        ))
            ->setSortColumn('ImageSort')
            ->setFolderName($this->getFolderName());
    }

    /**
     * Used in getCSMFields.
     *
     * @return SortableUploadField
     */
    protected function getAdditionalFilesField()
    {
        return (new SortableUploadField(
            'AdditionalFiles',
            'Download Files'
        ))
            ->setSortColumn('FileSort')
            ->setFolderName($this->getFolderName());
    }

    /**
     * Here you can add additional information to your product
     * links such as the AddLink and the RemoveLink.
     * One useful parameter you can add is the BackURL link.
     *
     * Usage would be by means of
     * 1. decorating product
     * 2. adding a updateLinkParameters method
     * 3. adding items to the array.
     *
     * You can also extend Product and override this method...
     *
     * @param mixed $type
     *
     * @return array
     */
    protected function linkParameters($type = '')
    {
        $array = [];
        $extendedArray = $this->extend('updateLinkParameters', $array, $type);
        if (null !== $extendedArray && is_array($extendedArray) && count($extendedArray)) {
            foreach ($extendedArray as $extendedArrayUpdate) {
                $array = array_merge($array, $extendedArrayUpdate);
            }
        }

        return $array;
    }

    public function ProductGroupIDsCached(): ?array
    {
        $cacheKey = 'ppg_' . $this->ID;
        $ids = null;
        if (EcommerceCache::inst()->hasCache($cacheKey)) {
            $ids = (array) EcommerceCache::inst()->retrieve($cacheKey, true);
        } else {
            $ids = DB::query('SELECT ProductGroupID FROM Product_ProductGroups WHERE ProductID = ' . $this->ID)->column();
            if (is_array($ids)) {
                EcommerceCache::inst()->save($cacheKey, $ids, true);
            }
        }
        return $ids;
    }

    public function getMinValueInOrder(): float
    {
        return 1;
    }

    public function getMaxValueInOrder(): float
    {
        return 999;
    }

    public function AlternativeNames(): ?ArrayList
    {
        if ($this->AlternativeProductNames) {
            $list = array_filter(array_filter(explode(',', (string) $this->AlternativeProductNames), 'trim'));
            $al = ArrayList::create();
            foreach ($list as $name) {
                $al->push(ArrayData::create(['Title' => trim($name)]));
            }
            return $al;
        }
        return null;
    }

    public function AlternativeNamesUnique(): array
    {
        $altNames = str_replace(', ', ' ', (string) $this->AlternativeProductNames);
        $altNamesArray = array_unique(
            array_map(
                'trim',
                explode(' ', $altNames)
            )
        );
        if ($altNamesArray !== []) {
            return $altNamesArray;
        }
        return [];
    }

    public function AlternativeNamesUniqueAsArrayList(): ?ArrayList
    {
        $list = $this->AlternativeNamesUnique();
        $arrayList = new ArrayList();
        foreach ($list as $name) {
            $arrayList->push(ArrayData::create(['Title' => $name]));
        }
        return $arrayList->count() > 1 ? $arrayList : null;
    }

    public function stageTableDefault(?string $alternativeClassName = null): string
    {
        if (! $alternativeClassName) {
            $alternativeClassName = static::class;
        }
        return $this->stageTable($this->getSchema()->tableName($alternativeClassName), Versioned::get_stage());
    }

    public function getSearchBoostCalculated(): ?int
    {
        return $this->SearchBoost;
    }

    public function getInternalItemIDCalculated(): ?string
    {
        $v = (string) $this->InternalItemID;
        $this->extend('updateInternalItemIDCalculated', $v);
        return $v;
    }

    protected function getExportFieldsForOrder(): array
    {
        $v = Config::inst()->get(Order::class, 'csv_export_fields');
        if (is_array($v)) {
            return $v;
        }
        return Config::inst()->get(Order::class, 'summary_fields');
    }
}
