<?php

namespace Sunnysideup\Ecommerce\Model\Process;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\CmsEditLinkField\Api\CMSEditLinkAPI;
use Sunnysideup\CmsEditLinkField\Forms\Fields\CMSEditLinkField;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Traits\OrderCached;

// Class used to describe the steps in the checkout

class OrderFeedback extends DataObject implements EditableEcommerceObject
{
    use OrderCached;

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $table_name = 'OrderFeedback';

    private static $db = [
        'Rating' => 'Varchar',
        'Note' => 'Text',
        'Actioned' => 'Boolean',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $has_one = [
        'Order' => Order::class,
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $searchable_fields = [
        'Rating' => 'PartialMatchFilter',
        'Note' => 'PartialMatchFilter',
        'OrderID' => [
            'field' => NumericField::class,
            'title' => 'Order Number',
        ],
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $summary_fields = [
        'Order.Title' => 'Order',
        'Created' => 'When',
        'Rating' => 'Rating',
        'Note' => 'Note',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $casting = [
        'Title' => 'Varchar',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $default_sorting = [
        'ID' => 'DESC',
    ];

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Order Feedback';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Checkout Feedback Entries';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'Customer Order Feedback';

    /**
     * standard SS variable.
     *
     * @return bool
     */
    private static $can_create = false;

    public function i18n_singular_name()
    {
        return _t('OrderFeedback.SINGULAR_NAME', 'Order Feedback');
    }

    public function i18n_plural_name()
    {
        return _t('OrderFeedback.PLURAL_NAME', 'Order Feedback Entries');
    }

    /**
     * these are only created programmatically
     * standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    /**
     * standard SS method.
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

        return parent::canView($member);
    }

    /**
     * standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        return false;
    }

    /**
     * standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * standard SS method.
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
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
     * casted variable.
     *
     * @return string
     */
    public function Title()
    {
        return $this->getTitle();
    }

    public function getTitle()
    {
        $string = $this->Created;
        if ($this->getOrderCached()) {
            $string .= ' (' . $this->getOrderCached()->getTitle() . ')';
        }
        $string .= ' - ' . $this->Rating;
        if ($this->Note) {
            $string .= ' / ' . substr($this->Note, 0, 25);
        }

        return $string;
    }

    /**
     * Event handler called before writing to the database.
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->Note = str_replace(["\n", "\r"], ' ¶ ', $this->Note);
        $this->Note = str_replace(['¶  ¶'], ' ¶ ', $this->Note);
        $this->Note = str_replace(['¶  ¶'], ' ¶ ', $this->Note);
        $this->Note = str_replace(['¶  ¶'], ' ¶ ', $this->Note);
    }
}
