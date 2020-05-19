<?php

namespace Sunnysideup\Ecommerce\Model;













use Translatable;




use Sunnysideup\Ecommerce\Model\Order;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\NumericField;
use Sunnysideup\Ecommerce\Forms\Fields\BuyableSelectField;
use Sunnysideup\Ecommerce\Interfaces\BuyableModel;
use Sunnysideup\Ecommerce\Model\OrderItem;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Money\EcommerceCurrency;
use Sunnysideup\Ecommerce\Forms\Fields\EcomQuantityField;
use SilverStripe\ORM\FieldType\DBField;
use Sunnysideup\Ecommerce\Pages\Product;
use SilverStripe\Control\Director;
use Sunnysideup\Ecommerce\Pages\CheckoutPage;
use Sunnysideup\Ecommerce\Control\ShoppingCartController;
use Sunnysideup\Ecommerce\Tasks\EcommerceTaskDebugCart;


/**
 * @description: An order item is a product which has been added to an order.
 * An order item links to a Buyable (product) by class name
 * That is, we only store the BuyableID and the ClassName
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model

 **/
class OrderItem extends OrderAttribute
{
    ######################
    ## TEMPLATE METHODS ##
    ######################

    protected static $calculated_buyable_price = [];

    /**
     * Store for buyables.
     * We store this here to speed up things a little
     * Format is like this
     * Array(
     *  0 => Buyable (versioned)
     *  1 => Buyable (current)
     * );.
     *
     * @var array
     */
    protected $tempBuyableStore = [];

    /**
     * what variables are accessible through  http://mysite.com/api/ecommerce/v1/OrderItem/.
     *
     * @var array
     */
    private static $api_access = [
        'view' => [
            'InternalItemID',
            'CalculatedTotal',
            'TableTitle',
            'TableSubTitleNOHTML',
            'Name',
            'TableValue',
            'Quantity',
            'BuyableID',
            'BuyableClassName',
            'Version',
            'UnitPrice',
            'Total',
            Order::class,
        ],
    ];

    /**
     * stardard SS variable.
     *
     * @var array
     */

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * OLD: private static $db (case sensitive)
  * NEW: 
    private static $table_name = '[SEARCH_REPLACE_CLASS_NAME_GOES_HERE]';

    private static $db (COMPLEX)
  * EXP: Check that is class indeed extends DataObject and that it is not a data-extension!
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
    
    private static $table_name = 'OrderItem';


/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: private static $db = (case sensitive)
  * NEW: private static $db = (COMPLEX)
  * EXP: Make sure to add a private static $table_name!
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
    private static $db = [
        'Quantity' => 'Double',
        'BuyableID' => 'Int',
        'BuyableClassName' => 'Varchar(60)',
        'Version' => 'Int',
    ];

    /**
     * @var array
     *            stardard SS definition
     */
    private static $indexes = [
        'Quantity' => true,
        'BuyableID' => true,
        'BuyableClassName' => true,
    ];

    /**
     * @var array
     *            stardard SS definition
     */
    private static $casting = [
        'UnitPrice' => 'Currency',
        'UnitPriceAsMoney' => 'Money',
        'Total' => 'Currency',
        'TotalAsMoney' => 'Money',
        'InternalItemID' => 'Varchar',
        'Link' => 'Varchar',
        'AbsoluteLink' => 'Varchar',
        'BuyableLink' => 'Varchar',
        'BuyableExists' => 'Boolean',
        'BuyableFullName' => 'Varchar',
        'BuyableMoreDetails' => 'Varchar',
    ];

    ######################
    ## CMS CONFIG ##
    ######################

    /**
     * @var array
     *            stardard SS definition
     */
    private static $searchable_fields = [
        'OrderID' => [

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: NumericField (case sensitive)
  * NEW: NumericField (COMPLEX)
  * EXP: check the number of decimals required and add as ->Step(123)
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            'field' => 'NumericField',
            'title' => 'Order Number',
        ],
        //"TableTitle" => "PartialMatchFilter",
        //"UnitPrice",
        'Quantity',
        //"Total"
    ];

    /**
     * @var array
     *            stardard SS definition
     */
    private static $field_labels = [
        //@todo - complete
    ];

