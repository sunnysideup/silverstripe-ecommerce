<?php


/***
 * Class used to describe the steps in the checkout
 *
 */

class OrderFeedback extends DataObject implements EditableEcommerceObject
{
    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $db = array(
        'Rating' => 'Varchar',
        'Note' => 'Text',
        'Actioned' => 'Boolean'
    );
    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $has_one = array(
        'Order' => 'Order'
    );

    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $searchable_fields = array(
        'Rating' => 'PartialMatchFilter',
        'Note' => 'PartialMatchFilter',
        'OrderID' => array(
            'field' => 'NumericField',
            'title' => 'Order Number',
        )
    );

    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $summary_fields = array(
        'Order.Title' => 'Order',
        'Created' => 'When',
        'Rating' => 'Rating',
        'Note' => 'Note'
    );

    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $casting = array(
        'Title' => 'Varchar'
    );

    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $default_sorting = array(
        'Created' => 'DESC'
    );

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $singular_name = 'Order Feedback';
    public function i18n_singular_name()
    {
        return _t('OrderFeedback.SINGULAR_NAME', 'Order Feedback');
    }

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $plural_name = 'Checkout Feedback Entries';
    public function i18n_plural_name()
    {
        return _t('OrderFeedback.PLURAL_NAME', 'Order Feedback Entries');
    }

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

    /**
     * these are only created programmatically
     * standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canCreate($member = null)
    {
        return false;
    }

    /**
     * standard SS method.
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

        return parent::canView($member);
    }

    /**
     * standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canEdit($member = null)
    {
        return false;
    }

    /**
     * standard SS method.
     *
     * @param Member $member
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
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField(
            'OrderID',
            CMSEditLinkField::create(
                'OrderIDLink',
                Injector::inst()->get('Order')->singular_name(),
                $this->Order()
            )
        );
        return $fields;
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
        if ($this->Order()) {
            $string .= ' ('.$this->Order()->getTitle().')';
        }
        $string .= ' - '.$this->Rating;
        if ($this->Note) {
            $string .= ' / '. substr($this->Note, 0, 25);
        }
        return $string;
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->Note = str_replace(array("\n", "\r"), ' ¶ ', $this->Note);
        $this->Note = str_replace(array("¶  ¶"), ' ¶ ', $this->Note);
        $this->Note = str_replace(array("¶  ¶"), ' ¶ ', $this->Note);
        $this->Note = str_replace(array("¶  ¶"), ' ¶ ', $this->Note);
    }
}
