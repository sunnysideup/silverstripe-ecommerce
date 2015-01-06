<?php
/**
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
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: control
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class ShoppingCart extends Object{

	/**
	 *
	 * List of names that can be used as session variables.
	 * Also @see ShoppingCart::sessionVariableName
	 * @var Array
	 */
	private static $session_variable_names = array("OrderID", "Messages");

	/**
	 *
	 * This is where we hold the (singleton) Shoppingcart
	 * @var Object (ShoppingCart)
	 */
	private static $singletoncart = null;

	/**
	 * Feedback message to user (e.g. cart updated, could not delete item, someone in standing behind you).
	 *@var Array
	 **/
	protected $messages = array();

	/**
	 * stores a reference to the current order object
	 * @var Object
	 **/
	protected $order = null;

	/**
	 * This variable is set to YES when we actually need an order (i.e. write it)
	 * @var Boolean
	 */
	protected $requireSavedOrder = false;

	/**
	 * Allows access to the cart from anywhere in code.
	 * @return ShoppingCart Object
	 */
	public static function singleton(){
		if(!self::$singletoncart){
			self::$singletoncart = ShoppingCart::create();
		}
		return self::$singletoncart;
	}

	/**
	 * Allows access to the current order from anywhere in the code..
	 * @return ShoppingCart Object
	 */
	public static function current_order() {
		return self::singleton()->currentOrder();
	}


	/**
	 * Gets or creates the current order.
	 * Based on the session ONLY!
	 * IMPORTANT FUNCTION!
	 * @todo - does this need to be public????
	 * @return void
	 */
	public function currentOrder(){
		if(!$this->order) {
			$sessionVariableName = $this->sessionVariableName("OrderID");
			$orderIDFromSession = intval(Session::get($sessionVariableName));
			if($orderIDFromSession > 0) {
				$this->order = Order::get()->byID($orderIDFromSession);
			}
			$member = Member::currentUser();
			if($this->order) {
				//first reason to set to null: it is already submitted
				if($this->order->IsSubmitted()) {
					$this->order = null;
				}
				//second reason to set to null: make sure we have permissions
				elseif(!$this->order->canView()) {
					$this->order = null;
				}
				//logged in, add Member.ID to order->MemberID
				elseif($member && $member->exists()) {
					if($this->order->MemberID != $member->ID) {
						$updateMember = false;
						if(!$this->order->MemberID) {
							$updateMember = true;
						}
						if(!$member->IsShopAdmin()) {
							$updateMember = true;
						}
						if($updateMember) {
							$this->order->MemberID = $member->ID;
							$this->order->write();
						}
					}
					//IF current order has nothing in it AND the member already has an order: use the old one first
					//first, lets check if the current order is worthwhile keeping
					if($this->order->StatusID || $this->order->TotalItems()) {
						//do NOTHING!
					}
					else {
						$firstStep = OrderStep::get()->First();
						//we assume the first step always exists.
						//TODO: what sort order?
						$count = 0;
						while(
							$firstStep &&
							$previousOrderFromMember = Order::get()
								->where("
									\"MemberID\" = ".$member->ID."
									AND (\"StatusID\" = ".$firstStep->ID. " OR \"StatusID\" = 0)
									AND \"Order\".\"ID\" <> ".$this->order->ID
								)
								->First()
						) {
							if($count > 12) {
								break;
							}
							$count++;
							if($previousOrderFromMember && $previousOrderFromMember->canView()) {
								if($previousOrderFromMember->StatusID || $previousOrderFromMember->TotalItems()) {
									$this->order->delete();
									$this->order = $previousOrderFromMember;
									break;
								}
								else {
									$previousOrderFromMember->delete();
								}
							}
						}
					}
				}
			}
			if(!$this->order) {
				if($member) {
					$firstStep = OrderStep::get()->First();
					if($firstStep) {
						$previousOrderFromMember = Order::get()
							->filter(array(
								"MemberID" => $member->ID,
								"StatusID" => array($firstStep->ID, 0)
							))
							->First();
						if($previousOrderFromMember) {
							if($previousOrderFromMember->canView()) {
								$this->order = $previousOrderFromMember;
							}
						}
					}
				}
				if(!$this->order) {
					//here we cleanup old orders, because they should be
					//cleaned at the same rate that they are created...
					if(EcommerceConfig::get("ShoppingCart", "cleanup_every_time")) {
						$cartCleanupTask = EcommerceTaskCartCleanup::create();
						$cartCleanupTask->runSilently();
					}
					//create new order
					$this->order = Order::create();
					if($member) {
						$this->order->MemberID = $member->ID;
					}
					$this->order->write();
				}
				Session::set($sessionVariableName,intval($this->order->ID));
			}
			//member just logged in and is not associated with order yet
			//if you are not logged in but the order belongs to a member then clear the cart.
			// THIS MAY NOT BE CORRECT, BECAUSE THIS MEANS YOU CAN NOT CREATE AN ORDER FOR A USER AND NOT BE LOGGED IN!!!
			elseif($this->order->MemberID && !$member) {
				$this->clear();
				return false;
			}
			if($this->order && $this->order->exists()) {
				$this->order->calculateOrderAttributes($force = false);
			}
			if(!$this->order->SessionID) {
				$this->order->SessionID = session_id();
				$this->order->write();
			}
		}
		return $this->order;
	}

	/**
	 * Allows access to the current order from anywhere in the code..
	 * @return ShoppingCart Object
	 */
	public function Link() {
		$order = self::singleton()->currentOrder();
		if($order) {
			return $order->Link();
		}
	}

	/**
	 * Adds any number of items to the cart.
	 * Returns the order item on succes OR false on failure.
	 * @param DataObject $buyable - the buyable (generally a product) being added to the cart
	 * @param Float $quantity - number of items add.
	 * @param Mixed $parameters - array of parameters to target a specific order item. eg: group=1, length=5
	 *                            if you make it a form, it will save the form into the orderitem
	 * @return false | DataObject (OrderItem)
	 */
	public function addBuyable(BuyableModel $buyable, $quantity = 1, $parameters = array()){
		if(!$buyable) {
			$this->addMessage(_t("Order.ITEMCOULDNOTBEFOUND", "This item could not be found."),'bad');
			return false;
		}
		if(!$buyable->canPurchase()) {
			$this->addMessage(_t("Order.ITEMCOULDNOTBEADDED", "This item is not for sale."),'bad');
			return false;
		}
		$item = $this->prepareOrderItem($buyable, $parameters, $mustBeExistingItem = false);
		$quantity = $this->prepareQuantity($buyable, $quantity);
		if($item && $quantity){ //find existing order item or make one
			$item->Quantity += $quantity;
			$item->write();
			$this->currentOrder()->Attributes()->add($item); //save to current order
			//TODO: distinquish between incremented and set
			//TODO: use sprintf to allow product name etc to be included in message
			if($quantity > 1) {
				$msg = _t("Order.ITEMSADDED", "Items added.");
			}
			else {
				$msg = _t("Order.ITEMADDED", "Item added.");
			}
			$this->addMessage($msg,'good');
		}
		elseif(!$item) {
			$this->addMessage(_t("Order.ITEMNOTFOUND", "Item could not be found.") ,'bad');
		}
		else {
			$this->addMessage(_t("Order.ITEMCOULDNOTBEADDED", "Item could not be added."),'bad');
		}
		return $item;
	}

	/**
	 * Sets quantity for an item in the cart.
	 * @param DataObject $buyable - the buyable (generally a product) being added to the cart
	 * @param Float $quantity - number of items add.
	 * @param Array $parameters - array of parameters to target a specific order item. eg: group=1, length=5
	 * @return false | DataObject (OrderItem)
	 */
	function setQuantity(BuyableModel $buyable, $quantity, Array $parameters = array()) {
		$item = $this->prepareOrderItem($buyable, $parameters, $mustBeExistingItem = false);
		$quantity = $this->prepareQuantity($buyable, $quantity);
		if($item) {
			$item->Quantity = $quantity; //remove quantity
			$item->write();
			$this->addMessage(_t("Order.ITEMUPDATED", "Item updated."),'good');
			return $item;
		}
		else {
			$this->addMessage(_t("Order.ITEMNOTFOUND", "Item could not be found.") ,'bad');
		}
		return false;
	}

	/**
	 * Removes any number of items from the cart.
	 * @param DataObject $buyable - the buyable (generally a product) being added to the cart
	 * @param Float $quantity - number of items add.
	 * @param Array $parameters - array of parameters to target a specific order item. eg: group=1, length=5
	 * @return false | DataObject (OrderItem)
	 */
	public function decrementBuyable(BuyableModel $buyable,$quantity = 1, Array $parameters = array()){
		$item = $this->prepareOrderItem($buyable, $parameters, $mustBeExistingItem = false);
		$quantity = $this->prepareQuantity($buyable, $quantity);
		if($item) {
			$item->Quantity -= $quantity; //remove quantity
			if($item->Quantity < 0 ) {
				$item->Quantity = 0;
			}
			$item->write();
			if($quantity > 1) {
				$msg = _t("Order.ITEMSREMOVED", "Items removed.");
			}
			else {
				$msg = _t("Order.ITEMREMOVED", "Item removed.");
			}
			$this->addMessage($msg ,'good');
			return $item;
		}
		else {
			$this->addMessage(_t("Order.ITEMNOTFOUND", "Item could not be found.") ,'bad');
		}
		return false;
	}

	/**
	 * Delete item from the cart.
	 * @param OrderItem $buyable - the buyable (generally a product) being added to the cart
	 * @param Array $parameters - array of parameters to target a specific order item. eg: group=1, length=5
	 * @return boolean | item - successfully removed
	 */
	function deleteBuyable(BuyableModel $buyable, Array $parameters = array()) {
		$item = $this->prepareOrderItem($buyable, $parameters, $mustBeExistingItem = true);
		if($item) {
			$this->currentOrder()->Attributes()->remove($item);
			$item->delete();
			$item->destroy();
			$this->addMessage(_t("Order.ITEMCOMPLETELYREMOVED", "Item removed from cart."),'good');
			return $item;
		}
		else {
			$this->addMessage(_t("Order.ITEMNOTFOUND", "Item could not be found.") ,'bad');
			return false;
		}
	}

	/**
	 * Checks and prepares variables for a quantity change (add, edit, remove) for an Order Item.
	 * @param DataObject $buyable - the buyable (generally a product) being added to the cart
	 * @param Float $quantity - number of items add.
	 * @param Boolean $mustBeExistingItems - if false, the Order Item gets created if it does not exist - if TRUE the order item is searched for and an error shows if there is no Order item.
	 * @param Array $parameters - array of parameters to target a specific order item. eg: group=1, length=5*
	 * @return boolean | DataObject ($orderItem)
	 */
	protected function prepareOrderItem(BuyableModel $buyable, $parameters = array(), $mustBeExistingItem = true) {
		if(!$buyable) {
			user_error("No buyable was provided", E_USER_WARNING);
		}
		if(!$buyable->canPurchase()) {
			$item = $this->getExistingItem($buyable,$parameters);
			if($item && $item->exists()) {
				$item->delete();
				$item->destroy();
			}
			return false;
		}
		$item = null;
		if($mustBeExistingItem) {
			$item = $this->getExistingItem($buyable,$parameters);
		}
		else {
			$item = $this->findOrMakeItem($buyable,$parameters); //find existing order item or make one
		}
		if(!$item){//check for existence of item
			return false;
		}
		return $item;
	}

	/**
	 * @todo: what does this method do???
	 * @return Integer
	 * @param DataObject ($buyable)
	 * @param Float $quantity
	 */
	protected function prepareQuantity(BuyableModel $buyable, $quantity) {
		$quantity = round($quantity, $buyable->QuantityDecimals());
		if($quantity < 0 || (!$quantity && $quantity !== 0)) {
			$this->addMessage(_t("Order.INVALIDQUANTITY", "Invalid quantity."),'warning');
			return false;
		}
		return $quantity;
	}

	/**
	 * Helper function for making / retrieving order items.
	 * we do not need things like "canPurchase" here, because that is with the "addBuyable" method.
	 * NOTE: does not write!
	 * @param DataObject $buyable
	 * @param Array $parameters
	 * @return OrderItem
	 */
	public function findOrMakeItem(BuyableModel $buyable, Array $parameters = array()){
		if($item = $this->getExistingItem($buyable,$parameters)){
			//do nothing
		}
		else {
			//otherwise create a new item
			if(!($buyable instanceof BuyableModel)) {
				$this->addMessage(_t("ShoppingCart.ITEMNOTFOUND", "Item is not buyable.") ,'bad');
				return false;
			}
			$className = $buyable->classNameForOrderItem();
			$item = new $className();
			if($order = $this->currentOrder()) {
				$item->OrderID = $order->ID;
				$item->BuyableID = $buyable->ID;
				$item->BuyableClassName = $buyable->ClassName;
				if(isset($buyable->Version)) {
					$item->Version = $buyable->Version;
				}
			}
		}
		if($parameters) {
			$item->Parameters = $parameters;
		}
		return $item;
	}

	/**
	 * submit the order so that it is no longer available
	 * in the cart but will continue its journey through the
	 * order steps.
	 * @return Boolean
	 */
	public function submit() {
		$this->currentOrder()->tryToFinaliseOrder();
		$this->clear();
		//little hack to clear static memory
		OrderItem::reset_price_has_been_fixed();
		//we cleanup the old orders here so that we immediately know if there is a problem.
		return true;
	}

	/**
	 * @return Boolean
	 */
	public function save(){
		$this->currentOrder()->write();
		$this->addMessage(_t("Order.ORDERSAVED", "Order Saved."),'good');
		return true;
	}

	/**
	 * Clears the cart contents completely by removing the orderID from session, and
	 * thus creating a new cart on next request.
	 * @return Boolean
	 */
	public function clear(){
		//we keep this here so that a flush can be added...
		set_time_limit(1200);
		self::$singletoncart = null;
		$this->order = null;
		$this->messages = array();
		foreach(self::$session_variable_names as $name){
			$sessionVariableName = $this->sessionVariableName($name);
			Session::set($sessionVariableName, null);
			Session::clear($sessionVariableName);
			Session::save();
		}
		$memberID = Intval(Member::currentUserID());
		if($memberID) {
			$orders = Order::get()->filter(array("MemberID" => $memberID));
			if($orders && $orders->count()) {
				foreach($orders as $order) {
					if(! $order->IsSubmitted()) {
						$order->delete();
					}
				}
			}
		}
		return true;
	}

	/**
	 * alias for clear
	 */
	public function reset(){
		return $this->clear();
	}

	/**
	 * Removes a modifier from the cart
	 * It does not actually remove it, but it just
	 * sets it as "removed", to avoid that it is being
	 * added again.
	 * @param OrderModifier $modifier
	 * @return Boolean
	 */
	public function removeModifier(OrderModifier $modifier){
		$modifier = (is_numeric($modifier)) ? OrderModifier::get()->byID($modifier) : $modifier;
		if(!$modifier){
			$this->addMessage(_t("Order.MODIFIERNOTFOUND", "Modifier could not be found."),'bad');
			return false;
		}
		if(!$modifier->CanBeRemoved()) {
			$this->addMessage(_t("Order.MODIFIERCANNOTBEREMOVED", "Modifier can not be removed."),'bad');
			return false;
		}
		$modifier->HasBeenRemoved = 1;
		$modifier->onBeforeRemove();
		$modifier->write();
		$modifier->onAfterRemove();
		$this->addMessage(_t("Order.MODIFIERREMOVED", "Removed."), 'good');
		return true;
	}

	/**
	 * Removes a modifier from the cart
	 * @param Int/ OrderModifier
	 * @return Boolean
	 */
	public function addModifier($modifier){
		if(is_numeric($modifier)) {
			$modifier = OrderModifier::get()->byID($modifier);
		}
		elseif(!(is_a($modifier, Object::getCustomClass("OrderModifier")))) {
			user_error("Bad parameter provided to ShoppingCart::addModifier", E_USER_WARNING);
		}
		if(!$modifier){
			$this->addMessage(_t("Order.MODIFIERNOTFOUND", "Modifier could not be found."),'bad');
			return false;
		}
		$modifier->HasBeenRemoved = 0;
		$modifier->write();
		$this->addMessage(_t("Order.MODIFIERREMOVED", "Added."), 'good');
		return true;
	}

	/**
	 * Sets an order as the current order.
	 * @param Int | Order $order
	 * @return Boolean
	 */
	public function loadOrder($order){
		//TODO: how to handle existing order
		//TODO: permission check - does this belong to another member? ...or should permission be assumed already?
		if(is_numeric($order)) {
			 $this->order = Order::get()->byID($order);
		}
		elseif(is_a($order, Object::getCustomClass("Order"))) {
			$this->order = $order;
		}
		else {
			user_error("Bad order provided as parameter to ShoppingCart::loadOrder()");
		}
		if($this->order){
			if($this->order->canView()) {
				$this->order->init(true);
				$sessionVariableName = $this->sessionVariableName("OrderID");
				Session::set($sessionVariableName, $this->order->ID);
				$this->addMessage(_t("Order.LOADEDEXISTING", "Order loaded."),'good');
				return true;
			}
			else {
				$this->addMessage(_t("Order.NOPERMISSION", "You do not have permission to view this order."),'bad');
				return false;
			}
		}
		else {
			$this->addMessage(_t("Order.NOORDER", "Order can not be found."),'bad');
			return false;
		}
	}

	/**
	 * NOTE: tried to copy part to the Order Class - but that was not much of a go-er.
	 * @param Int | Order $order
	 * @return DataObject(Order)
	 **/
	public function copyOrder($oldOrder) {
		if(is_numeric($oldOrder)) {
			 $oldOrder = Order::get()->byID(intval($oldOrder));
		}
		elseif(is_a($oldOrder, Object::getCustomClass("Order"))) {
			//$oldOrder = $oldOrder;
		}
		else {
			user_error("Bad order provided as parameter to ShoppingCart::loadOrder()");
		}
		if($oldOrder){
			if($oldOrder->canView()) {
				$newOrder = Order::create();
				//copying fields.
				$newOrder->UseShippingAddress = $oldOrder->UseShippingAddress;
				//important to set it this way...
				$newOrder->setCurrency($oldOrder->CurrencyUsed());
				$newOrder->MemberID = $oldOrder->MemberID;
				//load the order
				$newOrder->write();
				$this->loadOrder($newOrder);
				$items = OrderItem::get()
					->filter(array("OrderID" => $oldOrder->ID));
				if($items->count()) {
					foreach($items as $item) {
						$buyable = $item->Buyable($current = true);
						if($buyable->canPurchase()) {
							$this->addBuyable($buyable, $item->Quantity);
						}
					}
				}
				$newOrder->CreateOrReturnExistingAddress("BillingAddress");
				$newOrder->CreateOrReturnExistingAddress("ShippingAddress");
				$newOrder->write();
				$this->addMessage(_t("Order.ORDERCOPIED", "Order has been copied."),'good');
				return true;
			}
			else {
				$this->addMessage(_t("Order.NOPERMISSION", "You do not have permission to view this order."),'bad');
				return false;
			}
		}
		else {
			$this->addMessage(_t("Order.NOORDER", "Order can not be found."),'bad');
			return false;
		}
	}

	/**
	 * sets country in order so that modifiers can be recalculated, etc...
	 * @param String - $countryCode
	 * @return Boolean
	 **/
	public function setCountry($countryCode) {
		if(EcommerceCountry::code_allowed($countryCode)) {
			$this->currentOrder()->SetCountryFields($countryCode);
			$this->addMessage(_t("Order.UPDATEDCOUNTRY", "Updated country."),'good');
			return true;
		}
		else {
			$this->addMessage(_t("Order.NOTUPDATEDCOUNTRY", "Could not update country."),'bad');
			return false;
		}
	}

	/**
	 * sets region in order so that modifiers can be recalculated, etc...
	 * @param Integer | String - $regionID you can use the ID or the code.
	 * @return Boolean
	 **/
	public function setRegion($regionID) {
		if(EcommerceRegion::regionid_allowed($regionID)) {
			$this->currentOrder()->SetRegionFields($regionID);
			$this->addMessage(_t("ShoppingCart.REGIONUPDATED", "Region updated."),'good');
			return true;
		}
		else {
			$this->addMessage(_t("ORDER.NOTUPDATEDREGION", "Could not update region."),'bad');
			return false;
		}
	}

	/**
	 * sets the display currency for the cart.
	 * @param String $currencyCode
	 * @return Boolean
	 **/
	public function setCurrency($currencyCode) {
		$currency = EcommerceCurrency::get_one_from_code($currencyCode);
		if($currency) {
			if($this->currentOrder()->MemberID) {
				$member = $this->currentOrder()->Member();
				if($member && $member->exists()) {
					$member->SetPreferredCurrency($currency);
				}
			}
			$this->currentOrder()->UpdateCurrency($currency);
			$msg = _t("Order.CURRENCYUPDATED", "Currency updated.");
			$this->addMessage($msg ,'good');
			return true;
		}
		else {
			$msg = _t("Order.CURRENCYCOULDNOTBEUPDATED", "Currency could not be updated.");
			$this->addMessage($msg ,'bad');
			return false;
		}
	}

	/**
	 * Produces a debug of the shopping cart.
	 */
	public function debug(){
		if(Director::isDev() || Permission::check("ADMIN")){
			debug::show($this->currentOrder());

			echo "<hr /><hr /><hr /><hr /><hr /><hr /><h1>Country</h1>";
			echo "GEOIP Country: ".EcommerceCountry::get_country_from_ip()."<br />";
			echo "Calculated Country Country: ".EcommerceCountry::get_country()."<br />";

			echo "<blockquote><blockquote><blockquote><blockquote>";

			echo "<hr /><hr /><hr /><hr /><hr /><hr /><h1>Items</h1>";
			$items = $this->currentOrder()->Items();
			echo $items->sql();
			echo "<hr />";
			if($items->count()) {
				foreach($items as $item) {
					Debug::show($item);
				}
			}
			else {
				echo "<p>there are no items for this order</p>";
			}

			echo "<hr /><hr /><hr /><hr /><hr /><hr /><h1>Modifiers</h1>";
			$modifiers = $this->currentOrder()->Modifiers();
			if($modifiers->count()) {
				foreach($modifiers as $modifier) {
					Debug::show($modifier);
				}
			}
			else {
				echo "<p>there are no modifiers for this order</p>";
			}

			echo "<hr /><hr /><hr /><hr /><hr /><hr /><h1>Addresses</h1>";
			$billingAddress = $this->currentOrder()->BillingAddress();
			if($billingAddress && $billingAddress->exists()) {
				Debug::show($billingAddress);
			}
			else {
				echo "<p>there is no billing address for this order</p>";
			}
			$shippingAddress = $this->currentOrder()->ShippingAddress();
			if($shippingAddress && $shippingAddress->exists()) {
				Debug::show($shippingAddress);
			}
			else {
				echo "<p>there is no shipping address for this order</p>";
			}

			$currencyUsed = $this->currentOrder()->CurrencyUsed();
			if($currencyUsed && $currencyUsed->exists()) {
				echo "<hr /><hr /><hr /><hr /><hr /><hr /><h1>Currency</h1>";
				Debug::show($currencyUsed);
			}

			$cancelledBy = $this->currentOrder()->CancelledBy();
			if($cancelledBy && $cancelledBy->exists()) {
				echo "<hr /><hr /><hr /><hr /><hr /><hr /><h1>Cancelled By</h1>";
				Debug::show($cancelledBy);
			}

			$logs = $this->currentOrder()->OrderStatusLogs();
			if($logs && $logs->count()) {
				echo "<hr /><hr /><hr /><hr /><hr /><hr /><h1>Logs</h1>";
				foreach($logs as $log) {
					Debug::show($log);
				}
			}

			$payments = $this->currentOrder()->Payments();
			if($payments  && $payments->count()) {
				echo "<hr /><hr /><hr /><hr /><hr /><hr /><h1>Payments</h1>";
				foreach($payments as $payment) {
					Debug::show($payment);
				}
			}

			$emails = $this->currentOrder()->Emails();
			if($emails && $emails->count()) {
				echo "<hr /><hr /><hr /><hr /><hr /><hr /><h1>Emails</h1>";
				foreach($emails as $email) {
					Debug::show($email);
				}
			}

			echo "</blockquote></blockquote></blockquote></blockquote>";
		}
		else {
			echo "Please log in as admin first";
		}
	}

	/**
	 * Stores a message that can later be returned via ajax or to $form->sessionMessage();
	 *
	 * @param $message - the message, which could be a notification of successful action, or reason for failure
	 * @param $type - please use good, bad, warning
	 */
	public function addMessage($message, $status = 'good'){
		//clean status for the lazy programmer
		//TODO: remove the awkward replace
		$status = strtolower($status);
		str_replace(array("success", "failure"), array("good", "bad"), $status);
		$statusOptions = array("good", "bad", "warning");
		if(!in_array($status, $statusOptions)) {
			user_error("Message status should be one of the following: ".implode(",", $statusOptions), E_USER_NOTICE);
		}
		$this->messages[] = array(
			'Message' => $message,
			'Type' => $status
		);
	}

	/*******************************************************
	* HELPER FUNCTIONS
	*******************************************************/


	/**
	 * Gets an existing order item based on buyable and passed parameters
	 *
	 * @param DataObject $buyable
	 * @param Array $parameters
	 * @return OrderItem or null
	 */
	protected function getExistingItem(BuyableModel $buyable, Array $parameters = array()){
		$filterString = $this->parametersToSQL($parameters);
		if($order = $this->currentOrder()) {
			$orderID = $order->ID;
			$obj = OrderItem::get()
				->where(
					" \"BuyableClassName\" = '".$buyable->ClassName."' AND
					\"BuyableID\" = ".$buyable->ID." AND
					\"OrderID\" = ".$orderID." ".
					$filterString
				)
				->First();
			return $obj;
		}
	}

	/**
	 * Removes parameters that aren't in the default array, merges with default parameters, and converts raw2SQL.
	 * @param Array $parameters
	 * @return cleaned array
	 */
	protected function cleanParameters(Array $params = array()){
		$defaultParamFilters = EcommerceConfig::get("ShoppingCart", "default_param_filters");
		$newarray = array_merge(array(),$defaultParamFilters); //clone array
		if(!count($newarray)) {
			return array(); //no use for this if there are not parameters defined
		}
		foreach($newarray as $field => $value){
			if(isset($params[$field])){
				$newarray[$field] = Convert::raw2sql($params[$field]);
			}
		}
		return $newarray;
	}

	/**
	 * @param Array $parameters
	 * Converts parameter array to SQL query filter
	 */
	protected function parametersToSQL(Array $parameters = array()){
		$defaultParamFilters = EcommerceConfig::get("ShoppingCart", "default_param_filters");
		if(!count($defaultParamFilters)) {
			return ""; //no use for this if there are not parameters defined
		}
		$cleanedparams = $this->cleanParameters($parameters);
		$outputArray = array();
		foreach($cleanedparams as $field => $value){
			$outputarray[$field] = "\"".$field."\" = ".$value;
		}
		if(count($outputArray)) {
			return implode(" AND ",$outputArray);
		}
		return "";
	}

	/*******************************************************
	* UI MESSAGE HANDLING
	*******************************************************/


	/**
	 * Retrieves all good, bad, and ugly messages that have been produced during the current request.
	 * @return array of messages
	 */
	function getMessages(){
		$sessionVariableName = $this->sessionVariableName("Messages");
		//get old messages
		$messages = unserialize(Session::get($sessionVariableName));
		//clear old messages
		Session::clear($sessionVariableName, "");
		//set to form????
		if($messages && count($messages)) {
			$this->messages = array_merge($messages, $this->messages);
		}
		return $this->messages;
	}

	/**
	 *Saves current messages in session for retrieving them later.
	 *@return array of messages
	 */
	protected function StoreMessagesInSession(){
		$sessionVariableName = $this->sessionVariableName("Messages");
		Session::set($sessionVariableName, serialize($this->messages));
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
	 *
	 * @param String $message
	 * @param String $status
	 * @param Form $form
	 * @returns String (JSON)
	 */
	public function setMessageAndReturn($message = "", $status = "", Form $form = null){
		if($message && $status) {
			$this->addMessage($message,$status);
		}
		//TODO: handle passing back multiple messages

		if(Director::is_ajax()){
			$responseClass = EcommerceConfig::get("ShoppingCart", "response_class");
			$obj = new $responseClass();
			return $obj->ReturnCartData($this->getMessages());
		}
		else {
			//TODO: handle passing a message back to a form->sessionMessage
			$this->StoreMessagesInSession();
			if($form) {
				//lets make sure that there is an order
				$this->currentOrder();
				//nowe we can (re)calculate the order
				$this->order->calculateOrderAttributes($force = false);
				$form->sessionMessage($message,$status);
				//let the form controller do the redirectback or whatever else is needed.
			}
			else {
				if(empty($_REQUEST["BackURL"])) {
					Controller::curr()->redirectBack();
				}
				else {
					Controller::cur()->redirect(urldecode($_REQUEST["BackURL"]));
				}
			}
			return;
		}
	}

	/**
	 *
	 * @return EcommerceDBConfig
	 */
	protected function EcomConfig(){
		return EcommerceDBConfig::current_ecommerce_db_config();
	}


	/**
	 * Return the name of the session variable that should be used.
	 * @param String $name
	 * @return String
	 */
	protected function sessionVariableName($name = "") {
		if(!in_array($name, self::$session_variable_names)) {
			user_error("Tried to set session variable $name, that is not in use", E_USER_NOTICE);
		}
		$sessionCode = EcommerceConfig::get("ShoppingCart", "session_code");
		return $sessionCode."_".$name;
	}

}