    /**
     * @var array
     * stardard SS definition
     */
    private static $summary_fields = [
        'OrderID' => 'Order ID',
        'BuyableFullName' => 'Item',
        'BuyableMoreDetails' => 'Details ... ',
        'UnitPrice' => 'Unit Price',
        'Quantity' => 'Quantity',
        'Total' => 'Total Price',
    ];

    /**
     * singular name of the object. it is recommended to override this
     * in any extensions of this class.
     *
     * @var string
     */
    private static $singular_name = 'Order Item';

    /**
     * plural name of the object. it is recommended to override this
     * in any extensions of this class.
     *
     * @var string
     */
    private static $plural_name = 'Order Items';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'Any item that is added to an order and sits before the sub-total. ';

    public function i18n_singular_name()
    {
        return _t('OrderItem.ORDERITEM', 'Order Item');
    }

    public function i18n_plural_name()
    {
        return _t('OrderItem.ORDERITEMS', 'Order Items');
    }

    /**
     * HACK: Versioned is BROKEN this method helps in fixing it.
     * Basically, in Versioned, you get a hard-coded error
     * when you retrieve an older version of a DataObject.
     * This method returns null if it does not exist.
     *
     * Idea is from Jeremy: https://github.com/burnbright/silverstripe-shop/blob/master/code/products/FixVersioned.php
     *
     * @param string $class
     * @param int    $id
     * @param int    $version
     *
     * @return DataObject | Null
     */
    public static function get_version($class, $id, $version)
    {
        $oldMode = Versioned::get_reading_mode();
        Versioned::set_reading_mode('');
        $versionedObject = Versioned::get_version($class, $id, $version);
        Versioned::set_reading_mode($oldMode);

        return $versionedObject;
    }

    /**
     * Standard SS method.
     *
     * @var string
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField('BuyableID', HiddenField::create('BuyableID'));
        $fields->replaceField('BuyableClassName', HiddenField::create('BuyableClassName'));
        $fields->replaceField('Version', HiddenField::create('Version'));
        if ($this->OrderID && $this->exists()) {
            $fields->replaceField('OrderID', $fields->dataFieldByName('OrderID')->performReadonlyTransformation());
            $fields->addFieldsToTab(
                'Root.Advanced',
                [
                    HeaderField::create('BuyableHeading', 'Buyable'),

                    ReadonlyField::create('BuyableIDCheck', 'BuyableID', $this->BuyableID),
                    ReadonlyField::create('BuyableClassNameCheck', 'BuyableClassName', $this->BuyableClassName),
                    ReadonlyField::create('VersionCheck', 'Version', $this->Version),
                    $linkField1 = ReadonlyField::create('BuyableLinkExample', 'Buyable Link', '<a href="' . $this->BuyableLink() . '">' . $this->BuyableLink() . '</a>'),
                    ReadonlyField::create('TableTitle', 'TableTitle', $this->TableTitle),
                    ReadonlyField::create('Subtitle', 'Table SubTitle', $this->TableSubTitleNOHTML()),
                    ReadonlyField::create('InternalItemID', 'InternalItemID', $this->InternalItemID()),
                    ReadonlyField::create('Name', 'Name', $this->Name),

                    HeaderField::create('OrderItemHeading', 'Order Item'),
                    $linkField2 = ReadonlyField::create('LinkExample', 'Link', '<a href="' . $this->Link() . '">' . $this->Link() . '</a>'),

                    ReadonlyField::create('ClassName'),
                    ReadonlyField::create('Created'),
                    ReadonlyField::create('LastEdited'),

                    HeaderField::create('PricingHeading', 'Pricing'),
                    ReadonlyField::create('QuantityCheck', 'Quantity', $this->Quantity),
                    ReadonlyField::create('UnitPrice', 'UnitPrice', $this->UnitPrice),
                    ReadonlyField::create('CalculatedTotal', 'Total', $this->CalculatedTotal),
                    ReadonlyField::create('TableValue', 'Table Value', $this->TableValue),
                    ReadonlyField::create('Total', 'Total', $this->Total),
                    ReadonlyField::create('TotalAsMoney', 'Total as Money Object', $this->TotalAsMoney()->Nice()),
                ]
            );

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: ->dontEscape (case sensitive)
  * NEW: ->dontEscape (COMPLEX)
  * EXP: dontEscape is not longer in use for form fields, please use HTMLReadonlyField (or similar) instead.
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            $linkField1->dontEscape = true;

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: ->dontEscape (case sensitive)
  * NEW: ->dontEscape (COMPLEX)
  * EXP: dontEscape is not longer in use for form fields, please use HTMLReadonlyField (or similar) instead.
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            $linkField2->dontEscape = true;
        } else {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: NumericField (case sensitive)
  * NEW: NumericField (COMPLEX)
  * EXP: check the number of decimals required and add as ->Step(123)
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            $fields->replaceField('OrderID', NumericField::create('OrderID', _t('Order.SINGULARNAME', Order::class)));
        }
        $fields->removeByName('Sort');
        $fields->removeByName('CalculatedTotal');
        $fields->removeByName('GroupSort');
        $fields->removeByName('OrderAttributeGroupID');
        if ($order = $this->Order()) {
            if ($order->IsSubmitted()) {
                if ($buyable = $this->Buyable()) {
                    if ($this->BuyableExists()) {
                        $buyableLink = '<a href="' . $buyable->CMSEditLink() . '">' . $this->getBuyableFullName() . '</a>';
                    } else {
                        $buyableLink = $this->getBuyableFullName()
                        . _t('OrderItem.NO_LONGER_AVAILABLE', ' - NO LONGER AVAILABLE');
                    }
                } else {
                    $buyableLink = _t('OrderItem.BUYABLE_NOT_FOUND', 'item not found');
                }
                $fields->addFieldToTab(
                    'Root.Main',
                    HeaderField::create('buyableLink', $buyableLink),
                    'Quantity'
                );

                $fields->addFieldToTab(
                    'Root.Main',
                    ReadonlyField::create('TableTitle', _t('OrderItem.ROW_TITLE', 'Row Title'), $this->TableTitle()),
                    'Quantity'
                );
                $fields->addFieldToTab(
                    'Root.Main',
                    ReadonlyField::create('TableSubTitleNOHTML', _t('OrderItem.SUB_TITLE', 'Sub Title'), $this->BuyableMoreDetails()),
                    'Quantity'
                );
            } else {
                $fields->addFieldToTab('Root.Main', BuyableSelectField::create('FindBuyable', _t('OrderItem.SELECITEM', 'Select Item'), $this->Buyable()));
            }
        } else {
            $fields->addFieldToTab('Root.Main', BuyableSelectField::create('FindBuyable', _t('OrderItem.SELECITEM', 'Select Item'), $this->Buyable()));
        }

        return $fields;
    }

    /**
     * standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     **/
    public function canDelete($member = null)
    {
        return $this->canEdit($member);
    }

