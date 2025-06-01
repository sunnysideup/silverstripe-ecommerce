<?php

namespace Sunnysideup\Ecommerce\Model\Config;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\HTMLReadonlyField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\Requirements;
use Sunnysideup\CmsEditLinkField\Api\CMSEditLinkAPI;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\Fields\ProductProductImageUploadField;
use Sunnysideup\Ecommerce\Interfaces\BuyableModel;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Money\EcommerceCurrency;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Sunnysideup\Ecommerce\Pages\AccountPage;
use Sunnysideup\Ecommerce\Pages\CartPage;
use Sunnysideup\Ecommerce\Pages\CheckoutPage;
use Sunnysideup\Ecommerce\Pages\OrderConfirmationPage;
use Sunnysideup\Ecommerce\Pages\Product;

class EcommerceDBConfig extends DataObject implements EditableEcommerceObject
{
    /**
     * @var array
     */
    private static $array_of_buyables = [
        Product::class,
    ];

    /**
     * @var string
     */
    private static $ecommerce_db_config_class_name = self::class;

    /**
     * Standard SS Variable.
     *
     * @var array
     */
    private static $table_name = 'EcommerceDBConfig';

    private static $db = [
        'Title' => 'Varchar(70)',
        'UseThisOne' => 'Boolean',
        'ShopClosed' => 'Boolean',
        'ShopPricesAreTaxExclusive' => 'Boolean',
        'DefaultTaxRate' => 'Float',
        'InvoiceTitle' => 'Varchar(200)',
        'InvoiceMessage' => 'HTMLText',
        'PackingSlipTitle' => 'Varchar(200)',
        'PackingSlipNote' => 'HTMLText',
        'ShopPhysicalAddress' => 'HTMLText',
        'ReceiptEmail' => 'Varchar(255)',
        'PostalCodeURL' => 'Varchar(255)',
        'PostalCodeLabel' => 'Varchar(255)',
        'NumberOfProductsPerPage' => 'Int',
        'ProductsAlsoInOtherGroups' => 'Boolean',
        'OnlyShowProductsThatCanBePurchased' => 'Boolean',
        'NotForSaleMessage' => 'HTMLText',
        'ProductsHaveWeight' => 'Boolean',
        'ProductsHaveModelNames' => 'Boolean',
        'ProductsHaveQuantifiers' => 'Boolean',
        //"ProductsHaveVariations" => "Boolean",
        'CurrenciesExplanation' => 'HTMLText',
        'AllowFreeProductPurchase' => 'Boolean',
        'ShowFullDetailsForProducts' => 'Boolean',
        'PhoneNumberForShop' => 'PhoneField',
        'ShippingDescriptionShort' => 'Text',
    ];

    /**
     * Standard SS Variable.
     *
     * @var array
     */
    private static $has_one = [
        'EmailLogo' => Image::class,
        'DefaultProductImage' => Image::class,
        'DeliveryInfoPage' => SiteTree::class,
        'ShopPolicyInfoPage' => SiteTree::class,
    ];

    /**
     * Standard SS Variable.
     *
     * @var array
     */
    private static $owns = [
        'EmailLogo',
        'DefaultProductImage',
    ];

    /**
     * Standard SS Variable.
     *
     * @var array
     */
    private static $indexes = [
        'UseThisOne' => true,
        'ShopClosed' => true,
        'ShopPricesAreTaxExclusive' => true,
        'NumberOfProductsPerPage' => true,
        'OnlyShowProductsThatCanBePurchased' => true,
    ];

    /**
     * Standard SS Variable.
     *
     * @var array
     */
    private static $casting = [
        'UseThisOneNice' => 'Varchar',
    ];
    //adds computed fields that can also have a type (e.g.

    /**
     * Standard SS Variable.
     *
     * @var array
     */
    private static $searchable_fields = [
        'Title' => 'PartialMatchFilter',
    ];

    /**
     * Standard SS Variable.
     *
     * @var array
     */
    private static $field_labels = [];

