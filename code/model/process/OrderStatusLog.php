<?php
/**
 * @description: see OrderStep.md
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderStatusLog extends DataObject implements EditableEcommerceObject
{
    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $db = array(
        'Title' => 'Varchar(100)',
        'Note' => 'HTMLText',
        'InternalUseOnly' => 'Boolean',
    );

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $has_one = array(
        'Author' => 'Member',
        'Order' => 'Order',
    );

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $casting = array(
        'CustomerNote' => 'HTMLText',
        'Type' => 'Varchar',
        'InternalUseOnlyNice' => 'Varchar',
    );

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $summary_fields = array(
        'Created' => 'Date',
        'Type' => 'Type',
        'Title' => 'Title',
        'InternalUseOnlyNice' => 'Internal use only',
    );

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $defaults = array(
        'InternalUseOnly' => true,
    );

    /**
     * casted method.
     *
     * @return string
     */
    public function InternalUseOnlyNice()
    {
        return $this->getInternalUseOnlyNice();
    }
    public function getInternalUseOnlyNice()
    {
        if ($this->InternalUseOnly) {
            return _t('OrderStatusLog.YES', 'Yes');
        }

        return _t('OrderStatusLog.No', 'No');
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canCreate($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
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
    public function canView($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }
        if ($this->InternalUseOnly) {
            //only Shop Administrators can see it ...
            return false;
        } else {
            if ($this->Order()) {
                if ($this->Order()->canView($member)) {
                    return true;
                }
            }
        }

        return parent::canView($member);
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canEdit($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if ($order = $this->Order()) {
            return $order->canEdit($member);
        }

        return false;
    }

    /**
     * Standard SS method
     * logs can never be deleted...
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return false;
    }

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $searchable_fields = array(
        'OrderID' => array(
            'field' => 'NumericField',
            'title' => 'Order Number',
        ),
        'ClassName' => array(
            'title' => 'Type',
            'filter' => 'ExactMatchFilter',
        ),
        'Title' => 'PartialMatchFilter',
        'Note' => 'PartialMatchFilter',
    );

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Order Log Entry';
    public function i18n_singular_name()
    {
        return _t('OrderStatusLog.ORDERLOGENTRY', 'Order Log Entry');
    }

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Order Log Entries';
    public function i18n_plural_name()
    {
        return _t('OrderStatusLog.ORDERLOGENTRIES', 'Order Log Entries');
    }

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A record of anything that happened with an order.';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $default_sort = '"Created" DESC';

    /**
     * standard SS method.
     */
    public function populateDefaults()
    {
        parent::populateDefaults();
        if (Security::database_is_ready()) {
            $this->AuthorID = Member::currentUserID();
        }
    }

    /**
     *@return FieldList
     **/
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->dataFieldByName('Note')->setRows(3);
        $fields->dataFieldByName('Title')->setTitle('Subject');
        $fields->addFieldToTab(
            'Root.Main',
            DropdownField::create(
                'AuthorID',
                _t('OrderStatusLog.AUTHOR', 'Author'),
                EcommerceRole::list_of_admins(true)
            )
        );
        if ($this->AuthorID) {
            if ($this->Author() && $this->Author()->exists()) {
                $fields->addFieldToTab(
                    'Root.Main',
                    $fields->dataFieldByName('AuthorID')->performReadonlyTransformation()
                );
            }
        }

        //OrderID Field
        $fields->removeByName('OrderID');
        if ($this->exists() && $this->OrderID && $this->Order()->exists()) {
            $fields->addFieldToTab('Root.Main', new ReadOnlyField('OrderTitle', _t('OrderStatusLog.ORDER_TITLE', 'Order Title'), $this->Order()->Title()));
        }

        //ClassName Field
        $availableLogs = EcommerceConfig::get('OrderStatusLog', 'available_log_classes_array');
        $availableLogs = array_merge($availableLogs, array(EcommerceConfig::get('OrderStatusLog', 'order_status_log_class_used_for_submitting_order')));
        $availableLogsAssociative = array();
        foreach ($availableLogs as $className) {
            $availableLogsAssociative[$className] = Injector::inst()->get($className)->singular_name();
        }
        $title = _t('OrderStatusLog.TYPE', 'Type');
        if (
                ($this->exists() || $this->limitedToOneClassName())
                && $this->ClassName &&
                isset($availableLogsAssociative[$this->ClassName])
        ) {
            $fields->removeByName('ClassName');
            $fields->addFieldsToTab(
                'Root.Main',
                array(
                    HiddenField::create('ClassName'),
                    ReadonlyField::create(
                        'ClassNameTitle',
                        $title,
                        $availableLogsAssociative[$this->ClassName]
                    )
                ),
                'Title'
            );
        } else {
            $ecommerceClassNameOrTypeDropdownField = EcommerceClassNameOrTypeDropdownField::create(
                'ClassName',
                _t('OrderStatusLog.TYPE', 'Type'),
                'OrderStatusLog',
                $availableLogsAssociative
            );
            $ecommerceClassNameOrTypeDropdownField->setIncludeBaseClass(true);
            $fields->addFieldToTab('Root.Main', $ecommerceClassNameOrTypeDropdownField, 'Title');
        }
        return $fields;
    }

    /**
     * when being created, can the user choose the type of log?
     *
     *
     * @return bool
     */
    protected function limitedToOneClassName()
    {
        if ($this->ClassName == 'OrderStatusLog') {
            return false;
        }
        return true;
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
        return Controller::join_links(
            Director::baseURL(),
            '/admin/sales/'.$this->ClassName.'/EditForm/field/'.$this->ClassName.'/item/'.$this->ID.'/',
            $action
        );
    }

    /**
     * @return string
     **/
    public function Type()
    {
        return $this->getType();
    }
    public function getType()
    {
        return $this->i18n_singular_name();
    }

    /**
     * Determine which properties on the DataObject are
     * searchable, and map them to their default {@link FormField}
     * representations. Used for scaffolding a searchform for {@link ModelAdmin}.
     *
     * Some additional logic is included for switching field labels, based on
     * how generic or specific the field type is.
     *
     * Used by {@link SearchContext}.
     *
     * @param array $_params
     *                       'fieldClasses': Associative array of field names as keys and FormField classes as values
     *                       'restrictFields': Numeric array of a field name whitelist
     *
     * @return FieldList
     */
    public function scaffoldSearchFields($_params = null)
    {
        $fields = parent::scaffoldSearchFields($_params);
        $fields->replaceField('OrderID', new NumericField('OrderID', 'Order Number'));
        $availableLogs = EcommerceConfig::get('OrderStatusLog', 'available_log_classes_array');
        $availableLogs = array_merge($availableLogs, array(EcommerceConfig::get('OrderStatusLog', 'order_status_log_class_used_for_submitting_order')));
        $ecommerceClassNameOrTypeDropdownField = EcommerceClassNameOrTypeDropdownField::create('ClassName', 'Type', 'OrderStatusLog', $availableLogs);
        $ecommerceClassNameOrTypeDropdownField->setIncludeBaseClass(true);
        $fields->replaceField('ClassName', $ecommerceClassNameOrTypeDropdownField);

        return $fields;
    }

    /**
     * standard SS method.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        //START HACK TO PREVENT LOSS OF ORDERID CAUSED BY COMPLEX TABLE FIELDS....
        // THIS MEANS THAT A LOG CAN NEVER SWITCH FROM ONE ORDER TO ANOTHER...
        if ($this->exists()) {
            $orderID = $this->getField('OrderID');
            if ($orderID) {
                $this->OrderID = $orderID;
            }
        }
        //END HACK TO PREVENT LOSS
        if (!$this->AuthorID) {
            if ($member = Member::currentUser()) {
                $this->AuthorID = $member->ID;
            }
        }
        if (!$this->Title) {
            $this->Title = _t('OrderStatusLog.ORDERUPDATE', 'Order Update');
        }
    }

    /**
     *@return string
     **/
    public function CustomerNote()
    {
        return $this->getCustomerNote();
    }
    public function getCustomerNote()
    {
        return $this->Note;
    }

    /**
     * returns the standard EcommerceDBConfig for use within OrderSteps.
     *
     * @return EcommerceDBConfig
     */
    protected function EcomConfig()
    {
        return EcommerceDBConfig::current_ecommerce_db_config();
    }

    /**
     * Debug helper method.
     * Can be called from /shoppingcart/debug/.
     *
     * @return string
     */
    public function debug()
    {
        return EcommerceTaskDebugCart::debug_object($this);
    }
}
