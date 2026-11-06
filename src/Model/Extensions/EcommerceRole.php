<?php

namespace Sunnysideup\Ecommerce\Model\Extensions;

use Psr\Log\LoggerInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\HTMLReadonlyField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\RememberLoginHash;
use SilverStripe\Security\Security;
use SilverStripe\SessionManager\Models\LoginSession;
use SilverStripe\View\Requirements;
use Throwable;
use Sunnysideup\CmsEditLinkField\Api\CMSEditLinkAPI;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Control\ShoppingCartController;
use Sunnysideup\Ecommerce\Forms\Fields\EcommerceCMSButtonField;
use Sunnysideup\Ecommerce\Forms\Validation\ShopAccountFormValidator;
use Sunnysideup\Ecommerce\Model\Address\BillingAddress;
use Sunnysideup\Ecommerce\Model\Money\EcommerceCurrency;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Sunnysideup\PermissionProvider\Api\PermissionProviderFactory;
use Sunnysideup\PermissionProvider\Interfaces\PermissionProviderFactoryProvider;

/**
 * Class \Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole
 *
 * @property \SilverStripe\Security\Member|\Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole $owner
 * @property string $Notes
 * @property string $DefaultSortOrder
 * @property string $DefaultFilter
 * @property string $DisplayStyle
 * @property int $PreferredCurrencyID
 * @method \Sunnysideup\Ecommerce\Model\Money\EcommerceCurrency PreferredCurrency()
 * @method \SilverStripe\ORM\DataList|\Sunnysideup\Ecommerce\Model\Order[] Orders() .Member()
 * @method \SilverStripe\ORM\DataList|\Sunnysideup\Ecommerce\Model\Order[] CancelledOrders() .CancelledBy()
 */
class EcommerceRole extends DataExtension implements PermissionProvider, PermissionProviderFactoryProvider
{
    protected static $adminMemberCache;

    protected static $shopAssistantMemberCache;

    private static $max_count_of_members_in_array = 1500;

    private static $api_access = [
        'view' => [
            'ID',
            'Orders',
            'PreferredCurrency',
        ],
    ];

    /**
     * @var string
     */
    private static $permission_category = 'E-commerce';

    /**
     * @var bool
     */
    private static $allow_customers_to_setup_accounts = true;

    /**
     * @var bool
     */
    private static $must_have_account_to_purchase = false;

    /**
     * @var bool
     */
    private static $automatically_update_member_details = true;

    /**
     * When a member verifies their email address for the first time (by logging in with a one-time code),
     * and the account already has a password, then that password may have been set by someone else
     * (nobody checked who owns the email address when the account was created).
     * If this is set to true then the password is removed at that moment and the (verified) owner can set a new one.
     *
     * @var bool
     */
    private static $clear_password_when_email_is_first_verified = true;

    /**
     * @var string
     */
    private static $admin_group_code = 'shopadministrators';

    /**
     * @var string
     */
    private static $admin_group_name = 'Shop Administrators';

    /**
     * @var string
     */
    private static $admin_group_user_first_name = '';

    /**
     * @var string
     */
    private static $admin_group_user_surname = '';

    /**
     * @var string
     */
    private static $admin_group_user_email = '';

    /**
     * @var string
     */
    private static $admin_permission_code = 'SHOPADMIN';

    /**
     * @var string
     */
    private static $admin_role_title = 'Managing Shop';

    /**
     * @var array
     */
    private static $admin_role_permission_codes = [
        // key one
        'CMS_ACCESS_SalesAdmin_PROCESS',

        'CMS_ACCESS_ProductsAndGroupsModelAdmin',
        'CMS_ACCESS_ProductConfigModelAdmin',
        'CMS_ACCESS_ProductSearchModelAdmin',
        'CMS_ACCESS_SalesAdmin',
        'CMS_ACCESS_SalesAdminByOrderSize',
        'CMS_ACCESS_SalesAdminByOrderStep',
        'CMS_ACCESS_SalesAdminByDeliveryOption',
        'CMS_ACCESS_SalesSalesAdminProcess',
        'CMS_ACCESS_SalesAdminByPaymentType',
        'CMS_ACCESS_SalesAdminExtras',
        'CMS_ACCESS_StoreAdmin',
        'CMS_ACCESS_AssetAdmin',
        'CMS_ACCESS_CMSMain',
    ];

    /**
     * @var string
     */
    private static $process_orders_permission_code = 'CMS_ACCESS_SalesAdmin_PROCESS';

    /**
     * standard SS method.
     */
    private static $table_name = 'EcommerceRole';

    private static $db = [
        // set when the member has proven that they own the email address (e.g. logged in with a one-time code).
        'EmailVerifiedAt' => 'Datetime',
        'Notes' => 'Text',
        'DefaultSortOrder' => 'Varchar',
        'DefaultFilter' => 'Varchar',
        'DisplayStyle' => 'Varchar',
    ];

    private static $has_one = [
        'PreferredCurrency' => EcommerceCurrency::class,
    ];

    private static $has_many = [
        'Orders.Member' => Order::class,
        'CancelledOrders.CancelledBy' => Order::class,
    ];

    private static $casting = [
        'CustomerDetails' => 'Varchar',
    ];

    public static function permission_provider_factory_runner(): Group
    {
        return PermissionProviderFactory::inst()
            ->setParentGroup(EcommerceRole::get_category())
            ->setCode(EcommerceConfig::get(EcommerceRole::class, 'admin_group_code'))
            ->setGroupName(EcommerceConfig::get(EcommerceRole::class, 'admin_group_name'))
            ->setPermissionCode(EcommerceConfig::get(EcommerceRole::class, 'admin_permission_code'))
            ->setRoleTitle(EcommerceConfig::get(EcommerceRole::class, 'admin_role_title'))
            ->setPermissionArray(EcommerceConfig::get(EcommerceRole::class, 'admin_role_permission_codes'))
            ->setDescription(
                _t(
                    'EcommerceRole.ADMINISTRATORS_HELP',
                    'Shop Manager - can edit everything to do with the e-commerce application.'
                )
            )
            ->setSort(99)
            ->CreateGroup();
    }