    /**
     * Standard SS Variable.
     *
     * @var array
     */
    private static $summary_fields = [
        'Title' => 'Title',
    ];
    //note no => for relational fields

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $default_sort = [
        'UseThisOne' => 'DESC',
        'ID' => 'ASC',
    ];

    /**
     * Standard SS variable.
     *
     * @var array
     */
    private static $defaults = [
        'Title' => 'Ecommerce Site Config',
        'UseThisOne' => true,
        'ShopClosed' => false,
        'ShopPricesAreTaxExclusive' => false,
        'DefaultTaxRate' => 0.15, // 15% GST
        'InvoiceTitle' => 'Invoice',
        'InvoiceMessage' => '<p>Thank you for your order</p>',
        'PackingSlipTitle' => 'Package Contents',
        'PackingSlipNote' => 'Please make sure that all items are contained in this package.',
        'ShopPhysicalAddress' => '<p>Enter your shop address here.</p>',
        //"ReceiptEmail" => "Varchar(255)", - see populate defaults
        'PostalCodeURL' => '',
        'PostalCodeLabel' => '',
        'NumberOfProductsPerPage' => 12,
        'ProductsAlsoInOtherGroups' => false,
        'OnlyShowProductsThatCanBePurchased' => false,
        'NotForSaleMessage' => '<p>Not for sale, please contact us for more information.</p>',
        'ProductsHaveWeight' => false,
        'ProductsHaveModelNames' => false,
        'ProductsHaveQuantifiers' => false,
        //"ProductsHaveVariations" => false,
        'CurrenciesExplanation' => '<p>Apart from our main currency, you can view prices in a number of other currencies. The exchange rate is indicative only.</p>',
        'AllowFreeProductPurchase' => true,
    ];

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Main E-commerce Configuration';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Main E-commerce Configurations';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A set of configurations for the shop. Each shop needs to have one or more of these settings.';

    /**
     * static holder for its own (or other EcommerceDBConfig) class.
     *
     * @var string
     */
    private static $_my_current_one;

    /**
     * Standard SS Method.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }

        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }

        if (EcommerceDBConfig::get()->exists()) {
            return false;
        }

        return $this->canEdit($member);
    }

    /**
     * Standard SS Method.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }

        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }

        return $this->canEdit($member);
    }

    /**
     * Standard SS Method.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }

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
     * Standard SS Method.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        if ($this->UseThisOne) {
            return false;
        }

        if (! $member) {
            $member = Security::getCurrentUser();
        }

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
     * Standard SS Method.
     *
     * @return array
     */
    public function populateDefaults()
    {
        $this->ReceiptEmail = Email::config()->admin_email;

        return parent::populateDefaults();
    }

    public function i18n_singular_name()
    {
        return _t('EcommerceDBConfig.ECOMMERCECONFIGURATION', 'Main E-commerce Configuration');
    }

    public function i18n_plural_name()
    {
        return _t('EcommerceDBConfig.ECOMMERCECONFIGURATIONS', 'Main E-commerce Configurations');
    }

    public static function reset_my_current_one()
    {
        self::$_my_current_one = null;
    }

    /**
     * implements singleton pattern.
     * Gets the current USE THIS ONE e-commerce option.
     *
     * @return EcommerceDBConfig|object
     */
    public static function current_ecommerce_db_config()
    {
        if (! self::$_my_current_one) {
            $className = EcommerceConfig::get(EcommerceDBConfig::class, 'ecommerce_db_config_class_name');

            if (! class_exists($className)) {
                $className = EcommerceDBConfig::class;
            }

            self::$_my_current_one = DataObject::get_one(
                $className,
                ['UseThisOne' => 1],
                $cacheDataObjectGetOne = false
            );

            if (! self::$_my_current_one) {
                self::$_my_current_one = $className::create();
            }
        }

        return self::$_my_current_one;
    }

