<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Control\CartResponse;
use Sunnysideup\Ecommerce\Interfaces\BuyableModel;
use Sunnysideup\Ecommerce\Model\Address\BillingAddress;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;
use Sunnysideup\Ecommerce\Model\Address\EcommerceRegion;
use Sunnysideup\Ecommerce\Model\Address\ShippingAddress;
use Sunnysideup\Ecommerce\Model\Money\EcommerceCurrency;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\OrderItem;
use Sunnysideup\Ecommerce\Model\OrderModifier;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Sunnysideup\Ecommerce\Model\Process\Referral;
use Sunnysideup\Ecommerce\Tasks\EcommerceTaskCartCleanup;

/*
 * ShoppingCart - provides a global way to interface with the cart (current order).
 *
 * This can be used in other code by calling $cart = ShoppingCart::singleton();
 *
 * The shopping cart can be accessed as an order handler from the back-end
 * (e.g. when creating an order programmatically), while the accompagnying controller
 * is used by web-users to manipulate their order.
 *
 * A bunch of core functions are also stored in the order itself.
 * Methods and variables are in the shopping cart if they are relevant
 * only before (and while) the order is placed (e.g. latest update message),
 * and others are in the order because they are relevant even after the
 * order has been submitted (e.g. Total Cost).
 *
 * Key methods:
 *
 * //get Cart
 * $myCart = ShoppingCart::singleton();
 *
 * //get order
 * $myOrder = ShoppingCart::current_order();
 *
 * //view order (from another controller)
 * $this->redirect(ShoppingCart::current_order()->Link());
 *
 * //add item to cart
 * ShoppingCart::singleton()->addBuyable($myProduct);
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: control
 *
 */
class ShoppingCart
{
    use Extensible;
    use Injectable;
    use Configurable;

    /**
     * Feedback message to user (e.g. cart updated, could not delete item, someone in standing behind you).
     *
     * @var array
     */
    protected $messages = [];

    /**
     * stores a reference to the current order object.
     *
     * @var null|Order
     */
    protected $order;

    /**
     * This variable is set to YES when we actually need an order (i.e. write it).
     *
     * @var bool
     */
    protected $requireSavedOrder = false;

    /**
     * List of names that can be used as session variables.
     * Also @see ShoppingCart::sessionVariableName.
     *
     * @var array
     */
    private static $session_variable_names = ['OrderID', 'Messages'];

    /**
     * @var string
     */
    private static $session_code = 'EcommerceShoppingCart';

    /**
     * @var bool
     */
    private static $cleanup_every_time = true;

    /**
     * @var array
     */
    private static $default_param_filters = [];

    /**
     * @var string
     */
    private static $response_class = CartResponse::class;

    /**
     * This is where we hold the (singleton) Shoppingcart.
     *
     * @var object (ShoppingCart)
     */
    private static $_singletoncart;

    private static $_allow_writes_cache;

    /**
     * Allows access to the cart from anywhere in code.
     *
     * @return ShoppingCart Object
     */
    public static function singleton()
    {
        if (! self::$_singletoncart) {
            self::$_singletoncart = Injector::inst()->get(ShoppingCart::class);
        }

        return self::$_singletoncart;
    }

    /**
     * Allows access to the current order from anywhere in the code..
     *
     * if you do not like the session Order then you can set it here ...
     *
     * @param Order $order (optional)
     *
     * @return Order
     */
    public static function current_order(?Order $order = null)
    {
        return self::singleton()->currentOrder(0, $order);
    }

    /**
     * useful when the order has been updated ...
     */
    public static function reset_order_reference()
    {
        return self::singleton()->order = null;
    }

    /**
     * looks up current order id.
     * you may supply an ID here, so that it looks up the current order ID
     * only when none is supplied.
     *
     * @param int|Order $orderOrOrderID
     *
     * @return int;
     */
    public static function current_order_id($orderOrOrderID = 0)
    {
        $orderID = 0;
        if (! $orderOrOrderID) {
            $order = self::current_order();
            if ($order && $order->exists()) {
                $orderID = $order->ID;
            }
        }

        if (ClassHelpers::check_for_instance_of($orderOrOrderID, Order::class, false)) {
            $orderID = $orderOrOrderID->ID;
        } elseif ((int) $orderOrOrderID) {
            $orderID = (int) $orderOrOrderID;
        }

        return $orderID;
    }

    /**
     * Allows access to the current order from anywhere in the code..
     */
    public static function session_order(): ?Order
    {
        $sessionVariableName = self::singleton()->sessionVariableName('OrderID');
        $orderIDFromSession = Controller::curr()->getRequest()->getSession()->get($sessionVariableName) - 0;

        // @var Order|null
        return Order::get_order_cached((int) $orderIDFromSession);
    }

    /**
     * set a specific order, other than the one from session ....
     *
     * @param Order $order
     *
     * @return Order
     */
    public function setOrder($order): Order|null
    {
        $this->order = $order;

        return $this->order;
    }

