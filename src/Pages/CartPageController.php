<?php

namespace Sunnysideup\Ecommerce\Pages;

use PageController;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;
use SilverStripe\View\ArrayData;
use Sunnysideup\Ecommerce\Api\SendLoginToken;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\ShopAccountForm;
use Sunnysideup\Ecommerce\Model\Order;

/**
 * Class \Sunnysideup\Ecommerce\Pages\CartPageController
 *
 * @property \Sunnysideup\Ecommerce\Pages\CartPage $dataRecord
 * @method \Sunnysideup\Ecommerce\Pages\CartPage data()
 * @mixin \Sunnysideup\Ecommerce\Pages\CartPage
 */
class CartPageController extends PageController
{
    /**
     * This ArraList holds DataObjects with a Link and Title each....
     *
     * @var ArrayList
     */
    protected $actionLinks;

    /**
     * to ensure messages and actions links are only worked out once...
     *
     * @var bool
     */
    protected $workedOutMessagesAndActions = false;

    /**
     * order currently being shown on this page.
     *
     * @var Order
     */
    protected $currentOrder;

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
     * @var string
     */
    private static $session_code = 'EcommerceCartPageMessage';

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
        'sendloginlink',
    ];

    /**
     * Message shown (e.g. no current order, could not find order, order updated, etc...).
     *
     * @var string
     * @todo: check if we need this....!
     */
    private $message = '';

    public static function set_message(string $s)
    {
        $sessionCode = EcommerceConfig::get(CartPageController::class, 'session_code');
        Controller::curr()->getRequest()->getSession()->set($sessionCode, $s);
    }

    /*
     * Actions
     ***********************




    /**
     * shows an order and loads it if it is not submitted.
     * @todo: do we still need loadorder controller method????
     * @param HTTPRequest $request
     * @return array just so that template shows
     */
    public function showorder(HTTPRequest $request)
    {
        if (! $this->currentOrder) {
            $this->message = _t('CartPage.ORDERNOTFOUND', 'Order can not be found.');
        } elseif (! $this->currentOrder->IsSubmitted()) {
            $shoppingCart = ShoppingCart::current_order();
            if ($shoppingCart->ID !== $this->currentOrder->ID) {
                if (ShoppingCart::singleton()->loadOrder($this->currentOrder)) {
                    $this->message = _t('CartPage.ORDERHASBEENLOADED', 'Order has been loaded.');
                } else {
                    $this->message = _t('CartPage.ORDERNOTLOADED', 'Order could not be loaded.');
                }
            }
        }

        return [];
    }

    /**
     * share an order ...
     *
     * @todo: do we still need loadorder controller method????
     *
     * @return array just so that template shows
     */
    public function share(HTTPRequest $request)
    {
        $codes = Convert::raw2sql($request->param('ID'));
        if (! $request->getVar('ready') && ! $request->getVar('done')) {
            return $this->redirect($this->Link('share/' . $codes) . '?ready=1');
        }

        $titleAppendixArray = [];
        $buyables = explode('-', $codes);
        if ([] !== $buyables) {
            $sc = ShoppingCart::singleton();
            $order = $sc->currentOrder();
            foreach ($buyables as $buyable) {
                $details = explode(',', $buyable);
                if (3 === count($details)) {
                    $className = $details[0];
                    $className = class_exists($className) ? $className : null;
                    $id = (int) $details[1];
                    $quantity = floatval($details[2]);
                    if ($className && $id && $quantity) {
                        $buyable = $className::get_by_id($id);
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
        if (strlen((string) $this->Title) > 255) {
            $this->Title = substr((string) $this->Title, 0, 255) . ' ...';
        }

        return [];
    }

    /**
     * Loads either the "current order""into the shopping cart.
     *
     * @todountested
     * @todowhat to do with old order
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
     * @return array
     *               TO DO: untested
     */
    public function saveorder(HTTPRequest $request)
    {
        $member = Security::getCurrentUser();
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
     * @todountested
     *
     * @return array
     */
    public function deleteorder(HTTPRequest $request)
    {
        if (! $this->CurrentOrderIsInCart() && $this->currentOrder->canDelete()) {
            $this->currentOrder->delete();
            self::set_message(_t('CartPage.ORDERDELETED', 'Order has been deleted.'));
        }

        self::set_message(_t('CartPage.ORDERNOTDELETED', 'Order could not be deleted.'));

        return [];
    }

    /**
     * Start a new order.
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
     * @return null|ArrayList
     */
    public function ActionLinks()
    {
        $this->workOutMessagesAndActions();
        if ($this->actionLinks->exists()) {
            return $this->actionLinks;
        }

        return ArrayList::create();
    }

    /**
     * @return string
     */
    public function Message()
    {
        $this->workOutMessagesAndActions();
        if (! $this->message) {
            $sessionCode = EcommerceConfig::get(CartPageController::class, 'session_code');
            $sessionMessage = $this->getRequest()->getSession()->get($sessionCode);
            if ($sessionMessage) {
                $this->message = $sessionMessage;
                Controller::curr()->getRequest()->getSession()->set($sessionCode, '');
                Controller::curr()->getRequest()->getSession()->clear($sessionCode);
            }
        }

        return DBField::create_field('HTMLText', $this->message);
    }

    /**
     * @return null|\SilverStripe\ORM\DataObject - Order
     */
    public function Order()
    {
        return $this->currentOrder;
    }

    /**
     * @return bool
     */
    public function CanEditOrder()
    {
        return $this->currentOrder && $this->currentOrder->canEdit() && $this->currentOrder->getTotalItems();
    }

    /**
     * Tells you if the order you are viewing at the moment is also in the cart.
     *
     * @return bool
     */
    public function CurrentOrderIsInCart()
    {
        $viewingRealCurrentOrder = false;
        $realCurrentOrder = ShoppingCart::current_order();
        if ($this->currentOrder && $realCurrentOrder && $realCurrentOrder->ID === $this->currentOrder->ID) {
            $viewingRealCurrentOrder = true;
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

        if (Security::getCurrentUser() || $this->currentOrder->MemberID) {
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
        parent::init();
        // find the current order if any
        $orderID = 0;
        //WE HAVE THIS FOR SUBMITTING FORMS!
        if (isset($_REQUEST['OrderID'])) {
            $orderID = (int) $_REQUEST['OrderID'];
            if ($orderID !== 0) {
                $this->currentOrder = Order::get_order_cached((int) $orderID);
            }
        } elseif ($this->request && $this->request->param('ID') && $this->request->param('Action')) {
            //we can not do intval here!
            $id = $this->request->param('ID');
            $action = $this->request->param('Action');
            $otherID = (int) $this->request->param('OtherID');
            //the code below is for submitted orders, but we still put it here so
            //we can do all the retrieval options at once.
            if (('retrieveorder' === $action) && $id && $otherID) {
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
                } else {
                    $this->httpError(404, 'No order was found.');
                    $this->message = _t('CartPage.ORDERNOTFOUND', 'Order can not be found.');
                }
            } elseif ((int) $id && in_array($action, $this->config()->get('allowed_actions'), true)) {
                $this->currentOrder = Order::get_order_cached((int) $id);
            }
        }

        if (! $this->currentOrder) {
            $this->currentOrder = ShoppingCart::current_order();
            if ($this->currentOrder && $this->currentOrder->IsSubmitted()) {
                $this->overrideCanView = true;
            }
        }

        //redirect if we are viewing the order with the wrong page!
        if ($this->currentOrder) {
            $this->currentOrder->setOverrideCanView($this->overrideCanView);
            $canView = $this->overrideCanView ? $this->currentOrder->canOverrideCanView() : $this->currentOrder->canView();
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
                    'default' => DBField::create_field('HTMLText', '<p class="message good">' . $this->LoginToOrderLinkLabel . '</p>'),
                    'logInAgain' => _t('CartPage.LOGINAGAIN', 'You have been logged out. If you would like to log in again, please do so below.'),
                ];
                Security::permissionFailure($this, $messages);

                return false;
            }

            if ($this->currentOrder->IsSubmitted()) {
                $this->currentOrder->tryToFinaliseOrder();
            } else {
                // we always want to make sure the order is up-to-date.
                $this->currentOrder->init($recalculate = true);

                if (! $this->currentOrder->getCalculatedOrderAttributesCache()) {
                    // recalculate after init! - this may already happen with init ....
                    // make it faster by checking if it did.
                    $this->currentOrder->calculateOrderAttributes($recalculate = true);
                }
            }
        } else {
            $this->message = _t('CartPage.ORDERNOTFOUND', 'Order can not be found.');
        }
        return null;
    }

    /**
     * We set sesssion ID for retrieval of order in non cart setting.
     *
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
     * we clear the retrieval Order ID.
     */
    protected function clearRetrievalOrderID()
    {
        $session = $this->getRequest()->getSession();
        $session->clear('CheckoutPageCurrentOrderID');
        $session->set('CheckoutPageCurrentOrderID', 0);
        $session->clear('CheckoutPageCurrentRetrievalTime');
        $session->set('CheckoutPageCurrentRetrievalTime', 0);
        $session->save($this->getRequest());
    }

    /**
     * work out the options for the user.
     */
    protected function workOutMessagesAndActions()
    {
        if (! $this->workedOutMessagesAndActions) {
            $this->actionLinks = new ArrayList([]);
            //what order are we viewing?
            $viewingRealCurrentOrder = $this->CurrentOrderIsInCart();
            $currentUserID = Security::getCurrentUser()?->ID;

            //Continue Shopping
            if (property_exists($this, 'ContinueShoppingLabel') && null !== $this->ContinueShoppingLabel && $this->ContinueShoppingLabel && $viewingRealCurrentOrder && $this->isCartPage()) {
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

            //Proceed To CheckoutLabel
            if (property_exists($this, 'ProceedToCheckoutLabel') && null !== $this->ProceedToCheckoutLabel && $this->ProceedToCheckoutLabel && $viewingRealCurrentOrder && $this->isCartPage()) {
                $checkoutPageLink = CheckoutPage::find_link();
                if ($checkoutPageLink && $this->currentOrder && $this->currentOrder->getTotalItems()) {
                    $this->actionLinks->push(new ArrayData([
                        'Title' => $this->ProceedToCheckoutLabel,
                        'Link' => $checkoutPageLink,
                    ]));
                }
            }

            //view account details
            if (property_exists($this, 'ShowAccountLabel') && null !== $this->ShowAccountLabel && $this->ShowAccountLabel && ($this->isOrderConfirmationPage() || $this->isCartPage()) && (AccountPage::find_link() && $currentUserID)) {
                $this->actionLinks->push(new ArrayData([
                    'Title' => $this->ShowAccountLabel,
                    'Link' => AccountPage::find_link(),
                ]));
            }

            //go to current order
            if (property_exists($this, 'CurrentOrderLinkLabel') && null !== $this->CurrentOrderLinkLabel && $this->CurrentOrderLinkLabel && $this->isCartPage() && ! $viewingRealCurrentOrder) {
                $this->actionLinks->push(new ArrayData([
                    'Title' => $this->CurrentOrderLinkLabel,
                    'Link' => ShoppingCart::current_order()->Link(),
                ]));
            }

            //Save order - we assume only current ones can be saved.
            if (property_exists($this, 'SaveOrderLinkLabel') && null !== $this->SaveOrderLinkLabel && $this->SaveOrderLinkLabel && $viewingRealCurrentOrder && ($currentUserID && $this->currentOrder->MemberID === $currentUserID && $this->isCartPage())) {
                if ($this->currentOrder && $this->currentOrder->getTotalItems() && ! $this->currentOrder->IsSubmitted()) {
                    $this->actionLinks->push(new ArrayData([
                        'Title' => $this->SaveOrderLinkLabel,
                        'Link' => $this->Link('saveorder') . '/' . $this->currentOrder->ID . '/',
                    ]));
                }
            }

            //load order
            if (property_exists($this, 'LoadOrderLinkLabel') && null !== $this->LoadOrderLinkLabel && $this->LoadOrderLinkLabel && ($this->isCartPage() && $this->currentOrder) && ! $viewingRealCurrentOrder) {
                $this->actionLinks->push(new ArrayData([
                    'Title' => $this->LoadOrderLinkLabel,
                    'Link' => $this->Link('loadorder') . '/' . $this->currentOrder->ID . '/',
                ]));
            }

            //delete order
            if (property_exists($this, 'DeleteOrderLinkLabel') && null !== $this->DeleteOrderLinkLabel && $this->DeleteOrderLinkLabel && ($this->isCartPage() && $this->currentOrder) && ! $viewingRealCurrentOrder) {
                $this->actionLinks->push(new ArrayData([
                    'Title' => $this->DeleteOrderLinkLabel,
                    'Link' => $this->Link('deleteorder') . '/' . $this->currentOrder->ID . '/',
                ]));
            }

            //Start new order
            //Strictly speaking this is only part of the
            //OrderConfirmationPage but we put it here for simplicity's sake
            if (property_exists($this, 'StartNewOrderLinkLabel') && null !== $this->StartNewOrderLinkLabel && $this->StartNewOrderLinkLabel && $this->isOrderConfirmationPage()) {
                $this->actionLinks->push(new ArrayData([
                    'Title' => $this->StartNewOrderLinkLabel,
                    'Link' => CartPage::new_order_link($this->currentOrder->ID),
                ]));
            }

            //copy order
            //Strictly speaking this is only part of the
            //OrderConfirmationPage but we put it here for simplicity's sake
            if (property_exists($this, 'CopyOrderLinkLabel') && null !== $this->CopyOrderLinkLabel && $this->CopyOrderLinkLabel && ($this->isOrderConfirmationPage() && $this->currentOrder->ID)) {
                $this->actionLinks->push(new ArrayData([
                    'Title' => $this->CopyOrderLinkLabel,
                    'Link' => OrderConfirmationPage::copy_order_link($this->currentOrder->ID),
                ]));
            }

            //actions from modifiers
            if ($this->isOrderConfirmationPage() && $this->currentOrder->ID) {
                $modifiers = $this->currentOrder->Modifiers();
                if ($modifiers->exists()) {
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
            // if (Security::getCurrentUser()) {
            //     if ($this->isOrderConfirmationPage()) {
            //         $this->actionLinks->push(new ArrayData([
            //             'Title' => _t('CartPage.LOGOUT', 'log out'),
            //             'Link' => '/Security/logout/',
            //         ]));
            //     }
            // }

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

    /*
     * HELPER METHOD (PROTECTED)
     ***********************






    /**
     * Is this a CartPage or is it another type (Checkout / OrderConfirmationPage)?
     * @return bool
     */
    protected function isCartPage()
    {
        return ! $this->isCheckoutPage() && ! $this->isOrderConfirmationPage();
    }

    /**
     * Is this a CheckoutPage or is it another type (CartPage / OrderConfirmationPage)?
     *
     * @return bool
     */
    protected function isCheckoutPage()
    {
        return $this->dataRecord instanceof CheckoutPage;
    }

    /**
     * Is this a OrderConfirmationPage or is it another type (CartPage / CheckoutPage)?
     *
     * @return bool
     */
    protected function isOrderConfirmationPage()
    {
        return $this->dataRecord instanceof OrderConfirmationPage;
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

    public function sendloginlink(HTTPRequest $request)
    {
        $email = $request->requestVar('email');
        $backURL = $request->requestVar('BackURL');
        if (SecurityToken::inst()->checkRequest($request)) {
            return $this->httpError(400, 'Invalid security token');
        }
        $obj = Injector::inst()->get(SendLoginToken::class);
        $obj->send($email, $backURL, $request);

        return _t(
            'CartPage.LOGINLINKSENT',
            'If you\'ve shopped with us before, a login link has been sent to your email.
            If you like you can use this to log-in and access your order history.
            If you haven\'t shopped with us before, please proceed to checkout as a guest or create an account.',
        );
    }
}