    /**
     * standard SS method for decorators.
     *
     * @param bool $includerelations
     *
     * @return array
     */
    public function fieldLabels($includerelations = true)
    {
        $defaultLabels = parent::fieldLabels();
        $newLabels = $this->customFieldLabels();
        $labels = array_merge($defaultLabels, $newLabels);
        $extendedLabels = $this->extend('updateFieldLabels', $labels);
        if (null !== $extendedLabels && is_array($extendedLabels) && count($extendedLabels)) {
            foreach ($extendedLabels as $extendedLabelsUpdate) {
                $labels = array_merge($labels, $extendedLabelsUpdate);
            }
        }

        return $labels;
    }

    /**
     * definition of field lables
     * TODO: is this a common SS method?
     *
     * @return array
     */
    public function customFieldLabels()
    {
        return [
            'Title' => _t('EcommerceDBConfig.TITLE', 'Name of settings'),
            'UseThisOne' => _t('EcommerceDBConfig.USETHISONE', 'Use these configuration settings'),
            'ShopClosed' => _t('EcommerceDBConfig.SHOPCLOSED', 'Shop Closed'),
            'ShopPricesAreTaxExclusive' => _t('EcommerceDBConfig.SHOPPRICESARETAXEXCLUSIVE', 'Shop prices are tax exclusive'),
            'DefaultTaxRate' => _t('EcommerceDBConfig.DEFAULTTAXRATE', 'Default Tax Rate'),
            'InvoiceTitle' => _t('EcommerceDBConfig.INVOICETITLE', 'Default Email title'),
            'InvoiceMessage' => _t('EcommerceDBConfig.INVOICEMESSAGE', 'Default Email Message'),
            'PackingSlipTitle' => _t('EcommerceDBConfig.PACKING_SLIP_TITLE', 'Packing slip title'),
            'PackingSlipNote' => _t('EcommerceDBConfig.PACKING_SLIP_NOTE', 'Packing slip notes'),
            'ShopPhysicalAddress' => _t('EcommerceDBConfig.SHOPPHYSICALADDRESS', 'Shop physical address'),
            'ReceiptEmail' => _t('EcommerceDBConfig.RECEIPTEMAIL', 'Shop Email Address'),
            'PostalCodeURL' => _t('EcommerceDBConfig.POSTALCODEURL', 'Postal code link'),
            'PostalCodeLabel' => _t('EcommerceDBConfig.POSTALCODELABEL', 'Postal code link label'),
            'NumberOfProductsPerPage' => _t('EcommerceDBConfig.NUMBEROFPRODUCTSPERPAGE', 'Number of products per page'),
            'OnlyShowProductsThatCanBePurchased' => _t('EcommerceDBConfig.ONLYSHOWPRODUCTSTHATCANBEPURCHASED', 'Only show products that can be purchased.'),
            'NotForSaleMessage' => _t('EcommerceDBConfig.NOTFORSALEMESSAGE', 'Not for sale message'),
            'ProductsHaveWeight' => _t('EcommerceDBConfig.PRODUCTSHAVEWEIGHT', 'Products have weight (e.g. 1.2kg)'),
            'ProductsHaveModelNames' => _t('EcommerceDBConfig.PRODUCTSHAVEMODELNAMES', 'Products have model names / numbers / codes'),
            'ProductsHaveQuantifiers' => _t('EcommerceDBConfig.PRODUCTSHAVEQUANTIFIERS', 'Products have quantifiers (e.g. per year, each, per dozen, etc...)'),
            'ProductsAlsoInOtherGroups' => _t('EcommerceDBConfig.PRODUCTSALSOINOTHERGROUPS', 'Allow products to show in multiple product groups'),
            //"ProductsHaveVariations" => _t("EcommerceDBConfig.PRODUCTSHAVEVARIATIONS", "Products have variations (e.g. size, colour, etc...)."),
            'CurrenciesExplanation' => _t('EcommerceDBConfig.CURRENCIESEXPLANATION', 'Currency explanation'),
            'EmailLogo' => _t('EcommerceDBConfig.EMAILLOGO', 'Email Logo'),
            'DefaultProductImage' => _t('EcommerceDBConfig.DEFAULTPRODUCTIMAGE', 'Default Product Image'),
            'DefaultThumbnailImageSize' => _t('EcommerceDBConfig.DEFAULTTHUMBNAILIMAGESIZE', 'Product Thumbnail Optimised Size'),
            'DefaultSmallImageSize' => _t('EcommerceDBConfig.DEFAULTSMALLIMAGESIZE', 'Product Small Image Optimised Size'),
            'DefaultContentImageSize' => _t('EcommerceDBConfig.DEFAULTCONTENTIMAGESIZE', 'Product Content Image Optimised Size'),
            'DefaultLargeImageSize' => _t('EcommerceDBConfig.DEFAULTLARGEIMAGESIZE', 'Product Large Image Optimised Size'),
            'AllowFreeProductPurchase' => _t('EcommerceDBConfig.ALLOWFREEPRODUCTPURCHASE', 'Allow free products to be purchased? '),
        ];
    }

