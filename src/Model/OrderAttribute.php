<?php

namespace Sunnysideup\Ecommerce\Model;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBMoney;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\CmsEditLinkField\Api\CMSEditLinkAPI;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfigAjax;
use Sunnysideup\Ecommerce\Config\EcommerceConfigAjaxDefinitions;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Money\EcommerceCurrency;
use Sunnysideup\Ecommerce\Tasks\EcommerceTaskDebugCart;
use Sunnysideup\Ecommerce\Traits\OrderCached;

/**
 * Class \Sunnysideup\Ecommerce\Model\OrderAttribute
 *
 * @property float $CalculatedTotal
 * @property int $Sort
 * @property int $GroupSort
 * @property int $OrderID
 */
class OrderAttribute extends DataObject implements EditableEcommerceObject
{
    use OrderCached;

    /**
     * save edit status for speed's sake.
     *
     * @var null|bool
     */
    protected $_canEdit;

    /**
     * save view status for speed's sake.
     *
     * @var null|bool
     */
    protected $_canView;

    /**
     * we use this variable to make sure that the parent::runUpdate() is called in all child classes
     * this is similar to the checks run for parent::init in the controller class.
     *
     * @var bool
     */
    protected $baseInitCalled = false;

    /**
     * what variables are accessible through  http://mysite.com/api/ecommerce/v1/ShippingAddress/.
     *
     * @var array
     */
    private static $api_access = [
        'view' => [
            'CalculatedTotal',
            'Sort',
            'GroupSort',
            'TableTitle',
            'TableSubTitleNOHTML',
            'CartTitle',
            'CartSubTitle',
            'Order',
        ],
    ];

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $table_name = 'OrderAttribute';

    private static $db = [
        'CalculatedTotal' => 'Currency',
        'Sort' => 'Int',
        'GroupSort' => 'Int',
        'TableSubTitleFixed' => 'HTMLVarchar(900)',
    ];

    /**
     * Standard SS variable.
     *
     * @var array
     */
    private static $has_one = [
        'Order' => Order::class,
    ];

    /**
     * Standard SS variable.
     *
     * @var array
     */
    private static $casting = [
        'TableSubTitleNOHTML' => 'Text',
        'TableTitle' => 'HTMLText',
        'CartTitle' => 'HTMLText',
        'CartSubTitle' => 'HTMLText',
        'CalculatedTotalAsMoney' => 'Money',
        'TableTitle' => 'HTMLText',
    ];

    /**
     * Standard SS variable.
     *
     * @var array
     */
    private static $default_sort = [
        'OrderAttribute.GroupSort' => 'ASC',
        'OrderAttribute.Sort' => 'ASC',
        'OrderAttribute.ID' => 'ASC',
    ];

    /**
     * Standard SS variable.
     *
     * @var array
     */
    private static $indexes = [
        'GroupSort' => true,
        'Sort' => true,
        'ID' => true,
    ];

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Order Entry';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Order Extra Descriptions';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'Any item that is added to the order - be it before (e.g. product) or after the subtotal (e.g. tax).';

    /**
     * Helps in speeding up code.
     * This can be a static variable as it is the same for all OrderItems for an Order.
     *
     * @var array
     */
    private static $_price_has_been_fixed = [];

    public function i18n_singular_name()
    {
        return _t('OrderAttribute.ORDERENTRY', 'Order Entry');
    }

    public function i18n_plural_name()
    {
        return _t('OrderAttribute.ORDERENTRIES', 'Order Entries');
    }

    /**
     * extended in OrderModifier and OrderItem
     * Starts up the order Atribute
     * TODO: introduce system like we have for Controller
     * which makes sure that all parent init methods are called.
     */
    public function init()
    {
        return true;
    }