    public function getCustomerDetails(): string
    {
        $owner = $this->getOwner();
        if ($owner->exists()) {
            // NB: only orders with a successful payment: carts, abandoned checkouts and failed payments are noise.
            $countPaid = $owner->getOrdersWithSuccessfulPaymentCount();
            $countHeld = $owner->getHeldOrdersCount();
            $name = $owner->IsGuestCustomer() ? '' : $owner->FirstName . ' ' . $owner->Surname . ', ';

            return $name . $owner->Email .
                ' (' . _t('Member.MEMBER_ORDER_COUNT_PAID', 'paid') . ': ' . $countPaid .
                ($countHeld ? ', ' . _t('Member.MEMBER_ORDER_COUNT_HELD', 'held') . ': ' . $countHeld : '') .
                ') - ' . $owner->getCustomerType();
        }

        return 'no customer';
    }

    /**
     * @return null|Group|\SilverStripe\ORM\DataObject
     */
    public static function get_customer_group()
    {
        $customerGroupCode = EcommerceConfig::get(EcommerceRoleCustomer::class, 'customer_group_code');

        return DataObject::get_one(
            Group::class,
            ['Code' => $customerGroupCode]
        );
    }

    public static function get_category(): string
    {
        return EcommerceConfig::get(EcommerceRole::class, 'permission_category');
    }

    /**
     * returns an aray of customers
     * The unselect option shows an extra line, basically allowing you to deselect the
     * current option.
     *
     * @param bool $showUnselectedOption
     *
     * @return array ( ID => Email (member.title) )
     */
    public static function list_of_customers($showUnselectedOption = false)
    {
        //start array
        $array = [];
        if ($showUnselectedOption) {
            $array[0] = _t('Member.SELECTCUSTOMER', ' --- SELECT CUSTOMER ---');
        }
        //get customer group
        $group = self::get_customer_group();
        //fill array
        if ($group) {
            $members = $group->Members();
            $membersCount = $members->count();
            if ($membersCount > 0 && $membersCount < Config::inst()->get(EcommerceRole::class, 'max_count_of_members_in_array')) {
                foreach ($members as $member) {
                    if ($member->Email) {
                        $array[$member->ID] = $member->Email . ' (' . $member->getTitle() . ')';
                    }
                }
            } else {
                return $array;
            }
        }
        //sort in a natural order
        natcasesort($array);

        return $array;
    }

    /**
     * returns an aray of customers
     * The unselect option shows an extra line, basically allowing you to deselect the
     * current option.
     *
     * @param bool $showUnselectedOption
     *
     * @return array ( ID => Email (member.title) )
     */
    public static function list_of_admins($showUnselectedOption = false)
    {
        //start array
        $array = [];
        if ($showUnselectedOption) {
            $array[0] = _t('Member.SELECT_ECOMMERCE_ADMIN', ' --- SELECT ADMIN ---');
        }
        //get customer group
        $group = self::get_admin_group();
        //fill array
        if ($group) {
            $members = $group->Members();
            $membersCount = $members->count();
            if ($membersCount > 0) {
                foreach ($members as $member) {
                    if ($member->Email) {
                        $array[$member->ID] = $member->Email . ' (' . $member->getTitle() . ')';
                    }
                }
            }
        }
        $group = DataObject::get_one(
            Group::class,
            ['Code' => 'administrators']
        );
        //fill array
        if ($group) {
            $members = $group->Members();
            $membersCount = $members->count();
            if ($membersCount > 0) {
                foreach ($members as $member) {
                    if ($member->Email) {
                        $array[$member->ID] = $member->Email . ' (' . $member->getTitle() . ')';
                    }
                }
            }
        }
        //sort in a natural order
        natcasesort($array);

        return $array;
    }

    /**
     * tells us if the current member is in the Shop Administrators Group.
     *
     * @param null|\SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public static function current_member_is_shop_admin($member = null)
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        if ($member) {
            return $member->IsShopAdmin();
        }

        return false;
    }

    /**
     * tells us if the current member is in the Shop Administrators Group.
     *
     * @param null|\SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public static function current_member_is_shop_assistant($member = null)
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        if ($member) {
            return $member->IsShopAssistant();
        }

        return false;
    }

    /**
     * tells us if the current member can process the orders.
     *
     * @param null|\SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public static function current_member_can_process_orders($member = null)
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        if ($member) {
            return $member->CanProcessOrders();
        }

        return false;
    }

    /**
     * @return \SilverStripe\ORM\DataObject (Group)|null
     */
    public static function get_admin_group()
    {
        $adminCode = EcommerceConfig::get(EcommerceRole::class, 'admin_group_code');

        return DataObject::get_one(
            Group::class,
            ['Code' => $adminCode]
        );
    }

    /**
     * @return \SilverStripe\ORM\DataObject (Group)|null
     */
    public static function get_assistant_group()
    {
        $assistantCode = EcommerceConfig::get(EcommerceRoleAssistant::class, 'assistant_group_code');

        return DataObject::get_one(
            Group::class,
            ['Code' => $assistantCode]
        );
    }

    /**
     * @return \SilverStripe\ORM\DataObject (Member)|null
     */
    public static function get_default_shop_admin_user()
    {
        if (null === self::$adminMemberCache) {
            $group = self::get_admin_group();
            if ($group) {
                self::$adminMemberCache = $group->Members()->First();
            }
        }

        return self::$adminMemberCache;
    }

    /**
     * @return \SilverStripe\ORM\DataObject (Member)|null
     */
    public static function get_default_shop_assistant_user()
    {
        if (null === self::$shopAssistantMemberCache) {
            $group = self::get_assistant_group();
            if ($group) {
                self::$shopAssistantMemberCache = $group->Members()->First();
            }
        }

        return self::$shopAssistantMemberCache;
    }

    protected static $_excluded_members_array;

