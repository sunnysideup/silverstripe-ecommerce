<?php

namespace Sunnysideup\Ecommerce\Pages;

use Page;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Order;

/**
 * Class \Sunnysideup\Ecommerce\Pages\AccountPage
 *
 */
class AccountPage extends Page
{
    /**
     * @var float
     */
    protected $calculatedTotal = 0;

    /**
     * @var float
     */
    protected $calculatedPaid = 0;

    /**
     * @var float
     */
    protected $calculatedOutstanding = 0;

    /**
     * @var DataList
     */
    protected $pastOrders;

    private static $table_name = 'AccountPage';

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $casting = [
        'RunningTotal' => 'Currency',
        'RunningPaid' => 'Currency',
        'RunningOutstanding' => 'Currency',
    ];

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $icon = 'sunnysideup/ecommerce: client/images/icons/AccountPage-file.gif';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Account Page';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Account Pages';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A page where the customer can view all their orders and update their details.';

    /**
     * Standard SS function, we only allow for one AccountPage to exist
     * but we do allow for extensions to exist at the same time.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return AccountPage::get()->filter(['ClassName' => AccountPage::class])->exists() ? false : $this->canEdit($member);
    }

    /**
     * Shop Admins can edit.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
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
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canPublish($member = null)
    {
        return $this->canEdit($member);
    }

    public function i18n_singular_name()
    {
        return _t('AccountPage.SINGULARNAME', 'Account Page');
    }

    public function i18n_plural_name()
    {
        return _t('AccountPage.PLURALNAME', 'Account Pages');
    }

    /**
     * Returns the link to the AccountPage on this site.
     *
     * @param string $action [optional]
     *
     * @return string (URLSegment)
     */
    public static function find_link(?string $action = null)
    {
        $page = DataObject::get_one(
            AccountPage::class,
            ['ClassName' => AccountPage::class]
        );
        if ($page) {
            return $page->Link($action);
        }

        return '404-account-page';
    }

    /**
     * Returns a list of all previous orders for the member / account.
     *
     * @return \SilverStripe\ORM\DataList
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
     * tells us if the current page is part of e-commerce.
     *
     * @return bool
     */
    public function IsEcommercePage()
    {
        return true;
    }

    /**
     * retrieves previous orders and adds totals to it...
     * return DataList.
     */
    protected function calculatePastOrders()
    {
        if (! $this->pastOrders) {
            $this->pastOrders = $this->pastOrdersSelection();
            $this->calculatedTotal = 0;
            $this->calculatedPaid = 0;
            $this->calculatedOutstanding = 0;
            if ($this->pastOrders->exists()) {
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
     * @return \SilverStripe\ORM\DataList (Orders)
     */
    protected function pastOrdersSelection()
    {
        $memberID = (int) Security::getCurrentUser()?->ID;
        if (! $memberID) {
            //set t
            $memberID = rand(0, 1000000) * -1;
        }
        if ($memberID) {
            return Order::get()
                ->where(
                    '"Order"."MemberID" = ' . $memberID . '
                    AND ("CancelledByID" = 0 OR "CancelledByID" IS NULL)'
                )
                ->innerJoin('OrderStep', '"Order"."StatusID" = "OrderStep"."ID"')
            ;
        }

        return 0;
    }
}