    /**
     * standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        if (!$member) {
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
     * Standard SS method
     * This is an important method.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canView($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }
        if (!$this->exists()) {
            return true;
        }
        if (null === $this->_canView) {
            $this->_canView = false;
            if ($this->OrderID) {
                $o = $this->getOrderCached();
                if ($o) {
                    if ($o->exists()) {
                        if ($o->canView($member)) {
                            $this->_canView = true;
                        }
                    }
                }
            }
        }

        return $this->_canView;
    }

    /**
     * Standard SS method
     * This is an important method.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canEdit($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }
        if (!$this->exists()) {
            return true;
        }
        if (null === $this->_canEdit) {
            $this->_canEdit = !$this->priceHasBeenFixed();
        }

        return $this->_canEdit;
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
        return false;
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
        return CMSEditLinkAPI::find_edit_link_for_object($this, $action);
    }

    /**
     * @param int  $orderID
     * @param bool $value
     */
    public static function set_price_has_been_fixed(?int $orderID = 0, $value = false)
    {
        $orderID = ShoppingCart::current_order_id($orderID);
        self::$_price_has_been_fixed[$orderID] = $value;
    }

    /**
     * @param int $orderID
     *
     * @return null|bool
     */
    public static function get_price_has_been_fixed(?int $orderID = 0)
    {
        $orderID = ShoppingCart::current_order_id($orderID);

        return self::$_price_has_been_fixed[$orderID] ?? null;
    }

    //#####################
    //# TEMPLATE METHODS ##
    //#####################

    /**
     * This is a key function that returns the type of the
     * object.  In principle anything can be returned
     * but the intention is to only return a few options
     * e.g. OrderItem, Tax, Delivery, etc... so that
     * computations can be carried out based on the type of
     * OrderAttribute we are looking at.
     * It also allows to get a group of Order Attributes that
     * contains both modifiers and orderItems.
     *
     * @return string
     */
    public function OrderAttributeType()
    {
        return $this->ClassName;
    }

    /**
     * returns the order - for some unknown reason it seems we need this.
     *
     * @return null|Order
     */
    public function Order()
    {
        return Order::get_order_cached((int) $this->OrderID);
    }

    /**
     * Return a string of class names, in order
     * of hierarchy from OrderAttribute for the
     * current attribute.
     *
     * e.g.: "product_orderitem orderitem
     * orderattribute".
     *
     * Used by the templates and for ajax updating functionality.
     *
     * @return string
     */
    public function Classes()
    {
        $class = static::class;
        $classes = [];
        $class = get_parent_class($class);
        while ($class && DataObject::class !== $class) {
            $classes[] = strtolower(ClassInfo::shortName($class));
            $class = get_parent_class($class);
        }
        if (is_a($this, EcommerceConfigClassNames::getName(OrderItem::class))) {
            $classes[] = strtolower((string) $this->BuyableClassName);
        }

        return implode(' ', $classes);
    }

    /**
     * returns the instance of EcommerceConfigAjax for use in templates.
     * In templates, it is used like this:
     * $EcommerceConfigAjax.TableID.
     *
     * @return EcommerceConfigAjaxDefinitions
     */
    public function AJAXDefinitions()
    {
        return EcommerceConfigAjax::get_one($this);
    }

    /*
     * Should this item be shown on check out page table?
     * @return bool
     */
    public function ShowInTable(): bool
    {
        return true;
    }

    /**
     *Should this item be shown on in the cart (which is on other pages than the checkout page).
     *
     * @return bool
     */
    public function ShowInCart()
    {
        return $this->ShowInTable();
    }

    /**
     * Return a name of what this attribute is
     * called e.g. "Product 21" or "Discount".
     *
     * @return string
     */
    public function TableTitle(): string
    {
        return $this->getTableTitle();
    }

    public function getTableTitle(): string
    {
        if($this->priceHasBeenFixed()) {
            if($this->Name) {
                return (string) $this->Name;
            }
        }
        return (string) $this->i18n_singular_name();
    }

    /**
     * Return a name of what this attribute is
     * called e.g. "Product 21" or "Discount"
     * Cart is a short version of table.
     *
     * @return string
     */
    public function CartTitle(): string
    {
        return $this->getCartTitle();
    }

