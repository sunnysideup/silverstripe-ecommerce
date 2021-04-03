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

/**
 * @description EcommerceRole provides specific customisations to the {@link Member}
 * class for the ecommerce module.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: extensions

 **/
class EcommerceRole extends DataExtension implements PermissionProvider
{
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
    private static $customer_group_code = 'shopcustomers';

    /**
     * @var string
     */
    private static $customer_group_name = 'Shop Customers';

    /**
     * @var string
     */
    private static $customer_permission_code = 'SHOPCUSTOMER';

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
    private static $admin_role_title = 'Managing Store';

    /**
     * @var array
     */
    private static $admin_role_permission_codes = [
        'CMS_ACCESS_ProductsAndGroupsModelAdmin',
        'CMS_ACCESS_ProductConfigModelAdmin',
        'CMS_ACCESS_SalesAdmin',
        'CMS_ACCESS_SalesAdminExtras',
        'CMS_ACCESS_StoreAdmin',
        'CMS_ACCESS_AssetAdmin',
        'CMS_ACCESS_CMSMain',
        'CMS_ACCESS_SalesAdmin_PROCESS',
    ];

    /**
     * @var string
     */
    private static $assistant_group_code = 'shopassistants';

    /**
     * @var string
     */
    private static $assistant_group_name = 'Shop Assistants';

    /**
     * @var string
     */
    private static $assistant_group_user_first_name = '';

    /**
     * @var string
     */
    private static $assistant_group_user_surname = '';

    /**
     * @var string
     */
    private static $assistant_group_user_email = '';

    /**
     * @var string
     */
    private static $assistant_permission_code = 'SHOPASSISTANTS';

    /**
     * @var string
     */
    private static $assistant_role_title = 'Store Assistant';