    /**
     * Gets or creates the current order.
     * Based on the session ONLY unless the order has been explictely set.
     * IMPORTANT FUNCTION!
     *
     * returns null if the current user does not allow order manipulation or saving (e.g. session disabled)
     *
     * However, you can pass an order in case you want to manipulate an order that is not in sesssion
     *
     * @return Order
     */
    public function currentOrder(?int $recurseCount = 0, ?Order $order = null)
    {
        if ($order) {
            $this->order = $order;
        }

        if ($this->allowWrites()) {
            if (! $this->order) {
                $this->order = self::session_order();
                $loggedInMember = Security::getCurrentUser();
                if ($this->order) {
                    //first reason to set to null: it is already submitted
                    if ($this->order->IsSubmitted()) {
                        $this->order = null;
                    } elseif (! $this->order->canView()) {
                        //second reason to set to null: make sure we have permissions
                        $this->order = null;
                    } elseif ($loggedInMember && $loggedInMember->exists()) {
                        //logged in, add Member.ID to order->MemberID
                        if ($this->order->MemberID !== $loggedInMember->ID) {
                            $updateMember = false;
                            if (! $this->order->MemberID) {
                                $updateMember = true;
                            }

                            if (! $loggedInMember->IsShopAdmin()) {
                                $updateMember = true;
                            }

                            if ($updateMember) {
                                $this->order->MemberID = $loggedInMember->ID;
                                $this->order->write();
                            }
                        }

                        //IF current order has nothing in it AND the member already has an order: use the old one first
                        //first, lets check if the current order is worthwhile keeping
                        if ($this->order->StatusID || $this->order->TotalItems()) {
                            //do NOTHING!
                        } else {
                            $firstStep = DataObject::get_one(OrderStep::class);
                            //we assume the first step always exists.
                            //TODO: what sort order?
                            $count = 0;
                            /** @var null|Order $previousOrderFromMember */
                            $previousOrderFromMember = DataObject::get_one(Order::class, '
                                    "MemberID" = ' . $loggedInMember->ID . '
                                    AND ("StatusID" = ' . $firstStep->ID . ' OR "StatusID" = 0)
                                    AND "Order"."ID" <> ' . $this->order->ID);
                            while ($firstStep && $previousOrderFromMember) {
                                //arbritary 12 attempts ...
                                if ($count > 12) {
                                    break;
                                }

                                ++$count;
                                if ($previousOrderFromMember && $previousOrderFromMember->canView()) {
                                    if ($previousOrderFromMember->StatusID || $previousOrderFromMember->TotalItems()) {
                                        $this->order->delete();
                                        $this->order = $previousOrderFromMember;

                                        break;
                                    }

                                    $previousOrderFromMember->delete();
                                }

                                $previousOrderFromMember = DataObject::get_one(Order::class, '
                                        "MemberID" = ' . $loggedInMember->ID . '
                                        AND ("StatusID" = ' . $firstStep->ID . ' OR "StatusID" = 0)
                                        AND "Order"."ID" <> ' . $this->order->ID);
                            }
                        }
                    }
                }

                if (! $this->order) {
                    if ($loggedInMember) {
                        //find previour order...
                        /** @var null|OrderStep $firstStep */
                        $firstStep = DataObject::get_one(OrderStep::class);
                        if ($firstStep) {
                            $previousOrderFromMember = Order::get()->filter(['MemberID' => $loggedInMember->ID, 'StatusID' => [$firstStep->ID, 0]])->first();
                            if ($previousOrderFromMember) {
                                if ($previousOrderFromMember->canView()) {
                                    $this->order = $previousOrderFromMember;
                                }
                            }
                        }
                    }

                    if ($this->order && ! $this->order->exists()) {
                        $this->order = null;
                    }

                    if (! $this->order) {
                        //here we cleanup old orders, because they should be
                        //cleaned at the same rate that they are created...
                        if (EcommerceConfig::get(ShoppingCart::class, 'cleanup_every_time')) {
                            $cartCleanupTask = Injector::inst()->get(EcommerceTaskCartCleanup::class);
                            $cartCleanupTask->runSilently();
                        }

                        //create new order
                        $this->order = Order::create();
                        if ($loggedInMember) {
                            $this->order->MemberID = $loggedInMember->ID;
                        }

                        $this->order->write();
                    }

                    $sessionVariableName = $this->sessionVariableName('OrderID');
                    Controller::curr()->getRequest()->getSession()->set($sessionVariableName, (int) $this->order->ID);
                }

                if ($this->order) {
                    if ($this->order->exists()) {
                        // when we first load it, we recalculate!
                        // dont worry about init, because we do not need to init it yet ...
                        $this->order->calculateOrderAttributes($recalculate = true);
                    }

                    if (! $this->order->SessionID) {
                        $this->order->write();
                    }

                    //add session ID...
                }
            }

            //try it again
            //but limit to three, just in case ...
            if (! $this->order && $recurseCount < 3) {
                ++$recurseCount;

                return $this->currentOrder($recurseCount, $order);
            }

            return $this->order;
        }

        //we still return an order so that we do not end up with errors...
        return Order::create();
    }

    /**
     * Allows access to the current order from anywhere in the code..
     *
     * @param Order $order (optional)
     *
     * @return string
     */
    public function Link(?Order $order = null): string
    {
        $order = self::singleton()->currentOrder(0, $order);
        if ($order) {
            return $order->Link();
        }
        return '';
    }

    /**
     * Adds any number of items to the cart.
     * Returns the order item on succes OR false on failure.
     *
     * @param BuyableModel $buyable    - the buyable (generally a product) being added to the cart
     * @param float        $quantity   - number of items add
     * @param mixed        $parameters - array of parameters to target a specific order item. eg: group=1, length=5
     *                                 if you make it a form, it will save the form into the orderitem
     *                                 returns null if the current user does not allow order manipulation or saving (e.g. session disabled)
     *
     * @return bool|OrderItem
     */
    public function addBuyable(BuyableModel $buyable, ?float $quantity = 1.00, $parameters = [])
    {
        if ($this->allowWrites()) {
            if (! $buyable) {
                $this->addMessage(_t('Order.ITEMCOULDNOTBEFOUND', 'This item could not be found.'), 'bad');

                return false;
            }

            if (! $buyable->canPurchase()) {
                $this->addMessage(_t('Order.ITEMCOULDNOTBEADDED', 'This item is not for sale.'), 'bad');

                return false;
            }

            $item = $this->prepareOrderItem($buyable, $parameters, $mustBeExistingItem = false);
            $quantity = $this->prepareQuantity($buyable, $quantity);
            if ($item && $quantity) {
                //find existing order item or make one
                $item->Quantity += $quantity;
                $item->write();
                $this->currentOrder()->Attributes()->add($item);
                $msg = $quantity > 1 ? _t('Order.ITEMSADDED', 'Items added.') : _t('Order.ITEMADDED', 'Item added.');
                $this->addMessage($msg);
            } elseif (! $item) {
                $this->addMessage(_t('Order.ITEMNOTFOUND', 'Item could not be found.'), 'bad');
            } else {
                $this->addMessage(_t('Order.ITEMCOULDNOTBEADDED', 'Item could not be added.'), 'bad');
            }

            return $item;
        }

        $this->addMessage(_t('Order.CAN_NOT_BE_WRITTEN', 'Cart can not be updated.'), 'bad');

        return false;
    }

    /**
     * Sets quantity for an item in the cart.
     *
     * returns null if the current user does not allow order manipulation or saving (e.g. session disabled)
     *
     * @param BuyableModel $buyable    - the buyable (generally a product) being added to the cart
     * @param float        $quantity   - number of items add
     * @param array        $parameters - array of parameters to target a specific order item. eg: group=1, length=5
     *
     * @return bool|OrderItem
     */
    public function setQuantity(BuyableModel $buyable, $quantity, array $parameters = [])
    {
        if ($this->allowWrites()) {
            /**
             * @var OrderItem $item
             */
            $item = $this->prepareOrderItem($buyable, $parameters, $mustBeExistingItem = false);
            $quantity = $this->prepareQuantity($buyable, $quantity);
            if ($item) {
                $item->Quantity = $quantity;
                //remove quantity
                $item->write();
                $this->addMessage(_t('Order.ITEMUPDATED', 'Item updated.'));

                return $item;
            }

            $this->addMessage(_t('Order.ITEMNOTFOUND', 'Item could not be found.'), 'bad');
        } else {
            $this->addMessage(_t('Order.CAN_NOT_BE_WRITTEN', 'Cart can not be updated.'), 'bad');
        }

        return false;
    }

    /**
     * Removes any number of items from the cart.
     *
     * returns null if the current user does not allow order manipulation or saving (e.g. session disabled)
     *
     * @param BuyableModel $buyable    - the buyable (generally a product) being added to the cart
     * @param float        $quantity   - number of items add
     * @param array        $parameters - array of parameters to target a specific order item. eg: group=1, length=5
     *
     * @return bool|OrderItem
     */
    public function decrementBuyable(BuyableModel $buyable, $quantity = 1.00, array $parameters = [])
    {
        if ($this->allowWrites()) {
            /**
             * @var OrderItem $item
             */
            $item = $this->prepareOrderItem($buyable, $parameters, $mustBeExistingItem = false);
            $quantity = $this->prepareQuantity($buyable, $quantity);
            if ($item) {
                $item->Quantity -= $quantity;
                //remove quantity
                if ($item->Quantity < 0) {
                    $item->Quantity = 0;
                }

                $item->write();
                $msg = $quantity > 1 ? _t('Order.ITEMSREMOVED', 'Items removed.') : _t('Order.ITEMREMOVED', 'Item removed.');
                $this->addMessage($msg);

                return $item;
            }

            $this->addMessage(_t('Order.ITEMNOTFOUND', 'Item could not be found.'), 'bad');
        } else {
            $this->addMessage(_t('Order.CAN_NOT_BE_WRITTEN', 'Cart can not be updated.'), 'bad');
        }

        return false;
    }

    /**
     * Delete item from the cart.
     *
     * returns null if the current user does not allow order manipulation or saving (e.g. session disabled)
     *
     * @param BuyableModel $buyable    - the buyable (generally a product) being added to the cart
     * @param array        $parameters - array of parameters to target a specific order item. eg: group=1, length=5
     *
     * @return bool|OrderItem
     */
    public function deleteBuyable(BuyableModel $buyable, array $parameters = [])
    {
        if ($this->allowWrites()) {
            $item = $this->prepareOrderItem($buyable, $parameters, $mustBeExistingItem = true);
            if ($item) {
                $this->currentOrder()->Attributes()->remove($item);
                $item->delete();
                $item->destroy();
                $this->addMessage(_t('Order.ITEMCOMPLETELYREMOVED', 'Item removed from cart.'));

                return true;
            }

            $this->addMessage(_t('Order.ITEMNOTFOUND', 'Item could not be found.'), 'bad');
        } else {
            $this->addMessage(_t('Order.CAN_NOT_BE_WRITTEN', 'Cart can not be updated.'), 'bad');
        }

        return false;
    }

    /**
     * Checks and prepares variables for a quantity change (add, edit, remove) for an Order Item.
     *
     * @param BuyableModel $buyable            - the buyable (generally a product) being added to the cart
     * @param array        $parameters
     * @param bool         $mustBeExistingItem - if false, the Order Item gets created if it does not exist - if TRUE the order item is searched for and an error shows if there is no Order item
     * @param array|Form   $parameters         - array of parameters to target a specific order item. eg: group=1, length=5*
     *                                         - form saved into item...
     *
     * @return OrderItem|null
     */
    public function prepareOrderItem(BuyableModel $buyable, $parameters = [], $mustBeExistingItem = true)
    {
        $parametersArray = $parameters;
        // $form = null;
        if (ClassHelpers::check_for_instance_of($parameters, Form::class, false)) {
            $parametersArray = [];
            // $form = $parameters;
        }

        if (! $buyable) {
            user_error('No buyable was provided', E_USER_WARNING);
        }

        if (! $buyable->canPurchase()) {
            return null;
        }

        $item = null;

        if ($mustBeExistingItem) {
            $item = $this->getExistingItem($buyable, $parametersArray);
        } else {
            $item = $this->findOrMakeItem($buyable, $parametersArray);
        }

        if (! $item) {
            return null;
        }

        // if ($form) {
        //     $form->saveInto($item);
        // }

        return $item;
    }

    /**
     * @todo: what does this method do???
     *
     * @param float $quantity
     *
     * @return float
     */
    public function prepareQuantity(BuyableModel $buyable, $quantity)
    {
        $quantity = round($quantity, $buyable->QuantityDecimals());
        if ($quantity > 0) {
            return $quantity;
        }

        $this->addMessage(_t('Order.INVALIDQUANTITY', 'Invalid quantity.'), 'warning');

        return 0;
    }

    /**
     * Helper function for making / retrieving order items.
     * we do not need things like "canPurchase" here, because that is with the "addBuyable" method.
     * NOTE: does not write!
     *
     * @return bool|OrderItem
     */
    public function findOrMakeItem(BuyableModel $buyable, array $parameters = [])
    {
        if ($this->allowWrites()) {
            $item = $this->getExistingItem($buyable, $parameters);
            if ($item) {
                //do nothing
            } else {
                //otherwise create a new item
                if (! $buyable instanceof BuyableModel) {
                    $this->addMessage(_t('ShoppingCart.ITEMNOTFOUND', 'Item is not buyable.'), 'bad');

                    return false;
                }

                $className = $buyable->classNameForOrderItem();

                $item = new $className();
                $order = $this->currentOrder();
                if ($order) {
                    $item->OrderID = $order->ID;
                    $item->BuyableID = $buyable->ID;
                    $item->BuyableClassName = $buyable->ClassName;
                    if (property_exists($buyable, 'Version') && null !== $buyable->Version) {
                        $item->Version = $buyable->Version;
                    }
                }
            }

            if ($parameters) {
                $item->Parameters = $parameters;
            }

            if (! $item) {
                $item = OrderItem::create();
            }

            return $item;
        }

        $this->addMessage(_t('Order.CAN_NOT_BE_WRITTEN', 'Cart can not be updated.'), 'bad');

        return false;
    }

    /**
     * submit the order so that it is no longer available
     * in the cart but will continue its journey through the
     * order steps.
     *
     * @return bool
     */
    public function submit()
    {
        if ($this->allowWrites()) {
            $this->currentOrder()->tryToFinaliseOrder();
            $this->clear();
            //little hack to clear static memory
            OrderItem::set_price_has_been_fixed($this->currentOrder()->ID, true);

            return true;
        }

        return false;
    }

    /**
     * returns null if the current user does not allow order manipulation or saving (e.g. session disabled).
     *
     * @return null|bool
     */
    public function save()
    {
        if ($this->allowWrites()) {
            $this->currentOrder()->write();
            $this->addMessage(_t('Order.ORDERSAVED', 'Order Saved.'));

            return true;
        }

        $this->addMessage(_t('Order.CAN_NOT_BE_WRITTEN', 'Cart can not be updated.'), 'bad');

        return false;
    }

    /**
     * Clears the cart contents completely by removing the orderID from session, and
     * thus creating a new cart on next request.
     *
     * @return bool
     */
    public function clear()
    {
        //we keep this here so that a flush can be added...
        set_time_limit(1 * 60);
        self::$_singletoncart = null;
        $this->order = null;
        $this->messages = [];
        foreach (self::$session_variable_names as $name) {
            $sessionVariableName = $this->sessionVariableName($name);
            Controller::curr()->getRequest()->getSession()->set($sessionVariableName, null);
            Controller::curr()->getRequest()->getSession()->clear($sessionVariableName);
            Controller::curr()->getRequest()->getSession()->save(Controller::curr()->getRequest());
        }

        $memberID = (int) Security::getCurrentUser()?->ID;
        if ($memberID) {
            $orders = Order::get()->filter(['MemberID' => $memberID]);
            if ($orders->exists()) {
                foreach ($orders as $order) {
                    if (! $order->IsSubmitted()) {
                        $order->delete();
                    }
                }
            }
        }

        return true;
    }

    /**
     * alias for clear.
     */
    public function reset()
    {
        return $this->clear();
    }

    /**
     * Removes a modifier from the cart
     * It does not actually remove it, but it just
     * sets it as "removed", to avoid that it is being
     * added again.
     *
     * returns null if the current user does not allow order manipulation or saving (e.g. session disabled)
     *
     * @param OrderModifier $modifier | int
     *
     * @return null|bool
     */
    public function removeModifier($modifier)
    {
        if ($this->allowWrites()) {
            $modifier = is_numeric($modifier) ? OrderModifier::get_by_id($modifier) : $modifier;
            if (! $modifier) {
                $this->addMessage(_t('Order.MODIFIERNOTFOUND', 'Modifier could not be found.'), 'bad');

                return false;
            }

            if (! $modifier->CanBeRemoved()) {
                $this->addMessage(_t('Order.MODIFIERCANNOTBEREMOVED', 'Modifier can not be removed.'), 'bad');

                return false;
            }

            $modifier->HasBeenRemoved = 1;
            $modifier->onBeforeRemove();
            $modifier->write();
            $modifier->onAfterRemove();
            $this->addMessage(_t('Order.MODIFIERREMOVED', 'Removed.'));

            return true;
        }

        $this->addMessage(_t('Order.CAN_NOT_BE_WRITTEN', 'Cart can not be updated.'), 'bad');

        return false;
    }

    /**
     * Removes a modifier from the cart.
     *
     * returns null if the current user does not allow order manipulation or saving (e.g. session disabled)
     *
     * @param OrderModifier $modifier | int
     *
     * @return bool
     */
    public function addModifier($modifier)
    {
        if ($this->allowWrites()) {
            if (is_numeric($modifier)) {
                $modifier = OrderModifier::get_by_id($modifier);
            } elseif (! is_a($modifier, EcommerceConfigClassNames::getName(OrderModifier::class))) {
                user_error('Bad parameter provided to ShoppingCart::addModifier', E_USER_WARNING);
            }

            if (! $modifier) {
                $this->addMessage(_t('Order.MODIFIERNOTFOUND', 'Modifier could not be found.'), 'bad');

                return false;
            }

            $modifier->HasBeenRemoved = 0;
            $modifier->write();
            $this->addMessage(_t('Order.MODIFIERREMOVED', 'Added.'));

            return true;
        }

        $this->addMessage(_t('Order.CAN_NOT_BE_WRITTEN', 'Cart can not be updated.'), 'bad');

        return false;
    }

    /**
     * Sets an order as the current order.
     *
     * @param int|Order $order
     *
     * @return bool
     */
    public function loadOrder($order)
    {
        if ($this->allowWrites()) {
            //TODO: how to handle existing order
            //TODO: permission check - does this belong to another member? ...or should permission be assumed already?
            if (is_numeric($order)) {
                $this->order = Order::get_order_cached((int) $order);
            } elseif (is_a($order, EcommerceConfigClassNames::getName(Order::class))) {
                $this->order = $order;
            } else {
                user_error('Bad order provided as parameter to ShoppingCart::loadOrder()');
            }

            if ($this->order) {
                //first can view and then, if can view, set as session...
                if ($this->order->canView()) {
                    $this->order->init(true);
                    $sessionVariableName = $this->sessionVariableName('OrderID');
                    //we set session ID after can view check ...
                    Controller::curr()->getRequest()->getSession()->set($sessionVariableName, $this->order->ID);
                    $this->addMessage(_t('Order.LOADEDEXISTING', 'Order loaded.'));

                    return true;
                }

                $this->addMessage(_t('Order.NOPERMISSION', 'You do not have permission to view this order.'), 'bad');

                return false;
            }

            $this->addMessage(_t('Order.NOORDER', 'Order can not be found.'), 'bad');

            return false;
        }

        $this->addMessage(_t('Order.CAN_NOT_BE_WRITTEN', 'Cart can not be updated.'), 'bad');

        return false;
    }

    /**
     * NOTE: tried to copy part to the Order Class - but that was not much of a go-er.
     *
     * returns null if the current user does not allow order manipulation or saving (e.g. session disabled)
     *
     * @param int|Order $oldOrder
     *
     * @return null|false|Order
     */
    public function copyOrder($oldOrder)
    {
        if ($this->allowWrites()) {
            if (is_numeric($oldOrder)) {
                $oldOrder = Order::get_order_cached((int) $oldOrder);
            } elseif (is_a($oldOrder, EcommerceConfigClassNames::getName(Order::class))) {
                //$oldOrder = $oldOrder;
            } else {
                user_error('Bad order provided as parameter to ShoppingCart::loadOrder()');
            }

            if ($oldOrder) {
                if ($oldOrder->canView() && $oldOrder->IsSubmitted()) {
                    $this->addMessage(_t('Order.ORDERCOPIED', 'Order has been copied.'));

                    $newOrder = Order::create();
                    $newOrder = $this->CopyOrderOnly($oldOrder, $newOrder);

                    $items = OrderItem::get()->filter([
                        'OrderID' => $oldOrder->ID,
                    ]);

                    if (count($items)) {
                        $newOrder = $this->CopyBuyablesToNewOrder($newOrder, $items);
                    }

                    $this->loadOrder($newOrder);

                    return $newOrder;
                }

                $this->addMessage(_t('Order.NOPERMISSION', 'You do not have permission to view this order.'), 'bad');

                return false;
            }

            $this->addMessage(_t('Order.NOORDER', 'Order can not be found.'), 'bad');
        } else {
            $this->addMessage(_t('Order.CAN_NOT_BE_WRITTEN', 'Cart can not be updated.'), 'bad');
        }

        return false;
    }

    /**
     * @param Order $oldOrder
     * @param Order $newOrder
     *
     * @return Order (the new order)
     */
    public function CopyOrderOnly($oldOrder, $newOrder)
    {
        //copying fields.
        $newOrder->UseShippingAddress = $oldOrder->UseShippingAddress;
        //important to set it this way...
        $newOrder->setCurrency($oldOrder->CurrencyUsed());
        $newOrder->MemberID = $oldOrder->MemberID;
        //load the order
        $newOrder->write();
        $newOrder->CreateOrReturnExistingAddress(BillingAddress::class);
        $newOrder->CreateOrReturnExistingAddress(ShippingAddress::class);
        $newOrder->write();

        return $newOrder;
    }

    /**
     * Add buyables into new Order.
     *
     * @param Order                 $newOrder
     * @param DataList   $items
     * @param array                 $parameters
     *
     * @return Order
     */
    public function CopyBuyablesToNewOrder($newOrder, $items, $parameters = [])
    {
        foreach ($items as $item) {
            $buyable = $item->getBuyableCached(true);

            if ($buyable && $buyable->canPurchase()) {
                $orderItem = $this->prepareOrderItem($buyable, $parameters, false);
                $quantity = $this->prepareQuantity($buyable, $item->Quantity);

                if ($orderItem && $quantity) {
                    $orderItem->Quantity = $quantity;
                    $orderItem->write();

                    $newOrder->Attributes()->add($orderItem);
                }
            }

            $newOrder->write();
        }

        return $newOrder;
    }

    /**
     * sets country in order so that modifiers can be recalculated, etc...
     *
     * @param string $countryCode
     *
     * @return bool
     */
    public function setCountry($countryCode)
    {
        if ($this->allowWrites()) {
            if (EcommerceCountry::code_allowed($countryCode)) {
                $this->currentOrder()->SetCountryFields($countryCode);
                $this->addMessage(_t('Order.UPDATEDCOUNTRY', 'Updated country.'));

                return true;
            }

            $this->addMessage(_t('Order.NOTUPDATEDCOUNTRY', 'Could not update country.'), 'bad');
        } else {
            $this->addMessage(_t('Order.CAN_NOT_BE_WRITTEN', 'Cart can not be updated.'), 'bad');
        }

        return false;
    }

    /**
     * sets region in order so that modifiers can be recalculated, etc...
     *
     * @param int|string $regionID you can use the ID or the code
     *
     * @return bool
     */
    public function setRegion($regionID): bool
    {
        if (EcommerceRegion::regionid_allowed($regionID)) {
            $this->currentOrder()->SetRegionFields($regionID);
            $this->addMessage(_t('ShoppingCart.REGIONUPDATED', 'Region updated.'));

            return true;
        }

        $this->addMessage(_t('ORDER.NOTUPDATEDREGION', 'Could not update region.'), 'bad');

        return false;
    }

    /**
     * sets the display currency for the cart.
     *
     * @param string $currencyCode
     *
     * @return bool
     */
    public function setCurrency($currencyCode): bool
    {
        $currency = EcommerceCurrency::get_one_from_code($currencyCode);
        if ($currency) {
            if ($this->currentOrder()->MemberID) {
                $member = $this->currentOrder()->Member();
                if ($member && $member->exists()) {
                    $member->SetPreferredCurrency($currency);
                }
            }

            $this->currentOrder()->UpdateCurrency($currency);
            $msg = _t('Order.CURRENCYUPDATED', 'Currency updated.');
            $this->addMessage($msg);

            return true;
        }

        $msg = _t('Order.CURRENCYCOULDNOTBEUPDATED', 'Currency could not be updated.');
        $this->addMessage($msg, 'bad');

        return false;
    }

    /**
     * Produces a debug of the shopping cart.
     */
    public function debug()
    {
        if (Director::isDev() || Permission::check('ADMIN')) {
            print_r($this->currentOrder());
            echo '<hr /><hr /><hr /><hr /><hr /><hr /><h1>Country</h1>';
            echo 'GEOIP Country: ' . EcommerceCountry::get_country_from_ip() . '<br />';
            echo 'Calculated Country: ' . EcommerceCountry::get_country() . '<br />';
            echo '<blockquote><blockquote><blockquote><blockquote>';
            echo '<hr /><hr /><hr /><hr /><hr /><hr /><h1>Items</h1>';
            $items = $this->currentOrder()->Items();
            echo $items->sql();
            echo '<hr />';
            if ($items->exists()) {
                foreach ($items as $item) {
                    print_r($item);
                }
            } else {
                echo '<p>there are no items for this order</p>';
            }

            echo '<hr /><hr /><hr /><hr /><hr /><hr /><h1>Modifiers</h1>';
            $modifiers = $this->currentOrder()->Modifiers();
            if ($modifiers->exists()) {
                foreach ($modifiers as $modifier) {
                    print_r($modifier);
                }
            } else {
                echo '<p>there are no modifiers for this order</p>';
            }

            echo '<hr /><hr /><hr /><hr /><hr /><hr /><h1>Addresses</h1>';
            $billingAddress = $this->currentOrder()->BillingAddress();
            if ($billingAddress && $billingAddress->exists()) {
                print_r($billingAddress);
            } else {
                echo '<p>there is no billing address for this order</p>';
            }

            $shippingAddress = $this->currentOrder()->ShippingAddress();
            if ($shippingAddress && $shippingAddress->exists()) {
                print_r($shippingAddress);
            } else {
                echo '<p>there is no shipping address for this order</p>';
            }

            $currencyUsed = $this->currentOrder()->CurrencyUsed();
            if ($currencyUsed && $currencyUsed->exists()) {
                echo '<hr /><hr /><hr /><hr /><hr /><hr /><h1>Currency</h1>';
                print_r($currencyUsed);
            }

            $cancelledBy = $this->currentOrder()->CancelledBy();
            if ($cancelledBy && $cancelledBy->exists()) {
                echo '<hr /><hr /><hr /><hr /><hr /><hr /><h1>Cancelled By</h1>';
                print_r($cancelledBy);
            }

            $logs = $this->currentOrder()->OrderStatusLogs();
            if ($logs->exists()) {
                echo '<hr /><hr /><hr /><hr /><hr /><hr /><h1>Logs</h1>';
                foreach ($logs as $log) {
                    print_r($log);
                }
            }

            $payments = $this->currentOrder()->Payments();
            if ($payments->exists()) {
                echo '<hr /><hr /><hr /><hr /><hr /><hr /><h1>Payments</h1>';
                foreach ($payments as $payment) {
                    print_r($payment);
                }
            }

            $emails = $this->currentOrder()->Emails();
            if ($emails->exists()) {
                echo '<hr /><hr /><hr /><hr /><hr /><hr /><h1>Emails</h1>';
                foreach ($emails as $email) {
                    print_r($email);
                }
            }

            echo '</blockquote></blockquote></blockquote></blockquote>';
        } else {
            echo 'Please log in as admin first';
        }
    }

    /**
     * Stores a message that can later be returned via ajax or to $form->sessionMessage();.
     *
     * $message the message, which could be a notification of successful action, or reason for failure
     *
     * @param string $status  - use good, bad, warning
     * @param mixed  $message
     */
    public function addMessage($message, $status = 'good')
    {
        //clean status for the lazy programmer
        //TODO: remove the awkward replace
        $status = strtolower((string) $status);
        str_replace(['success', 'failure'], ['good', 'bad'], $status);
        $statusOptions = ['good', 'bad', 'warning'];
        if (! in_array($status, $statusOptions, true)) {
            user_error('Message status should be one of the following: ' . implode(',', $statusOptions), E_USER_NOTICE);
        }

        $this->messages[] = ['Message' => $message, 'Type' => $status];
    }

    public function addReferral($params): int
    {
        if (count($params)) {
            $order = $this->currentOrder();
            if ($order && $order->exists()) {
                if (Referral::add_referral($order, $params)) {
                    return $order->ID;
                }
            }
        }
        return -2;
    }

    // UI MESSAGE HANDLING

    /**
     * Retrieves all good, bad, and ugly messages that have been produced during the current request.
     *
     * @return array of messages
     */
    public function getMessages()
    {
        $sessionVariableName = $this->sessionVariableName('Messages');
        $messages = [];
        $session = $this->getSession();
        if ($session) {
            //get old messages
            $messages = unserialize((string) $session->get($sessionVariableName));
            //clear old messages
            $session->clear($sessionVariableName);
        }

        //set to form????
        if ($messages && count($messages)) {
            $this->messages = array_merge($messages, $this->messages);
        }

        return $this->messages;
    }

    protected function getSession(): ?Session
    {
        $curr = Controller::curr();
        if ($curr) {
            $request = $curr->getRequest();
            if ($request) {
                return $request->getSession();
            }
        }
        return null;
    }

    /**
     * This method is used to return data after an ajax call was made.
     * When a asynchronious request is made to the shopping cart (ajax),
     * then you will first action the request and then use this function
     * to return some values.
     *
     * It can also be used without ajax, in wich case it will redirects back
     * to the last page.
     *
     * Note that you can set the ajax response class in the configuration file.
     *
     * @param string $message
     * @param string $status
     * @returns String (JSON)
     */
    public function setMessageAndReturn($message = '', $status = '', Form $form = null)
    {
        if ($message && $status) {
            $this->addMessage($message, $status);
        }

        // recalculate... this is often a change so well worth it.
        $order = $this->currentOrder();
        if ($order) {
            //todo- why would there not be an order?
            $order->calculateOrderAttributes($recalculate = true);
        }

        //TODO: handle passing back multiple messages
        if (Director::is_ajax()) {
            $responseClass = EcommerceConfig::get(ShoppingCart::class, 'response_class');
            $obj = new $responseClass();

            return $obj->ReturnCartData($this->getMessages());
        }


        //TODO: handle passing a message back to a form->sessionMessage
        $this->StoreMessagesInSession();
        if ($form) {
            // now we can (re)calculate the order
            $form->sessionMessage($message, $status);
            // let the form controller do the redirectback or whatever else is needed.
        } elseif (empty($_REQUEST['BackURL']) && Controller::has_curr()) {
            Controller::curr()->redirectBack();
        } else {
            Controller::curr()->redirect(urldecode((string) $_REQUEST['BackURL']));
        }
    }

    /**
     * can the current user use sessions and therefore write to cart???
     * the method also returns true if an order has explicitely been set.
     *
     * @return bool
     */
    protected function allowWrites()
    {
        if (null === self::$_allow_writes_cache) {
            if ($this->order) {
                self::$_allow_writes_cache = true;
            } elseif (PHP_SAPI === 'cli') {
                self::$_allow_writes_cache = false;
            } else {
                $noSession = '' === session_id();
                self::$_allow_writes_cache = ! $noSession;
            }
        }

        return self::$_allow_writes_cache;
    }

    // HELPER FUNCTIONS

    /**
     * Gets an existing order item based on buyable and passed parameters.
     *
     * @return null|OrderItem
     */
    protected function getExistingItem(BuyableModel $buyable, array $parameters = [])
    {
        $filterString = $this->parametersToSQL($parameters);
        $order = $this->currentOrder();
        if ($order) {
            $orderID = $order->ID;

            return DataObject::get_one(
                OrderItem::class,
                '"BuyableClassName" = ' . Convert::raw2sql($buyable->ClassName, true) . ' AND "BuyableID" = ' . $buyable->ID . ' AND "OrderID" = ' . $orderID . ' ' . $filterString,
                $cacheDataObjectGetOne = false
            );
        }
    }

    /**
     * Removes parameters that aren't in the default array, merges with default parameters, and converts raw2SQL.
     *
     * @return array cleaned
     */
    protected function cleanParameters(array $params = [])
    {
        $defaultParamFilters = EcommerceConfig::get(ShoppingCart::class, 'default_param_filters');
        $newarray = array_merge([], $defaultParamFilters);
        //clone array
        if ([] === $newarray) {
            return [];
            //no use for this if there are not parameters defined
        }

        foreach (array_keys($newarray) as $field) {
            if (isset($params[$field])) {
                $newarray[$field] = Convert::raw2sql($params[$field]);
            }
        }

        return $newarray;
    }

    /**
     * Converts parameter array to SQL query filter.
     */
    protected function parametersToSQL(array $parameters = [])
    {
        $defaultParamFilters = EcommerceConfig::get(ShoppingCart::class, 'default_param_filters');
        if (! count($defaultParamFilters)) {
            return '';
            //no use for this if there are not parameters defined
        }

        $cleanedparams = $this->cleanParameters($parameters);
        $outputArray = [];
        foreach ($cleanedparams as $field => $value) {
            $outputarray[$field] = '"' . $field . '" = ' . $value;
        }

        if ([] !== $outputArray) {
            return implode(' AND ', $outputArray);
        }

        return '';
    }

    /**
     *Saves current messages in session for retrieving them later.
     */
    protected function StoreMessagesInSession()
    {
        $sessionVariableName = $this->sessionVariableName('Messages');
        Controller::curr()->getRequest()->getSession()->set($sessionVariableName, serialize($this->messages));
    }

    /**
     * Return the name of the session variable that should be used.
     *
     * @param string $name
     *
     * @return string
     */
    protected function sessionVariableName($name = '')
    {
        if (! in_array($name, self::$session_variable_names, true)) {
            user_error("Tried to set session variable {$name}, that is not in use", E_USER_NOTICE);
        }

        $sessionCode = EcommerceConfig::get(ShoppingCart::class, 'session_code');

        return $sessionCode . '_' . $name;
    }
}
