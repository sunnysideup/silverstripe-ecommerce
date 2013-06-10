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
	 *@var DataList
	 */
	protected $pastOrders = null;

	/**
	 * Standard SS variable
	 * @Var String
	 */
	public static $icon = 'ecommerce/images/icons/AccountPage';

	/**
	 * Standard SS function, we only allow for one AccountPage to exist
	 * but we do allow for extensions to exist at the same time.
	 * @param Member $member
	 * @return Boolean
	 **/
	function canCreate($member = null) {
		return AccountPage::get()->filter(array("ClassName" => "AccountPage"))->Count() ? false : true;
	}

	/**
	 * standard SS variable
	 * @Var String
	 */
	public static $singular_name = "Account Page";
		function i18n_singular_name() { return _t("AccountPage.SINGULARNAME", "Account Page");}

	/**
	 * standard SS variable
	 * @Var String
	 */
	public static $plural_name = "Account Pages";
		function i18n_plural_name() { return _t("AccountPage.PLURALNAME", "Account Pages");}

	/**
	 * Standard SS variable.
	 * @var String
	 */
	public static $description = "A page where the customer can view all their orders and update their details.";

	/**
	 * Returns the link to the AccountPage on this site
	 * @return String (URLSegment)
	 */
	public static function find_link() {
		$page = AccountPage::get()
			->filter(array("ClassName" => "AccountPage"))
			->First();
		if($page) {
			return $page->Link();
		}
	}

	/**
	 * Returns a list of all previous orders for the member / account
	 * @return DataList
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
	 * return DataList
	 **/
	protected function calculatePastOrders(){
		if(!$this->pastOrders) {
			$this->pastOrders = $this->pastOrdersSelection();
			$this->calculatedTotal = 0;
			$this->calculatedPaid = 0;
			$this->calculatedOutstanding = 0;
			$member = Member::currentUser();
			$canDelete = false;
			if($this->pastOrders->count()) {
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
	 * @return DataList (Orders)
	 */
	protected function pastOrdersSelection(){
		$memberID = intval(Member::currentUserID());
		if(!$memberID) {
			//set t
			$memberID = RAND(0, 1000000) * -1;
		}
		if($memberID) {
			return Order::get()
				->where(
					"\"Order\".\"MemberID\" = ".$memberID."
					AND (\"CancelledByID\" = 0 OR \"CancelledByID\" IS NULL)")
				->innerJoin("OrderStep", "\"Order\".\"StatusID\" = \"OrderStep\".\"ID\"");
		}
		return 0;
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
		if(!$this->AccountMember() && 1 == 2) {
			$messages = array(
				'default' => '<p class="message good">' . _t('Account.LOGINFIRST', 'You will need to log in before you can access the account page. ') . '</p>',
				'logInAgain' => _t('Account.LOGINAGAIN', 'You have been logged out. If you would like to log in again, please do so below.')
			);
			Security::permissionFailure($this, $messages);
			return false;
		}
		Requirements::themedCSS("AccountPage", 'ecommerce');
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
		return Member::currentUser();
	}

}
