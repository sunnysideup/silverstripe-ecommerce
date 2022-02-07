<?php

namespace Sunnysideup\Ecommerce\Model\Extensions;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\HTMLReadonlyField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use Sunnysideup\CmsEditLinkField\Api\CMSEditLinkAPI;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Control\ShoppingCartController;
use Sunnysideup\Ecommerce\Forms\Fields\EcommerceCMSButtonField;
use Sunnysideup\Ecommerce\Model\Address\BillingAddress;
use Sunnysideup\Ecommerce\Model\Money\EcommerceCurrency;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\PermissionProvider\Api\PermissionProviderFactory;
use Sunnysideup\PermissionProvider\Interfaces\PermissionProviderFactoryProvider;

/**
 * @description EcommerceRole provides specific customisations to the {@link Member}
 * class for the ecommerce module.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: extensions
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

            ->setEmail(EcommerceConfig::get(EcommerceRole::class, 'admin_group_user_email'))
            ->setFirstName(EcommerceConfig::get(EcommerceRole::class, 'admin_group_user_first_name'))
            ->setSurname(EcommerceConfig::get(EcommerceRole::class, 'admin_group_user_surname'))
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
            ->CreateGroup($member = null)
        ;
    }

    public function getCustomerDetails(): string
    {
        if ($this->getOwner()->exists()) {
            $count = $this->getOwner()->Orders()->count();

            return $this->getOwner()->FirstName . ' ' . $this->getOwner()->Surname .
                ', ' . $this->getOwner()->Email .
                ' (' . _t('Member.PREVIOUS_ORDER_COUNT', 'previous orders') . ': ' . $count . ')';
        }

        return 'no customer';
    }

    /**
     * @return null|Group|\SilverStripe\ORM\DataObject
     */
    public static function get_customer_group()
    {
        $customerCode = EcommerceConfig::get(EcommerceRoleCustomer::class, 'customer_group_code');

        return DataObject::get_one(
            Group::class,
            ['Code' => $customerCode]
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

    /**
     * you can't delete a Member with one or more orders.
     *
     * @param \SilverStripe\Security\Member $member
     */
    public function canDelete($member = null)
    {
        if ($this->getOrders()->exists()) {
            return false;
        }

        return parent::canDelete($member);
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

    public function getOrders()
    {
        return Order::get()->filter(['MemberID' => $this->getOwner()->ID]);
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

    /**
     * Update the CMS Fields
     * for /admin/security.
     */
    public function updateCMSFields(FieldList $fields)
    {
        $orderField = $fields->dataFieldByName('Orders');
        if ($orderField) {
            $config = GridFieldConfig_RecordEditor::create();
            $config->removeComponentsByType(GridFieldDeleteAction::class);
            $config->removeComponentsByType(GridFieldAddNewButton::class);
            if ($orderField instanceof GridField) {
                $orderField->setConfig($config);
                $orderField->setList($this->getOrders());
            }
        } else {
            $orderField = new HiddenField('Orders', 'Orders');
        }
        $preferredCurrencyField = $fields->dataFieldByName('PreferredCurrencyID');
        $notesFields = $fields->dataFieldByName('Notes');
        $loginAsField = new LiteralField(
            'LoginAsThisCustomer',
            '<p class="actionInCMS"><a href="' . $this->getOwner()->LoginAsLink() . '" target="_blank">Login as this customer</a></p>'
        );
        $link = Controller::join_links(
            Director::baseURL(),
            Config::inst()->get(ShoppingCartController::class, 'url_segment') . '/placeorderformember/' . $this->getOwner()->ID . '/'
        );
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
    }

    /**
     * Save a preferred currency for a member.
     *
     * @param EcommerceCurrency $currency - object for the currency
     */
    public function SetPreferredCurrency(EcommerceCurrency $currency)
    {
        if ($this->getOwner()->exists()) {
            if ($currency && $currency->exists()) {
                $this->getOwner()->PreferredCurrencyID = $currency->ID;
                $this->getOwner()->write();
            }
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
        $memberTitle = HTMLReadonlyField::create('MemberTitle', _t('Member.TITLE', 'Name'), '<p>' . $this->getOwner()->getTitle() . '</p>');
        $fields->push($memberTitle);
        $memberEmail = HTMLReadonlyField::create('MemberEmail', _t('Member.EMAIL', 'Email'), '<p><a href="mailto:' . $this->getOwner()->Email . '">' . $this->getOwner()->Email . '</a></p>');
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
            _t('Member.EDIT', 'Edit') . ' <i>' . $this->getOwner()->getTitle() . '</i>'
        );
        $fields->push($headerField);
        $fields->push($linkField1);

        if (EcommerceRole::current_member_can_process_orders(Security::getCurrentUser())) {
            $linkField2 = EcommerceCMSButtonField::create(
                'MemberLinkFieldEditAllCustomers',
                CMSEditLinkAPI::find_edit_link_for_object($group),
                _t('Member.EDIT_ALL_CUSTOMERS', 'Edit All ' . $group->Title)
            );
            $fields->push($linkField2);
        }

        return $fields;
    }

    /**
     * @param mixed $mustCreateAccount
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function getEcommerceFields($mustCreateAccount = false)
    {
        if (! EcommerceConfig::get(EcommerceRole::class, 'allow_customers_to_setup_accounts')) {
            //if no accounts are made then we simply return the basics....
            $fields = new FieldList(
                new TextField('FirstName', _t('EcommerceRole.FIRSTNAME', 'First Name')),
                new TextField('Surname', _t('EcommerceRole.SURNAME', 'Surname')),
                new EmailField('Email', _t('EcommerceRole.EMAIL', 'Email'))
            );
        } else {
            Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomPasswordField.js');

            if ($this->getOwner()->exists()) {
                if ($this->getOwner()->Password) {
                    $passwordField = new PasswordField('PasswordCheck1', _t('Account.NEW_PASSWORD', 'New Password'));
                    $passwordDoubleCheckField = new PasswordField('PasswordCheck2', _t('Account.CONFIRM_NEW_PASSWORD', 'Confirm New Password'));
                    $updatePasswordLinkField = new LiteralField('UpdatePasswordLink', '<a href="#Password"  datano="' . Convert::raw2att(_t('Account.DO_NOT_UPDATE_PASSWORD', 'Do not update password')) . '"  class="updatePasswordLink passwordToggleLink secondary-button" rel="Password">' . _t('Account.UPDATE_PASSWORD', 'Update Password') . '</a>');
                }
                //if they dont have a password then we now force them to create one.
                //the fields of which are added further down the line...
                //we simply hide these fields, as they add little extra ....
                $loginDetailsHeader = new HiddenField('LoginDetails', _t('Account.LOGINDETAILS', 'Login Details'), 5);
                $loginDetailsDescription = new HiddenField(
                    'AccountInfo',
                    '<p>' .
                    _t('OrderForm.PLEASE_REVIEW', 'Please review your log in details below.')
                    . '</p>'
                );
            } elseif (EcommerceConfig::get(EcommerceRole::class, 'must_have_account_to_purchase') || $mustCreateAccount) {
                $loginDetailsHeader = new HeaderField('CreateAnAccount', _t('OrderForm.SETUPYOURACCOUNT', 'Create an account'), 3);
                //dont allow people to purchase without creating a password
                $loginDetailsDescription = new LiteralField(
                    'AccountInfo',
                    '<p class"password-info">' .
                    _t('OrderForm.MUSTCREATEPASSWORD', 'Please choose a password to create your account.')
                    . '</p>'
                );
            } else {
                $loginDetailsHeader = new HeaderField('CreateAnAccount', _t('OrderForm.CREATEANACCONTOPTIONAL', 'Create an account (optional)'), 3);
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

            $passwordDoubleCheckField = null;

            if (empty($passwordField)) {
                $passwordField = new PasswordField('PasswordCheck1', _t('Account.CREATE_PASSWORD', 'Password'));
                $passwordDoubleCheckField = new PasswordField('PasswordCheck2', _t('Account.CONFIRM_PASSWORD', 'Confirm Password'));
            }
            if (empty($updatePasswordLinkField)) {
                $updatePasswordLinkField = new LiteralField('UpdatePasswordLink', '');
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

            if ($passwordDoubleCheckField) {
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
            if ($this->getOwner()->exists()) {
                if ($this->getOwner()->Password) {
                    $passwordFieldIsRequired = false;
                }
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
    public function LastOrder($includeUnsubmittedOrders = false)
    {
        //limit to 10
        if ($includeUnsubmittedOrders) {
            $orders = Order::get_datalist_of_orders_with_submit_record(false);
        } else {
            $orders = Order::get_datalist_of_orders_with_submit_record(true);
        }

        return $orders
            ->Filter(['MemberID' => $this->getOwner()->ID])
            ->First()
        ;
    }

    /**
     * standard SS method
     * Make sure the member is added as a customer.
     */
    public function onAfterWrite()
    {
        $customerGroup = self::get_customer_group();
        if ($customerGroup) {
            $existingMembers = $customerGroup->Members();
            if ($existingMembers) {
                $existingMembers->add($this->owner);
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
    public function previousOrderAddresses($type = BillingAddress::class, $excludeID = 0, $onlyLastRecord = false, $keepDoubles = false)
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
                    '"Obsolete" = 0 AND "Order"."MemberID" = ' . $this->getOwner()->ID
                )
                ->sort('LastEdited', 'DESC')
                ->exclude(['ID' => $excludeID])
                ->limit($limit)
                ->innerJoin('Order', '"Order"."' . $fieldName . '" = "OrderAddress"."ID"')
            ;
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
    public function previousOrderAddress($type = BillingAddress::class, $excludeID = 0)
    {
        $addresses = $this->previousOrderAddresses($type, $excludeID, true);
        if ($addresses->exists()) {
            return $addresses->First();
        }
    }

    public function LoginAsLink()
    {
        return Controller::join_links(
            Director::baseURL(),
            Config::inst()->get(ShoppingCartController::class, 'url_segment') .
            '/loginas/' . $this->getOwner()->ID . '/'
        );
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
}