    /**
     * definition of field lables
     * TODO: is this a common SS method?
     *
     * @return array
     */
    public function customDescriptionsForFields()
    {
        return [
            'Title' => _t('EcommerceDBConfig.TITLE_DESCRIPTION', 'For internal use only.'),
            'UseThisOne' => _t('EcommerceDBConfig.USETHISONE_DESCRIPTION', 'You can create several setting records so that you can switch between configurations.'),
            'ShopPricesAreTaxExclusive' => _t('EcommerceDBConfig.SHOPPRICESARETAXEXCLUSIVE_DESCRIPTION', 'If this option is NOT ticked, it is assumed that prices are tax inclusive.'),
            'DefaultTaxRate' => _t('EcommerceDBConfig.DEFAULTTAXRATE_DESCRIPTION', 'For a 10% tax, please enter 0.1.'),
            'ReceiptEmail' => _t('EcommerceDBConfig.RECEIPTEMAIL_DESCRIPTION_DESCRIPTION', 'e.g. sales@app.com, you can also use something like: "Our Shop Name Goes Here" &lt;sales@app.com&gt;'),
            'AllowFreeProductPurchase' => _t('EcommerceDBConfig.ALLOWFREEPRODUCTPURCHASE_DESCRIPTION', 'This is basically a protection to disallow sales of products that do not have a price entered yet. '),
            'CurrenciesExplanation' => _t('EcommerceDBConfig.CURRENCIESEXPLANATION_DESCRIPTION', 'Explain how the user can switch between currencies and how the exchange rates are worked out.'),
            'PackingSlipTitle' => _t('EcommerceDBConfig.PACKINGSLIPTITLE_DESCRIPTION', 'e.g. Package Contents'),
            'PackingSlipNote' => _t('EcommerceDBConfig.PACKING_SLIP_NOTE_DESCRIPTION', 'e.g. a disclaimer'),
            'InvoiceTitle' => _t('EcommerceDBConfig.INVOICETITLE_DESCRIPTION', 'e.g. Tax Invoice or Update for your recent order on www.yoursite.co.nz'),
            'InvoiceMessage' => _t('EcommerceDBConfig.INVOICEMESSAGE_DESCRIPTION', 'e.g. Thank you for your order.'),
        ];
    }

