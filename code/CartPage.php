<?php

/**
 * @description: This is a page that shows the cart content,
 * without "leading to" checking out. That is, there is no "next step" functionality
 * or a way to submit the order.
 * NOTE: both the Account and the Checkout Page extend from this class as they
 * share some functionality.
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: Pages
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class CartPage extends Page{

	/**
	 * Standard SS variable
	 * @var String
	 */
	private static $icon = 'ecommerce/images/icons/CartPage';

	/**
	 * Standard SS variable
	 * @var Array
	 */
	private static $db = array(
		'ContinueShoppingLabel' => 'Varchar(100)',
		'ProceedToCheckoutLabel' => 'Varchar(100)',
		'ShowAccountLabel' => 'Varchar(100)',
		'CurrentOrderLinkLabel' => 'Varchar(100)',
		'LoginToOrderLinkLabel' => 'Varchar(100)',
		'SaveOrderLinkLabel' => 'Varchar(100)',
		'LoadOrderLinkLabel' => 'Varchar(100)',
		'DeleteOrderLinkLabel' => 'Varchar(100)',
		'NoItemsInOrderMessage' => 'HTMLText',
		'NonExistingOrderMessage' => 'HTMLText'
	);

	/**
	 * Standard SS variable
	 * @var Array
	 */
	private static $defaults = array(
		'ContinueShoppingLabel' => 'continue shopping',
		'ProceedToCheckoutLabel' => 'proceed to checkout',
		'ShowAccountLabel' => 'view account details',
		'CurrentOrderLinkLabel' => 'view current order',
		'LoginToOrderLinkLabel' => 'you must log in to view this order',
		'SaveOrderLinkLabel' => 'save current order',
		'DeleteOrderLinkLabel' => 'delete this order',
		'LoadOrderLinkLabel' => 'finalise this order',
		'NoItemsInOrderMessage' => '<p>You do not have any items in your current order</p>',
		'NonExistingOrderMessage' => '<p>Sorry, the order you are trying to open does not exist</p>'
	);

	/**
	 * Standard SS variable
	 * @var Array
	 */
	private static $casting = array(
		'MenuTitle' => 'HTMLVarchar'
	);

	/**
	 * Standard SS variable.
	 */
	private static $singular_name = "Cart Page";
		function i18n_singular_name() { return _t("CartPage.SINGULARNAME", "Cart Page");}

	/**
	 * Standard SS variable.
	 */
	private static $plural_name = "Cart Pages";
		function i18n_plural_name() { return _t("CartPage.PLURALNAME", "Cart Pages");}

	/**
	 * Standard SS variable.
	 * @var String
	 */
	private static $description = "A page where the customer can view the current order (cart) without finalising the order.";

	/***
	 * override core function to turn "checkout" into "Checkout (1)"
	 * @return DBField
	 */
	public function obj($fieldName, $arguments = null, $forceReturnedObject = true, $cache = false, $cacheName = null) {
		if($fieldName == "MenuTitle") {
			return DBField::create_field('HTMLVarchar', strip_tags($this->EcommerceMenuTitle()), "MenuTitle", $this);
		}
		else {
			return parent::obj($fieldName);
		}
	}


	/**
	 * Standard SS function, we only allow for one CartPage page to exist
	 * but we do allow for extensions to exist at the same time.
	 * @param Member $member
	 * @return Boolean
	 */
	function canCreate($member = null) {
		return CartPage::get()->Filter(array("ClassName" => "CartPage"))->Count() ? false : true;
	}

	/**
	 *@return FieldList
	 **/
	function getCMSFields(){
		$fields = parent::getCMSFields();
		$fields->addFieldsToTab(
			'Root.Messages',
			array (
				new TabSet(
					"Messages",
					new Tab(
						'Actions',
						new TextField('ContinueShoppingLabel', _t('CartPage.CONTINUESHOPPINGLABEL', 'Label on link to continue shopping - e.g. click here to continue shopping')),
						new TextField('ProceedToCheckoutLabel', _t('CartPage.PROCEEDTOCHECKOUTLABEL', 'Label on link to proceed to checkout - e.g. click here to finalise your order')),
						new TextField('ShowAccountLabel', _t('CartPage.SHOWACCOUNTLABEL', 'Label on the link \'view account details\' - e.g. click here to vuiew your account details')),
						new TextField('CurrentOrderLinkLabel', _t('CartPage.CURRENTORDERLINKLABEL', 'Label for the link pointing to the current order - e.g. click here to view current order')),
						new TextField('LoginToOrderLinkLabel', _t('CartPage.LOGINTOORDERLINKLABEL', 'Label for the link pointing to the order which requires a log in - e.g. you must login to view this order')),
						new TextField('SaveOrderLinkLabel', _t('CartPage.SAVEORDERLINKLABEL', 'Label for the saving an order - e.g. click here to save current order')),
						new TextField('LoadOrderLinkLabel', _t('CartPage.LOADORDERLINKLABEL', 'Label for the loading an order into the cart - e.g. click here to finalise this order')),
						new TextField('DeleteOrderLinkLabel', _t('CartPage.DELETEORDERLINKLABEL', 'Label for the deleting an order - e.g. click here to delete this order'))
					),
					new Tab(
						'Errors',
						$htmlEditorField1 = new HTMLEditorField('NoItemsInOrderMessage', _t('CartPage.NOITEMSINORDERMESSAGE','No items in order - shown when the customer tries to view an order without items.')),
						$htmlEditorField2 = new HTMLEditorField('NonExistingOrderMessage', _t('CartPage.NONEXISTINGORDERMESSAGE','Non-existing Order - shown when the customer tries to load a non-existing order.'))
					)
				)
			)
		);
		$htmlEditorField1->setRows(3);
		$htmlEditorField2->setRows(3);
		return $fields;
	}

	/**
	 * Returns the Link to the CartPage on this site
	 * @return String (URLSegment)
	 */
	public static function find_link() {
		$page = CartPage::get()->Filter(array("ClassName" => "CartPage"))->First();
		if($page) {
			return $page->Link();
		}
		else {
			return CheckoutPage::find_link();
		}
	}

	/**
	 * Returns the "new order" link
	 * @param Int | String $orderID - not used in CartPage
	 * @return String (URLSegment)
	 */
	public static function new_order_link($orderID) {
		return self::find_link()."startneworder/";
	}

	/**
	 * Returns the "copy order" link
	 * @param Int | String $orderID - not used in CartPage
	 * @return String (URLSegment)
	 */
	public static function copy_order_link($orderID) {
		return OrderConfirmationPage::find_link()."copyorder/".$orderID."/";
	}

	/**
	 * Return a link to view the order on this page.
	 * @param int|string $orderID ID of the order
	 * @return Int | String (URLSegment)
	 */
	public static function get_order_link($orderID) {
		return self::find_link(). 'showorder/' . $orderID . '/';
	}

	/**
	 * Return a link to view the order on this page.
	 * @param int|string $orderID ID of the order
	 * @return String (URLSegment)
	 */
	public function getOrderLink($orderID) {
		return self::get_order_link($orderID);
	}

	/**
	 * tells us if the current page is part of e-commerce.
	 * @return Boolean
	 */
	function IsEcommercePage () {
		return true;
	}

	/**
	 *@return String (HTML Snippet)
	 **/
	function EcommerceMenuTitle() {
		$count = 0;
		$order = ShoppingCart::current_order();
		if($order) {
			$count = $order->TotalItems();
			$oldSSViewer = Config::inst()->get("SSViewer", "source_file_comments");
			Config::inst()->update("SSViewer", "source_file_comments", false);
			$this->customise(array("Count"=> $count, "OriginalMenuTitle" => $this->MenuTitle) );
			$s = $this->renderWith("AjaxNumItemsInCart");
			Config::inst()->update("SSViewer", "source_file_comments", $oldSSViewer);
			return $s;
		}
		return $this->OriginalMenuTitle();
	}

	/**
	 * The original menu title of the page
	 * @return String
	 */
	function OriginalMenuTitle(){
		return $this->MenuTite;
	}

}

