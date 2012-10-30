<?php
/**
 * @description:
 * The Account Page allows the user to update their details.
 * You do not need to be logged in to the account page in order to view it... If you are not logged in
 * then the account page can be a page to create an account.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: Pages
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class AccountPage extends Page {


	/**
	 * standard SS variable
	 *@var Array
	 */
	static $casting = array(
		"RunningTotal" => "Currency",
		"RunningPaid" => "Currency",
		"RunningOutstanding" => "Currency"
	);

	/**
	 *@var float
	 */
	protected $calculatedTotal = 0;

	/**
	 *@var float
	 */
	protected $calculatedPaid = 0;

	/**
	 *@var float
	 */
	protected $calculatedOutstanding = 0;

	/**
	 *@var Null | DataObjectSet
	 */
	protected $pastOrders = null;

	/**
	 * Standard SS variable
	 * @Var String
	 */
	public static $icon = 'ecommerce/images/icons/AccountPage';

	/**
	 * standard SS method
	 * @return Boolean
	 **/
	function canCreate($member = null) {
		return !DataObject::get_one("AccountPage", "\"ClassName\" = 'AccountPage'");
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
	 * Returns a list of all previous orders for the member / account
	 * @return Null | DataObjectSet
	 */
	function PastOrders(){
		$this->calculatePastOrders();
		return $this->pastOrders;
	}

	/**
	 * casted variable
	 * @return Float (casted as Currency)
	 */
	function getRunningTotal(){return $this->getRunningTotal();}
	function RunningTotal(){
		$this->calculatePastOrders();
		return $this->calculatedTotal;
	}

	/**
	 * casted variable
	 * @return Float (casted as Currency)
	 */
	function getRunningPaid(){return $this->getRunningPaid();}
	function RunningPaid(){
		$this->calculatePastOrders();
		return $this->calculatedPaid;
	}

	/**
	 * casted variable
	 * @return Float (casted as Currency)
	 */
	function getRunningOutstanding(){return $this->getRunningOutstanding();}
	function RunningOutstanding(){
		$this->calculatePastOrders();
		return $this->calculatedOutstanding;
	}


	/**
	 * retrieves previous orders and adds totals to it...
	 * return NULL | DataObjectSet
	 **/
	protected function calculatePastOrders(){
		if(!$this->pastOrders) {
			$this->pastOrders = $this->pastOrdersSelection();
			$this->calculatedTotal = 0;
			$this->calculatedPaid = 0;
			$this->calculatedOutstanding = 0;
			$member = Member::currentUser();
			$canDelete = false;
			if($this->pastOrders) {
				foreach($this->pastOrders as $order) {
					$this->calculatedTotal += $order->Total();
					$this->calculatedPaid += $order->TotalPaid();
					$this->calculatedOutstanding += $order->TotalOutstanding();
				}
			}
		}
		return $this->pastOrders;
	}

	/**
	 *
	 * @return DataObjectSet | NULL
	 */
	protected function pastOrdersSelection(){
		return DataObject::get(
			"Order",
			"\"Order\".\"MemberID\" = ".Member::CurrentMember()->ID."
				AND (\"CancelledByID\" = 0 OR \"CancelledByID\" IS NULL)
				AND \"OrderStep\".\"ShowAsUncompletedOrder\" = 0 ",
			"\"Created\" DESC",
			"INNER JOIN \"OrderStep\" ON \"Order\".\"StatusID\" = \"OrderStep\".\"ID\" "
		);
	}

	/**
	 * tells us if the current page is part of e-commerce.
	 * @return Boolean
	 */
	function IsEcommercePage () {
		return true;
	}

}

class AccountPage_Controller extends Page_Controller {

	//TODO: why do we need this?
	static $allowed_actions = array(
		'MemberForm'
	);

	/**
	 * standard controller function
	 **/
	function init() {
		parent::init();
		if(!$this->AccountMember()) {
			$messages = array(
				'default' => '<p class="message good">' . _t('Account.LOGINFIRST', 'You will need to log in before you can access the account page. ') . '</p>',
				'logInAgain' => _t('Account.LOGINAGAIN', 'You have been logged out. If you would like to log in again, please do so below.')
			);
			Security::permissionFailure($this, $messages);
			return false;
		}
		Requirements::themedCSS("AccountPage");
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

	/**
	 * Returns the current member
	 * @return NULL | Member
	 */
	function AccountMember(){
		return Member::CurrentMember();
	}

}