    /**
     * Determine which properties on the DataObject are
     * searchable, and map them to their default {@link FormField}
     * representations. Used for scaffolding a searchform for {@link ModelAdmin}.
     *
     * Some additional logic is included for switching field labels, based on
     * how generic or specific the field type is.
     *
     * Used by {@link SearchContext}.
     *
     * @param array $_params
     *                       'fieldClasses': Associative array of field names as keys and FormField classes as values
     *                       'restrictFields': Numeric array of a field name whitelist
     *
     * @return FieldList
     */
    public function scaffoldSearchFields($_params = null)
    {
        $fields = parent::scaffoldSearchFields($_params);

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: NumericField (case sensitive)
  * NEW: NumericField (COMPLEX)
  * EXP: check the number of decimals required and add as ->Step(123)
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        $fields->replaceField('OrderID', new NumericField('OrderID', 'Order Number'));

        return $fields;
    }

    /**
     * standard SS method.
     *
     * @param BuyableModel $buyable
     * @param float        $quantity
     *
     * @return FieldList
     **/
    public function addBuyableToOrderItem(BuyableModel $buyable, $quantity = 1)
    {
        $this->Version = $buyable->Version;
        $this->BuyableID = $buyable->ID;
        $this->BuyableClassName = $buyable->ClassName;
        $this->Quantity = $quantity;
    }