class CartPage_Controller extends Page_Controller{

	/**
	 * @static array
	 * standard SS variable
	 * it is important that we list all the options here
	 */
	private static $allowed_actions = array(
		'saveorder',
		'CreateAccountForm',
		'retrieveorder',
		'loadorder',
		'startneworder',
		'showorder',
		'LoginForm'
	);


	/**
	 * This ArraList holds DataObjects with a Link and Title each....
	 * @var ArraList
	 **/
	protected $actionLinks = null;

	/**
	 * to ensure messages and actions links are only worked out once...
	 * @var $workedOutMessagesAndActions Boolean
	 **/
	protected $workedOutMessagesAndActions = false;

	/**
	 * order currently being shown on this page
	 * @var $order DataObject
	 **/
	protected $currentOrder = null;

	/**
	 * Message shown (e.g. no current order, could not find order, order updated, etc...)
	 *
	  *@var $message String
	 * @todo: check if we need this....!
	 **/
	private $message = "";
	public static function set_message($s) {
		$sessionCode = EcommerceConfig::get("CartPage_Controller", "session_code");
		Session::set($sessionCode, $s);
	}

	/**
	 *
	 * @standard SS method
	 */
	public function init() {
		parent::init();
		// find the current order if any
		$orderID = 0;
		$overrideCanView = false;
		//WE HAVE THIS FOR SUBMITTING FORMS!
		if(isset($_REQUEST['OrderID'])) {
			$orderID = intval($_REQUEST['OrderID']);
			if($orderID) {
				$this->currentOrder = Order::get()->byID($orderID);
			}
		}
		elseif($this->request && $this->request->param('ID') && $this->request->param('Action')) {
			//we can not do intval here!
			$id = $this->request->param('ID');
			$action = $this->request->param('Action');
			$otherID = intval($this->request->param("OtherID"));
			//the code below is for submitted orders, but we still put it here so
			//we can do all the retrieval options in once.
			if(($action == "retrieveorder") && $id && $otherID) {
				$sessionID = Convert::raw2sql($id);
				$retrievedOrder = Order::get()
					->Filter(array(
						"SessionID" => $sessionID,
						"ID" => $otherID
					))
					->First();
				$this->currentOrder = $retrievedOrder;
				$overrideCanView = true;
			}
			elseif(intval($id) && in_array($action, $this->stat("allowed_actions"))){
				$this->currentOrder = Order::get()->byID(intval($id));
			}
		}
		if(!$this->currentOrder) {
			$this->currentOrder = ShoppingCart::current_order();
			if($this->currentOrder) {
				if($this->currentOrder->IsSubmitted()) {
					$overrideCanView = true;
				}
			}
		}
		//redirect if we are viewing the order with the wrong page!
		if($this->currentOrder) {

			//IMPORTANT SECURITY QUESTION!
			if($this->currentOrder->canView() || $overrideCanView) {
				if($this->currentOrder->IsSubmitted() && $this->onlyShowUnsubmittedOrders()) {
					$this->redirect($this->currentOrder->Link());
				}
				elseif((!$this->currentOrder->IsSubmitted()) && $this->onlyShowSubmittedOrders()) {
					$this->redirect($this->currentOrder->Link());
				}
			}
			else {
				if(!$this->LoginToOrderLinkLabel) {
					$this->LoginToOrderLinkLabel = _t('CartPage.LOGINFIRST', 'You will need to log in before you can access the requested order order. ');
				}
				$messages = array(
					'default' => '<p class="message good">' . $this->LoginToOrderLinkLabel . '</p>',
					'logInAgain' => _t('CartPage.LOGINAGAIN', 'You have been logged out. If you would like to log in again, please do so below.')
				);
				Security::permissionFailure($this, $messages);
				return false;
			}
			if(!$this->currentOrder->IsSubmitted()) {
				//we always want to make sure the order is up-to-date.
				$this->currentOrder->init($force = false);
				$this->currentOrder->calculateOrderAttributes($force = true);
				$this->currentOrder->calculateOrderAttributes($force = true);
			}
		}
		else {
			$this->message = _t('CartPage.ORDERNOTFOUND', 'Order can not be found.');
		}
	}