    /**
     * standard SS method.
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(
            (
                function (FieldList $fields) {
                    //important - first remove fields with custom labels before adding them again,
                    //otherwise there will be a server error when attempting to save
                    $fields->addFieldsToTab(
                        'Root',
                        [
                            Tab::create(
                                'Pricing',
                                _t('EcommerceDBConfig.PRICING', 'Pricing')
                            ),
                            Tab::create(
                                'Products',
                                _t('EcommerceDBConfig.PRODUCTS', 'Products')
                            ),
                            Tab::create(
                                'ProductImages',
                                _t('EcommerceDBConfig.PRODUCT_IMAGES', 'Product Images')
                            ),
                            Tab::create(
                                'AddressAndDelivery',
                                _t('EcommerceDBConfig.ADDRESS_AND_DELIVERY', 'Address and Delivery')
                            ),
                            Tab::create(
                                'Payments',
                                _t('EcommerceDBConfig.PAYMENT_TAB', 'Payments')
                            ),
                            Tab::create(
                                'Emails',
                                _t('EcommerceDBConfig.EMAILS', 'Emails')
                            ),
                            Tab::create(
                                'Process',
                                _t('EcommerceDBConfig.PROCESS', 'Process')
                            ),
                            Tab::create(
                                'Advanced',
                                _t('EcommerceDBConfig.ADVANCED', 'Advanced')
                            ),
                            Tab::create(
                                'Offline',
                                _t('EcommerceDBConfig.OFFLINE', 'Offline')
                            ),
                        ]
                    );
                }
            )
        );
        // foreach ($self->customFieldLabels() as $name => $label) {
        //     $fields->removeByName($name);
        // }
        $fields = parent::getCMSFields();
        //new section
        $fieldDescriptions = $this->customDescriptionsForFields();
        $fieldLabels = $this->fieldLabels();
        $fields->addFieldsToTab(
            'Root.Main',
            [
                new TextField('Title', $fieldLabels['Title']),
                CheckboxField::create(
                    'UseThisOne',
                    $fieldLabels['UseThisOne']
                ),
                new TreeDropdownField('ShopPolicyInfoPageID', 'Shop policy info', SiteTree::class),
                HTMLReadonlyField::create(
                    'RefreshWebsite',
                    'Update site',
                    '<h2><a href="/shoppingcart/clear/?flush=all" target="_blank">Refresh website / clear caches</a></h2>'
                ),
            ]
        );
        $fields->addFieldsToTab(
            'Root.Pricing',
            [
                new CheckboxField('ShopPricesAreTaxExclusive', $fieldLabels['ShopPricesAreTaxExclusive']),
                (new NumericField('DefaultTaxRate', $fieldLabels['DefaultTaxRate']))
                    ->setScale(null),
                new CheckboxField('AllowFreeProductPurchase', $fieldLabels['AllowFreeProductPurchase']),
                new HTMLEditorField('CurrenciesExplanation', $fieldLabels['CurrenciesExplanation']),
            ]
        );
        $fields->addFieldsToTab(
            'Root.Offline',
            [
                new CheckboxField('ShopClosed', $fieldLabels['ShopClosed']),
            ]
        );
        $fields->addFieldsToTab(
            'Root.Products',
            [
                (new CheckboxField('ShowFullDetailsForProducts', $fieldLabels['ShowFullDetailsForProducts']))
                    ->setDescription('In the CMS, show additional info about price and order changes on products'),
                new NumericField('NumberOfProductsPerPage', $fieldLabels['NumberOfProductsPerPage']),
                new CheckboxField('ProductsAlsoInOtherGroups', $fieldLabels['ProductsAlsoInOtherGroups']),
                new CheckboxField('OnlyShowProductsThatCanBePurchased', $fieldLabels['OnlyShowProductsThatCanBePurchased']),
                new HTMLEditorField('NotForSaleMessage', $fieldLabels['NotForSaleMessage']),
                new CheckboxField('ProductsHaveWeight', $fieldLabels['ProductsHaveWeight']),
                new CheckboxField('ProductsHaveModelNames', $fieldLabels['ProductsHaveModelNames']),
                new CheckboxField('ProductsHaveQuantifiers', $fieldLabels['ProductsHaveQuantifiers']),
                //new CheckboxField("ProductsHaveVariations", $fieldLabels["ProductsHaveVariations"])
            ]
        );
        $fields->addFieldsToTab(
            'Root.ProductImages',
            [
                new ProductProductImageUploadField('DefaultProductImage', $fieldLabels['DefaultProductImage']),
            ]
        );
        $fields->addFieldsToTab(
            'Root.AddressAndDelivery',
            [
                new TextField('PostalCodeURL', $fieldLabels['PostalCodeURL']),
                new TextField('PostalCodeLabel', $fieldLabels['PostalCodeLabel']),
                new TextField('PhoneNumberForShop', 'Phone Number for Shop'),
                new TreeDropdownField('DeliveryInfoPageID', 'Delivery Info Page', SiteTree::class),
                new TextField('ShippingDescriptionShort', 'Delivery Info Short', SiteTree::class),
                new HTMLEditorField('ShopPhysicalAddress', $fieldLabels['ShopPhysicalAddress']),
                new TextField('PackingSlipTitle', $fieldLabels['PackingSlipTitle']),
                new HTMLEditorField('PackingSlipNote', $fieldLabels['PackingSlipNote']),
            ]
        );
        $fields->addFieldsToTab(
            'Root.Payments',
            []
        );
        $fields->addFieldsToTab(
            'Root.Emails',
            [
                new TextField('ReceiptEmail', $fieldLabels['ReceiptEmail']),
                new UploadField('EmailLogo', $fieldLabels['EmailLogo']),
                new TextField('InvoiceTitle', $fieldLabels['InvoiceTitle']),
                $htmlEditorField5 = new HTMLEditorField('InvoiceMessage', $fieldLabels['InvoiceMessage']),
            ]
        );
        $fields->addFieldsToTab(
            'Root.Process',
            [
                $this->getOrderStepsField(),
            ]
        );
        $fields->addFieldsToTab(
            'Root.Advanced',
            [
                new LiteralField(
                    'ReviewHardcodedSettings',
                    '<p>
                        Your developer has pre-set some configurations for you.
                        You can
                        <a href="/dev/ecommerce/ecommercetaskcheckconfiguration" data-popup="true">review these settings</a>
                        but you will need to ask your developer to change them if they are not right.
                        The reason they can not be set is that changing them can break your application.
                    </p>'
                ),
            ]
        );

        foreach ($fields->dataFields() as $field) {
            if (isset($fieldDescriptions[$field->getName()])) {
                $field->setDescription($fieldDescriptions[$field->Name]);
            }
        }

        Requirements::block('sunnysideup/ecommerce: client/javascript/EcomPrintAndMail.js');

        return $fields;
    }

    /**
     * link to edit the record.
     *
     * @param null|string $action - e.g. edit
     *
     * @return string
     */
    public function CMSEditLink($action = null)
    {
        return CMSEditLinkAPI::find_edit_link_for_object($this);
    }

