<?php

namespace Sunnysideup\Ecommerce\Pages;

use PageController;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTP;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\View\ArrayData;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\ShopAccountForm;
use Sunnysideup\Ecommerce\Model\Order;

class CartPageController extends PageController
{
    /**
     * This ArraList holds DataObjects with a Link and Title each....
     *
     * @var ArraList
     **/
    protected $actionLinks = null;

    /**
     * to ensure messages and actions links are only worked out once...
     *
     * @var boolean
     **/
    protected $workedOutMessagesAndActions = false;

    /**
     * order currently being shown on this page.
     *
     * @var DataObject
     **/
    protected $currentOrder = null;

    /**
     * show the order even if canView returns false.
     *
     * @var bool
     */
    protected $overrideCanView = false;

    /**
     * @var bool
     */
    protected $showCreateAccountForm = false;

    /**
     * @static array
     * standard SS variable
     * it is important that we list all the options here
     */
    private static $allowed_actions = [
        'saveorder',
        'CreateAccountForm',
        'retrieveorder',
        'loadorder',
        'deleteorder',
        'startneworder',
        'showorder',
        'share',
        'LoginForm',
    ];

    /**
     * Message shown (e.g. no current order, could not find order, order updated, etc...).
     *
     *@var string
     * @todo: check if we need this....!
     **/
    private $message = '';

    public static function set_message($s)
    {
        $sessionCode = EcommerceConfig::get(CartPageController::class, 'session_code');
        Controller::curr()->getRequest()->getSession()->set($sessionCode, $s);
    }

    /***********************
     * Actions
     ***********************




    /**
     * shows an order and loads it if it is not submitted.
     * @todo: do we still need loadorder controller method????
     * @param SS_HTTPRequest $request
     * @return array just so that template shows
     **/
    public function showorder(HTTPRequest $request)
    {
        if (! $this->currentOrder) {
            $this->message = _t('CartPage.ORDERNOTFOUND', 'Order can not be found.');
        } else {
            if (! $this->currentOrder->IsSubmitted()) {
                $shoppingCart = ShoppingCart::current_order();
                if ($shoppingCart->ID !== $this->currentOrder->ID) {
                    if (ShoppingCart::singleton()->loadOrder($this->currentOrder)) {
                        $this->message = _t('CartPage.ORDERHASBEENLOADED', 'Order has been loaded.');
                    } else {
                        $this->message = _t('CartPage.ORDERNOTLOADED', 'Order could not be loaded.');
                    }
                }
            }
        }

        return [];
    }

    /**
     * share an order ...
     * @todo: do we still need loadorder controller method????
     * @param SS_HTTPRequest $request
     * @return array just so that template shows
     **/
    public function share(HTTPRequest $request)
    {
        $codes = Convert::raw2sql($request->param('ID'));
        if (! $request->getVar('ready') && ! $request->getVar('done')) {
            return $this->redirect($this->Link('share/' . $codes) . '?ready=1');
        }
        $titleAppendixArray = [];
        $buyables = explode('-', $codes);
        if (count($buyables)) {
            $sc = ShoppingCart::singleton();
            $order = $sc->currentOrder();
            foreach ($buyables as $buyable) {
                $details = explode(',', $buyable);
                if (count($details) === 3) {
                    $className = $details[0];
                    $className = class_exists($className) ? $className : null;
                    $id = intval($details[1]);
                    $quantity = floatval($details[2]);
                    if ($className && $id && $quantity) {
                        $buyable = $className::get()->byID($id);
                        if ($buyable && $buyable->canPurchase()) {
                            $sc->addBuyable($buyable, $quantity);
                            $sc->setQuantity($buyable, $quantity);
                            if ($request->getVar('done')) {
                                $titleAppendixArray[] = $buyable->getTitle();
                            }
                        }
                    }
                }
            }
            $order->calculateOrderAttributes(false);
            if (! $request->getVar('done')) {
                return $this->redirect($this->Link('share/' . $codes) . '?done=1');
            }
        }
        $this->Title .= ': ' . implode(', ', $titleAppendixArray);
        if (strlen($this->Title) > 255) {
            $this->Title = substr($this->Title, 0, 255) . ' ...';
        }
        return [];
    }

