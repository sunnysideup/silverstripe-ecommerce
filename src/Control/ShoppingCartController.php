<?php

namespace Sunnysideup\Ecommerce\Control;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Interfaces\BuyableModel;
use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\OrderItem;
use Sunnysideup\Ecommerce\Pages\AccountPage;
use Sunnysideup\Ecommerce\Pages\CartPage;
use Sunnysideup\Ecommerce\Pages\CheckoutPage;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * ShoppingCartController.
 *
 * Handles the modification of a shopping cart via http requests.
 * Provides links for making these modifications.
 *
 */
class ShoppingCartController extends Controller
{
    /**
     * We need to only use the Security ID on a few
     * actions, these are listed here.
     *
     * @var array
     */
    protected $methodsRequiringSecurityID = [
        // 'additem', // actions could be cached
        // 'removeitem', // actions could be cached
        // 'removeallitem', // actions could be cached
        // 'removeallitemandedit', // actions could be cached
        'removemodifier',
        'addmodifier',
        'copyorder',
        'deleteorder',
        'save',
    ];

    /**
     * @var ShoppingCart
     */
    protected $cart;

    /**
     * @var string
     */
    private static $url_segment = 'shoppingcart';

    /**
     * Default URL handlers - (Action)/(ID)/(OtherID).
     */
    private static $url_handlers = [
        '$Action//$ID/$OtherID/$Version' => 'handleAction',
    ];

    private static $allowed_actions = [
        'json',
        'index',
        'additem',
        'addreferral',
        'removeitem',
        'removeallitem',
        'removeallitemandedit',
        'removemodifier',
        'addmodifier',
        'setcountry',
        'setregion',
        'setcurrency',
        'removefromsale',
        'setquantityitem',
        'clear',
        'clearandlogout',
        'save',
        'deleteorder',
        'numberofitemsincart',
        'showcart',
        'loadorder',
        'copyorder',
        'removeaddress',
        'submittedbuyable',
        'placeorderformember',
        'loginas', // no need to set to  => 'ADMIN',
        'debug', // no need to set to  => 'ADMIN',
        'ajaxtest', // no need to set to  => 'ADMIN',
    ];

    public function index()
    {
        if ($this->cart) {
            $this->redirect($this->cart->Link());

            return;
        }
        user_error(_t('Order.NOCARTINITIALISED', 'no cart initialised'), E_USER_NOTICE);

        return $this->goToErrorPage();
        user_error(_t('Order.NOCARTINITIALISED', 'no 404 page available'), E_USER_ERROR);
    }

    // CONTROLLER LINKS

    /**
     * @param string $action
     *
     * @return string (Link)
     */
    public function Link($action = null)
    {
        return self::create_link($action);
    }

