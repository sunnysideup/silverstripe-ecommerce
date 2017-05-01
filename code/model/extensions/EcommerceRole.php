<?php
/**
 * @description EcommerceRole provides specific customisations to the {@link Member}
 * class for the ecommerce module.
 *
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: extensions
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class EcommerceRole extends DataExtension implements PermissionProvider
{
    private static $max_count_of_members_in_array = 1500;

    private static $api_access = array(
        'view' => array(
            'ID',
            'Orders',
            'PreferredCurrency',
        ),
    );

    /**
     * standard SS method.
     */
    private static $db = array(
        'Notes' => 'Text',
    );

    private static $has_one = array(
        'PreferredCurrency' => 'EcommerceCurrency',
    );

    private static $has_many = array(
        'Orders' => 'Order',
    );

    /**
     *@return Group | NULL
     **/
    public static function get_customer_group()
    {
        $customerCode = EcommerceConfig::get('EcommerceRole', 'customer_group_code');

        return DataObject::get_one(
            'Group',
            array('Code' => $customerCode)
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
        $array = array();
        if ($showUnselectedOption) {
            $array[0] = _t('Member.SELECTCUSTOMER', ' --- SELECT CUSTOMER ---');
        }
        //get customer group
        $customerCode = EcommerceConfig::get('EcommerceRole', 'customer_group_code');
        $group = self::get_customer_group();
        //fill array
        if ($group) {
            $members = $group->Members();
            $membersCount = $members->count();
            if ($membersCount > 0 && $membersCount < Config::inst()->get('EcommerceRole', 'max_count_of_members_in_array')) {
                foreach ($members as $member) {
                    if ($member->Email) {
                        $array[$member->ID] = $member->Email.' ('.$member->getTitle().')';
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
        $array = array();
        if ($showUnselectedOption) {
            $array[0] = _t('Member.SELECT_ECOMMERCE_ADMIN', ' --- SELECT ADMIN ---');
        }
        //get customer group
        $customerCode = EcommerceConfig::get('EcommerceRole', 'customer_group_code');
        $group = self::get_admin_group();
        //fill array
        if ($group) {
            $members = $group->Members();
            $membersCount = $members->count();
            if ($membersCount > 0) {
                foreach ($members as $member) {
                    if ($member->Email) {
                        $array[$member->ID] = $member->Email.' ('.$member->getTitle().')';
                    }
                }
            }
        }
        $group = DataObject::get_one(
            'Group',
            array('Code' => 'administrators')
        );
        //fill array
        if ($group) {
            $members = $group->Members();
            $membersCount = $members->count();
            if ($membersCount > 0) {
                foreach ($members as $member) {
                    if ($member->Email) {
                        $array[$member->ID] = $member->Email.' ('.$member->getTitle().')';
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
     * @param Member | Null $member
     *
     * @return bool
     */
    public static function current_member_is_shop_admin($member = null)
    {
        if (!$member) {
            $member = Member::currentUser();
        }
        if ($member) {
            return $member->IsShopAdmin();
        }

        return false;
    }

    /**
     * tells us if the current member is in the Shop Administrators Group.
     *
     * @param Member | Null $member
     *
     * @return bool
     */
    public static function current_member_is_shop_assistant($member = null)
    {
        if (!$member) {
            $member = Member::currentUser();
        }
        if ($member) {
            return $member->IsShopAssistant();
        }

        return false;
    }

    /**
     * tells us if the current member can process the orders
     *
     * @param Member | Null $member
     *
     * @return bool
     */
    public static function current_member_can_process_orders($member = null)
    {
        if (!$member) {
            $member = Member::currentUser();
        }
        if ($member) {
            return $member->CanProcessOrders();
        }

        return false;
    }

    /**
     * @return DataObject (Group) | NULL
     **/
    public static function get_admin_group()
    {
        $adminCode = EcommerceConfig::get('EcommerceRole', 'admin_group_code');

        return DataObject::get_one(
            'Group',
            array('Code' => $adminCode)
        );
    }

     /**
     * @return DataObject (Group) | NULL
     **/
    public static function get_assistant_group()
    {
        $assistantCode = EcommerceConfig::get('EcommerceRole', 'assistant_group_code');

        return DataObject::get_one(
            'Group',
            array('Code' => $assistantCode)
        );
    }

    /**
     * @return DataObject (Member) | NULL
     **/
    public static function get_default_shop_admin_user()
    {
        $group = self::get_admin_group();
        if ($group) {
            return $group->Members()->First();
        }
    }

    /**
     * @return DataObject (Member) | NULL
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
    public function canDelete($member = null)
    {
        if ($this->getOrders()->count()) {
            return false;
        }
    }

    /**
     * we need this function because $this->Orders does not return anything
     * that is probably because Order links the member twice (placed by and cancelled by).
     *
     * @return DataList
     */
    public function getOrders()
    {
        return Order::get()->filter(array('MemberID' => $this->owner->ID));
    }

    /**
     * creates two permission roles.
     * standard SS Method.
     *
     * @return array
     */
    public function providePermissions()
    {
        $category = EcommerceConfig::get('EcommerceRole', 'permission_category');
        $perms[EcommerceConfig::get('EcommerceRole', 'customer_permission_code')] = array(
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
        );
        $perms[EcommerceConfig::get('EcommerceRole', 'admin_permission_code')] = array(
            'name' => EcommerceConfig::get('EcommerceRole', 'admin_role_title'),
            'category' => $category,
            'help' => _t(
                'EcommerceRole.ADMINISTRATORS_HELP',
                'Store Manager - can edit everything to do with the e-commerce application.'
            ),
            'sort' => 99,
        );
        $perms[EcommerceConfig::get('EcommerceRole', 'assistant_permission_code')] = array(
            'name' => EcommerceConfig::get('EcommerceRole', 'assistant_role_title'),
            'category' => $category,
            'help' => _t(
                'EcommerceRole.STORE_ASSISTANTS_HELP',
                'Store Assistant - can only view sales details and makes notes about orders'
            ),
            'sort' => 100,
        );
        $perms[EcommerceConfig::get('EcommerceRole', 'process_orders_permission_code')] = array(
           'name' => _t(
               'EcommerceRole.PROCESS_ORDERS_PERMISSION_NAME',
               'Can process orders'
           ),
           'category' => $category,
           'help' => _t(
               'EcommerceRole.PROCESS_ORDERS_PERMISSION_HELP',
               'Can the user progress orders through the order steps (e.g. dispatch orders)'
           ),
           'sort' => 101
        );
        return $perms;
    }

    /**
     * Update the CMS Fields
     * for /admin/security.
     *
     * @param FieldList $fields
     *
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $orderField = $fields->dataFieldByName('Orders');
        if ($orderField) {
            $config = GridFieldConfig_RecordEditor::create();
            $config->removeComponentsByType('GridFieldDeleteAction');
            $config->removeComponentsByType('GridFieldAddNewButton');
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
            "<p class=\"actionInCMS\"><a href=\"".$this->owner->LoginAsLink()."\" target=\"_blank\">Login as this customer</a></p>"
        );
        $link = Controller::join_links(
            Director::baseURL(),
            Config::inst()->get('ShoppingCart_Controller', 'url_segment').'/placeorderformember/'.$this->owner->ID.'/'
        );
        $orderForLink = new LiteralField('OrderForCustomerLink', "<p class=\"actionInCMS\"><a href=\"$link\" target=\"_blank\">Place order for customer</a></p>");
        $fields->addFieldsToTab(
            'Root.Orders',
            array(
                $orderField,
                $preferredCurrencyField,
                $notesFields,
                $loginAsField,
                $orderForLink,
            )
        );

        return $fields;
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
        $memberTitle = new ReadonlyField('MemberTitle', _t('Member.TITLE', 'Name'), '<p>'._t('Member.TITLE', 'Name').': '.$this->owner->getTitle().'</p>');
        $memberTitle->dontEscape = true;
        $fields->push($memberTitle);
        $memberEmail = new ReadonlyField('MemberEmail', _t('Member.EMAIL', 'Email'), '<p>'._t('Member.EMAIL', 'Email').': <a href="mailto:'.$this->owner->Email.'">'.$this->owner->Email.'</a></p>');
        $memberEmail->dontEscape = true;
        $fields->push($memberEmail);
        $lastLogin = new ReadonlyField('MemberLastLogin', _t('Member.LASTLOGIN', 'Last Login'), '<p>'._t('Member.LASTLOGIN', 'Last Login').': '.$this->owner->dbObject('LastVisited')->Nice().'</p>');
        $lastLogin->dontEscape = true;
        $fields->push($lastLogin);
        $group = self::get_customer_group();
        if (!$group) {
            $group = new Group();
        }
        $headerField = HeaderField::create('MemberLinkFieldHeader', _t('Member.EDIT_CUSTOMER', 'Edit Customer'));
        $linkField1 = EcommerceCMSButtonField::create(
            'MemberLinkFieldEditThisCustomer',
            $this->owner->CMSEditLink(),
            _t('Member.EDIT', 'Edit').' <i>'.$this->owner->getTitle().'d</i>'
        );
        $fields->push($headerField);
        $fields->push($linkField1);

        if (EcommerceRole::current_member_can_process_orders(Member::currentUser())) {
            $linkField2 = EcommerceCMSButtonField::create(
                'MemberLinkFieldEditAllCustomers',
                CMSEditLinkAPI::find_edit_link_for_object($group),
                _t('Member.EDIT_ALL_CUSTOMERS', 'Edit All '.$group->Title)
            );
            $fields->push($linkField2);
        }
        return $fields;
    }

    /**
     * @param bool $additionalFields: add extra fields.
     *
     * @return FieldList
     */
    public function getEcommerceFields($mustCreateAccount = false)
    {
        if (! EcommerceConfig::get('EcommerceRole', 'allow_customers_to_setup_accounts')) {
            //if no accounts are made then we simply return the basics....
            $fields = new FieldList(
                new HeaderField('PersonalInformation', _t('EcommerceRole.PERSONALINFORMATION', 'Personal Information'), 3),
                new TextField('FirstName', _t('EcommerceRole.FIRSTNAME', 'First Name')),
                new TextField('Surname', _t('EcommerceRole.SURNAME', 'Surname')),
                new EmailField('Email', _t('EcommerceRole.EMAIL', 'Email'))
            );
        } else {
            Requirements::javascript('ecommerce/javascript/EcomPasswordField.js');

            if ($this->owner->exists()) {
                if ($this->owner->Password) {
                    $passwordField = new PasswordField('PasswordCheck1', _t('Account.NEW_PASSWORD', 'New Password'));
                    $passwordDoubleCheckField = new PasswordField('PasswordCheck2', _t('Account.CONFIRM_NEW_PASSWORD', 'Confirm New Password'));
                    $updatePasswordLinkField = new LiteralField('UpdatePasswordLink', '<a href="#Password"  datano="'.Convert::raw2att(_t('Account.DO_NOT_UPDATE_PASSWORD', 'Do not update password')).'"  class="updatePasswordLink passwordToggleLink" rel="Password">'._t('Account.UPDATE_PASSWORD', 'Update Password').'</a>');
                } else {
                    //if they dont have a password then we now force them to create one.
                    //the fields of which are added further down the line...
                }
                //we simply hide these fields, as they add little extra ....
                $loginDetailsHeader = new HiddenField('LoginDetails', _t('Account.LOGINDETAILS', 'Login Details'), 5);
                $loginDetailsDescription = new HiddenField(
                    'AccountInfo',
                    '<p>'.
                    _t('OrderForm.PLEASE_REVIEW', 'Please review your log in details below.')
                    .'</p>'
                );
            } else {
                //login invite right on the top
                if (EcommerceConfig::get('EcommerceRole', 'must_have_account_to_purchase') || $mustCreateAccount) {
                    $loginDetailsHeader = new HeaderField('CreateAnAccount', _t('OrderForm.SETUPYOURACCOUNT', 'Create an account'), 3);
                    //dont allow people to purchase without creating a password
                    $loginDetailsDescription = new LiteralField(
                        'AccountInfo',
                        '<p class"password-info">'.
                        _t('OrderForm.MUSTCREATEPASSWORD', 'Please choose a password to create your account.')
                        .'</p>'
                    );
                } else {
                    $loginDetailsHeader = new HeaderField('CreateAnAccount', _t('OrderForm.CREATEANACCONTOPTIONAL', 'Create an account (optional)'), 3);
                    //allow people to purchase without creating a password
                    $updatePasswordLinkField = new LiteralField('UpdatePasswordLink', '<a href="#Password" datano="'.Convert::raw2att(_t('Account.DO_NOT_CREATE_ACCOUNT', 'do not create account')).'" class="choosePassword passwordToggleLink">choose a password</a>');
                    $loginDetailsDescription = new LiteralField(
                        'AccountInfo',
                        '<p class="password-info">'.
                        _t('OrderForm.SELECTPASSWORD', 'Please enter a password; this will allow you to check your order history in the future.')
                        .'</p>'
                    );
                    //close by default
                }
            }

            if (empty($passwordField)) {
                $passwordField = new PasswordField('PasswordCheck1', _t('Account.CREATE_PASSWORD', 'Password'));
                $passwordDoubleCheckField = new PasswordField('PasswordCheck2', _t('Account.CONFIRM_PASSWORD', 'Confirm Password'));
            }
            if (empty($updatePasswordLinkField)) {
                $updatePasswordLinkField = new LiteralField('UpdatePasswordLink', '');
            }
            $fields = new FieldList(
                new HeaderField('PersonalInformation', _t('EcommerceRole.PERSONALINFORMATION', 'Personal Information'), 3),
                new TextField('FirstName', _t('EcommerceRole.FIRSTNAME', 'First Name')),
                new TextField('Surname', _t('EcommerceRole.SURNAME', 'Surname')),
                new EmailField('Email', _t('EcommerceRole.EMAIL', 'Email')),
                $loginDetailsHeader,
                $loginDetailsDescription,
                $updatePasswordLinkField,
                $passwordField,
                $passwordDoubleCheckField
            );
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
        $fields = array(
            'FirstName',
            'Surname',
            'Email',
        );
        if (EcommerceConfig::get('EcommerceRole', 'must_have_account_to_purchase')) {
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
        } else {
            return Permission::checkMember($this->owner, EcommerceConfig::get('EcommerceRole', 'admin_permission_code'));
        }
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

        return Permission::checkMember($this->owner, EcommerceConfig::get('EcommerceRole', 'assistant_permission_code'));
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

        return Permission::checkMember($this->owner, EcommerceConfig::get('EcommerceRole', 'process_orders_permission_code'));
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
        $lastOrder = $orders
            ->Filter(array('MemberID' => $this->owner->ID))
            ->First();

        return $lastOrder;
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
     * @return ArrayList (BillingAddresses | ShippingAddresses)
     **/
    public function previousOrderAddresses($type = 'BillingAddress', $excludeID = 0, $onlyLastRecord = false, $keepDoubles = false)
    {
        $returnArrayList = new ArrayList();
        if ($this->owner->exists()) {
            $fieldName = $type.'ID';
            $limit = 999;
            if ($onlyLastRecord) {
                $limit = 1;
            }
            $addresses = $type::get()
                ->where(
                    '"Obsolete" = 0 AND "Order"."MemberID" = '.$this->owner->ID
                )
                ->sort('LastEdited', 'DESC')
                ->exclude(array('ID' => $excludeID))
                //->limit($limit)
                ->innerJoin('Order', '"Order"."'.$fieldName.'" = "OrderAddress"."ID"');
            if ($addresses->count()) {
                if ($keepDoubles) {
                    foreach ($addresses as $address) {
                        $returnArrayList->push($address);
                    }
                } else {
                    $addressCompare = array();
                    foreach ($addresses as $address) {
                        $comparisonString = $address->comparisonString();
                        if (in_array($comparisonString, $addressCompare)) {
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
    public function previousOrderAddress($type = 'BillingAddress', $excludeID = 0)
    {
        $addresses = $this->previousOrderAddresses($type, $excludeID, true, false);
        if ($addresses->count()) {
            return $addresses->First();
        }
    }

    public function LoginAsLink()
    {
        return Controller::join_links(
            Director::baseURL(),
            Config::inst()->get('ShoppingCart_Controller', 'url_segment').
            '/loginas/'.$this->owner->ID.'/'
        );
    }

    /**
     * link to edit the record.
     *
     * @param string | Null $action - e.g. edit
     *
     * @return string
     */
    public function CMSEditLink($action = null)
    {
        return CMSEditLinkAPI::find_edit_link_for_object($this->owner);
    }
}