    /**
     * Loads either the "current order""into the shopping cart.
     *
     * TO DO: untested
     * TO DO: what to do with old order
     *
     * @param SS_HTTPRequest $request
     *
     * @return array
     */
    public function loadorder(HTTPRequest $request)
    {
        self::set_message(_t('CartPage.ORDERLOADED', 'Order has been loaded.'));
        ShoppingCart::singleton()->loadOrder($this->currentOrder->ID);
        $this->redirect($this->Link());

        return [];
    }

    /**
     * save the order to a member. If no member exists then create the member first using the ShopAccountForm.
     *
     * @param SS_HTTPRequest $request
     *
     * @return array
     *               TO DO: untested
     */
    public function saveorder(HTTPRequest $request)
    {
        $member = Member::currentUser();
        if (! $member) {
            $this->showCreateAccountForm = true;

            return [];
        }
        if ($this->currentOrder && $this->currentOrder->getTotalItems()) {
            $this->currentOrder->write();
            self::set_message(_t('CartPage.ORDERSAVED', 'Your order has been saved.'));
        } else {
            self::set_message(_t('CartPage.ORDERCOULDNOTBESAVED', 'Your order could not be saved.'));
        }
        $this->redirectBack();

        return [];
    }

    /**
     * Delete the currently viewed order.
     *
     * TO DO: untested
     *
     * @param SS_HTTPRequest $request
     *
     * @return array
     */
    public function deleteorder(HTTPRequest $request)
    {
        if (! $this->CurrentOrderIsInCart()) {
            if ($this->currentOrder->canDelete()) {
                $this->currentOrder->delete();
                self::set_message(_t('CartPage.ORDERDELETED', 'Order has been deleted.'));
            }
        }
        self::set_message(_t('CartPage.ORDERNOTDELETED', 'Order could not be deleted.'));

        return [];
    }

    /**
     * Start a new order.
     *
     * @param SS_HTTPRequest $request
     *
     * @return array
     *               TO DO: untested
     */
    public function startneworder(HTTPRequest $request)
    {
        ShoppingCart::singleton()->clear();
        self::set_message(_t('CartPage.NEWORDERSTARTED', 'New order has been started.'));
        $this->redirect($this->Link());

        return [];
    }

    /**
     * This returns a ArraList, each dataobject has two vars: Title and Link.
     *
     * @return ArraList
     **/
    public function ActionLinks()
    {
        $this->workOutMessagesAndActions();
        if ($this->actionLinks && $this->actionLinks->count()) {
            return $this->actionLinks;
        }

        return;
    }

    /**
     * The link that Google et al. need to index.
     * @return string
     */
    public function CanonicalLink()
    {
        $link = $checkoutPageLink = CheckoutPage::find_link();
        $this->extend('UpdateCanonicalLink', $link);

        return $link;
    }

    /**
     * @return string
     **/
    public function Message()
    {
        $this->workOutMessagesAndActions();
        if (! $this->message) {
            $sessionCode = EcommerceConfig::get(CartPageController::class, 'session_code');
            if ($sessionMessage = $this->getRequest()->getSession()->get($sessionCode)) {
                $this->message = $sessionMessage;
                Controller::curr()->getRequest()->getSession()->set($sessionCode, '');
                Controller::curr()->getRequest()->getSession()->clear($sessionCode);
            }
        }
        return DBField::create_field('HTMLText', $this->message);
    }

    /**
     * @return DataObject | Null - Order
     **/
    public function Order()
    {
        return $this->currentOrder;
    }