	/***********************
	 * Actions
	 ***********************




	/**
	 * shows an order and loads it if it is not submitted.
	 * @todo: do we still need loadorder controller method????
	 * @param SS_HTTPRequest
	 * @return array just so that template shows
	 **/
	function showorder(SS_HTTPRequest $request) {
		if(!$this->currentOrder) {
			$this->message = _t('CartPage.ORDERNOTFOUND', 'Order can not be found.');
		}
		else {
			if(!$this->currentOrder->IsSubmitted()){
				$shoppingCart = ShoppingCart::current_order();
				if($shoppingCart->ID != $this->currentOrder->ID) {
					if(ShoppingCart::singleton()->loadOrder($this->currentOrder)) {
						$this->message = _t('CartPage.ORDERHASBEENLOADED', 'Order has been loaded.');
					}
					else {
						$this->message = _t('CartPage.ORDERNOTLOADED', 'Order could not be loaded.');
					}
				}
			}
		}
		return array();
	}

	/**
	 * Loads either the "current order""into the shopping cart.
	 *
	 * TO DO: untested
	 * TO DO: what to do with old order
	 * @param SS_HTTPRequest
	 * @return Array
	 */
	function loadorder(SS_HTTPRequest $request) {
		self::set_message(_t("CartPage.ORDERLOADED", "Order has been loaded."));
		ShoppingCart::singleton()->loadOrder($this->currentOrder->ID);
		$this->redirect($this->Link());
		return array();
	}