    /**
     * @var array
     */
    private static $assistant_role_permission_codes = [
        'CMS_ACCESS_SalesAdmin',
        'CMS_ACCESS_SalesAdminExtras',
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

    public function getCustomerDetails()
    {
        return $this->owner->FirstName . ' ' . $this->owner->Surname .
            ', ' . $this->owner->Email .
            ' (' . $this->owner->Orders()->count() . ')';
    }

    /**
     * @return Group | \SilverStripe\ORM\DataObject|null
     **/
    public static function get_customer_group()
    {
        $customerCode = EcommerceConfig::get(EcommerceRole::class, 'customer_group_code');

        return DataObject::get_one(
            Group::class,
            ['Code' => $customerCode]
        );
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
     * @param \SilverStripe\Security\Member|null $member
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
     * @param \SilverStripe\Security\Member|null $member
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
     * tells us if the current member can process the orders
     *
     * @param \SilverStripe\Security\Member|null $member
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
     **/
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
     **/
    public static function get_assistant_group()
    {
        $assistantCode = EcommerceConfig::get(EcommerceRole::class, 'assistant_group_code');

        return DataObject::get_one(
            Group::class,
            ['Code' => $assistantCode]
        );
    }

    /**
     * @return \SilverStripe\ORM\DataObject (Member)|null
     **/
    public static function get_default_shop_admin_user()
    {
        $group = self::get_admin_group();
        if ($group) {
            return $group->Members()->First();
        }
    }

    /**
     * @return \SilverStripe\ORM\DataObject (Member)|null
     **/
    public static function get_default_shop_assistant_user()
    {
        $group = self::get_assistant_group();
        if ($group) {
            return $group->Members()->First();
        }
    }

    /**
     * you can't delete a Member with one or more orders.
     */
    public function canDelete($member = null, $context = [])
    {
        if ($this->getOrders()->count()) {
            return false;
        }
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
        return Order::get()->filter(['MemberID' => $this->owner->ID]);
    }

    public function CancelledOrders()
    {
        return $this->getCancelledOrders();
    }

    public function getCancelledOrders()
    {
        return Order::get()->filter(['CancelledByID' => $this->owner->ID]);
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
        $perms[EcommerceConfig::get(EcommerceRole::class, 'customer_permission_code')] = [
            'name' => _t(
                'EcommerceRole.CUSTOMER_PERMISSION_ANME',
                'Customers'
            ),
            'category' => $category,
            'help' => _t(
                'EcommerceRole.CUSTOMERS_HELP',
                'Customer Permissions (usually very little)'
            ),
            'sort' => 98,
        ];
        $perms[EcommerceConfig::get(EcommerceRole::class, 'admin_permission_code')] = [
            'name' => EcommerceConfig::get(EcommerceRole::class, 'admin_role_title'),
            'category' => $category,
            'help' => _t(
                'EcommerceRole.ADMINISTRATORS_HELP',
                'Store Manager - can edit everything to do with the e-commerce application.'
            ),
            'sort' => 99,
        ];
        $perms[EcommerceConfig::get(EcommerceRole::class, 'assistant_permission_code')] = [
            'name' => EcommerceConfig::get(EcommerceRole::class, 'assistant_role_title'),
            'category' => $category,
            'help' => _t(
                'EcommerceRole.STORE_ASSISTANTS_HELP',
                'Store Assistant - can only view sales details and makes notes about orders'
            ),
            'sort' => 100,
        ];
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
            '<p class="actionInCMS"><a href="' . $this->owner->LoginAsLink() . '" target="_blank">Login as this customer</a></p>'
        );
        $link = Controller::join_links(
            Director::baseURL(),
            Config::inst()->get(ShoppingCartController::class, 'url_segment') . '/placeorderformember/' . $this->owner->ID . '/'
        );
        $orderForLink = new LiteralField('OrderForCustomerLink', "<p class=\"actionInCMS\"><a href=\"{$link}\" target=\"_blank\">Place order for customer</a></p>");
        $fields->addFieldsToTab(
            'Root.Orders',
            [
                $orderField,
                $preferredCurrencyField,
                $notesFields,
                $loginAsField,
                $orderForLink,
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
        if ($this->owner->exists()) {
            if ($currency && $currency->exists()) {
                $this->owner->PreferredCurrencyID = $currency->ID;
                $this->owner->write();
            }
        }
    }

    /**
     * get CMS fields describing the member in the CMS when viewing the order.
     *
     * @return CompositeField
     **/
    public function getEcommerceFieldsForCMS()
    {
        $fields = new CompositeField();
        $memberTitle = HTMLReadonlyField::create('MemberTitle', _t('Member.TITLE', 'Name'), '<p>' . $this->owner->getTitle() . '</p>');
        $fields->push($memberTitle);
        $memberEmail = HTMLReadonlyField::create('MemberEmail', _t('Member.EMAIL', 'Email'), '<p><a href="mailto:' . $this->owner->Email . '">' . $this->owner->Email . '</a></p>');
        $fields->push($memberEmail);
        $lastLogin = HTMLReadonlyField::create('MemberLastLogin', _t('Member.LASTLOGIN', 'Last Login'), '<p>' . $this->owner->dbObject('LastVisited') . '</p>');
        $fields->push($lastLogin);
        $group = self::get_customer_group();
        if (! $group) {
            $group = new Group();
        }
        $headerField = HeaderField::create('MemberLinkFieldHeader', _t('Member.EDIT_CUSTOMER', 'Edit Customer'));
        $linkField1 = EcommerceCMSButtonField::create(
            'MemberLinkFieldEditThisCustomer',
            $this->owner->CMSEditLink(),
            _t('Member.EDIT', 'Edit') . ' <i>' . $this->owner->getTitle() . '</i>'
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

            if ($this->owner->exists()) {
                if ($this->owner->Password) {
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
                $updatePasswordLinkField = new LiteralField('UpdatePasswordLink', '<a href="#Password" datano="' . Convert::raw2att(_t('Account.DO_NOT_CREATE_ACCOUNT', 'do not create account')) . '" class="choosePassword passwordToggleLink">choose a password</a>');
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
            $fields = new FieldList(
                new TextField('FirstName', _t('EcommerceRole.FIRSTNAME', 'First Name')),
                new TextField('Surname', _t('EcommerceRole.SURNAME', 'Surname')),
                new EmailField('Email', _t('EcommerceRole.EMAIL', 'Email')),
                $loginDetailsHeader,
                $loginDetailsDescription,
                $updatePasswordLinkField,
                $passwordField
            );

            if ($passwordDoubleCheckField) {
                $fields->push($passwordDoubleCheckField);
            }
        }

        $this->owner->extend('augmentEcommerceFields', $fields);

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
            if ($this->owner->exists()) {
                if ($this->owner->Password) {
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
        $this->owner->extend('augmentEcommerceRequiredFields', $fields);

        return $fields;
    }

    /**
     * Is the member a member of the ShopAdmin Group.
     *
     * @return bool
     **/
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
     **/
    public function IsShopAssistant()
    {
        if ($this->owner->IsShopAdmin()) {
            return true;
        }

        return Permission::checkMember($this->owner, EcommerceConfig::get(EcommerceRole::class, 'assistant_permission_code'));
    }

    /**
     * Is the member a member of the SHOPASSISTANTS Group.
     *
     * @return bool
     **/
    public function CanProcessOrders()
    {
        if ($this->owner->IsShopAdmin()) {
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
            ->Filter(['MemberID' => $this->owner->ID])
            ->First();
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
     **/
    public function previousOrderAddresses($type = BillingAddress::class, $excludeID = 0, $onlyLastRecord = false, $keepDoubles = false)
    {
        $returnArrayList = new ArrayList();
        if ($this->owner->exists()) {
            $fieldName = Config::inst()->get($type, 'table_name') . 'ID';
            $limit = 999;
            if ($onlyLastRecord) {
                $limit = 1;
            }
            $addresses = $type::get()
                ->where(
                    '"Obsolete" = 0 AND "Order"."MemberID" = ' . $this->owner->ID
                )
                ->sort('LastEdited', 'DESC')
                ->exclude(['ID' => $excludeID])
                ->limit($limit)
                ->innerJoin('Order', '"Order"."' . $fieldName . '" = "OrderAddress"."ID"');
            if ($addresses->count()) {
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
     **/
    public function previousOrderAddress($type = BillingAddress::class, $excludeID = 0)
    {
        $addresses = $this->previousOrderAddresses($type, $excludeID, true);
        if ($addresses->count()) {
            return $addresses->First();
        }
    }

    public function LoginAsLink()
    {
        return Controller::join_links(
            Director::baseURL(),
            Config::inst()->get(ShoppingCartController::class, 'url_segment') .
            '/loginas/' . $this->owner->ID . '/'
        );
    }

    /**
     * link to edit the record.
     *
     * @param string|null $action - e.g. edit
     *
     * @return string
     */
    public function CMSEditLink($action = null)
    {
        return CMSEditLinkAPI::find_edit_link_for_object($this->owner);
    }
}