    /**
     * used to return data for ajax.
     *
     * @param array $js
     *
     * @return array used to create JSON for AJAX
     **/
    public function updateForAjax(array $js)
    {
        $function = EcommerceConfig::get(OrderItem::class, 'ajax_total_format');
        if (is_array($function)) {
            list($function, $format) = $function;
        }
        $total = $this->{$function}();
        if (isset($format)) {
            $total = $total->{$format}();
        }
        $ajaxObject = $this->AJAXDefinitions();
        if ($this->Quantity) {
            $js[] = [
                't' => 'id',
                's' => $ajaxObject->TableID(),
                'p' => 'hide',
                'v' => 0,
            ];
            $js[] = [
                't' => 'name',
                's' => $ajaxObject->QuantityFieldName(),
                'p' => 'value',
                'v' => $this->Quantity,
            ];
            $js[] = [
                't' => 'id',
                's' => $ajaxObject->TableTitleID(),
                'p' => 'innerHTML',
                'v' => $this->TableTitle(),
            ];
            $js[] = [
                't' => 'id',
                's' => $ajaxObject->CartTitleID(),
                'p' => 'innerHTML',
                'v' => $this->CartTitle(),
            ];
            $js[] = [
                't' => 'id',
                's' => $ajaxObject->TableSubTitleID(),
                'p' => 'innerHTML',
                'v' => $this->TableSubTitle(),
            ];
            $js[] = [
                't' => 'id',
                's' => $ajaxObject->CartSubTitleID(),
                'p' => 'innerHTML',
                'v' => $this->CartSubTitle(),
            ];
            $js[] = [
                't' => 'id',
                's' => $ajaxObject->TableTotalID(),
                'p' => 'innerHTML',
                'v' => $total,
            ];
        } else {
            $js[] = [
                't' => 'id',
                's' => $ajaxObject->TableID(),
                'p' => 'hide',
                'v' => 1,
            ];
        }

        return $js;
    }

    /**
     * saves details about the Order Item before the order is submittted.
     *
     * @param bool $recalculate - run it, even if it has run already
     **/
    public function runUpdate($recalculate = false)
    {
        $buyable = $this->Buyable(true);
        if ($buyable && $buyable->canPurchase()) {
            if (isset($buyable->Version)) {
                if ($this->Version !== $buyable->Version) {
                    $this->Version = $buyable->Version;
                    $this->write();
                }
            }
            $oldValue = $this->CalculatedTotal - 0;
            $newValue = ($this->getUnitPrice() * $this->Quantity) - 0;
            if ((round($newValue, 5) !== round($oldValue, 5)) || $recalculate) {
                $this->CalculatedTotal = $newValue;
                $this->write();
            }
        } else {
            //if it can not be purchased or it does not exist
            //then we do not accept it!!!!
            $this->delete();
        }

        return parent::runUpdate($recalculate);
    }