	/**
	 * save the order to a member. If no member exists then create the member first using the ShopAccountForm.
	 * @param SS_HTTPRequest
	 * @return Array
	 * TO DO: untested
	 */
	function saveorder(SS_HTTPRequest $request) {
		$member = Member::currentUser();
		if(!$member) {
			$this->showCreateAccountForm = true;
			return array();
		}
		if($this->currentOrder && $this->currentOrder->getTotalItems()) {
			$this->currentOrder->write();
			self::set_message(_t("CartPage.ORDERSAVED", "Your order has been saved."));
		}
		else {
			self::set_message(_t("CartPage.ORDERCOULDNOTBESAVED", "Your order could not be saved."));
		}
		$this->redirectBack();
		return array();
	}

	/**
	 * Delete the currently viewed order.
	 *
	 * TO DO: untested
	 * @param SS_HTTPRequest
	 * @return Array
	 */
	function deleteorder(SS_HTTPRequest $request) {
		if(!$this->CurrentOrderIsInCart()) {
			if($this->currentOrder->canDelete()) {
				$this->currentOrder->delete();
				self::set_message(_t("CartPage.ORDERDELETED", "Order has been deleted."));
			}
		}
		self::set_message(_t("CartPage.ORDERNOTDELETED", "Order could not be deleted."));
		return array();
	}


	/**
	 * Start a new order
	 * @param SS_HTTPRequest
	 * @return Array
	 * TO DO: untested
	 */
	function startneworder(SS_HTTPRequest $request) {
		ShoppingCart::singleton()->clear();
		self::set_message(_t("CartPage.NEWORDERSTARTED", "New order has been started."));
		$this->redirect($this->Link());
		return array();
	}




	/***********************
	 * For use in templates
	 ***********************






	/**
	 * This returns a ArraList, each dataobject has two vars: Title and Link
	 * @return ArraList
	 **/
	function ActionLinks() {
		$this->workOutMessagesAndActions();
		if ($this->actionLinks && $this->actionLinks->count()) {
			return $this->actionLinks;
		}
		return null;
	}

	/**
	 * @return String
	 **/
	function Message() {
		$this->workOutMessagesAndActions();
		if(!$this->message) {
			$sessionCode = EcommerceConfig::get("CartPage_Controller", "session_code");
			if($sessionMessage = Session::get($sessionCode)) {
				$this->message = $sessionMessage;
				Session::set($sessionCode, "");
				Session::clear($sessionCode);
			}
		}
		$field = DBField::create_field("HTMLText", $this->message);
		return $field;
	}

	/**
	 *
	 * @return DataObject | Null - Order
	 **/
	public function Order() {
		return $this->currentOrder;
	}

