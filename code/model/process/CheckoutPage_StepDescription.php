<?php


/***
 * Class used to describe the steps in the checkout
 *
 */

class CheckoutPage_StepDescription extends DataObject implements EditableEcommerceObject
{
    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $db = array(
        'Heading' => 'Varchar',
        'Above' => 'Text',
        'Below' => 'Text',
    );

    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $searchable_fields = array(
        'Heading' => 'PartialMatchFilter',
        'Above' => 'PartialMatchFilter',
        'Below' => 'PartialMatchFilter',
    );

    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $field_labels = array(
        'Above' => 'Above Checkout Step',
        'Below' => 'Below Checkout Step',
    );

    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $summary_fields = array(
        'ID' => 'Step Number',
        'Heading' => 'Heading',
    );

    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $casting = array(
        'Code' => 'Varchar',
        'Title' => 'Varchar',
    );

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $singular_name = 'Checkout Step Description';
    public function i18n_singular_name()
    {
        return _t('CheckoutPage.CHECKOUTSTEPDESCRIPTION', 'Checkout Step Description');
    }

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $plural_name = 'Checkout Step Descriptions';
    public function i18n_plural_name()
    {
        return _t('CheckoutPage.CHECKOUTSTEPDESCRIPTIONS', 'Checkout Step Descriptions');
    }

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A step within the checkout process (e.g. confirm details).';

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
        if( ! $member) {
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
     * standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canEdit($member = null)
    {
        if( ! $member) {
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
     * standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        $array = EcommerceConfig::get('CheckoutPage_Controller', 'checkout_steps');
        if (in_array($this->getCode, $array)) {
            return false;
        }
        if( ! $member) {
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
     * standard SS method.
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField('Description', new TextareaField('Description', _t('Checkout.DESCRIPTION', 'Description')));
        $fields->replaceField('Above', new TextareaField('Above', _t('Checkout.ABOVE', 'Top of section note')));
        $fields->replaceField('Below', new TextareaField('Below', _t('Checkout.BELOW', 'Bottom of section note')));

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
        return Controller::join_links(
            Director::baseURL(),
            '/admin/shop/'.$this->ClassName.'/EditForm/field/'.$this->ClassName.'/item/'.$this->ID.'/',
            $action
        );
    }

    /**
     * casted variable.
     *
     * @return string
     */
    public function Code()
    {
        return $this->getCode();
    }
    public function getCode()
    {
        $array = EcommerceConfig::get('CheckoutPage_Controller', 'checkout_steps');
        $number = $this->ID - 1;
        if (is_array($array) && isset($array[$number])) {
            return $array[$number];
        }

        return _t('CheckoutPage.ERROR', 'Error');
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
        return $this->Heading;
    }

    /**
     * standard SS method.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $steps = EcommerceConfig::get('CheckoutPage_Controller', 'checkout_steps');
        if (is_array($steps) && count($steps)) {
            $idArray = array();
            foreach ($steps as $id => $code) {
                $newID = $id + 1;
                $idArray[$newID] = $newID;
                if ($obj = CheckoutPage_StepDescription::get()->byID($newID)) {
                    //do nothing
                } else {
                    $obj = self::create();
                    $obj->ID = $newID;
                    $obj->Heading = $this->getDefaultTitle($code);
                    $obj->write();
                    DB::alteration_message("Creating CheckoutPage_StepDescription $code", 'created');
                }
            }
            $toDeleteObjects = CheckoutPage_StepDescription::get()->exclude(array('ID' => $idArray));
            if ($toDeleteObjects->count()) {
                foreach ($toDeleteObjects as $toDeleteObject) {
                    DB::alteration_message('Deleting CheckoutPage_StepDescription '.$toDeleteObject->ID, 'deleted');
                    $toDeleteObject->delete();
                }
            }
        }
    }

    /**
     * turns code into title (default values).
     *
     * @param string $code - code
     *
     * @return string
     */
    private function getDefaultTitle($code)
    {
        switch ($code) {
            case 'orderitems':
                return _t('CheckoutPage.ORDERITEMS', 'Order items');
                break;
            case 'orderformaddress':
                return _t('CheckoutPage.ORDERFORMADDRESS', 'Your details');
                break;
            case 'orderconfirmationandpayment':
                return _t('CheckoutPage.ORDERCONFIRMATIONANDPAYMENT', 'Confirm and pay');
                break;
        }

        return $code;
    }
}
