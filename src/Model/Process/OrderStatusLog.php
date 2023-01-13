<?php

namespace Sunnysideup\Ecommerce\Model\Process;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\CmsEditLinkField\Api\CMSEditLinkAPI;
use Sunnysideup\CmsEditLinkField\Forms\Fields\CMSEditLinkField;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\Fields\EcommerceClassNameOrTypeDropdownField;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLogs\OrderStatusLogSubmitted;
use Sunnysideup\Ecommerce\Tasks\EcommerceTaskDebugCart;
use Sunnysideup\Ecommerce\Traits\OrderCached;

/**
 * @description: see OrderStep.md
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 */
class OrderStatusLog extends DataObject implements EditableEcommerceObject
{
    use OrderCached;

    /**
     * @var array
     */
    private static $available_log_classes_array = [];

    /**
     * @var string
     */
    private static $order_status_log_class_used_for_submitting_order = OrderStatusLogSubmitted::class;

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $table_name = 'OrderStatusLog';

    private static $db = [
        'Title' => 'Varchar(100)',
        'Note' => 'HTMLText',
        'InternalUseOnly' => 'Boolean',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $has_one = [
        'Author' => Member::class,
        'Order' => Order::class,
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $casting = [
        'CustomerNote' => 'HTMLText',
        'Type' => 'Varchar',
        'InternalUseOnlyNice' => 'Varchar',
        'PopUpLink' => 'Varchar',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $summary_fields = [
        'Created' => 'Date',
        'Order.Title' => 'Order',
        'Type' => 'Type',
        'Title' => 'Title',
        'InternalUseOnlyNice' => 'Internal use only',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $defaults = [
        'InternalUseOnly' => true,
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $searchable_fields = [
        'OrderID' => [
            'field' => NumericField::class,
            'title' => 'Order Number',
        ],
        'ClassName' => [
            'title' => 'Type',
            'filter' => 'ExactMatchFilter',
        ],
        'Title' => 'PartialMatchFilter',
        'Note' => 'PartialMatchFilter',
    ];

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Order Log Entry';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Order Log Entries';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A record of anything that happened with an order.';

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $default_sort = [
        'ID' => 'DESC',
    ];

    private static $indexes = [
        'Title' => true,
        'InternalUseOnly' => true,
    ];

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

    public function PopUpLink()
    {
        return $this->getPopUpLink();
    }

    public function getPopUpLink()
    {
        $className = 'Sunnysideup\\DataObjectSorter\\DataObjectOneRecordUpdateController';
        if (class_exists($className)) {
            $link = $className::popup_link($this->getOwner()->ClassName, $this->getOwner()->ID, $this->getPopUpLinkTitle());
        } else {
            $link = '<a href="' . $this->CMSEditLink() . '">' . $this->getPopUpLinkTitle() . '</a>"';
        }

        return DBHTMLText::create_field(
            'HTMLText',
            $link
        );
    }

    /**
     * Standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }
        //is the member is a shop assistant they can always view it
        if (EcommerceRole::current_member_is_shop_assistant($member)) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }
        //is the member is a shop assistant they can always view it
        if (EcommerceRole::current_member_is_shop_assistant($member)) {
            return true;
        }
        if ($this->InternalUseOnly) {
            //only Shop Administrators can see it ...
            return false;
        }
        $order = $this->getOrderCached();
        if ($order && $order->canView($member)) {
            return true;
        }

        return parent::canView($member);
    }

    /**
     * Standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }

        $order = $this->getOrderCached();
        if ($order) {
            //Order Status Logs are so basic, anyone can edit them
            if (OrderStatusLog::class === $this->ClassName) {
                return $order->canView($member);
            }

            if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
                return $order->canEdit($member);
            }
        }

        return false;
    }

    /**
     * Standard SS method
     * logs can never be deleted...
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }

        return false;
    }

    public function i18n_singular_name()
    {
        return _t('OrderStatusLog.ORDERLOGENTRY', 'Order Log Entry');
    }

    public function i18n_plural_name()
    {
        return _t('OrderStatusLog.ORDERLOGENTRIES', 'Order Log Entries');
    }

    /**
     * standard SS method.
     */
    public function populateDefaults()
    {
        if (Security::database_is_ready()) {
            $this->AuthorID = Member::currentUserID();
        }

        return parent::populateDefaults();
    }

    /**
     * @return \SilverStripe\Forms\FieldList
     */
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
        if ($this->exists() && $this->OrderID) {
            $order = $this->getOrderCached();
            if ($order && $order->exists()) {
                $fields->removeByName('OrderID');
                $fields->addFieldToTab(
                    'Root.Main',
                    CMSEditLinkField::create(
                        'OrderID',
                        $order->singular_name(),
                        $order
                    )
                );
            }
        }

        //ClassName Field
        $availableLogs = EcommerceConfig::get(OrderStatusLog::class, 'available_log_classes_array');
        $availableLogs = array_merge($availableLogs, [EcommerceConfig::get(OrderStatusLog::class, 'order_status_log_class_used_for_submitting_order')]);

        $availableLogsAssociative = [];

        foreach ($availableLogs as $className) {
            $availableLogsAssociative[$className] = Injector::inst()->get($className)->singular_name();
        }
        $title = _t('OrderStatusLog.TYPE', 'Type');
        if (($this->exists() || $this->limitedToOneClassName())
                && $this->ClassName &&
                isset($availableLogsAssociative[$this->ClassName])
        ) {
            $fields->removeByName('ClassName');
            $fields->addFieldsToTab(
                'Root.Main',
                [
                    HiddenField::create('ClassName'),
                    ReadonlyField::create(
                        'ClassNameTitle',
                        $title,
                        $availableLogsAssociative[$this->ClassName]
                    ),
                ],
                'Title'
            );
        } else {
            $ecommerceClassNameOrTypeDropdownField = EcommerceClassNameOrTypeDropdownField::create(
                'ClassName',
                _t('OrderStatusLog.TYPE', 'Type'),
                OrderStatusLog::class,
                $availableLogsAssociative
            );
            $ecommerceClassNameOrTypeDropdownField->setIncludeBaseClass(true);
            $fields->addFieldToTab('Root.Main', $ecommerceClassNameOrTypeDropdownField, 'Title');
        }
        $fields->replaceField(
            'OrderID',
            CMSEditLinkField::create(
                'OrderID',
                Injector::inst()->get(Order::class)->singular_name(),
                $this->getOrderCached()
            )
        );

        return $fields;
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
        return CMSEditLinkAPI::find_edit_link_for_object($this, $action);
    }

    /**
     * @return string
     */
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
     * @return \SilverStripe\Forms\FieldList
     */
    public function scaffoldSearchFields($_params = null)
    {
        $fields = parent::scaffoldSearchFields($_params);
        $fields->replaceField('OrderID', NumericField::create('OrderID', 'Order Number'));

        $availableLogs = EcommerceConfig::get(OrderStatusLog::class, 'available_log_classes_array');

        $availableLogs = array_merge($availableLogs, [EcommerceConfig::get(OrderStatusLog::class, 'order_status_log_class_used_for_submitting_order')]);

        $ecommerceClassNameOrTypeDropdownField = EcommerceClassNameOrTypeDropdownField::create('ClassName', 'Type', OrderStatusLog::class, $availableLogs);
        $ecommerceClassNameOrTypeDropdownField->setIncludeBaseClass(true);

        $fields->replaceField('ClassName', $ecommerceClassNameOrTypeDropdownField);

        return $fields;
    }

    /**
     * @return string
     */
    public function CustomerNote()
    {
        return $this->getCustomerNote();
    }

    public function getCustomerNote()
    {
        return $this->Note;
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

    protected function getPopUpLinkTitle(): string
    {
        return 'Update Details';
    }

    /**
     * standard SS method.
     */
    protected function onBeforeWrite()
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
        if (! $this->AuthorID) {
            $member = Security::getCurrentUser();
            if ($member) {
                $this->AuthorID = $member->ID;
            }
        }
        if (! $this->Title) {
            $this->Title = _t('OrderStatusLog.ORDERUPDATE', 'Order Update');
        }
    }

    /**
     * when being created, can the user choose the type of log?
     *
     * @return bool
     */
    protected function limitedToOneClassName()
    {
        return OrderStatusLog::class !== $this->ClassName;
    }
}
