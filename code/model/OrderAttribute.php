<?php
/**
 * @description: base class for OrderItem (item in cart) and OrderModifier (extra - e.g. Tax)
 *
 * @see OrderModifier
 * @see OrderItem
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderAttribute extends DataObject implements EditableEcommerceObject
{
    /**
     * what variables are accessible through  http://mysite.com/api/ecommerce/v1/ShippingAddress/.
     *
     * @var array
     */
    private static $api_access = array(
        'view' => array(
            'CalculatedTotal',
            'Sort',
            'GroupSort',
            'TableTitle',
            'TableSubTitleNOHTML',
            'CartTitle',
            'CartSubTitle',
            'Order',
        ),
     );

    /**
     * Standard SS variable.
     *
     * @var array
     */
    private static $db = array(
        'CalculatedTotal' => 'Currency',
        'Sort' => 'Int',
        'GroupSort' => 'Int',
    );

    /**
     * Standard SS variable.
     *
     * @var array
     */
    private static $has_one = array(
        'Order' => 'Order',
    );

    /**
     * Standard SS variable.
     *
     * @var array
     */
    private static $casting = array(
        'TableTitle' => 'HTMLText',
        'TableSubTitle' => 'HTMLText',
        'TableSubTitleNOHTML' => 'Text',
        'CartTitle' => 'HTMLText',
        'CartSubTitle' => 'HTMLText',
        'CalculatedTotalAsMoney' => 'Money',
    );

    /**
     * Standard SS variable.
     *
     * @var string
     **/
    private static $default_sort = [
        'OrderAttribute.GroupSort' => 'ASC',
        'OrderAttribute.Sort' => 'ASC',
        'OrderAttribute.ID' => 'ASC'
    ];

    /**
     * Standard SS variable.
     *
     * @var array
     */
    private static $indexes = array(
        'GroupSort' => true,
        'Sort' => true,
        'ID' => true
    );

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Order Entry';
    public function i18n_singular_name()
    {
        return _t('OrderAttribute.ORDERENTRY', 'Order Entry');
    }

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Order Extra Descriptions';
    public function i18n_plural_name()
    {
        return _t('OrderAttribute.ORDERENTRIES', 'Order Entries');
    }

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'Any item that is added to the order - be it before (e.g. product) or after the subtotal (e.g. tax).';

    /**
     * save edit status for speed's sake.
     *
     * @var bool
     */
    protected $_canEdit = null;

    /**
     * save view status for speed's sake.
     *
     * @var bool
     */
    protected $_canView = null;

    /**
     * we use this variable to make sure that the parent::runUpdate() is called in all child classes
     * this is similar to the checks run for parent::init in the controller class.
     *
     * @var bool
     **/
    protected $baseInitCalled = false;

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
     * @param Member $member
     *
     * @return bool
     **/
    public function canCreate($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS method
     * This is an important method.
     *
     * @param Member $member
     *
     * @return bool
     **/
    public function canView($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (!$this->exists()) {
            return true;
        }
        if ($this->_canView === null) {
            $this->_canView = false;
            if ($this->OrderID) {
                if ($o = $this->Order()) {
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
     * @param Member $member
     *
     * @return bool
     **/
    public function canEdit($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (!$this->exists()) {
            return true;
        }
        if ($this->_canEdit === null) {
            $this->_canEdit = $this->priceHasBeenFixed() ? false : true;
        }

        return $this->_canEdit;
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     **/
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * link to edit the record.
     *
     * @param string | Null $action - e.g. edit
     *
     * @return string
     */
    public function CMSEditLink($action = null)
    {
        return CMSEditLinkAPI::find_edit_link_for_object($this, $action);
    }


    /**
     * Helps in speeding up code.
     * This can be a static variable as it is the same for all OrderItems for an Order.
     *
     * @var array
     */
    private static $_price_has_been_fixed = array();


    /**
     * @param int $orderID
     * @param bool $value
     */
    public static function set_price_has_been_fixed($orderID = 0, $value = false)
    {
        $orderID = ShoppingCart::current_order_id($orderID);
        self::$_price_has_been_fixed[$orderID] = $value;
    }

    /**
     * @param int $orderID
     * @return bool|null
     */
    public static function get_price_has_been_fixed($orderID = 0)
    {
        $orderID = ShoppingCart::current_order_id($orderID);

        return isset(self::$_price_has_been_fixed[$orderID]) ? self::$_price_has_been_fixed[$orderID] : null;
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
     * @return bool
     **/
    protected function priceHasBeenFixed($recalculate = false)
    {
        if (self::get_price_has_been_fixed($this->OrderID) === null || $recalculate) {
            self::$_price_has_been_fixed[$this->OrderID] = false;
            if ($order = $this->Order()) {
                if ($order->IsSubmitted()) {
                    self::$_price_has_been_fixed[$this->OrderID] = true;
                    if ($recalculate) {
                        user_error('You are trying to recalculate an order that is already submitted.', E_USER_NOTICE);
                    }
                }
            }
        }

        return self::$_price_has_been_fixed[$this->OrderID];
    }

    ######################
    ## TEMPLATE METHODS ##
    ######################

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
     * @return Order | null
     */
    public function Order()
    {
        return Order::get()->byID($this->OrderID);
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
        $class = get_class($this);
        $classes = array();
        $classes[] = strtolower($class);
        while (get_parent_class($class) != 'DataObject' && $class = get_parent_class($class)) {
            $classes[] = strtolower($class);
        }
        if (is_a($this, Object::getCustomClass('OrderItem'))) {
            $classes[] = strtolower($this->BuyableClassName);
        }

        return implode(' ', $classes);
    }

    /**
     * returns the instance of EcommerceConfigAjax for use in templates.
     * In templates, it is used like this:
     * $EcommerceConfigAjax.TableID.
     *
     * @return EcommerceConfigAjax
     **/
    public function AJAXDefinitions()
    {
        return EcommerceConfigAjax::get_one($this);
    }

    /**
     * returns the instance of EcommerceDBConfig.
     *
     * @return EcommerceDBConfig
     **/
    public function EcomConfig()
    {
        return EcommerceDBConfig::current_ecommerce_db_config();
    }

    /*
     * Should this item be shown on check out page table?
     * @return Boolean
     **/
    public function ShowInTable()
    {
        return true;
    }

    /**
     *Should this item be shown on in the cart (which is on other pages than the checkout page).
     *
     *@return bool
     **/
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
    public function TableTitle()
    {
        return $this->getTableTitle();
    }
    public function getTableTitle()
    {
        return $this->i18n_singular_name();
    }

    /**
     * Return a name of what this attribute is
     * called e.g. "Product 21" or "Discount"
     * Cart is a short version of table.
     *
     * @return string
     */
    public function CartTitle()
    {
        return $this->getCartTitle();
    }
    public function getCartTitle()
    {
        return $this->TableTitle();
    }

    /**
     * the sub title for the order item or order modifier.
     *
     * @return string
     **/
    public function TableSubTitle()
    {
        return $this->getTableSubTitle();
    }
    public function getTableSubTitle()
    {
        return '';
    }

    /**
     * the sub title for the order item or order modifier.
     *
     * @return string
     **/
    public function TableSubTitleNOHTML()
    {
        return $this->getTableSubTitleNOHTML();
    }
    public function getTableSubTitleNOHTML()
    {
        return str_replace("\n", '', strip_tags($this->getTableSubTitle()));
    }

    /**
     * the sub title for the order item or order modifier.
     * Cart is a short version of table.
     *
     * @return string
     **/
    public function CartSubTitle()
    {
        return $this->getCartSubTitle();
    }
    public function getCartSubTitle()
    {
        return $this->TableSubTitle();
    }

    /**
     * Returns the Money object of the CalculatedTotal.
     *
     * @return Money
     **/
    public function CalculatedTotalAsMoney()
    {
        return $this->getCalculatedTotalAsMoney();
    }
    public function getCalculatedTotalAsMoney()
    {
        return EcommerceCurrency::get_money_object_from_order_currency($this->CalculatedTotal, $this->Order());
    }

    public function runUpdate($force = false)
    {
        $this->baseRunUpdateCalled = true;
    }

    /**
     * Standard SS method
     * We add the Sort value from the OrderAttribute_Group to the OrderAttribute.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($this->OrderAttribute_GroupID) {
            if ($group = $this->OrderAttribute_Group()) {
                $this->GroupSort = $group->Sort;
            }
        }
    }

    /**
     * Standard SS method.
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        //crucial!
        Order::set_needs_recalculating(true, $this->OrderID);
    }

    /**
     * Debug helper method.
     * Access through : /shoppingcart/debug/.
     */
    public function debug()
    {
        $html = EcommerceTaskDebugCart::debug_object($this);

        return $html;
    }
}