    /**
     * @return array of Member IDs that may need to be excluded from order analysis, such as site administrators and shop assistants.
     */
    public static function get_excluded_members_array(): array
    {
        // return Permission::get_members_by_permission('CMS_ACCESS')->column('ID');
        if (empty(self::$_excluded_members_array)) {
            self::$_excluded_members_array = [-1 => -1];
            $adminGroups = Permission::get_groups_by_permission(['ADMIN']);
            foreach ($adminGroups as $group) {
                self::$_excluded_members_array = array_merge(self::$_excluded_members_array, $group->Members()->columnUnique('ID'));
            }
            $shopAdminGroup = EcommerceRole::get_admin_group()->Members()->columnUnique('ID');
            $assitantGroup = EcommerceRole::get_assistant_group()->Members()->columnUnique('ID');
            self::$_excluded_members_array = array_merge(self::$_excluded_members_array, $shopAdminGroup, $assitantGroup);
        }

        return self::$_excluded_members_array;
    }

    /**
     * you can't delete a Member with one or more orders.
     *
     * @param \SilverStripe\Security\Member $member
     */
    public function canDelete($member = null)
    {
        // including the orders that are held until the email address is verified
        if ($this->getOrdersIncludingUnverified()->exists()) {
            return false;
        }

        return parent::canDelete($member);
    }

    /**
     * Called by the one-time-code login (sunnysideup/one-time-code-authentication)
     * after the member has logged in with a code that was sent to their email address.
     * That proves that they control the email address, so:
     * 1. we remember that the email address has been verified
     * 2. we link the guest orders that were placed with this email address to their account.
     */
    public function onAfterOneTimeCodeLogin(): void
    {
        $this->markEmailAsVerified();
        $this->claimGuestOrdersForVerifiedEmail();
        // the code that was just used proves that the member controls the email address,
        // so orders that were placed with their email address by someone else can now be shown to them.
        // (a password login can not do this: see afterMemberLoggedIn).
        $this->releaseOrdersHeldUntilEmailIsVerified();
    }

    /**
     * Called after every login (core SilverStripe).
     * Members who have verified their email address (see markEmailAsVerified)
     * get the guest orders that were placed with their email address, whatever way they log in.
     * Members who have not verified their email address do NOT get any orders linked:
     * a password only proves that someone chose it, not that they own the email address.
     *
     * Orders that are held until the email address is verified (Order.EmailNotVerified)
     * are NEVER released here, even if the email address was verified some time ago:
     * a password does not prove that the member controls the email address NOW, and
     * these orders were placed by someone else.  Only a one-time code does
     * (see onAfterOneTimeCodeLogin).
     */
    public function afterMemberLoggedIn(): void
    {
        if ($this->getOwner()->EmailVerifiedAt) {
            $this->claimGuestOrdersForVerifiedEmail();
        }
    }

    /**
     * Remembers that the member has proven that they own their email address.
     * ONLY call this after a login method that proves this (e.g. a code sent to the email address).
     *
     * The first time this happens, a password that is already on the account is removed
     * (see clear_password_when_email_is_first_verified).
     */
    public function markEmailAsVerified(): void
    {
        $owner = $this->getOwner();
        if (! $owner->exists() || $owner->EmailVerifiedAt) {
            return;
        }
        $now = DBDatetime::now()->Rfc2822();
        $clearPassword = $owner->Password && Config::inst()->get(EcommerceRole::class, 'clear_password_when_email_is_first_verified');
        // direct updates: we do not want any of the side effects of writing a member.
        if ($clearPassword) {
            // NB: the encryption algorithm must stay a valid one (see create_guest_member).
            DB::prepared_query(
                'UPDATE "Member" SET "EmailVerifiedAt" = ?, "Password" = NULL, "Salt" = NULL, "PasswordEncryption" = ?, "AutoLoginHash" = NULL, "AutoLoginExpired" = NULL WHERE "ID" = ?',
                [$now, self::default_password_encryption(), (int) $owner->ID]
            );
            // NB: we do NOT change the password on $owner itself: that would make a later write() hash an empty password.
        } else {
            DB::prepared_query('UPDATE "Member" SET "EmailVerifiedAt" = ? WHERE "ID" = ?', [$now, (int) $owner->ID]);
        }
        $owner->EmailVerifiedAt = $now;

        // Nobody had proven that they own this email address until now, so every login that exists
        // for this account (e.g. from someone who registered it with an email address that is not theirs)
        // can not be trusted any more. Only the login that has just happened (with the code) is kept.
        // remember-me logins also have to go.
        RememberLoginHash::get()->filter(['MemberID' => $owner->ID])->removeAll();
        $this->revokeOtherLoginSessions();
    }

    /**
     * Ends all the logins of this member (see the silverstripe/session-manager module),
     * except the login of the current request.
     * A request is logged out as soon as its login session is gone, so this takes effect straight away.
     *
     * If the current login can not be worked out, then all logins are ended (the member can simply log in again).
     */
    protected function revokeOtherLoginSessions(): void
    {
        if (! class_exists(LoginSession::class)) {
            return;
        }

        $owner = $this->getOwner();
        try {
            $keepID = 0;
            if (Controller::has_curr()) {
                $current = LoginSession::getCurrentLoginSession(Controller::curr()->getRequest());
                if ($current && (int) $current->MemberID === (int) $owner->ID) {
                    $keepID = (int) $current->ID;
                }
            }

            $sessions = LoginSession::get()->filter(['MemberID' => $owner->ID]);
            if ($keepID) {
                $sessions = $sessions->exclude(['ID' => $keepID]);
            }

            $sessions->removeAll();
        } catch (Throwable $throwable) {
            // do not break the login, but do not hide the problem either.
            Injector::inst()->get(LoggerInterface::class)->error(
                'Could not end the other logins of member ' . (int) $owner->ID . ': ' . $throwable->getMessage()
            );
        }
    }