    public function getOrderStepsField()
    {
        $gridFieldConfig = GridFieldConfig::create()->addComponents(
            new GridFieldToolbarHeader(),
            new GridFieldSortableHeader(),
            new GridFieldDataColumns(),
            new GridFieldPaginator(200),
            new GridFieldEditButton(),
            new GridFieldDeleteAction(),
            new GridFieldDetailForm()
        );

        return new GridField('OrderSteps', _t('OrderStep.PLURALNAME', 'Order Steps'), OrderStep::get(), $gridFieldConfig);
    }

    /**
     * tells us if a Class Name is a buyable.
     *
     * @todo: consider using Ecomerce Configuration instead?
     * In EcomConfig we only list base classes.
     *
     * @param string $className - name of the class to be tested
     *
     * @return bool
     */
    public static function is_buyable(?string $className = null): bool
    {
        if ($className) {
            $className = ClassHelpers::unsanitise_class_name($className);
            if (class_exists($className)) {
                $implementorsArray = class_implements($className);
                return is_array($implementorsArray) && !empty($implementorsArray) && in_array(BuyableModel::class, $implementorsArray, true);
            }
        } else {
            user_error('No class name provided', E_USER_NOTICE);
        }
        return false;
    }

    /**
     * Returns the current member.
     *
     * @return Member
     */
    public function Customer()
    {
        return Security::getCurrentUser();
    }

    /**
     * Returns the member for the current order.
     *
     * @return Member
     */
    public function CustomerForOrder()
    {
        $order = ShoppingCart::current_order();

        return $order->Member();
    }

    /**
     * Return the currency being used on the site e.g. "NZD" or "USD".
     */
    public function Currency(): string
    {
        return EcommerceConfig::get(EcommerceCurrency::class, 'default_currency');
    }

    /**
     * return null if there is less than two currencies in use
     * on the site.
     */
    public function Currencies(): ?DataList
    {
        $list = EcommerceCurrency::get_list();

        if ($list && $list->limit(2)->count() > 1) {
            return $list;
        }

        return null;
    }

