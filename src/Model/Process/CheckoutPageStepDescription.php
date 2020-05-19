<?php

namespace Sunnysideup\Ecommerce\Model\Process;

use DataObject;
use EditableEcommerceObject;
use Member;
use Permission;
use Config;
use EcommerceConfig;
use TextareaField;
use DropdownField;
use CMSEditLinkAPI;
use DB;



/***
 * Class used to describe the steps in the checkout
 *
 */

class CheckoutPageStepDescription extends DataObject implements EditableEcommerceObject
{
    /**
     * standard SS variable.
     *
     * @Var Array
     */

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * OLD: private static $db (case sensitive)
  * NEW: 
    private static $table_name = '[SEARCH_REPLACE_CLASS_NAME_GOES_HERE]';

    private static $db (COMPLEX)
  * EXP: Check that is class indeed extends DataObject and that it is not a data-extension!
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
    
    private static $table_name = 'CheckoutPageStepDescription';


/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: private static $db = (case sensitive)
  * NEW: private static $db = (COMPLEX)
  * EXP: Make sure to add a private static $table_name!
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
    private static $db = [
        'Heading' => 'Varchar',
        'Above' => 'Text',
        'Below' => 'Text',
        'Code' => 'Varchar(100)',
    ];

    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $searchable_fields = [
        'Heading' => 'PartialMatchFilter',
        'Above' => 'PartialMatchFilter',
        'Below' => 'PartialMatchFilter',
    ];

    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $field_labels = [
        'Above' => 'Above Checkout Step',
        'Below' => 'Below Checkout Step',
    ];

    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $summary_fields = [
        'ID' => 'Step Number',
        'Heading' => 'Heading',
    ];

    private static $indexes = [
        'Code' => true,
    ];

    /**
     * standard SS variable.
     *
     * @Var Array
     */
    private static $casting = [
        'Title' => 'Varchar',
    ];

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $singular_name = 'Checkout Step Description';

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $plural_name = 'Checkout Step Descriptions';

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

    public function i18n_singular_name()
    {
        return _t('CheckoutPage.CHECKOUTSTEPDESCRIPTION', 'Checkout Step Description');
    }

    public function i18n_plural_name()
    {
        return _t('CheckoutPage.CHECKOUTSTEPDESCRIPTIONS', 'Checkout Step Descriptions');
    }

    /**
     * these are only created programmatically
     * standard SS method.
     *
     * @param Member $member
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
     * @param Member $member
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
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
     * standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
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
     * standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canDelete($member = null, $context = [])
    {
        $array = EcommerceConfig::get('CheckoutPage_Controller', 'checkout_steps');
        if (in_array($this->getCode, $array, true)) {
            return false;
        }
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
        $fields->replaceField(
            'Code',
            DropdownField::create(
                'Code',
                'Code',
                array_combine(
                    EcommerceConfig::get('CheckoutPage_Controller', 'checkout_steps'),
                    EcommerceConfig::get('CheckoutPage_Controller', 'checkout_steps')
                )
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
            $idArray = [];
            $addCodeSteps = CheckoutPageStepDescription::get()
                ->where('"Code" = \'\' OR "Code" IS NULL');

            $stepsToAdd = $steps;
            if ($addCodeSteps->count()) {
                foreach ($addCodeSteps as $addCodeStep) {
                    DB::alteration_message('Adding Code to Step ...' . $addCodeStep->Code, 'created');
                    $addCodeStep->Code = array_shift($stepsToAdd);
                    $addCodeStep->write();
                }
            }
            foreach ($steps as $code) {
                $filter = ['Code' => $code];
                $obj = CheckoutPageStepDescription::get()->filter($filter)->first();
                if ($obj) {
                    //do nothing
                } else {
                    $obj = CheckoutPageStepDescription::create($filter);
                    $obj->Heading = $this->getDefaultTitle($code);
                    $obj->write();
                    DB::alteration_message("Creating CheckoutPageStepDescription ${code}", 'created');
                }
                $idArray[$obj->ID] = $obj->ID;
            }
            $toDeleteObjects = CheckoutPageStepDescription::get()->exclude(['ID' => $idArray]);
            if ($toDeleteObjects->count()) {
                foreach ($toDeleteObjects as $toDeleteObject) {
                    DB::alteration_message('Deleting CheckoutPageStepDescription ' . $toDeleteObject->Code, 'deleted');
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