    /**
     * Links submitted orders that do not have a member yet (guest orders) and
     * that have been placed with the email address of this member (billing address email)
     * to this member.  Cancelled orders are included.
     *
     * ONLY call this if the member has verified that they control their email address.
     *
     * @return int number of orders linked
     */
    public function claimGuestOrdersForVerifiedEmail(?int $batchSize = 200): int
    {
        $owner = $this->getOwner();
        $email = trim((string) $owner->Email);
        if (! $owner->exists() || ! $owner->EmailVerifiedAt || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 0;
        }
        // staff place orders on behalf of customers: do not link those to the staff account.
        if ($owner->IsShopAdmin() || $owner->IsShopAssistant()) {
            return 0;
        }
        $count = 0;
        // in batches, so that we get all of them without loading too many at once.
        for ($i = 0; $i < 100; ++$i) {
            $orderIDs = Order::get_datalist_of_orders_with_submit_record(true, true)
                // NB: email addresses are not case sensitive (:nocase)
                ->filter(['MemberID' => 0, 'BillingAddress.Email:nocase' => $email])
                ->limit($batchSize)
                ->column('ID');
            if ([] === $orderIDs) {
                break;
            }
            foreach ($orderIDs as $orderID) {
                // direct update: we do not want to change anything else about the (submitted) order,
                // not even the LastEdited date.
                DB::prepared_query(
                    'UPDATE "Order" SET "MemberID" = ? WHERE "ID" = ? AND "MemberID" = 0',
                    [(int) $owner->ID, (int) $orderID]
                );
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Orders that were placed by someone who entered the email address of this member (Order.EmailNotVerified)
     * are held: the member can not see them.  Once the member has proven (with a one-time code)
     * that they control the email address, they are released.
     *
     * ONLY call this straight after a login that proves that the member controls their email address NOW.
     *
     * @return int number of orders released
     */
    public function releaseOrdersHeldUntilEmailIsVerified(): int
    {
        $owner = $this->getOwner();
        if (! $owner->exists() || ! $owner->EmailVerifiedAt) {
            return 0;
        }
        $count = (int) $this->getOrdersIncludingUnverified()->filter(['EmailNotVerified' => 1])->count();
        if (0 === $count) {
            return 0;
        }
        // direct update: we do not want to change anything else about the (submitted) order,
        // not even the LastEdited date.
        DB::prepared_query(
            'UPDATE "Order" SET "EmailNotVerified" = 0 WHERE "MemberID" = ? AND "EmailNotVerified" = 1',
            [(int) $owner->ID]
        );

        return $count;
    }

    /**
     * Finds a member by email address.  Email addresses are NOT case sensitive,
     * so we do not rely on the collation of the database.
     */
    public static function find_member_by_email(string $email): ?Member
    {
        $email = trim($email);
        if ('' === $email) {
            return null;
        }
        $uniqueField = (string) (Member::config()->get('unique_identifier_field') ?: 'Email');
        $uniqueField = preg_replace('/[^A-Za-z0-9_]/', '', $uniqueField);

        return Member::get()
            ->where(['LOWER("Member"."' . $uniqueField . '") = ?' => mb_strtolower($email)])
            ->sort(['ID' => 'ASC'])
            ->first();
    }

    /**
     * The password encryption algorithm that new members get (e.g. blowfish).
     */
    protected static function default_password_encryption(): string
    {
        return (string) (Security::config()->get('password_encryption_algorithm') ?: 'blowfish');
    }

    /**
     * Every order has a member, even if the customer checks out as a guest.
     * (the email address is the unique identifier of a member).
     *
     * This links a guest order (an order without a member) to a member. It sets
     * MemberID and EmailNotVerified on the order, but it does NOT write the order.
     *
     * 1. Someone is logged in (and is not staff): the order goes to that member.
     * 2. There is no member with the email address of the billing address:
     *    a member is created (with the name of the billing address).
     *    The new member has NO password and the visitor is NOT logged in:
     *    nobody has proven that they own the email address.  The customer can log in
     *    with a one-time code (which proves that they own the email address) and then set a password.
     * 3. There IS a member with this email address: the order is linked to that member
     *    but it is marked as EmailNotVerified, because the visitor has not proven that they own the email address.
     *    The member will not see the order until they verify their email address, and nothing
     *    about the member (data, trust) is available to the order or the visitor.
     *
     * @param bool $isBackfill if TRUE then it is an existing order (not the visitor who placed it), so that
     *                         we trust an email address that the member has verified already (EmailVerifiedAt).
     *
     * @return null|Member the member that the order is linked to (null if the order can not be linked to a member)
     */
    public static function attach_member_to_guest_order(Order $order, ?bool $isBackfill = false): ?Member
    {
        if ($order->MemberID) {
            $member = $order->Member();

            return $member && $member->exists() ? $member : null;
        }

        if (! $isBackfill) {
            $currentUser = Security::getCurrentUser();
            if ($currentUser && $currentUser->exists()) {
                if ($currentUser->IsShopAdmin() || $currentUser->IsShopAssistant()) {
                    // staff place orders for a customer: the order must already be linked to the customer
                    // (see Order::submissionProblems)
                    return null;
                }
                $order->MemberID = $currentUser->ID;
                $order->EmailNotVerified = false;

                return $currentUser;
            }
        }

        $billingAddress = $order->BillingAddressID ? $order->BillingAddress() : null;
        if (! $billingAddress || ! $billingAddress->exists()) {
            return null;
        }
        $email = trim((string) $billingAddress->Email);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $member = self::find_member_by_email($email);
        if (! $member instanceof Member) {
            $member = self::create_guest_member($email, (string) $billingAddress->FirstName, (string) $billingAddress->Surname);
            if ($member instanceof Member) {
                $order->MemberID = $member->ID;
                $order->EmailNotVerified = false;

                return $member;
            }
            // could not create one: has someone else just created one with this email address?
            $member = self::find_member_by_email($email);
            if (! $member instanceof Member) {
                return null;
            }
        }

        // there is already a member with this email address:
        // the order is linked to them, but it is held until they have verified their email address.
        $order->MemberID = $member->ID;
        $order->EmailNotVerified = ! ($isBackfill && $member->EmailVerifiedAt);

        return $member;
    }

    /**
     * Creates a member that has no password (see attach_member_to_guest_order).
     */
    protected static function create_guest_member(string $email, string $firstName, string $surname): ?Member
    {
        $uniqueField = Member::config()->get('unique_identifier_field') ?: 'Email';
        $member = Member::create();
        $member->{$uniqueField} = trim($email);
        $member->FirstName = mb_substr(trim($firstName), 0, 100);
        $member->Surname = mb_substr(trim($surname), 0, 100);
        try {
            $member->write();
        } catch (ValidationException) {
            return null;
        }
        if (! $member->exists()) {
            return null;
        }
        // SilverStripe hashes the password when a member is first written, even if it is empty
        // (so the member would end up with the hash of an empty password).
        // This member must not have a password at all, so we remove it.
        // NB: the encryption algorithm must stay a valid one: the login code looks up the algorithm
        // of the member and it throws an error if it can not find it.
        DB::prepared_query(
            'UPDATE "Member" SET "Password" = NULL, "Salt" = NULL, "PasswordEncryption" = ? WHERE "ID" = ?',
            [self::default_password_encryption(), (int) $member->ID]
        );

        // return a fresh copy, so that a later write() can not hash an empty password again.
        return Member::get_by_id((int) $member->ID);
    }

    /**
     * we need this function because $this->Orders does not return anything
     * that is probably because Order links the member twice (placed by and cancelled by).
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function Orders()
    {
        return $this->getOrders();
    }

    /**
     * The orders of this member that the member is allowed to see.
     *
     * This does NOT include orders that were placed by someone who entered the email address of this member
     * without proving that they own it (Order.EmailNotVerified).  Use getOrdersIncludingUnverified for staff.
     * Every customer-facing list of orders (and everything derived from it) should use this method.
     *
     * @return \SilverStripe\ORM\DataList|Order[]
     */
    public function getOrders()
    {
        return $this->getOrdersIncludingUnverified()->filter(['EmailNotVerified' => 0]);
    }

    /**
     * ALL the orders that are linked to this member, including the ones that are held until
     * the member has verified their email address.  For staff, NOT for customers.
     *
     * @return \SilverStripe\ORM\DataList|Order[]
     */
    public function getOrdersIncludingUnverified()
    {
        return Order::get()->filter(['MemberID' => $this->getOwner()->ID]);
    }

    /**
     * The number of orders of this member that have at least one successful payment.
     * (an order with several successful payments is counted once).
     * Orders that are held until the email address is verified are not the member's orders, so they are not counted.
     */
    public function getOrdersWithSuccessfulPaymentCount(): int
    {
        $owner = $this->getOwner();
        if (! $owner->exists()) {
            return 0;
        }
        $orderTable = Order::getSchema()->tableName(Order::class);
        $paymentTable = Order::getSchema()->tableName(EcommercePayment::class);

        return (int) DB::prepared_query(
            'SELECT COUNT(DISTINCT "' . $orderTable . '"."ID") FROM "' . $orderTable . '"
            INNER JOIN "' . $paymentTable . '" ON "' . $paymentTable . '"."OrderID" = "' . $orderTable . '"."ID"
            WHERE "' . $orderTable . '"."MemberID" = ? AND "' . $orderTable . '"."EmailNotVerified" = 0 AND "' . $paymentTable . '"."Status" = ?',
            [(int) $owner->ID, EcommercePayment::SUCCESS_STATUS]
        )->value();
    }

    /**
     * A guest is someone who has checked out without creating an account:
     * a member without a password, who has never proven that they own their email address (by logging in with a code).
     * (a customer who logs in with a code has proven it, so they are no longer a guest.)
     */
    public function IsGuestCustomer(): bool
    {
        $owner = $this->getOwner();
        if (! $owner->exists()) {
            return true;
        }

        return ! $owner->Password && ! $owner->EmailVerifiedAt;
    }

    /**
     * "guest", "unverified account" or "verified account" (verified = they have logged in with a code that was emailed to them).
     */
    public function getCustomerType(): string
    {
        $owner = $this->getOwner();
        if ($owner->IsGuestCustomer()) {
            return _t('Member.TYPE_GUEST', 'guest');
        }

        return $owner->EmailVerifiedAt
            ? _t('Member.TYPE_VERIFIED', 'verified account')
            : _t('Member.TYPE_UNVERIFIED', 'unverified account');
    }

    /**
     * Number of orders that are waiting for this member to verify their email address.
     */
    public function getHeldOrdersCount(): int
    {
        $owner = $this->getOwner();
        if (! $owner->exists()) {
            return 0;
        }

        return $this->getOrdersIncludingUnverified()->filter(['EmailNotVerified' => 1])->count();
    }

    /**
     * Orders linked to this member by member ID, plus any orders that share the
     * member's email address (through the order's member or through the order's
     * billing address) without being linked by member ID.
     *
     * @return \SilverStripe\ORM\DataList|Order[]
     */
    public function getOrdersIncludingEmailMatches()
    {
        $owner = $this->getOwner();
        $filters = ['MemberID' => (int) $owner->ID];
        $email = trim((string) $owner->Email);
        if ($email !== '') {
            $filters['Member.Email'] = $email;
            $filters['BillingAddress.Email'] = $email;
        }

        return Order::get()->filterAny($filters)->sort(['ID' => 'DESC']);
    }

    public function getCompletedOrders()
    {
        $orderSteps = OrderStep::get()->filter(['ShowAsCompletedOrder' => true])->columnUnique();
        return $this->getOrders()->filter(['StatusID' => $orderSteps]);
    }

    public function CancelledOrders()
    {
        return $this->getCancelledOrders();
    }

    public function getCancelledOrders()
    {
        return Order::get()->filter(['CancelledByID' => $this->getOwner()->ID]);
    }

    /**
     * creates two permission roles.
     * standard SS Method.
     *
     * @return array
     */
    public function providePermissions()
    {
        $category = EcommerceConfig::get(EcommerceRole::class, 'permission_category');

        $perms[EcommerceConfig::get(EcommerceRole::class, 'process_orders_permission_code')] = [
            'name' => _t(
                'EcommerceRole.PROCESS_ORDERS_PERMISSION_NAME',
                'Can process orders'
            ),
            'category' => $category,
            'help' => _t(
                'EcommerceRole.PROCESS_ORDERS_PERMISSION_HELP',
                'Can the user progress orders through the order steps (e.g. dispatch orders)'
            ),
            'sort' => 101,
        ];

        return $perms;
    }

    public function updateFormScaffolder($fs, $owner)
    {
        $fs->includeRelations = false;
    }

    /**
     * Update the CMS Fields
     * for /admin/security.
     */
    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->getOwner();
        // only set by the system (after a login that proves that the member owns the email address)
        $fields->replaceField(
            'EmailVerifiedAt',
            ReadonlyField::create(
                'EmailVerifiedAtNice',
                _t('Member.EMAIL_VERIFIED_AT', 'Email address verified'),
                $owner->EmailVerifiedAt ?: _t('Member.EMAIL_NOT_VERIFIED', 'not verified')
            )
        );
        $orders = $owner->ID ? $this->getOrdersIncludingEmailMatches() : null;
        if ($orders && $orders->exists()) {
            $orderField = GridField::create(
                'OrdersNice',
                'Orders (linked by member or by email address)',
                $orders,
                GridFieldConfig_RecordViewer::create()
            );
        } else {
            $orderField = new ReadonlyField('OrdersNice', 'Orders', 'no orders');
        }

        $cancelledOrdersField = $fields->dataFieldByName('CancelledOrders.CancelledBy');
        if ($cancelledOrdersField) {
            if ($cancelledOrdersField instanceof GridField) {
                $config = GridFieldConfig_RecordEditor::create();
                $config->removeComponentsByType(GridFieldDeleteAction::class);
                $config->removeComponentsByType(GridFieldAddNewButton::class);
                $config->removeComponentsByType(GridFieldFilterHeader::class);
                $cancelledOrdersField->setConfig($config);
                $cancelledOrdersField->setList($this->getCancelledOrders());
            }
        } else {
            $cancelledOrdersField = new HiddenField('CancelledOrders', 'Cancelled Orders');
        }
        $preferredCurrencyField = $fields->dataFieldByName('PreferredCurrencyID');
        $notesFields = $fields->dataFieldByName('Notes');
        $loginAsField = new LiteralField(
            'LoginAsThisCustomer',
            '<p class="actionInCMS"><a href="' . $this->getOwner()->LoginAsLink() . '" target="_blank">Login as this customer</a></p>'
        );
        $link = ShoppingCartController::place_order_for_member_link($this->getOwner()->ID);
        $orderForLink = new LiteralField('OrderForCustomerLink', "<p class=\"actionInCMS\"><a href=\"{$link}\" target=\"_blank\">Place order for customer</a></p>");
        $fields->addFieldsToTab(
            'Root.ecommerce',
            [
                $orderField,
                $preferredCurrencyField,
                $notesFields,
                $loginAsField,
                $orderForLink,
                $fields->dataFieldByName('Notes'),
                HeaderField::create(
                    'ProductFilterSortPreferences',
                    'Product Filter Sort Preferences'
                ),
                $fields->dataFieldByName('DefaultSortOrder'),
                $fields->dataFieldByName('DefaultFilter'),
                $fields->dataFieldByName('DisplayStyle'),
            ]
        );
        $fields->removeFieldFromTab('Root', 'Orders');
    }

    /**
     * Save a preferred currency for a member.
     *
     * @param EcommerceCurrency $currency - object for the currency
     */
    public function SetPreferredCurrency(EcommerceCurrency $currency)
    {
        if ($this->getOwner()->exists() && ($currency && $currency->exists())) {
            $this->getOwner()->PreferredCurrencyID = $currency->ID;
            $this->getOwner()->write();
        }
    }

    /**
     * get CMS fields describing the member in the CMS when viewing the order.
     *
     * @return CompositeField
     */
    public function getEcommerceFieldsForCMS()
    {
        $fields = new CompositeField();
        // SECURITY: the name and email are typed in by customers (also guests), and this is shown to admins.
        // Everything that goes into HTML here has to be escaped.
        $memberName = Convert::raw2xml((string) $this->getOwner()->getTitle());
        $memberEmailAddress = (string) $this->getOwner()->Email;
        $memberTitle = HTMLReadonlyField::create('MemberTitle', _t('Member.TITLE', 'Name'), '<p>' . $memberName . '</p>');
        $fields->push($memberTitle);
        $memberEmail = HTMLReadonlyField::create('MemberEmail', _t('Member.EMAIL', 'Email'), '<p><a href="mailto:' . Convert::raw2att($memberEmailAddress) . '">' . Convert::raw2xml($memberEmailAddress) . '</a></p>');
        $fields->push($memberEmail);
        $lastLogin = HTMLReadonlyField::create('MemberLastLogin', _t('Member.LASTLOGIN', 'Last Login'), '<p>' . $this->getOwner()->dbObject('LastVisited') . '</p>');
        $fields->push($lastLogin);
        $group = self::get_customer_group();
        if (! $group) {
            $group = new Group();
        }
        $headerField = HeaderField::create('MemberLinkFieldHeader', _t('Member.EDIT_CUSTOMER', 'Edit Customer'));
        $linkField1 = EcommerceCMSButtonField::create(
            'MemberLinkFieldEditThisCustomer',
            $this->getOwner()->CMSEditLink(),
            _t('Member.EDIT', 'Edit') . ' <i>' . $memberName . '</i>'
        );
        $fields->push($headerField);
        $fields->push($linkField1);

        if (EcommerceRole::current_member_can_process_orders(Security::getCurrentUser())) {
            $linkField2 = EcommerceCMSButtonField::create(
                'MemberLinkFieldEditAllCustomers',
                CMSEditLinkAPI::find_edit_link_for_object($group),
                _t('Member.EDIT_ALL_CUSTOMERS', 'Edit All ') . Convert::raw2xml((string) $group->Title)
            );
            $fields->push($linkField2);
        }

        return $fields;
    }

    /**
     * @param mixed $mustCreateAccount
     * @param bool $enforcePasswordLength
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function getEcommerceFields($mustCreateAccount = false, $enforcePasswordLength = false)
    {
        $passwordDoubleCheckField = null;
        if (! EcommerceConfig::get(EcommerceRole::class, 'allow_customers_to_setup_accounts')) {
            //if no accounts are made then we simply return the basics....
            $fields = new FieldList(
                new TextField('FirstName', _t('EcommerceRole.FIRSTNAME', 'First Name')),
                new TextField('Surname', _t('EcommerceRole.SURNAME', 'Surname')),
                new EmailField('Email', _t('EcommerceRole.EMAIL', 'Email'))
            );
        } else {
            Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomPasswordField.js');
            if ($this->getOwner()->exists() && Security::getCurrentUser()) {
                $passwordField = new PasswordField('PasswordCheck1', _t('Account.NEW_PASSWORD', 'New Password'));
                $passwordDoubleCheckField = new PasswordField('PasswordCheck2', _t('Account.CONFIRM_NEW_PASSWORD', 'Confirm New Password'));
                $updatePasswordLinkField = new LiteralField(
                    'UpdatePasswordLink',
                    '<a href="#Password"  datano="' . Convert::raw2att(_t('Account.DO_NOT_UPDATE_PASSWORD', 'Do not update password')) . '"  class="updatePasswordLink passwordToggleLink secondary-button" rel="Password">' . _t('Account.UPDATE_PASSWORD', 'Update Password') . '</a>'
                );
                //if they dont have a password then we now force them to create one.
                //the fields of which are added further down the line...
                //we simply hide these fields, as they add little extra ....
                $loginDetailsHeader = new HiddenField('LoginDetails', _t('Account.LOGINDETAILS', 'Login Details'), 5);
                $loginDetailsDescription = new HiddenField(
                    'AccountInfo',
                    '<p>' .
                        _t('OrderForm.PLEASE_REVIEW', 'Please review your log-in details below.')
                        . '</p>'
                );
            } elseif (EcommerceConfig::get(EcommerceRole::class, 'must_have_account_to_purchase') || $mustCreateAccount) {
                $loginDetailsHeader = new HeaderField('CreateAnAccount', _t('OrderForm.SETUPYOURACCOUNT', 'Create an account'), 2);
                //dont allow people to purchase without creating a password
                $loginDetailsDescription = new LiteralField(
                    'AccountInfo',
                    '<p class="password-info">' .
                        _t('OrderForm.MUSTCREATEPASSWORD', 'Please choose a password to create your account.')
                        . '</p>'
                );
            } else {
                $loginDetailsHeader = new HeaderField('CreateAnAccount', _t('OrderForm.CREATEANACCONTOPTIONAL', 'Create an account (optional)'), 2);
                //allow people to purchase without creating a password
                $updatePasswordLinkField = new LiteralField('UpdatePasswordLink', '<div class="choose-password-holder"><a href="#Password" datano="' . Convert::raw2att(_t('Account.DO_NOT_CREATE_ACCOUNT', 'do not create account')) . '" class="choosePassword passwordToggleLink">choose a password</a></div>');
                $loginDetailsDescription = new LiteralField(
                    'AccountInfo',
                    '<p class="password-info">' .
                        _t('OrderForm.SELECTPASSWORD', 'Please enter a password; this will allow you to check your order history in the future.')
                        . '</p>'
                );
                //close by default
            }

            if (empty($passwordField)) {
                $passwordField = new PasswordField('PasswordCheck1', _t('Account.CREATE_PASSWORD', 'Password'));
                $passwordDoubleCheckField = new PasswordField('PasswordCheck2', _t('Account.CONFIRM_PASSWORD', 'Confirm Password'));
            }
            if (empty($updatePasswordLinkField)) {
                $updatePasswordLinkField = new LiteralField('UpdatePasswordLink', '');
            }

            if ($enforcePasswordLength) {
                $minLength = ShopAccountFormValidator::getMinimumPasswordLength();
                if ($minLength > 0) {
                    $passwordField->setAttribute('minlength', $minLength);
                    $passwordDoubleCheckField->setAttribute('minlength', $minLength);
                    $passwordField->setAttribute('placeholder', _t('Account.PASSWORDPLACEHOLDER', 'At least {minLength} characters long', ['minLength' => $minLength]));
                    $passwordDoubleCheckField->setAttribute('placeholder', _t('Account.PASSWORDPLACEHOLDER', 'At least {minLength} characters long', ['minLength' => $minLength]));
                }
            }

            $loginDetailsField = CompositeField::create();
            $loginDetailsField->setName('LoginDetails');
            $loginDetailsField->push($loginDetailsHeader);
            $loginDetailsField->push($loginDetailsDescription);
            $loginDetailsField->push($updatePasswordLinkField);
            $loginDetailsField->push($passwordField);

            $fields = new FieldList(
                new TextField('FirstName', _t('EcommerceRole.FIRSTNAME', 'First Name')),
                new TextField('Surname', _t('EcommerceRole.SURNAME', 'Surname')),
                new EmailField('Email', _t('EcommerceRole.EMAIL', 'Email')),
                $loginDetailsField,
            );

            if ($passwordDoubleCheckField && $passwordDoubleCheckField instanceof PasswordField) {
                $fields->push($passwordDoubleCheckField);
            }
        }

        $this->getOwner()->extend('augmentEcommerceFields', $fields);

        return $fields;
    }

    /**
     * Return which member fields should be required on {@link OrderForm}
     * and {@link ShopAccountForm}.
     *
     * @return array
     */
    public function getEcommerceRequiredFields()
    {
        $fields = [
            'FirstName',
            'Surname',
            'Email',
        ];
        if (EcommerceConfig::get(EcommerceRole::class, 'must_have_account_to_purchase')) {
            $passwordFieldIsRequired = true;
            if ($this->getOwner()->exists() && $this->getOwner()->Password) {
                $passwordFieldIsRequired = false;
            }
        } else {
            $passwordFieldIsRequired = false;
        }
        if ($passwordFieldIsRequired) {
            $fields[] = 'PasswordCheck1';
            $fields[] = 'PasswordCheck2';
        }
        $this->getOwner()->extend('augmentEcommerceRequiredFields', $fields);

        return $fields;
    }

    /**
     * Is the member a member of the ShopAdmin Group.
     *
     * @return bool
     */
    public function IsShopAdmin()
    {
        if (Permission::checkMember($this->owner, 'ADMIN')) {
            return true;
        }

        return Permission::checkMember($this->owner, EcommerceConfig::get(EcommerceRole::class, 'admin_permission_code'));
    }

    /**
     * Is the member a member of the SHOPASSISTANTS Group.
     *
     * @return bool
     */
    public function IsShopAssistant()
    {
        if ($this->getOwner()->IsShopAdmin()) {
            return true;
        }

        return Permission::checkMember($this->owner, EcommerceConfig::get(EcommerceRoleAssistant::class, 'assistant_permission_code'));
    }

    /**
     * Is the member a member of the SHOPASSISTANTS Group.
     *
     * @return bool
     */
    public function CanProcessOrders()
    {
        if ($this->getOwner()->IsShopAdmin()) {
            return true;
        }

        return Permission::checkMember($this->owner, EcommerceConfig::get(EcommerceRole::class, 'process_orders_permission_code'));
    }

    /**
     * returns the last (submitted) order  by the member.
     *
     * @param bool $includeUnsubmittedOrders - set to TRUE to include unsubmitted orders
     */
    public function LastOrder(?bool $includeUnsubmittedOrders = false)
    {
        $onlySubmittedOrders = ! $includeUnsubmittedOrders;
        $orders = Order::get_datalist_of_orders_with_submit_record($onlySubmittedOrders);

        return $orders
            ->sort(['OrderStatusLog.ID' => 'DESC'])
            // not the orders that are held until the email address is verified: they were not placed by this member.
            ->filter(['MemberID' => $this->getOwner()->ID, 'EmailNotVerified' => 0])
            ->first();
    }

    public function previousUnsubmittedOrders(int $currentOrderID, ?int $firstStepID = 0)
    {
        $owner = $this->getOwner();
        if (! $firstStepID) {
            $firstStepID = DataObject::get_one(OrderStep::class)->ID;
        }
        return Order::get()
            ->filter(
                [
                    'MemberID' => $owner->ID,
                    'StatusID' => [0, $firstStepID],
                    'ID:not' => $currentOrderID
                ]
            )
            ->sort(['ID' => 'DESC'])
            ->first()
        ;
    }

    /**
     * standard SS method
     * If the email address of a member changes then the new email address has not been verified
     * (only the old one was), so it must be verified again with a one-time code
     * before guest orders with that email address can be linked to the member.
     */
    public function onBeforeWrite()
    {
        $owner = $this->getOwner();
        if ($owner->isInDB() && $owner->EmailVerifiedAt) {
            $changes = $owner->getChangedFields(false, DataObject::CHANGE_VALUE);
            if (isset($changes['Email'])) {
                $before = mb_strtolower(trim((string) ($changes['Email']['before'] ?? '')));
                $after = mb_strtolower(trim((string) ($changes['Email']['after'] ?? '')));
                if ($before !== $after) {
                    $owner->EmailVerifiedAt = null;
                }
            }
        }
    }

    /**
     * standard SS method
     * Make sure the member is added as a customer.
     */
    public function onAfterWrite()
    {
        $owner = $this->getOwner();
        $customerGroup = self::get_customer_group();
        if ($customerGroup) {
            $existingMembers = $customerGroup->Members();
            if ($existingMembers) {
                $existingMembers->add($owner);
            }
        }
    }

    /**
     * Finds previous addresses from the member of the current address.
     *
     * @param string $type
     * @param int    $excludeID      - the ID of the record to exlcude (if any)
     * @param bool   $onlyLastRecord - only select one
     * @param bool   $keepDoubles    - keep addresses that are the same (if set to false, only unique addresses are returned)
     *
     * @return \SilverStripe\ORM\ArrayList (BillingAddresses | ShippingAddresses)
     */
    public function previousOrderAddresses(?string $type = BillingAddress::class, ?int $excludeID = 0, ?bool $onlyLastRecord = false, ?bool $keepDoubles = false)
    {
        $returnArrayList = new ArrayList();
        if ($this->getOwner()->exists()) {
            $fieldName = Config::inst()->get($type, 'table_name') . 'ID';
            $limit = 999;
            if ($onlyLastRecord) {
                $limit = 1;
            }
            $addresses = $type::get()
                ->where(
                    // NB: not the addresses of orders that are held until the email address is verified:
                    // someone else entered those, they must never show up as addresses of this member.
                    '"Obsolete" = 0 AND "Order"."EmailNotVerified" = 0 AND "Order"."MemberID" = ' . (int) $this->getOwner()->ID
                )
                ->sort(['LastEdited' => 'DESC'])
                ->exclude(['ID' => $excludeID])
                ->limit($limit)
                ->innerJoin('Order', '"Order"."' . $fieldName . '" = "OrderAddress"."ID"');
            if ($addresses->exists()) {
                if ($keepDoubles) {
                    foreach ($addresses as $address) {
                        $returnArrayList->push($address);
                    }
                } else {
                    $addressCompare = [];
                    foreach ($addresses as $address) {
                        $comparisonString = $address->comparisonString();
                        if (in_array($comparisonString, $addressCompare, true)) {
                            //do nothing
                        } else {
                            $addressCompare[$address->ID] = $comparisonString;
                            $returnArrayList->push($address);
                        }
                    }
                }
            }
        }

        return $returnArrayList;
    }

    /**
     * Finds the last address used by this member.
     *
     * @param string $type
     * @param int    $excludeID - the ID of the record to exlcude (if any)
     */
    public function previousOrderAddress(?string $type = BillingAddress::class, ?int $excludeID = 0)
    {
        $addresses = $this->previousOrderAddresses($type, $excludeID, true);
        if ($addresses->exists()) {
            return $addresses->First();
        }
    }

    public function LoginAsLink() : string
    {
        return ShoppingCartController::login_as_link($this->getOwner()->ID);
    }

    /**
     * link to edit the record.
     *
     * @param null|string $action - e.g. edit
     *
     * @return string
     */
    public function CMSEditLink($action = null)
    {
        return CMSEditLinkAPI::find_edit_link_for_object($this->owner);
    }

    public function LastPurchaseTs(): ?int
    {
        $successfulPayments = DB::query(
            "SELECT \"EcommercePayment\".\"Created\"
                FROM \"EcommercePayment\"
                INNER JOIN \"Order\"
                ON \"EcommercePayment\".\"OrderID\" = \"Order\".\"ID\"
                WHERE
                    \"EcommercePayment\".\"Status\" = '" . EcommercePayment::SUCCESS_STATUS . "'
                    AND \"Order\".\"MemberID\" = " . $this->getOwner()->ID . '
                ORDER BY
                    "EcommercePayment"."ID" DESC
                LIMIT 1
            '
        );

        if ($successfulPayments->numRecords()) {
            foreach ($successfulPayments as $successfulPayment) {
                return strtotime((string) $successfulPayment['Created']);
            }
        }
        return null;
    }
}