    /**
     * @return string (URLSegment)
     */
    public function AccountPageLink(): string
    {
        return AccountPage::find_link();
    }

    /**
     * @return string (URLSegment)
     */
    public function CheckoutLink(): string
    {
        return CheckoutPage::find_link();
    }

    /**
     * @return string (URLSegment)
     */
    public function CartPageLink(): string
    {
        return CartPage::find_link();
    }

    /**
     * @return string (URLSegment)
     */
    public function OrderConfirmationPageLink(): string
    {
        return OrderConfirmationPage::find_link();
    }

    /**
     * Returns a link to a default image.
     * If a default image is set in the site config then this link is returned
     * Otherwise, a standard link is returned.
     */
    public function DefaultImageLink(): string
    {
        if ($this->DefaultProductImageID) {
            $defaultImage = $this->DefaultProductImage();
            if ($defaultImage && $defaultImage->exists()) {
                return $defaultImage->Link();
            }
        }

        return ModuleResourceLoader::resourceURL('sunnysideup/ecommerce: client/images/productPlaceHolderSmaller.jpg');
    }

    /**
     * Returns the default image or a dummy one if it does not exists.
     *
     * @return Image
     */
    public function DefaultImage()
    {
        if ($this->DefaultProductImageID) {
            $defaultImage = $this->DefaultProductImage();
            if ($defaultImage) {
                if ($defaultImage->exists()) {
                    return $defaultImage;
                }
            }
        }

        $obj = Image::create();
        $obj->Link = $this->DefaultImageLink();
        $obj->URL = $this->DefaultImageLink();

        return $obj;
    }

    /**
     * standard SS Method.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        if (! self::current_ecommerce_db_config()) {
            $obj = self::create();
            $obj->write();
        }

        DB::alteration_message(
            '
            <hr /><hr /><hr /><hr /><hr />
            <h1 style="color: darkRed">Please make sure to review your <a href="/dev/ecommerce/">e-commerce settings</a>.</h1>
            <hr /><hr /><hr /><hr /><hr />',
            'edited'
        );
    }

    /**
     * returns site config.
     *
     * @return SiteConfig
     */
    public function SiteConfig()
    {
        return SiteConfig::current_site_config();
    }

    /**
     * Casted Variable.
     *
     * @return string
     */
    public function UseThisOneNice()
    {
        return $this->UseThisOne ? 'YES' : 'NO';
    }

    /**
     * get final value for recursive lookups.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function recursiveValue(string $fieldNameOrMethod, $default = null)
    {
        $value = null;
        $fieldNameOrMethodWithGet = 'get' . $fieldNameOrMethod;
        if ($this->hasMethod($fieldNameOrMethod)) {
            $outcome = $this->{$fieldNameOrMethod}();
            if (is_object($outcome) && $outcome->exists()) {
                $value = $outcome;
            } elseif ($outcome && ! is_object($outcome)) {
                $value = $outcome;
            }
        } elseif ($this->hasMethod($fieldNameOrMethodWithGet)) {
            $outcome = $this->{$fieldNameOrMethodWithGet}();
            if (is_object($outcome) && $outcome->exists()) {
                $value = $outcome;
            } elseif ($outcome && ! is_object($outcome)) {
                $value = $outcome;
            }
        } else {
            $value = $this->{$fieldNameOrMethod} ?? null;
        }

        if (! $value) {
            $value = $default;
        }

        return $value;
    }

    /**
     * standard SS method.
     */
    protected function onAfterWrite()
    {
        if ($this->UseThisOne) {
            $configs = EcommerceDBConfig::get()
                ->Filter(['UseThisOne' => 1])
                ->Exclude(['ID' => $this->ID]);
            if ($configs->exists()) {
                foreach ($configs as $config) {
                    $config->UseThisOne = 0;
                    $config->write();
                }
            }
        }

        $configs = EcommerceDBConfig::get()
            ->Filter(['Title' => $this->Title])
            ->Exclude(['ID' => $this->ID]);
        if ($configs->exists()) {
            foreach ($configs as $config) {
                $config->Title .= '_' . $config->ID;
                $config->write();
            }
        }
    }
}