    /**
     * @return bool
     **/
    public function CanEditOrder()
    {
        if ($this->currentOrder) {
            if ($this->currentOrder->canEdit()) {
                if ($this->currentOrder->getTotalItems()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Tells you if the order you are viewing at the moment is also in the cart.
     *
     * @return bool
     **/
    public function CurrentOrderIsInCart()
    {
        $viewingRealCurrentOrder = false;
        $realCurrentOrder = ShoppingCart::current_order();
        if ($this->currentOrder && $realCurrentOrder) {
            if ($realCurrentOrder->ID === $this->currentOrder->ID) {
                $viewingRealCurrentOrder = true;
            }
        }

        return $viewingRealCurrentOrder;
    }

    /**
     * Do we need to show the Create Account Form?
     *
     * @return bool
     */
    public function ShowCreateAccountForm()
    {
        if ($this->getRequest()->getSession()->get('CartPageCreateAccountForm')) {
            $this->getRequest()->getSession()->set('CartPageCreateAccountForm', false);
            return true;
        }
        if (Member::currentUser() || $this->currentOrder->MemberID) {
            return false;
        }

        $this->getRequest()->getSession()->set('CartPageCreateAccountForm', true);

        return true;
    }

    /**
     * Returns the CreateAccountForm.
     *
     * @return ShopAccountForm
     */
    public function CreateAccountForm()
    {
        return ShopAccountForm::create($this, 'CreateAccountForm');
    }

    /**
     * @standard SS method
     */
    protected function init()
    {
        HTTP::set_cache_age(0);
        parent::init();
        // find the current order if any
        $orderID = 0;
        //WE HAVE THIS FOR SUBMITTING FORMS!
        if (isset($_REQUEST['OrderID'])) {
            $orderID = intval($_REQUEST['OrderID']);
            if ($orderID) {
                $this->currentOrder = Order::get()->byID($orderID);
            }
        } elseif ($this->request && $this->request->param('ID') && $this->request->param('Action')) {
            //we can not do intval here!
            $id = $this->request->param('ID');
            $action = $this->request->param('Action');
            $otherID = intval($this->request->param('OtherID'));
            //the code below is for submitted orders, but we still put it here so
            //we can do all the retrieval options at once.
            if (($action === 'retrieveorder') && $id && $otherID) {
                $sessionID = Convert::raw2sql($id);
                $retrievedOrder = Order::get()->filter(
                    [
                        'SessionID' => $sessionID,
                        'ID' => $otherID,
                    ]
                )->first();
                if ($retrievedOrder) {
                    $this->currentOrder = $retrievedOrder;
                    $this->overrideCanView = true;
                    $this->setRetrievalOrderID($this->currentOrder->ID);
                }
            } elseif (intval($id) && in_array($action, $this->stat('allowed_actions'), true)) {
                $this->currentOrder = Order::get()->byID(intval($id));
            }
        }
        if (! $this->currentOrder) {
            $this->currentOrder = ShoppingCart::current_order();
            if ($this->currentOrder) {
                if ($this->currentOrder->IsSubmitted()) {
                    $this->overrideCanView = true;
                }
            }
        }
        //redirect if we are viewing the order with the wrong page!
        if ($this->currentOrder) {
            if ($this->overrideCanView) {
                $canView = $this->currentOrder->canOverrideCanView();
            } else {
                $canView = $this->currentOrder->canView();
            }
            //IMPORTANT SECURITY QUESTION!
            if ($canView) {
                if ($this->currentOrder->IsSubmitted() && $this->onlyShowUnsubmittedOrders()) {
                    $this->redirect($this->currentOrder->Link());
                } elseif (! $this->currentOrder->IsSubmitted() && $this->onlyShowSubmittedOrders()) {
                    $this->redirect($this->currentOrder->Link());
                }
            } else {
                if (! $this->LoginToOrderLinkLabel) {
                    $this->LoginToOrderLinkLabel = _t('CartPage.LOGINFIRST', 'You will need to log in before you can access the requested order order. ');
                }
                $messages = [
                    'default' => '<p class="message good">' . $this->LoginToOrderLinkLabel . '</p>',
                    'logInAgain' => _t('CartPage.LOGINAGAIN', 'You have been logged out. If you would like to log in again, please do so below.'),
                ];
                Security::permissionFailure($this, $messages);

                return false;
            }
            if (! $this->currentOrder->IsSubmitted()) {
                //we always want to make sure the order is up-to-date.
                $this->currentOrder->init($force = false);
                $this->currentOrder->calculateOrderAttributes($force = true);
                $this->currentOrder->calculateOrderAttributes($force = true);
            }
        } else {
            $this->message = _t('CartPage.ORDERNOTFOUND', 'Order can not be found.');
        }
    }

    /**
     * We set sesssion ID for retrieval of order in non cart setting
     * @param int $orderID
     * @param int $validUntilTS timestamp (unix epoch) until which the current Order ID is valid
     */
    protected function setRetrievalOrderID($orderID, $validUntilTS = null)
    {
        if (! $validUntilTS) {
            $validUntilTS = time() + 3600;
        }
        $this->getRequest()->getSession()->set('CheckoutPageCurrentOrderID', $orderID);

        $this->getRequest()->getSession()->set('CheckoutPageCurrentRetrievalTime', $validUntilTS);

        $this->getRequest()->getSession()->save($this->getRequest());
    }

    /**
     * we clear the retrieval Order ID
     */
    protected function clearRetrievalOrderID()
    {
        $this->getRequest()->getSession()->clear('CheckoutPageCurrentOrderID');
        $this->getRequest()->getSession()->set('CheckoutPageCurrentOrderID', 0);
        $this->getRequest()->getSession()->clear('CheckoutPageCurrentRetrievalTime');
        $this->getRequest()->getSession()->set('CheckoutPageCurrentRetrievalTime', 0);
        $this->getRequest()->getSession()->save($this->getRequest());
    }

    /**
     * work out the options for the user.
     **/
    protected function workOutMessagesAndActions()
    {
        if (! $this->workedOutMessagesAndActions) {
            $this->actionLinks = new ArrayList([]);
            //what order are we viewing?
            $viewingRealCurrentOrder = $this->CurrentOrderIsInCart();
            $currentUserID = Member::currentUserID();

            //Continue Shopping
            if (isset($this->ContinueShoppingLabel) && $this->ContinueShoppingLabel) {
                if ($viewingRealCurrentOrder) {
                    if ($this->isCartPage()) {
                        $continueLink = $this->ContinueShoppingLink();
                        if ($continueLink) {
                            $this->actionLinks->push(
                                ArrayData::create(
                                    [
                                        'Title' => $this->ContinueShoppingLabel,
                                        'Link' => $continueLink,
                                    ]
                                )
                            );
                        }
                    }
                }
            }

            //Proceed To CheckoutLabel
            if (isset($this->ProceedToCheckoutLabel) && $this->ProceedToCheckoutLabel) {
                if ($viewingRealCurrentOrder) {
                    if ($this->isCartPage()) {
                        $checkoutPageLink = CheckoutPage::find_link();
                        if ($checkoutPageLink && $this->currentOrder && $this->currentOrder->getTotalItems()) {
                            $this->actionLinks->push(new ArrayData([
                                'Title' => $this->ProceedToCheckoutLabel,
                                'Link' => $checkoutPageLink,
                            ]));
                        }
                    }
                }
            }

            //view account details
            if (isset($this->ShowAccountLabel) && $this->ShowAccountLabel) {
                if ($this->isOrderConfirmationPage() || $this->isCartPage()) {
                    if (AccountPage::find_link()) {
                        if ($currentUserID) {
                            $this->actionLinks->push(new ArrayData([
                                'Title' => $this->ShowAccountLabel,
                                'Link' => AccountPage::find_link(),
                            ]));
                        }
                    }
                }
            }

            //go to current order
            if (isset($this->CurrentOrderLinkLabel) && $this->CurrentOrderLinkLabel) {
                if ($this->isCartPage()) {
                    if (! $viewingRealCurrentOrder) {
                        $this->actionLinks->push(new ArrayData([
                            'Title' => $this->CurrentOrderLinkLabel,
                            'Link' => ShoppingCart::current_order()->Link(),
                        ]));
                    }
                }
            }

            //Save order - we assume only current ones can be saved.
            if (isset($this->SaveOrderLinkLabel) && $this->SaveOrderLinkLabel) {
                if ($viewingRealCurrentOrder) {
                    if ($currentUserID && $this->currentOrder->MemberID === $currentUserID) {
                        if ($this->isCartPage()) {
                            if ($this->currentOrder && $this->currentOrder->getTotalItems() && ! $this->currentOrder->IsSubmitted()) {
                                $this->actionLinks->push(new ArrayData([
                                    'Title' => $this->SaveOrderLinkLabel,
                                    'Link' => $this->Link('saveorder') . '/' . $this->currentOrder->ID . '/',
                                ]));
                            }
                        }
                    }
                }
            }

            //load order
            if (isset($this->LoadOrderLinkLabel) && $this->LoadOrderLinkLabel) {
                if ($this->isCartPage() && $this->currentOrder) {
                    if (! $viewingRealCurrentOrder) {
                        $this->actionLinks->push(new ArrayData([
                            'Title' => $this->LoadOrderLinkLabel,
                            'Link' => $this->Link('loadorder') . '/' . $this->currentOrder->ID . '/',
                        ]));
                    }
                }
            }

            //delete order
            if (isset($this->DeleteOrderLinkLabel) && $this->DeleteOrderLinkLabel) {
                if ($this->isCartPage() && $this->currentOrder) {
                    if (! $viewingRealCurrentOrder) {
                        $this->actionLinks->push(new ArrayData([
                            'Title' => $this->DeleteOrderLinkLabel,
                            'Link' => $this->Link('deleteorder') . '/' . $this->currentOrder->ID . '/',
                        ]));
                    }
                }
            }

            //Start new order
            //Strictly speaking this is only part of the
            //OrderConfirmationPage but we put it here for simplicity's sake
            if (isset($this->StartNewOrderLinkLabel) && $this->StartNewOrderLinkLabel) {
                if ($this->isOrderConfirmationPage()) {
                    $this->actionLinks->push(new ArrayData([
                        'Title' => $this->StartNewOrderLinkLabel,
                        'Link' => CartPage::new_order_link($this->currentOrder->ID),
                    ]));
                }
            }

            //copy order
            //Strictly speaking this is only part of the
            //OrderConfirmationPage but we put it here for simplicity's sake
            if (isset($this->CopyOrderLinkLabel) && $this->CopyOrderLinkLabel) {
                if ($this->isOrderConfirmationPage() && $this->currentOrder->ID) {
                    $this->actionLinks->push(new ArrayData([
                        'Title' => $this->CopyOrderLinkLabel,
                        'Link' => OrderConfirmationPage::copy_order_link($this->currentOrder->ID),
                    ]));
                }
            }

            //actions from modifiers
            if ($this->isOrderConfirmationPage() && $this->currentOrder->ID) {
                $modifiers = $this->currentOrder->Modifiers();
                if ($modifiers->count()) {
                    foreach ($modifiers as $modifier) {
                        $array = $modifier->PostSubmitAction();
                        if (is_array($array) && count($array)) {
                            $this->actionLinks->push(new ArrayData($array));
                        }
                    }
                }
            }

            //log out
            //Strictly speaking this is only part of the
            //OrderConfirmationPage but we put it here for simplicity's sake
            if (Member::currentUser()) {
                if ($this->isOrderConfirmationPage()) {
                    $this->actionLinks->push(new ArrayData([
                        'Title' => _t('CartPage.LOGOUT', 'log out'),
                        'Link' => '/Security/logout/',
                    ]));
                }
            }

            //no items
            if ($this->currentOrder) {
                if (! $this->currentOrder->getTotalItems()) {
                    $this->message = $this->NoItemsInOrderMessage;
                }
            } else {
                $this->message = $this->NonExistingOrderMessage;
            }

            $this->workedOutMessagesAndActions = true;
            //does nothing at present....
        }
    }

    /***********************
     * HELPER METHOD (PROTECTED)
     ***********************






    /**
     * Is this a CartPage or is it another type (Checkout / OrderConfirmationPage)?
     * @return bool
     */
    protected function isCartPage()
    {
        if ($this->isCheckoutPage() || ($this->isOrderConfirmationPage())) {
            return false;
        }

        return true;
    }

    /**
     * Is this a CheckoutPage or is it another type (CartPage / OrderConfirmationPage)?
     *
     * @return bool
     */
    protected function isCheckoutPage()
    {
        if ($this->dataRecord instanceof CheckoutPage) {
            return true;
        }
        return false;
    }

    /**
     * Is this a OrderConfirmationPage or is it another type (CartPage / CheckoutPage)?
     *
     * @return bool
     */
    protected function isOrderConfirmationPage()
    {
        if ($this->dataRecord instanceof OrderConfirmationPage) {
            return true;
        }
        return false;
    }

    /**
     * Can this page only show Submitted Orders (e.g. OrderConfirmationPage) ?
     *
     * @return bool
     */
    protected function onlyShowSubmittedOrders()
    {
        return false;
    }

    /**
     * Can this page only show Unsubmitted Orders (e.g. CartPage) ?
     *
     * @return bool
     */
    protected function onlyShowUnsubmittedOrders()
    {
        return true;
    }
}