    /**
     * Standard SS method.
     * If the quantity is zero then we set it to 1.
     * TODO: evaluate this rule.
     */
    public function onBeforeWrite()
    {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: Session:: (case sensitive)
  * NEW: SilverStripe\Control\Controller::curr()->getRequest()->getSession()-> (COMPLEX)
  * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly. 
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        if (SilverStripe\Control\Controller::curr()->getRequest()->getSession()->get('EcommerceOrderGETCMSHack') && ! $this->OrderID) {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: Session:: (case sensitive)
  * NEW: SilverStripe\Control\Controller::curr()->getRequest()->getSession()-> (COMPLEX)
  * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly. 
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            $this->OrderID = intval(SilverStripe\Control\Controller::curr()->getRequest()->getSession()->get('EcommerceOrderGETCMSHack'));
        }
        if (! $this->exists()) {
            if ($buyable = $this->Buyable(true)) {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $this->ClassName (case sensitive)
  * NEW: $this->ClassName (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
                if ($this->ClassName === OrderItem::class && $this->BuyableClassName !== OrderItem::class) {
                    $this->setClassName($buyable->classNameForOrderItem());
                }
            }
        }
        //now we can do the parent thing
        parent::onBeforeWrite();
        //always keep quantity above 0
        if (floatval($this->Quantity) === 0) {
            $this->Quantity = 1;
        }
        if (! $this->Version && $buyable = $this->Buyable(true)) {
            $this->Version = $buyable->Version;
        }
    }

    /**
     * Standard SS method
     * the method below is very important...
     * We initialise the order once it has an OrderItem.
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        $order = $this->Order();
        if ($order) {
            if (! $order->StatusID) {
                //this adds the modifiers and automatically WRITES AGAIN - WATCH RACING CONDITIONS!
                $order->init(true);
            }
        }
    }

    /**
     * Check if two Order Items are the same.
     * Useful when adding two items to cart.
     *
     * @param OrderItem $orderItem
     *
     * @return bool
     **/
    public function hasSameContent(OrderItem $orderItem)
    {
        return is_a($orderItem, Object::getCustomClass(OrderItem::class)) &&
            $this->BuyableID === $orderItem->BuyableID &&
            $this->BuyableClassName === $orderItem->BuyableClassName &&
            $this->Version === $orderItem->Version;
    }

    public static function reset_calculated_buyable_price()
    {
        self::$calculated_buyable_price = [];
    }

    public function UnitPrice($recalculate = false)
    {
        return $this->getUnitPrice($recalculate);
    }

    public function getUnitPrice($recalculate = false)
    {
        if ($this->priceHasBeenFixed($recalculate) && ! $recalculate) {
            if (! $this->Quantity) {
                $this->Quantity = 1;
            }

            return $this->CalculatedTotal / $this->Quantity;
        } elseif ($buyable = $this->Buyable()) {
            if (! isset(self::$calculated_buyable_price[$this->ID]) || $recalculate) {
                self::$calculated_buyable_price[$this->ID] = $buyable->getCalculatedPrice();
            }
            $unitPrice = self::$calculated_buyable_price[$this->ID];
        } else {
            $unitPrice = 0;
        }
        //$updatedUnitPrice = $this->extend('updateUnitPrice', $price);
        //if ($updatedUnitPrice !== null && is_array($updatedUnitPrice) && count($updatedUnitPrice)) {
        //    $unitPrice = $updatedUnitPrice[0];
        //}

        return $unitPrice;
    }

    public function UnitPriceAsMoney($recalculate = false)
    {
        return $this->getUnitPriceAsMoney($recalculate);
    }

    public function getUnitPriceAsMoney($recalculate = false)
    {
        return EcommerceCurrency::get_money_object_from_order_currency($this->getUnitPrice($recalculate), $this->Order());
    }

    /**
     * @param bool $recalculate - forces recalculation of price
     *
     * @return float
     */
    public function Total($recalculate = false)
    {
        return $this->getTotal();
    }

    public function getTotal($recalculate = false)
    {
        if ($this->priceHasBeenFixed()) {
            //get from database
            $total = $this->CalculatedTotal;
        } else {
            $total = $this->getUnitPrice($recalculate) * $this->Quantity;
        }
        $updatedTotal = $this->extend('updateTotal', $total);
        if ($updatedTotal !== null && is_array($updatedTotal) && count($updatedTotal)) {
            $total = $updatedTotal[0];
        }

        return $total;
    }

    /**
     * @param bool $recalculate - forces recalculation of price
     *
     * @return Money
     */
    public function TotalAsMoney($recalculate = false)
    {
        return $this->getTotalAsMoney($recalculate);
    }

    public function getTotalAsMoney($recalculate = false)
    {
        return EcommerceCurrency::get_money_object_from_order_currency($this->getTotal($recalculate), $this->Order());
    }

    /**
     * Casted variable
     * returns InternalItemID from Buyable.
     */
    public function InternalItemID()
    {
        return $this->getInternalItemID();
    }

    public function getInternalItemID()
    {
        if ($buyable = $this->Buyable()) {
            return $buyable->InternalItemID;
        }
    }

    /**
     * @return Field (EcomQuantityField)
     **/
    public function QuantityField()
    {
        return EcomQuantityField::create($this);
    }

    /**
     * @return Currency (DB Object)
     **/
    public function TotalAsCurrencyObject()
    {
        return DBField::create_field('Currency', $this->Total());
    }

    ##########################
    ## OTHER LOOKUP METHODS ##
    ##########################

    /**
     * @param int $orderID
     */
    public static function reset_price_has_been_fixed($orderID = 0)
    {
        self::set_price_has_been_fixed($orderID, false);
    }

    /**
     * @param bool $current - is this a current one, or an older VERSION ?
     *
     * @return DataObject (Any type of Data Object that is buyable)
     **/
    public function Buyable($current = false)
    {
        return $this->getBuyable($current);
    }

    /**
     * @param string $current - is this a current one, or an older VERSION ?
     *
     * @return DataObject (Any type of Data Object that is buyable)
     **/
    public function getBuyable($current = '')
    {
        $currentOrVersion = $current ? 'current' : 'version';
        if ($this->Order() !== null && ! $current) {
            if (! $this->Order()->IsSubmitted()) {
                $currentOrVersion = 'current';
            }
        } elseif ($current === 'version') {
            $currentOrVersion = 'version';
        }
        if (! isset($this->tempBuyableStore[$currentOrVersion])) {
            if (! $this->BuyableID) {
                user_error('There was an error retrieving the product', E_USER_NOTICE);
                return Product::create();
            }
            //start hack
            if (! $this->BuyableClassName) {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $this->ClassName (case sensitive)
  * NEW: $this->ClassName (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
                $this->BuyableClassName = str_replace('_OrderItem', '', $this->ClassName);
            }
            $turnTranslatableBackOn = false;

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $className (case sensitive)
  * NEW: $className (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            $className = $this->BuyableClassName;

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $this->class (case sensitive)
  * NEW: $this->class (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $className (case sensitive)
  * NEW: $className (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            if ($className::has_extension($this->class, 'Translatable')) {
                Translatable::disable_locale_filter();
                $turnTranslatableBackOn = true;
            }
            //end hack!
            $obj = null;
            if ($currentOrVersion === 'current') {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $className (case sensitive)
  * NEW: $className (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
                $obj = $className::get()->byID($this->BuyableID);
            }

            //run if current not available or current = false
            if (! $obj || ! $current) {
                if (! $obj || (! $obj->exists()) && $this->Version) {
                    /* @TODO: check if the version exists?? - see sample below
                    $versionTable = $this->BuyableClassName."_versions";
                    $dbConnection = DB::get_conn();
                    if($dbConnection && $dbConnection instanceOf MySQLDatabase && $dbConnection->hasTable($versionTable)) {
                        $result = DB::query("
                            SELECT COUNT(\"ID\")
                            FROM \"$versionTable\"
                            WHERE
                                \"RecordID\" = ".intval($this->BuyableID)."
                                AND \"Version\" = ".intval($this->Version)."
                        ");
                        if($result->value()) {
                     */
                    $obj = self::get_version($this->BuyableClassName, $this->BuyableID, $this->Version);
                }
                //our second to last resort
                if (! $obj || (! $obj->exists())) {
                    $obj = Versioned::get_latest_version($this->BuyableClassName, $this->BuyableID);
                }
            }
            //our final backup
            if (! $obj || (! $obj->exists())) {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $className (case sensitive)
  * NEW: $className (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
                $obj = $className::get()->byID($this->BuyableID);
            }
            if ($turnTranslatableBackOn) {
                Translatable::enable_locale_filter();
            }
            $this->tempBuyableStore[$currentOrVersion] = $obj;
        }
        //check for data integrity
        return $this->tempBuyableStore[$currentOrVersion];
    }

    /**
     * @alias for getBuyableTitle
     * @return string
     **/
    public function BuyableTitle()
    {
        return $this->getBuyableTitle();
    }

    /**
     * @return string
     **/
    public function getBuyableTitle()
    {
        return $this->getTitle();
    }

    public function getTitle()
    {
        if ($buyable = $this->Buyable()) {
            if ($title = $buyable->Title) {
                return $title;
            }
            //This should work in all cases, because ultimately, it will return #ID - see DataObject
            return parent::getTitle();
        }
        return 'ERROR: product not found';
        user_error('No Buyable could be found for OrderItem with ID: ' . $this->ID, E_USER_NOTICE);
    }

    /**
     * @alias for getBuyableLink
     * @return string
     */
    public function BuyableLink()
    {
        return $this->getBuyableLink();
    }

    /**
     * @return string
     */
    public function getBuyableLink()
    {
        $buyable = $this->Buyable();
        if ($buyable && $buyable->exists()) {
            $order = $this->Order();
            if ($order && $order->IsSubmitted()) {
                return Director::absoluteURL($buyable->VersionedLink());
            }
            return Director::absoluteURL($buyable->Link());
        }
        return $this->getLink();
    }

    /**
     * @alias for getBuyableExists
     * @return bool
     */
    public function BuyableExists()
    {
        return $this->getBuyableExists();
    }

    /**
     * @return bool
     */
    public function getBuyableExists()
    {
        if ($buyable = $this->Buyable(true)) {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $className (case sensitive)
  * NEW: $className (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            $className = $buyable->ClassName;
            $id = $buyable->ID;

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $className (case sensitive)
  * NEW: $className (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
            return $className::get()->byID($id) ? true : false;
        }

        return false;
    }

    /**
     * @alias for getBuyableFullName
     * @return string
     */
    public function BuyableFullName()
    {
        return $this->getBuyableFullName();
    }

    /**
     * @return string
     */
    public function getBuyableFullName()
    {
        $buyable = $this->Buyable();
        if ($buyable && $buyable->exists()) {
            return $buyable->FullName;
        }
        return $this->getBuyableTitle();
    }

    /**
     * @alias for getBuyableMoreDetails
     * @return string
     */
    public function BuyableMoreDetails()
    {
        return $this->getBuyableMoreDetails();
    }

    /**
     * @return string
     */
    public function getBuyableMoreDetails()
    {
        if ($subtitle = $this->TableSubTitleNOHTML) {
            return $subtitle;
        }
        $buyable = $this->Buyable();
        if ($buyable && $buyable->exists()) {
            if ($buyable->ShortDescription) {
                return $buyable->ShortDescription;
            }
        }

        return _t('OrderItem.NA', 'n/a');
    }

    ##########################
    ## LINKS                ##
    ##########################

    /**
     * @return string (URLSegment)
     **/
    public function Link()
    {
        return $this->getLink();
    }

    /**
     * @return string (URLSegment)
     **/
    public function getLink()
    {
        $order = $this->Order();
        if ($order) {
            return $order->Link();
        }
        return Director::absoluteURL('order-link-could-not-be-found');
    }

    /**
     * alias
     * @return string
     */
    public function AbsoluteLink()
    {
        return $this->getAbsoluteLink();
    }

    /**
     * @return string
     */
    public function getAbsoluteLink()
    {
        return Director::absoluteURL($this->getLink());
    }

    /**
     * @return string (URLSegment)
     **/
    public function CheckoutLink()
    {
        return CheckoutPage::find_link();
    }

    ## Often Overloaded functions ##

    /**
     * @return string (URLSegment)
     **/
    public function AddLink()
    {
        return ShoppingCartController::add_item_link($this->BuyableID, $this->BuyableClassName, $this->linkParameters());
    }

    /**
     * @return string (URLSegment)
     **/
    public function IncrementLink()
    {
        return ShoppingCartController::add_item_link($this->BuyableID, $this->BuyableClassName, $this->linkParameters());
    }

    /**
     * @return string (URLSegment)
     **/
    public function DecrementLink()
    {
        return ShoppingCartController::remove_item_link($this->BuyableID, $this->BuyableClassName, $this->linkParameters());
    }

    /**
     * @return string (URLSegment)
     **/
    public function RemoveLink()
    {
        return ShoppingCartController::remove_item_link($this->BuyableID, $this->BuyableClassName, $this->linkParameters());
    }

    /**
     * @return string (URLSegment)
     **/
    public function RemoveAllLink()
    {
        return ShoppingCartController::remove_all_item_link($this->BuyableID, $this->BuyableClassName, $this->linkParameters());
    }

    /**
     * @return string (URLSegment)
     **/
    public function RemoveAllAndEditLink()
    {
        return ShoppingCartController::remove_all_item_and_edit_link($this->BuyableID, $this->BuyableClassName, $this->linkParameters());
    }

    /**
     * @return string (URLSegment)
     **/
    public function SetSpecificQuantityItemLink($quantity)
    {
        return ShoppingCartController::set_quantity_item_link($this->BuyableID, $this->BuyableClassName, array_merge($this->linkParameters(), ['quantity' => $quantity]));
    }

    public function debug()
    {
        $html = EcommerceTaskDebugCart::debug_object($this);
        $html .= '<ul>';
        $html .= '<li><b>Buyable Price:</b> ' . $this->Buyable()->Price . ' </li>';
        $html .= '<li><b>Buyable Calculated Price:</b> ' . $this->Buyable()->CalculatedPrice() . ' </li>';
        $html .= '</ul>';

        return $html;
    }

    /**
     * @Todo: do we still need this?
     *
     * @return array for use as get variables in link
     **/
    protected function linkParameters()
    {
        $array = [];
        $updatedLinkParameters = $this->extend('updateLinkParameters', $array);
        if ($updatedLinkParameters !== null && is_array($updatedLinkParameters) && count($updatedLinkParameters)) {
            foreach ($updatedLinkParameters as $updatedLinkParametersUpdate) {
                $array = array_merge($array, $updatedLinkParametersUpdate);
            }
        }

        return $array;
    }
}

