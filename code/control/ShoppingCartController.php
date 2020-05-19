<?php


/**
 * ShoppingCartController.
 *
 * Handles the modification of a shopping cart via http requests.
 * Provides links for making these modifications.
 *
 *@author: Jeremy Shipman, Nicolaas Francken
 *@package: ecommerce
 *
 *@todo supply links for adding, removing, and clearing cart items
 *@todo link for removing modifier(s)
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
        'additem',
        'removeitem',
        'removeallitem',
        'removeallitemandedit',
        'removemodifier',
        'addmodifier',
        'copyorder',
        'deleteorder',
        'save',
    ];

    /**
     * @var ShoppingCart
     */
    protected $cart = null;

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

    public function init()
    {
        parent::init();
        $action = $this->request->param('Action');
        if (! isset($_GET['cached'])) {
            if ($action && (in_array($action, $this->methodsRequiringSecurityID, true))) {
                $savedSecurityID = Session::get('SecurityID');
                if ($savedSecurityID) {
                    if (! isset($_GET['SecurityID'])) {
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
        }
        $this->cart = ShoppingCart::singleton();
    }

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

    /*******************************************************
    * CONTROLLER LINKS
    *******************************************************/

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
     * @param array  $parameters
     *
     * @return string
     */
    public static function add_item_link($buyableID, $classNameForBuyable = 'Product', array $parameters = [])
    {
        return self::create_link('additem/' . $buyableID . '/' . $classNameForBuyable . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @param int    $buyableID
     * @param string $classNameForBuyable
     * @param array  $parameters
     *
     * @return string
     */
    public static function remove_item_link($buyableID, $classNameForBuyable = 'Product', array $parameters = [])
    {
        return self::create_link('removeitem/' . $buyableID . '/' . $classNameForBuyable . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @param int    $buyableID
     * @param string $classNameForBuyable
     * @param array  $parameters
     *
     * @return string
     */
    public static function remove_all_item_link($buyableID, $classNameForBuyable = 'Product', array $parameters = [])
    {
        return self::create_link('removeallitem/' . $buyableID . '/' . $classNameForBuyable . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @param int    $buyableID
     * @param string $classNameForBuyable
     * @param array  $parameters
     *
     * @return string
     */
    public static function remove_all_item_and_edit_link($buyableID, $classNameForBuyable = 'Product', array $parameters = [])
    {
        return self::create_link('removeallitemandedit/' . $buyableID . '/' . $classNameForBuyable . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @param int    $buyableID
     * @param string $classNameForBuyable
     * @param array  $parameters
     *
     * @return string
     */
    public static function set_quantity_item_link($buyableID, $classNameForBuyable = 'Product', array $parameters = [])
    {
        return self::create_link('setquantityitem/' . $buyableID . '/' . $classNameForBuyable . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @param int   $modifierID
     * @param array $parameters
     *
     * @return string
     */
    public static function remove_modifier_link($modifierID, array $parameters = [])
    {
        return self::create_link('removemodifier/' . $modifierID . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @param int   $modifierID
     * @param array $parameters
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
     * @param array  $parameters
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
     * @param array $parameters
     *
     * @return string
     */
    public static function save_cart_link(array $parameters = [])
    {
        return self::create_link('save/' . self::params_to_get_string($parameters));
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    public static function clear_cart_and_logout_link(array $parameters = [])
    {
        return self::create_link('clearandlogout/' . self::params_to_get_string($parameters));
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    public static function delete_order_link($orderID, array $parameters = [])
    {
        return self::create_link('deleteorder/' . $orderID . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @return string|null
     */
    public static function copy_order_link($orderID, $parameters = [])
    {
        $order = Order::get()->byID($orderID);
        if ($order && $order->IsSubmitted()) {
            return self::create_link('copyorder/' . $orderID . '/' . self::params_to_get_string($parameters));
        }
    }

    /**
     * returns a link that allows you to set a currency...
     * dont be fooled by the set_ part...
     *
     * @param string $code
     *
     * @return string
     */
    public static function set_currency_link($code, array $parameters = [])
    {
        return self::create_link('setcurrency/' . $code . '/' . self::params_to_get_string($parameters));
    }

    /**
     * @param  int    $id
     * @param  string $className
     * @return string
     */
    public static function remove_from_sale_link($id, $className)
    {
        return self::create_link('removefromsale/' . $className . '/' . $id . '/');
    }

    /**
     * return json for cart... no further actions.
     *
     * @param SS_HTTPRequest $request
     *
     * @return JSON
     */
    public function json(SS_HTTPRequest $request)
    {
        return $this->cart->setMessageAndReturn();
    }

    /**
     * Adds item to cart via controller action; one by default.
     *
     * @param HTTPRequest $request
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page.
     */
    public function additem(SS_HTTPRequest $request)
    {
        $buyable = $this->buyable();
        if ($buyable) {
            $this->cart->addBuyable($buyable, $this->quantity(), $this->parameters());
            return $this->cart->setMessageAndReturn();
        }
        return $this->goToErrorPage();
    }

    /**
     * Sets the exact passed quantity.
     * Note: If no ?quantity=x is specified in URL, then quantity will be set to 1.
     *
     * @param HTTPRequest $request
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page.
     */
    public function setquantityitem(SS_HTTPRequest $request)
    {
        $buyable = $this->buyable();
        if ($buyable) {
            $this->cart->setQuantity($buyable, $this->quantity(), $this->parameters());

            return $this->cart->setMessageAndReturn();
        }
        return $this->goToErrorPage();
    }

    /**
     * Removes item from cart via controller action; one by default.
     *
     * @param HTTPRequest $request
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page.
     */
    public function removeitem(SS_HTTPRequest $request)
    {
        $buyable = $this->buyable();
        if ($buyable) {
            $this->cart->decrementBuyable($buyable, $this->quantity(), $this->parameters());

            return $this->cart->setMessageAndReturn();
        }
        return $this->goToErrorPage();
    }

    /**
     * Removes all of a specific item.
     *
     * @param HTTPRequest $request
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page.
     */
    public function removeallitem(SS_HTTPRequest $request)
    {
        $buyable = $this->buyable();
        if ($buyable) {
            $this->cart->deleteBuyable($buyable, $this->parameters());
            //added this because cart was not updating correctly
            $order = $this->cart->CurrentOrder();
            $order->calculateOrderAttributes($force = true);

            return $this->cart->setMessageAndReturn();
        }
        return $this->goToErrorPage();
    }

    /**
     * Removes all of a specific item AND return back.
     *
     * @param HTTPRequest $request
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page.
     */
    public function removeallitemandedit(SS_HTTPRequest $request)
    {
        $buyable = $this->buyable();
        if ($buyable) {
            $link = $buyable->Link();
            $this->cart->deleteBuyable($buyable, $this->parameters());
            $this->redirect($link);
        } else {
            $this->redirectBack();
        }
    }

    /**
     * Removes a specified modifier from the cart;.
     *
     * @param HTTPRequest $request
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page.
     */
    public function removemodifier(SS_HTTPRequest $request)
    {
        $modifierID = intval($request->param('ID'));
        $this->cart->removeModifier($modifierID);
        $order = $this->cart->CurrentOrder();
        $order->calculateOrderAttributes($force = true);

        return $this->cart->setMessageAndReturn();
    }

    /**
     * Adds a specified modifier to the cart;.
     *
     * @param HTTPRequest $request
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page.
     */
    public function addmodifier(SS_HTTPRequest $request)
    {
        $modifierID = intval($request->param('ID'));
        $this->cart->addModifier($modifierID);
        $order = $this->cart->CurrentOrder();
        $order->calculateOrderAttributes($force = true);

        return $this->cart->setMessageAndReturn();
    }

    /**
     * sets the country.
     *
     * @param SS_HTTPRequest $request
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page.
     **/
    public function setcountry(SS_HTTPRequest $request)
    {
        $countryCode = Convert::raw2sql($request->param('ID'));
        //set_country will check if the country code is actually allowed....
        $this->cart->setCountry($countryCode);

        return $this->cart->setMessageAndReturn();
    }

    /**
     * @param SS_HTTPRequest $request
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page.
     **/
    public function setregion(SS_HTTPRequest $request)
    {
        $regionID = intval($request->param('ID'));
        $this->cart->setRegion($regionID);

        return $this->cart->setMessageAndReturn();
    }

    /**
     * @param SS_HTTPRequest $request
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page.
     **/
    public function setcurrency(SS_HTTPRequest $request)
    {
        $currencyCode = Convert::raw2sql($request->param('ID'));
        $this->cart->setCurrency($currencyCode);

        return $this->cart->setMessageAndReturn();
    }

    /**
     * @param SS_HTTPRequest $request
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page.
     **/
    public function removefromsale(SS_HTTPRequest $request)
    {
        if (EcommerceRole::current_member_is_shop_assistant()) {
            $className = Convert::raw2sql($request->param('ID'));
            $id = intval($request->param('OtherID'));
            if (class_exists($className)) {
                $obj = $className::get()->byID($id);
                $obj->AllowPurchase = 0;
                if ($obj instanceof SiteTree) {
                    $obj->writeToStage('Stage');
                    $obj->doPublish();
                } else {
                    $obj->write();
                }
            }

            return $this->cart->setMessageAndReturn();
        }
        return Security::permissionFailure($this);
    }

    /**
     * @param SS_HTTPRequest $request
     *
     * @return mixed - if the request is AJAX, it returns JSON - CartResponse::ReturnCartData();
     *               If it is not AJAX it redirects back to requesting page.
     **/
    public function save(SS_HTTPRequest $request)
    {
        $this->cart->save();

        return $this->cart->setMessageAndReturn();
    }

    /**
     * @param SS_HTTPRequest $request
     *
     * @return REDIRECT
     **/
    public function clear(SS_HTTPRequest $request)
    {
        $this->cart->clear();
        $this->redirect(Director::baseURL());

        return [];
    }

    /**
     * @param SS_HTTPRequest $request
     *
     * @return REDIRECT
     **/
    public function clearandlogout(SS_HTTPRequest $request)
    {
        $this->cart->clear();
        if ($member = Member::currentUser()) {
            $member->logout();
        }
        $this->redirect(Director::baseURL());

        return [];
    }

    /**
     * @param SS_HTTPRequest $request
     *
     * @return REDIRECT
     **/
    public function deleteorder(SS_HTTPRequest $request)
    {
        $orderID = intval($request->param('ID'));
        if ($order = Order::get_by_id_if_can_view($orderID)) {
            if ($order->canDelete()) {
                $order->delete();
            }
        }
        $this->redirectBack();
    }

    public function copyorder($request)
    {
        $orderID = intval($request->param('ID'));
        if ($order = Order::get_by_id_if_can_view($orderID)) {
            $this->cart->copyOrder($order);
        }
        $link = CheckoutPage::find_link();
        return $this->redirect($link);
    }

    /**
     * return number of items in cart.
     *
     * @param SS_HTTPRequest $request
     *
     * @return int
     **/
    public function numberofitemsincart(SS_HTTPRequest $request)
    {
        $order = $this->cart->CurrentOrder();

        return $order->TotalItems($recalculate = true);
    }

    /**
     * return cart for ajax call.
     *
     * @param SS_HTTPRequest $request
     *
     * @return HTML
     */
    public function showcart(SS_HTTPRequest $request)
    {
        return $this->customise($this->cart->CurrentOrder())->renderWith('AjaxCart');
    }

    /**
     * loads an order.
     *
     * @param SS_HTTPRequest $request
     *
     * @return REDIRECT
     */
    public function loadorder(SS_HTTPRequest $request)
    {
        $this->cart->loadOrder(intval($request->param('ID')));
        $cartPageLink = CartPage::find_link();
        if ($cartPageLink) {
            return $this->redirect($cartPageLink);
        }
        return $this->redirect(Director::baseURL());
    }

    /**
     * remove address from list of available addresses in checkout.
     *
     * @param SS_HTTPRequest $request
     *
     * @return string | REDIRECT
     * @TODO: add non-ajax version of this request.
     */
    public function removeaddress(SS_HTTPRequest $request)
    {
        $id = intval($request->param('ID'));
        $className = Convert::raw2sql($request->param('OtherID'));
        if (class_exists($className)) {
            $address = $className::get()->byID($id);
            if ($address && $address->canView()) {
                $member = Member::currentUser();
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
     * @param SS_HTTPRequest $request
     *
     * @return REDIRECT
     */
    public function submittedbuyable(SS_HTTPRequest $request)
    {
        $buyableClassName = Convert::raw2sql($this->getRequest()->param('ID'));
        $buyableID = intval($this->getRequest()->param('OtherID'));
        $version = intval($this->getRequest()->param('Version'));
        if ($buyableClassName && $buyableID) {
            if (EcommerceDBConfig::is_buyable($buyableClassName)) {
                $bestBuyable = $buyableClassName::get()->byID($buyableID);
                if ($bestBuyable instanceof ProductVariation) {
                    $link = $bestBuyable->Link('filterforvariations/' . $buyableID . '/?version=' . $version . '/');
                    $this->redirect($link);

                    return [];
                }
                if ($bestBuyable) {
                    //show singleton with old version
                    $link = $bestBuyable->Link('viewversion/' . $version . '/');
                    $this->redirect($link);

                    return [];
                }
            }
        }
        $errorPage404 = DataObject::get_one(
            'ErrorPage',
            ['ErrorCode' => '404']
        );
        if ($errorPage404) {
            return $this->redirect($errorPage404->Link());
        }

        return;
    }

    /**
     * This can be used by admins to log in as customers
     * to place orders on their behalf...
     *
     * @param SS_HTTPRequest $request
     *
     * @return REDIRECT
     */
    public function placeorderformember(SS_HTTPRequest $request)
    {
        if (EcommerceRole::current_member_is_shop_admin()) {
            $member = Member::get()->byID(intval($request->param('ID')));
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
    }

    /**
     * This can be used by admins to log in as customers
     * to place orders on their behalf...
     *
     * @param SS_HTTPRequest $request
     *
     * @return REDIRECT
     */
    public function loginas(SS_HTTPRequest $request)
    {
        if (Permission::check('ADMIN')) {
            $newMember = Member::get()->byID(intval($request->param('ID')));
            if ($newMember) {
                //$memberToTest->logout();
                $newMember->logIn();
                if ($accountPage = DataObject::get_one('AccountPage')) {
                    return $this->redirect($accountPage->Link());
                }
                return $this->redirect(Director::baseURL());
            }
            user_error('Can not find this member.');
        } else {
            return Security::permissionFailure($this);
        }
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
     * @return output to buffer
     */
    public function ajaxtest(SS_HTTPRequest $request)
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
        if (! $request->isAjax()) {
            die('---- make sure to add ?ajax=1 to the URL ---');
        }
    }

    /**
     * returns ABSOLUTE link to the shopping cart controller.
     * @param array|string|null $actionAndOtherLinkVariables
     * @return string
     */
    protected static function create_link($actionAndOtherLinkVariables = null)
    {
        return Controller::join_links(
            Director::baseURL(),
            Config::inst()->get('ShoppingCartController', 'url_segment'),
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
        if (! isset($array['SecurityID'])) {
            $array['SecurityID'] = $token->getValue();
        }

        return '?' . http_build_query($array);
    }

    /**
     * Gets a buyable object based on URL actions.
     *
     * @return DataObject | Null - returns buyable
     */
    protected function buyable()
    {
        $buyableClassName = Convert::raw2sql($this->getRequest()->param('OtherID'));
        $buyableID = intval($this->getRequest()->param('ID'));
        if ($buyableClassName && $buyableID) {
            if (EcommerceDBConfig::is_buyable($buyableClassName)) {
                $obj = $buyableClassName::get()->byID(intval($buyableID));
                if ($obj) {
                    if ($obj->ClassName === $buyableClassName) {
                        return $obj;
                    }
                }
            } else {
                if (strpos($buyableClassName, 'OrderItem')) {
                    user_error('ClassName in URL should be buyable and not an orderitem', E_USER_NOTICE);
                }
            }
        }

        return;
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
     * @param $getpost - choose between obtaining the chosen parameters from GET or POST
     *
     * @return array
     */
    protected function parameters($getpost = 'GET')
    {
        return $getpost === 'GET' ? $this->getRequest()->getVars() : $_POST;
    }

    protected function goToErrorPage()
    {
        $errorPage404 = DataObject::get_one(
            'ErrorPage',
            ['ErrorCode' => '404']
        );
        if ($errorPage404) {
            return $this->redirect($errorPage404->Link());
        }
        return $this->redirect('page-not-found');
    }
}