    /**
     * @param int    $buyableID
     * @param string $classNameForBuyable
     *
     * @return string
     */
    public static function add_item_link($buyableID, $classNameForBuyable = Product::class, array $parameters = [])
    {
        $classNameForBuyable = ClassHelpers::sanitise_class_name($classNameForBuyable);

        return self::create_link('additem/' . $buyableID . '/' . $classNameForBuyable . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @param int    $buyableID
     * @param string $classNameForBuyable
     *
     * @return string
     */
    public static function remove_item_link($buyableID, $classNameForBuyable = Product::class, array $parameters = [])
    {
        $classNameForBuyable = ClassHelpers::sanitise_class_name($classNameForBuyable);

        return self::create_link('removeitem/' . $buyableID . '/' . $classNameForBuyable . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @param int    $buyableID
     * @param string $classNameForBuyable
     *
     * @return string
     */
    public static function remove_all_item_link($buyableID, $classNameForBuyable = Product::class, array $parameters = [])
    {
        $classNameForBuyable = ClassHelpers::sanitise_class_name($classNameForBuyable);

        return self::create_link('removeallitem/' . $buyableID . '/' . $classNameForBuyable . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @param int    $buyableID
     * @param string $classNameForBuyable
     *
     * @return string
     */
    public static function remove_all_item_and_edit_link($buyableID, $classNameForBuyable = Product::class, array $parameters = [])
    {
        $classNameForBuyable = ClassHelpers::sanitise_class_name($classNameForBuyable);

        return self::create_link('removeallitemandedit/' . $buyableID . '/' . $classNameForBuyable . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @param int    $buyableID
     * @param string $classNameForBuyable
     *
     * @return string
     */
    public static function set_quantity_item_link($buyableID, $classNameForBuyable = Product::class, array $parameters = [])
    {
        $classNameForBuyable = ClassHelpers::sanitise_class_name($classNameForBuyable);

        return self::create_link('setquantityitem/' . $buyableID . '/' . $classNameForBuyable . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @param int $modifierID
     *
     * @return string
     */
    public static function remove_modifier_link($modifierID, array $parameters = [])
    {
        return self::create_link('removemodifier/' . $modifierID . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @param int $modifierID
     *
     * @return string
     */
    public static function add_modifier_link($modifierID, array $parameters = [])
    {
        return self::create_link('addmodifier/' . $modifierID . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @param int    $addressID
     * @param string $addressClassName
     *
     * @return string
     */
    public static function remove_address_link($addressID, $addressClassName, array $parameters = [])
    {
        return self::create_link('removeaddress/' . $addressID . '/' . $addressClassName . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    public static function clear_cart_link($parameters = [])
    {
        return self::create_link('clear/' . self::params_to_get_string($parameters));
    }

    /**
     * @return string
     */
    public static function save_cart_link(array $parameters = [])
    {
        return self::create_link('save/' . self::params_to_get_string($parameters));
    }

    /**
     * @return string
     */
    public static function clear_cart_and_logout_link(array $parameters = [])
    {
        return self::create_link('clearandlogout/' . self::params_to_get_string($parameters));
    }

    /**
     * @param mixed $orderID
     *
     * @return string
     */
    public static function delete_order_link($orderID, array $parameters = [])
    {
        return self::create_link('deleteorder/' . $orderID . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @param mixed $parameters
     */
    public static function copy_order_link(int $orderID, $parameters = []): string
    {
        $order = Order::get_order_cached($orderID);
        if ($order && $order->IsSubmitted()) {
            return self::create_link('copyorder/' . $orderID . '/' . self::params_to_get_string($parameters));
        }

        return '';
    }

    /**
     * returns a link that allows you to set a currency...
     * dont be fooled by the set_ part...
     */
    public static function set_currency_link(string $code, array $parameters = []): string
    {
        return self::create_link('setcurrency/' . $code . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @param int    $id
     * @param string $className
     *
     * @return string
     */
    public static function remove_from_sale_link($id, $className)
    {
        $className = ClassHelpers::sanitise_class_name($className);

        return self::create_link('removefromsale/' . $className . '/' . $id . '/');
    }

    /**
     * return json for cart... no further actions.
     *
     * @return string
     */
    public function json(HTTPRequest $request)
    {
        return $this->cart->setMessageAndReturn();
    }

    /**
     * Adds item to cart via controller action; one by default.
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page
     */
    public function additem(HTTPRequest $request)
    {
        $buyable = $this->buyable();
        if ($buyable) {
            $this->cart->addBuyable($buyable, $this->quantity(), $this->parameters(true));

            return $this->cart->setMessageAndReturn();
        }

        return $this->goToErrorPage();
    }

    /**
     * Adds item to cart via controller action; one by default.
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page
     */
    public function addreferral(HTTPRequest $request)
    {
        if ($this->cart) {
            return $this->cart->addReferral($this->parameters(true));
        }
        return -1;
    }

    /**
     * Sets the exact passed quantity.
     * Note: If no ?quantity=x is specified in URL, then quantity will be set to 1.
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page
     */
    public function setquantityitem(HTTPRequest $request)
    {
        $buyable = $this->buyable();
        if ($buyable) {
            $this->cart->setQuantity($buyable, $this->quantity(), $this->parameters(true));

            return $this->cart->setMessageAndReturn();
        }

        return $this->goToErrorPage();
    }

    /**
     * Removes item from cart via controller action; one by default.
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page
     */
    public function removeitem(HTTPRequest $request)
    {
        $buyable = $this->buyable();
        if ($buyable) {
            $this->cart->decrementBuyable($buyable, $this->quantity(), $this->parameters(true));

            return $this->cart->setMessageAndReturn();
        }

        return $this->goToErrorPage();
    }

    /**
     * Removes all of a specific item.
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page
     */
    public function removeallitem(HTTPRequest $request)
    {
        $buyable = $this->buyable();
        if ($buyable) {
            $this->cart->deleteBuyable($buyable, $this->parameters(true));
            //added this because cart was not updating correctly
            // $order = $this->cart->CurrentOrder();
            // $order->calculateOrderAttributes($recalculate = true);

            return $this->cart->setMessageAndReturn();
        }

        return $this->goToErrorPage();
    }

    /**
     * Removes all of a specific item AND return back.
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page
     */
    public function removeallitemandedit(HTTPRequest $request)
    {
        /** @var Product|BuyableModel $buyable */
        $buyable = $this->buyable();
        if ($buyable) {
            $link = $buyable->Link();
            $this->cart->deleteBuyable($buyable, $this->parameters(true));
            $this->redirect($link);
        } else {
            $this->redirectBack();
        }
    }

    /**
     * Removes a specified modifier from the cart;.
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page
     */
    public function removemodifier(HTTPRequest $request)
    {
        $modifierID = (int) $request->param('ID');
        $this->cart->removeModifier($modifierID);
        // $order = $this->cart->CurrentOrder();
        // $order->calculateOrderAttributes($recalculate = true);

        return $this->cart->setMessageAndReturn();
    }

    /**
     * Adds a specified modifier to the cart;.
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page
     */
    public function addmodifier(HTTPRequest $request)
    {
        $modifierID = (int) $request->param('ID');
        $this->cart->addModifier($modifierID);
        // $order = $this->cart->CurrentOrder();
        // $order->calculateOrderAttributes($recalculate = true);

        return $this->cart->setMessageAndReturn();
    }

    /**
     * sets the country.
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page
     */
    public function setcountry(HTTPRequest $request)
    {
        $countryCode = Convert::raw2sql($request->param('ID'));
        //set_country will check if the country code is actually allowed....
        $this->cart->setCountry($countryCode);

        return $this->cart->setMessageAndReturn();
    }

    /**
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page
     */
    public function setregion(HTTPRequest $request)
    {
        $regionID = (int) $request->param('ID');
        $this->cart->setRegion($regionID);

        return $this->cart->setMessageAndReturn();
    }

    /**
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page
     */
    public function setcurrency(HTTPRequest $request)
    {
        $currencyCode = Convert::raw2sql($request->param('ID'));
        $this->cart->setCurrency($currencyCode);

        return $this->cart->setMessageAndReturn();
    }

    /**
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page
     */
    public function removefromsale(HTTPRequest $request)
    {
        if (EcommerceRole::current_member_is_shop_assistant()) {
            $className = Convert::raw2sql($request->param('ID'));
            $id = (int) $request->param('OtherID');
            if (class_exists($className)) {
                $obj = $className::get_by_id($id);
                $obj->AllowPurchase = 0;
                if ($obj instanceof SiteTree) {
                    $obj->writeToStage(Versioned::DRAFT);
                    $obj->publishRecursive();
                } else {
                    $obj->write();
                }
            }

            return $this->cart->setMessageAndReturn();
        }

        return Security::permissionFailure($this);
    }

    /**
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page
     */
    public function save(HTTPRequest $request)
    {
        $this->cart->save();

        return $this->cart->setMessageAndReturn();
    }

    /**
     * @return \SilverStripe\Control\HTTPResponse|string
     */
    public function clear(HTTPRequest $request)
    {
        $this->cart->clear();
        $this->redirect(Director::baseURL());

        return [];
    }

    /**
     * @return \SilverStripe\Control\HTTPResponse|string
     */
    public function clearandlogout(HTTPRequest $request)
    {
        $this->cart->clear();
        $member = Security::getCurrentUser();
        if ($member) {
            $this->redirect(Security::logout_url());
        } else {
            $this->redirect(Director::baseURL());
        }

        return [];
    }

    /**
     * @return \SilverStripe\Control\HTTPResponse|string
     */
    public function deleteorder(HTTPRequest $request)
    {
        $orderID = (int) $request->param('ID');
        $order = Order::get_by_id_if_can_view($orderID);
        if ($order) {
            if ($order->canDelete()) {
                $order->delete();
            }
        }

        return $this->redirectBack();
    }

    public function copyorder(HTTPRequest $request)
    {
        $orderID = (int) $request->param('ID');
        $order = Order::get_by_id_if_can_view($orderID);
        if ($order) {
            $this->cart->copyOrder($order);
        }
        $link = CheckoutPage::find_link();

        return $this->redirect($link);
    }

    /**
     * return number of items in cart.
     */
    public function numberofitemsincart(HTTPRequest $request): float
    {
        $order = $this->cart->CurrentOrder();
        if ($order) {
            return (int) $order->TotalItems($recalculate = false);
        }

        return 0;
    }

    /**
     * return cart for ajax call.
     *
     * @return \SilverStripe\ORM\FieldType\DBHTMLText
     */
    public function showcart(HTTPRequest $request)
    {
        return $this->customise($this->cart->CurrentOrder())->renderWith('Sunnysideup\Ecommerce\AjaxCart');
    }

    /**
     * loads an order.
     *
     * @return \SilverStripe\Control\HTTPResponse|string
     */
    public function loadorder(HTTPRequest $request)
    {
        $this->cart->loadOrder((int) $request->param('ID'));
        $cartPageLink = CartPage::find_link();
        if ($cartPageLink) {
            return $this->redirect($cartPageLink);
        }

        return $this->redirect(Director::baseURL());
    }

    /**
     * remove address from list of available addresses in checkout.
     *
     * @return \SilverStripe\Control\HTTPResponse|string
     * @TODO: add non-ajax version of this request.
     */
    public function removeaddress(HTTPRequest $request)
    {
        $id = (int) $request->param('ID');

        $className = Convert::raw2sql($request->param('OtherID'));

        if (class_exists($className)) {
            $address = $className::get_by_id($id);
            if ($address && $address->canView()) {
                $member = Security::getCurrentUser();
                if ($member) {
                    $address->MakeObsolete($member);
                    if ($request->isAjax()) {
                        return _t('Order.ADDRESSREMOVED', 'Address removed.');
                    }
                    $this->redirectBack();
                }
            }
        }
        if ($request->isAjax()) {
            return _t('Order.ADDRESSNOTREMOVED', 'Address could not be removed.');
        }
        $this->redirectBack();

        return [];
    }

    /**
     * allows us to view out-dated buyables that have been deleted
     * where only old versions exist.
     * this method should redirect.
     *
     * @return \SilverStripe\Control\HTTPResponse|string
     */
    public function submittedbuyable(HTTPRequest $request)
    {
        $buyableClassName = Convert::raw2sql($this->getRequest()->param('ID'));
        $buyableClassName = ClassHelpers::unsanitise_class_name($buyableClassName);

        $buyableID = (int) $this->getRequest()->param('OtherID');
        $version = (int) $this->getRequest()->param('Version');
        if ($buyableClassName && $buyableID) {
            if (EcommerceDBConfig::is_buyable($buyableClassName)) {
                $bestBuyable = $buyableClassName::get_by_id($buyableID);
                if (Product::is_product_variation($bestBuyable)) {
                    //todo: make this part of ProductVariation.
                    $link = $bestBuyable->Link('filterforvariations/' . $buyableID . '/?version=' . $version . '/');

                    return $this->redirect($link);
                }
                if ($bestBuyable) {
                    //show singleton with old version
                    $link = $bestBuyable->Link('viewversion/' . $version . '/');

                    return $this->redirect($link);
                }
            }
        }
        $errorPage404 = DataObject::get_one(
            ErrorPage::class,
            ['ErrorCode' => '404']
        );
        if ($errorPage404) {
            return $this->redirect($errorPage404->Link());
        }

        return '404-can-not-submit-buyable';
    }

    /**
     * This can be used by admins to log in as customers
     * to place orders on their behalf...
     *
     * @return \SilverStripe\Control\HTTPResponse|string
     */
    public function placeorderformember(HTTPRequest $request)
    {
        if (EcommerceRole::current_member_is_shop_admin()) {
            $member = Member::get_by_id((int) $request->param('ID'));
            if ($member) {
                $newOrder = Order::create();
                //copying fields.
                $newOrder->MemberID = $member->ID;
                //load the order
                $newOrder->write();
                $this->cart->loadOrder($newOrder);

                return $this->redirect($newOrder->Link());
            }
            user_error('Can not find this member.');
        } else {
            //echo "please <a href=\"Security/login/?BackURL=".urlencode($this->config()->get("url_segment")."/placeorderformember/".$request->param("ID")."/")."\">log in</a> first.";
            return Security::permissionFailure($this);
        }

        return '404-error-placeholder-for-member';
    }

    /**
     * This can be used by admins to log in as customers to place orders on
     * their behalf...
     *
     * @return \SilverStripe\Control\HTTPResponse|string
     */
    public function loginas(HTTPRequest $request)
    {
        if (Permission::check('ADMIN')) {
            $newMember = Member::get_by_id((int) $request->param('ID'));

            if ($newMember) {
                Security::setCurrentUser($newMember);
                Injector::inst()->get(IdentityStore::class)->logIn($newMember);

                $accountPage = DataObject::get_one(AccountPage::class);

                if ($accountPage) {
                    return $this->redirect($accountPage->Link());
                }

                return $this->redirect(Director::baseURL());
            }
        }

        return Security::permissionFailure($this);
    }

    /**
     * Handy debugging action visit.
     * Log in as an administrator and visit mysite/shoppingcart/debug.
     */
    public function debug()
    {
        if (Director::isDev() || EcommerceRole::current_member_is_shop_admin()) {
            return $this->cart->debug();
        }

        return Security::permissionFailure($this);
        //echo "please <a href=\"Security/login/?BackURL=".urlencode($this->config()->get("url_segment")."/debug/")."\">log in</a> first.";
    }

    /**
     * test the ajax response
     * for developers only.
     *
     * @return mixed
     */
    public function ajaxtest(HTTPRequest $request)
    {
        if (Director::isDev() || Permission::check('ADMIN')) {
            header('Content-Type', 'text/plain');
            echo '<pre>';
            $_REQUEST['ajax'] = 1;
            $v = $this->cart->setMessageAndReturn('test only');
            $v = str_replace(',', ",\r\n\t\t", $v);
            $v = str_replace('}', "\r\n\t}", $v);
            $v = str_replace('{', "\t{\r\n\t\t", $v);
            $v = str_replace(']', "\r\n]", $v);
            echo $v;
            echo '</pre>';
        } else {
            echo 'please <a href="Security/login/?BackURL=' . urlencode($this->config()->get('url_segment') . '/ajaxtest/') . '">log in</a> first.';
        }
        if (!$request->isAjax()) {
            user_error('---- make sure to add ?ajax=1 to the URL ---');
        }

        return [];
    }

    protected function init()
    {
        parent::init();
        $action = $this->request->param('Action');
        if ($action && (in_array($action, $this->methodsRequiringSecurityID, true))) {
            $savedSecurityID = $this->getRequest()->getSession()->get('SecurityID');
            if ($savedSecurityID) {
                if (!isset($_GET['SecurityID'])) {
                    $_GET['SecurityID'] = '';
                }
                if ($savedSecurityID) {
                    if ($_GET['SecurityID'] !== $savedSecurityID) {
                        $this->httpError(400, "Security token doesn't match, possible CSRF attack.");
                    }
                    //all OK!
                }
            }
        }
        $this->cart = ShoppingCart::singleton();
    }

    /**
     * returns ABSOLUTE link to the shopping cart controller.
     *
     * @param null|array|string $actionAndOtherLinkVariables
     *
     * @return string
     */
    protected static function create_link($actionAndOtherLinkVariables = null)
    {
        return Controller::join_links(
            Director::baseURL(),
            Config::inst()->get(ShoppingCartController::class, 'url_segment'),
            $actionAndOtherLinkVariables
        );
    }

    /**
     * Helper function used by link functions
     * Creates the appropriate url-encoded string parameters for links from array.
     *
     * Produces string such as: MyParam%3D11%26OtherParam%3D1
     *     ...which decodes to: MyParam=11&OtherParam=1
     *
     * you will need to decode the url with javascript before using it.
     *
     * @todo: check that comment description actually matches what it does
     *
     * @return string (URLSegment)
     */
    protected static function params_to_get_string(array $array)
    {
        $token = SecurityToken::inst();
        if (!isset($array['SecurityID'])) {
            $array['SecurityID'] = $token->getValue();
        }

        return '?' . http_build_query($array);
    }

    /**
     * Gets a buyable object based on URL actions.
     *
     * @return null|BuyableModel - returns buyable
     */
    protected function buyable()
    {
        $buyableClassName = Convert::raw2sql($this->getRequest()->param('OtherID'));
        $buyableClassName = ClassHelpers::unsanitise_class_name($buyableClassName);

        $buyableID = (int) $this->getRequest()->param('ID');
        if ($buyableClassName && $buyableID) {
            if (EcommerceDBConfig::is_buyable($buyableClassName)) {
                $obj = $buyableClassName::get_by_id((int) $buyableID);
                if ($obj) {
                    if ($obj->ClassName === $buyableClassName) {
                        return $obj;
                    }
                }
            } elseif (strpos($buyableClassName, OrderItem::class)) {
                user_error('ClassName in URL should be buyable and not an orderitem', E_USER_NOTICE);
            }
        }

        return null;
    }

    /**
     * Gets the requested quantity.
     *
     * @return float
     */
    protected function quantity()
    {
        $quantity = $this->getRequest()->getVar('quantity');
        if (is_numeric($quantity)) {
            return $quantity;
        }

        return 1;
    }

    /**
     * Gets the request parameters.
     *
     * @param string $getpost - choose between obtaining the chosen parameters from GET or POST
     *
     * @return array
     */
    protected function parameters(?bool $getVars = true)
    {
        // TODO: postvars do not seem to work!!!
        return $getVars ? $this->getRequest()->getVars() : $this->getRequest()->postVars();
    }

    protected function goToErrorPage()
    {
        $errorPage404 = DataObject::get_one(
            ErrorPage::class,
            ['ErrorCode' => '404']
        );
        if ($errorPage404) {
            return $this->redirect($errorPage404->Link());
        }

        return $this->redirect('page-not-found');
    }
}
