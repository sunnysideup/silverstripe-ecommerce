<?php
/**
 * @description:
 * The Account Page allows the user to update their details.
 * You do not need to be logged in to the account page in order to view it... If you are not logged in
 * then the account page can be a page to create an account.

 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: pages
 *
 **/

class AccountPage extends Page {

	public static $icon = 'ecommerce/images/icons/AccountPage';

	/**
	 * standard SS method
	 *@return Boolean
	 **/
	function canCreate($member = null) {
		return !DataObject :: get_one("AccountPage", "\"ClassName\" = 'AccountPage'");
	}

	/**
	 * Returns the link to the AccountPage on this site
	 * @return String (URLSegment)
	 */
	public static function find_link() {
		if($page = DataObject::get_one('AccountPage', "\"ClassName\" = 'AccountPage'")) {
			return $page->Link();
		}
	}


	/**
	 *@return DataObjectSet or Null - DataObjectSet contains DataObjects. Each DataObject has two params: Heading and Orders
	 * we use this format so that we can easily iterate through all the orders in the template.
	 * TO DO: make this more standardised.
	 * TO DO: create Object called OrdersDataObject with standardised fields (Title, Orders, etc...)
	 **/
	public function AllMemberOrders() {
		$dos = new DataObjectSet();
		$doCurrentOrders = $this->putTogetherOrderDataObjectSet("ShoppingCartOrders", _t("Account.CURRENTORDER", "Current Shopping Cart"));
		if($doCurrentOrders){
			$dos->push($doCurrentOrders);
		}
		$incompleteOrders = $this->putTogetherOrderDataObjectSet("IncompleteOrders", _t("Account.INCOMPLETEORDERS", "Incomplete Orders"));
		if($incompleteOrders){
			$dos->push($incompleteOrders);
		}
		$inProcessOrders = $this->putTogetherOrderDataObjectSet("InProcessOrders", _t("Account.INPROCESSORDERS", "In Process Orders"));
		if($inProcessOrders){
			$dos->push($inProcessOrders);
		}
		$completeOrders = $this->putTogetherOrderDataObjectSet("CompleteOrders", _t("Account.COMPLETEORDERS", "Completed Orders"));
		if($completeOrders){
			$dos->push($completeOrders);
		}
		if($dos->count()) {
			return $dos;
		}
		return null;
	}

	/**
	 *
	 *
	 *@return DataObject - returns a dataobject with two variables: Orders and Heading.... Orders contains a dataobjectset of orders, Heading is the name of the Orders.
	 **/
	protected function putTogetherOrderDataObjectSet($method, $title) {
		$dos = new DataObject();
		$dos->Orders = $this->$method();
		if($dos->Orders) {
			$dos->Heading = DBField::create($className = "TextField", $title);
		}
		return null;
	}

	/**
	 * Returns all {@link Order} records for this
	 * member that are incomplete.
	 *
	 * @return DataObjectSet | null
	 */
	function ShoppingCartOrders() {
		$order = ShoppingCart::current_order();
		if($order) {
			$dos = new DataObjectSet();
			$dos->push($order);
			return $dos;
		}
		return null;
	}

	/**
	 * Returns all {@link Order} records for this
	 * member that are incomplete.
	 *
	 * @return DataObjectSet | null
	 */
	function IncompleteOrders() {
		$statusFilter = "\"OrderStep\".\"ShowAsUncompletedOrder\" = 1 ";
		return $this->otherOrderSQL($statusFilter);
	}

	/**
	 * Returns all {@link Order} records for this
	 * member that are completed.
	 *
	 * @return DataObjectSet | null
	 */
	function InProcessOrders() {
		$statusFilter = "\"OrderStep\".\"ShowAsInProcessOrder\" = 1";
		return $this->otherOrderSQL($statusFilter);
	}

	/**
	 * Returns all {@link Order} records for this
	 * member that are completed.
	 *
	 * @return DataObjectSet | null
	 */
	function CompleteOrders() {
		$statusFilter = "\"OrderStep\".\"ShowAsCompletedOrder\" = 1";
		return $this->otherOrderSQL($statusFilter);
	}

	/**
	 *@return DataObjectSet  | null
	 **/
	protected function otherOrderSQL ($statusFilter) {
		$memberID = Member::currentUserID();
		if($memberID) {
			$orders = DataObject::get(
				$className = 'Order',
				$where = "\"Order\".\"MemberID\" = '$memberID' AND ".$statusFilter." AND \"CancelledByID\" = 0",
				$sort = "\"Created\" DESC",
				$join = "INNER JOIN \"OrderStep\" ON \"Order\".\"StatusID\" = \"OrderStep\".\"ID\""
			);
			if($orders) {
				foreach($orders as $order) {
					if(!$order->Items() || !$order->canView()) {
						$orders->remove($order);
					}
					elseif($order->IsSubmitted())  {
						$order->tryToFinaliseOrder();
					}
				}
				return $orders;
			}
		}
		return null;
	}



}

class AccountPage_Controller extends Page_Controller {

	static $allowed_actions = array(
		'MemberForm'
	);

	/**
	 * standard controller function
	 **/
	function init() {
		parent::init();
		if(!Member::CurrentMember()) {
			$messages = array(
				'default' => '<p class="message good">' . _t('Account.LOGINFIRST', 'You will need to log in before you can access the account page. If you are not registered, you will not be able to access it until you place your first order, otherwise please enter your details below.') . '</p>',
				'logInAgain' => _t('Account.LOGINAGAIN', 'You have been logged out. If you would like to log in again, please do so below.')
			);
			Security::permissionFailure($this, $messages);
			return false;
		}
		Requirements::themedCSS("AccountPage");
	}


	/**
	 * tells us if the current page is part of e-commerce.
	 * @return Boolean
	 */
	function IsEcommercePage () {
		return true;
	}

	/**
	 * Return a form allowing the user to edit
	 * their details with the shop.
	 *
	 * @return ShopAccountForm
	 */
	function MemberForm() {
		return new ShopAccountForm($this, 'MemberForm');
	}

	protected $total, $paid, $outstanding;

	function PastOrders(){
		$pastOrders = DataObject::get("Order", "\"Order\".\"MemberID\" = ".Member::CurrentMember()->ID, "\"Created\" ASC", "INNER JOIN OrderAttribute ON OrderAttribute.OrderID = \"Order\".\"ID\" INNER JOIN OrderItem ON OrderItem.ID = OrderAttribute.ID");
		$this->total = 0;
		$this->paid = 0;
		$this->outstanding = 0;
		if($pastOrders) {
			foreach($pastOrders as $order) {
				$this->total += $order->Total;
				$this->paid += $order->TotalPaid;
				$this->outstanding += $order->TotalOutstanding;

			}
		}
		return $pastOrders;
	}

	function RunningTotal(){
		return DBField::create("Currency", $this->total, "total")->Nice();
	}
	function RunningPaid(){
		return DBField::create("Currency", $this->paid, "paid")->Nice();
	}
	function RunningOutstanding(){
		return DBField::create("Currency", $this->outstanding, "outstanding")->Nice();
	}

}