	/**
	 *
	 * @return Boolean
	 **/
	function CanEditOrder() {
		if($this->currentOrder) {
			if( $this->currentOrder->canEdit()) {
				if($this->currentOrder->getTotalItems()) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Tells you if the order you are viewing at the moment is also in the cart
	 * @return Boolean
	 **/
	function CurrentOrderIsInCart() {
		$viewingRealCurrentOrder = false;
		$realCurrentOrder = ShoppingCart::current_order();
		if($this->currentOrder && $realCurrentOrder) {
			if($realCurrentOrder->ID == $this->currentOrder->ID) {
				$viewingRealCurrentOrder = true;
			}
		}
		return $viewingRealCurrentOrder;
	}



	/**
	 * @var Boolean
	 */
	protected $showCreateAccountForm = false;

	/**
	 * Do we need to show the Create Account Form?
	 * @return Boolean
	 */
	function ShowCreateAccountForm(){
		if(Session::get("CartPageCreateAccountForm")) {
			Session::set("CartPageCreateAccountForm", false);
			return true;
		}
		if(Member::currentUser() || $this->currentOrder->MemberID) {
			return false;
		}
		else {
			Session::set("CartPageCreateAccountForm", true);
			return true;
		}
	}

	/**
	 * Returns the CreateAccountForm
	 * @return ShopAccountForm
	 */
	function CreateAccountForm() {
		return ShopAccountForm::create($this, "CreateAccountForm");
	}

	/**
	 * work out the options for the user
	 **/
	protected function workOutMessagesAndActions(){
		if(!$this->workedOutMessagesAndActions) {
			$this->actionLinks = new ArrayList(array());
			//what order are we viewing?
			$viewingRealCurrentOrder = $this->CurrentOrderIsInCart();
			$currentUserID = Member::currentUserID();

			//Continue Shopping
			if(isset($this->ContinueShoppingLabel) && $this->ContinueShoppingLabel) {
				if($viewingRealCurrentOrder) {
					if($this->isCartPage()) {
						$continueLink = $this->ContinueShoppingLink();
						if($continueLink) {
							$this->actionLinks->push(new ArrayData(array (
								"Title" => $this->ContinueShoppingLabel,
								"Link" => $continueLink
							)));
						}
					}
				}
			}

			//Proceed To CheckoutLabel
			if(isset($this->ProceedToCheckoutLabel) && $this->ProceedToCheckoutLabel) {
				if($viewingRealCurrentOrder) {
					if($this->isCartPage()) {
						$checkoutPageLink = CheckoutPage::find_link();
						if($checkoutPageLink && $this->currentOrder && $this->currentOrder->getTotalItems()) {
							$this->actionLinks->push(new ArrayData(array (
								"Title" => $this->ProceedToCheckoutLabel,
								"Link" => $checkoutPageLink
							)));
						}
					}
				}
			}

			//view account details
			if(isset($this->ShowAccountLabel) && $this->ShowAccountLabel) {
				if($this->isOrderConfirmationPage() || $this->isCartPage()) {
					if(AccountPage::find_link()) {
						if($currentUserID) {
							$this->actionLinks->push(new ArrayData(array (
								"Title" => $this->ShowAccountLabel,
								"Link" => AccountPage::find_link()
							)));
						}
					}
				}
			}
			//go to current order
			if(isset($this->CurrentOrderLinkLabel) && $this->CurrentOrderLinkLabel) {
				if($this->isCartPage()) {
					if(!$viewingRealCurrentOrder) {
						$this->actionLinks->push(new ArrayData(array (
							"Title" => $this->CurrentOrderLinkLabel,
							"Link" => ShoppingCart::current_order()->Link()
						)));
					}
				}
			}



			//Save order - we assume only current ones can be saved.
			if(isset($this->SaveOrderLinkLabel) && $this->SaveOrderLinkLabel) {
				if($viewingRealCurrentOrder) {
					if($currentUserID && $this->currentOrder->MemberID == $currentUserID) {
						if($this->isCartPage()) {
							if($this->currentOrder && $this->currentOrder->getTotalItems() && !$this->currentOrder->IsSubmitted()) {
								$this->actionLinks->push(new ArrayData(array (
									"Title" => $this->SaveOrderLinkLabel,
									"Link" => $this->Link("saveorder")."/".$this->currentOrder->ID."/"
								)));
							}
						}
					}
				}
			}

			//load order
			if(isset($this->LoadOrderLinkLabel) && $this->LoadOrderLinkLabel) {
				if($this->isCartPage() && $this->currentOrder) {
					if(!$viewingRealCurrentOrder) {
						$this->actionLinks->push(new ArrayData(array (
							"Title" => $this->LoadOrderLinkLabel,
							"Link" => $this->Link("loadorder")."/".$this->currentOrder->ID."/"
						)));
					}
				}
			}

			//delete order
			if(isset($this->DeleteOrderLinkLabel) && $this->DeleteOrderLinkLabel) {
				if($this->isCartPage() && $this->currentOrder) {
					if(!$viewingRealCurrentOrder) {
						$this->actionLinks->push(new ArrayData(array (
							"Title" => $this->DeleteOrderLinkLabel,
							"Link" => $this->Link("deleteorder")."/".$this->currentOrder->ID."/"
						)));
					}
				}
			}

			//Start new order
			//Strictly speaking this is only part of the
			//OrderConfirmationPage but we put it here for simplicity's sake
			if(isset($this->StartNewOrderLinkLabel) && $this->StartNewOrderLinkLabel) {
				if($this->isOrderConfirmationPage()) {
					$this->actionLinks->push(new ArrayData(array (
						"Title" => $this->StartNewOrderLinkLabel,
						"Link" => CartPage::new_order_link($this->currentOrder->ID)
					)));
				}
			}

			//copy order
			//Strictly speaking this is only part of the
			//OrderConfirmationPage but we put it here for simplicity's sake
			if(isset($this->CopyOrderLinkLabel) && $this->CopyOrderLinkLabel) {
				if($this->isOrderConfirmationPage() && $this->currentOrder->ID) {
					$this->actionLinks->push(new ArrayData(array (
						"Title" => $this->CopyOrderLinkLabel,
						"Link" => OrderConfirmationPage::copy_order_link($this->currentOrder->ID)
					)));
				}
			}

			//actions from modifiers
			if($this->isOrderConfirmationPage() && $this->currentOrder->ID) {
				$modifiers = $this->currentOrder->Modifiers();
				if($modifiers->count()) {
					foreach($modifiers as $modifier) {
						$array = $modifier->PostSubmitAction();
						if(is_array($array) && count($array)) {
							$this->actionLinks->push(new ArrayData($array));
						}
					}
				}
			}

			//log out
			//Strictly speaking this is only part of the
			//OrderConfirmationPage but we put it here for simplicity's sake
			if(Member::currentUser()) {
				if($this->isOrderConfirmationPage()) {
					$this->actionLinks->push(new ArrayData(array (
						"Title" => _t("CartPage.LOGOUT","log out"),
						"Link" => "/Security/logout/"
					)));
				}
			}

			//no items
			if($this->currentOrder) {
				if(!$this->currentOrder->getTotalItems())  {
					$this->message = $this->NoItemsInOrderMessage;
				}
			}
			else {
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
	 * @return Boolean
	 */
	protected function isCartPage() {
		if(($this->isCheckoutPage()) || ($this->isOrderConfirmationPage())) {
			return false;
		}
		return true;
	}

	/**
	 * Is this a CheckoutPage or is it another type (CartPage / OrderConfirmationPage)?
	 * @return Boolean
	 */
	protected function isCheckoutPage() {if($this->dataRecord instanceOf CheckoutPage){ return true;}else {return false;}}

	/**
	 * Is this a OrderConfirmationPage or is it another type (CartPage / CheckoutPage)?
	 * @return Boolean
	 */
	protected function isOrderConfirmationPage() {if($this->dataRecord instanceOf OrderConfirmationPage){ return true;}else {return false;}}

	/**
	 * Can this page only show Submitted Orders (e.g. OrderConfirmationPage) ?
	 * @return Boolean
	 */
	protected function onlyShowSubmittedOrders() {return false;}

	/**
	 * Can this page only show Unsubmitted Orders (e.g. CartPage) ?
	 * @return Boolean
	 */
	protected function onlyShowUnsubmittedOrders() {return true;}

}



