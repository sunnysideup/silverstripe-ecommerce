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
class AccountPage extends Page
{

    /**
     * standard SS variable.
     *
     *@var array
     */
    private static $casting = array(
        'RunningTotal' => 'Currency',
        'RunningPaid' => 'Currency',
        'RunningOutstanding' => 'Currency',
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
     * Standard SS variable.
     *
     * @Var String
     */
    private static $icon = 'ecommerce/images/icons/AccountPage';

    /**
     * Standard SS function, we only allow for one AccountPage to exist
     * but we do allow for extensions to exist at the same time.
     *
     * @param Member $member
     *
     * @return bool
     **/
    public function canCreate($member = null)
    {
        return AccountPage::get()->filter(array('ClassName' => 'AccountPage'))->Count() ? false : $this->canEdit($member);
    }

    /**
     * Shop Admins can edit.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canEdit($member = null)
    {
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        return $this->canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canPublish($member = null)
    {
        return $this->canEdit($member);
    }

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $singular_name = 'Account Page';
    public function i18n_singular_name()
    {
        return _t('AccountPage.SINGULARNAME', 'Account Page');
    }

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $plural_name = 'Account Pages';
    public function i18n_plural_name()
    {
        return _t('AccountPage.PLURALNAME', 'Account Pages');
    }

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A page where the customer can view all their orders and update their details.';

    /**
     * Returns the link to the AccountPage on this site.
     * @param string $action [optional]
     * @return string (URLSegment)
     */
    public static function find_link($action = null)
    {
        $page = DataObject::get_one(
            'AccountPage',
            array('ClassName' => 'AccountPage')
        );
        if ($page) {
            return $page->Link($action);
        }
    }

    /**
     * Returns a list of all previous orders for the member / account.
     *
     * @return DataList
     */
    public function PastOrders()
    {
        $this->calculatePastOrders();

        return $this->pastOrders;
    }

    /**
     * casted variable.
     *
     * @return float (casted as Currency)
     */
    public function getRunningTotal()
    {
        return $this->getRunningTotal();
    }
    public function RunningTotal()
    {
        $this->calculatePastOrders();

        return $this->calculatedTotal;
    }

    /**
     * casted variable.
     *
     * @return float (casted as Currency)
     */
    public function getRunningPaid()
    {
        return $this->getRunningPaid();
    }
    public function RunningPaid()
    {
        $this->calculatePastOrders();

        return $this->calculatedPaid;
    }

    /**
     * casted variable.
     *
     * @return float (casted as Currency)
     */
    public function getRunningOutstanding()
    {
        return $this->getRunningOutstanding();
    }
    public function RunningOutstanding()
    {
        $this->calculatePastOrders();

        return $this->calculatedOutstanding;
    }

    /**
     * retrieves previous orders and adds totals to it...
     * return DataList.
     **/
    protected function calculatePastOrders()
    {
        if (!$this->pastOrders) {
            $this->pastOrders = $this->pastOrdersSelection();
            $this->calculatedTotal = 0;
            $this->calculatedPaid = 0;
            $this->calculatedOutstanding = 0;
            $member = Member::currentUser();
            $canDelete = false;
            if ($this->pastOrders->count()) {
                foreach ($this->pastOrders as $order) {
                    $this->calculatedTotal += $order->Total();
                    $this->calculatedPaid += $order->TotalPaid();
                    $this->calculatedOutstanding += $order->TotalOutstanding();
                }
            }
        }

        return $this->pastOrders;
    }

    /**
     * @return DataList (Orders)
     */
    protected function pastOrdersSelection()
    {
        $memberID = intval(Member::currentUserID());
        if (!$memberID) {
            //set t
            $memberID = RAND(0, 1000000) * -1;
        }
        if ($memberID) {
            return Order::get()
                ->where(
                    '"Order"."MemberID" = '.$memberID.'
                    AND ("CancelledByID" = 0 OR "CancelledByID" IS NULL)')
                ->innerJoin('OrderStep', '"Order"."StatusID" = "OrderStep"."ID"');
        }

        return 0;
    }

    /**
     * tells us if the current page is part of e-commerce.
     *
     * @return bool
     */
    public function IsEcommercePage()
    {
        return true;
    }
}

class AccountPage_Controller extends Page_Controller
{
    //TODO: why do we need this?
    private static $allowed_actions = array(
        'MemberForm',
    );

    /**
     * standard controller function.
     **/
    public function init()
    {
        parent::init();
        if (!$this->AccountMember() && 1 == 2) {
            $messages = array(
                'default' => '<p class="message good">'._t('Account.LOGINFIRST', 'You will need to log in before you can access the account page. ').'</p>',
                'logInAgain' => _t('Account.LOGINAGAIN', 'You have been logged out. If you would like to log in again, please do so below.'),
            );
            Security::permissionFailure($this, $messages);

            return false;
        }
        Requirements::themedCSS('AccountPage', 'ecommerce');
    }

    /**
     * Return a form allowing the user to edit
     * their details with the shop.
     *
     * @return ShopAccountForm
     */
    public function MemberForm()
    {
        return ShopAccountForm::create($this, 'MemberForm', $mustCreateAccount = true);
    }

    /**
     * Returns the current member.
     */
    public function AccountMember()
    {
        return Member::currentUser();
    }


    /**
     * The link that Google et al. need to index.
     * @return string
     */
    public function CanonicalLink()
    {
        $link = $this->Link();
        $this->extend('UpdateCanonicalLink', $link);

        return $link;
    }
}