    public function getCartTitle(): string
    {
        return $this->getTableTitle();
    }

    /**
     * the sub title for the order item or order modifier.
     *
     * @return string
     */
    public function TableSubTitle(): string
    {
        return $this->getTableSubTitle();
    }

    public function getTableSubTitle(): string
    {
        if($this->priceHasBeenFixed()) {
            if($this->TableSubTitleFixed) {
                return (string) $this->TableSubTitleFixed;
            }
        }
        return (string) '';
    }

    /**
     * the sub title for the order item or order modifier.
     *
     * @return string
     */
    public function TableSubTitleNOHTML(): string
    {
        return $this->getTableSubTitleNOHTML();
    }

    public function getTableSubTitleNOHTML(): string
    {
        return (string) str_replace("\n", '', strip_tags((string) $this->getTableSubTitle()));
    }

    /**
     * the sub title for the order item or order modifier.
     * Cart is a short version of table.
     *
     * @return string
     */
    public function CartSubTitle(): string
    {
        return $this->getCartSubTitle();
    }

    public function getCartSubTitle(): string
    {
        return (string) $this->TableSubTitle();
    }

    /**
     * Returns the Money object of the CalculatedTotal.
     *
     * @return DBMoney
     */
    public function CalculatedTotalAsMoney()
    {
        return $this->getCalculatedTotalAsMoney();
    }

    public function getCalculatedTotalAsMoney()
    {
        return EcommerceCurrency::get_money_object_from_order_currency($this->CalculatedTotal, $this->getOrderCached());
    }

    public function runUpdate($recalculate = false)
    {
        $this->extend('runUpdateExtension', $this);
        $this->baseRunUpdateCalled = true;
    }

    /**
     * Debug helper method.
     * Access through : /shoppingcart/debug/.
     */
    public function debug()
    {
        return EcommerceTaskDebugCart::debug_object($this);
    }

    /**
     * Standard SS method
     * We add the Sort value from the OrderAttributeGroup to the OrderAttribute.
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($this->priceHasBeenFixed()) {
            //do nothing ...
        } else {
            if ($this->OrderAttributeGroupID) {
                $group = $this->OrderAttributeGroup();
                if ($group) {
                    $this->GroupSort = $group->Sort;
                }
            }
            $this->TableSubTitleFixed = $this->getTableSubTitle();
        }

    }

    /**
     * Standard SS method.
     */
    protected function onAfterWrite()
    {
        parent::onAfterWrite();
        //crucial!
        Order::set_needs_recalculating(true, $this->OrderID);
    }

    /**
     * Standard SS method.
     */
    protected function onBeforeDelete()
    {
        parent::onBeforeDelete();
        //crucial!
        Order::set_needs_recalculating(true, $this->OrderID);
    }

    /**
     * @description - tells you if an order item price has been "fixed"
     * meaning that is has been saved in the CalculatedTotal field so that
     * it can not be altered.
     *
     * Default returns false; this is good for uncompleted orders
     * but not so good for completed ones.
     *
     * We use direct calls to self::$_price_has_been_fixed to make the code simpler and faster.
     *
     * @param mixed $recalculate
     *
     * @return bool
     */
    protected function priceHasBeenFixed($recalculate = false)
    {
        if ($this->OrderID) {
            if (null === self::get_price_has_been_fixed($this->OrderID) || $recalculate || Order::get_needs_recalculating($this->OrderID)) {
                self::$_price_has_been_fixed[$this->OrderID] = false;
                $order = $this->getOrderCached();
                if ($order && $order->IsSubmitted()) {
                    self::$_price_has_been_fixed[$this->OrderID] = true;
                    if ($recalculate) {
                        user_error('You are trying to recalculate an order that is already submitted.', E_USER_NOTICE);
                    }
                }
            }
            return self::$_price_has_been_fixed[$this->OrderID];
        }
        return false;
    }
}
