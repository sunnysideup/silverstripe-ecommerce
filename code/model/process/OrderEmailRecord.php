<?php

/**
 * @Description: DataObject recording all order emails sent.
 *
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderEmailRecord extends DataObject implements EditableEcommerceObject
{
    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $db = array(
        'From' => 'Varchar(255)',
        'To' => 'Varchar(255)',
        'Subject' => 'Varchar(255)',
        'Content' => 'HTMLText',
        'Result' => 'Boolean',
    );

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $has_one = array(
        'Order' => 'Order',
        'OrderStep' => 'OrderStep',
    );

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $casting = array(
        'Title' => 'Varchar',
        'OrderStepNice' => 'Varchar',
        'ResultNice' => 'Varchar',
    );

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $summary_fields = array(
        'Created' => 'Send',
        'OrderStepNice' => 'What',
        'From' => 'From',
        'To' => 'To',
        'Subject' => 'Subject',
        'ResultNice' => 'Sent Succesfully',
    );

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
        'From' => 'PartialMatchFilter',
        'To' => 'PartialMatchFilter',
        'Subject' => 'PartialMatchFilter',
        //make sure to keep the item below, otherwise they do not show in form
        'OrderStepID' => array(
            'filter' => 'OrderEmailRecordFilters_MultiOptionsetStatusIDFilter',
        ),
        'Result' => true,
    );

    /**
     * casted Variable.
     *
     * @var string
     */
    public function ResultNice()
    {
        return $this->getResultNice();
    }
    public function getResultNice()
    {
        if ($this->Result) {
            return _t('OrderEmailRecord.YES', 'Yes');
        }

        return _t('OrderEmailRecord.NO', 'No');
    }

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Customer Email';
    public function i18n_singular_name()
    {
        return _t('OrderEmailRecord.CUSTOMEREMAIL', 'Customer Email');
    }

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Customer Emails';
    public function i18n_plural_name()
    {
        return _t('OrderEmailRecord.CUSTOMEREMAILS', 'Customer Emails');
    }

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A record of any email that has been sent in relation to an order.';

    /**
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

    //defaults

    /**
     * standard SS variable.
     *
     * @return string
     */
    private static $default_sort = '"Created" DESC';

    /**
     * standard SS method.
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $emailLink = OrderEmailRecord_Review::review_link($this);
        $fields->replaceField('Content', new LiteralField('Content', "<iframe src=\"$emailLink\" width=\"100%\" height=\"300\"  style=\"border: 5px solid #2e7ead; border-radius: 2px;\"></iframe>"));
        $fields->replaceField('OrderID', $fields->dataFieldByName('OrderID')->performReadonlyTransformation());
        $fields->replaceField('OrderStep', new ReadonlyField('OrderStepNice', 'Order Step', $this->OrderStepNice()));

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
            '/admin/sales/'.$this->ClassName.'/EditForm/field/'.$this->ClassName.'/item/'.$this->ID.'/',
            $action
        );
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
        $fieldList = parent::scaffoldSearchFields($_params);
        $fieldList->replaceField('OrderID', new NumericField('OrderID', 'Order Number'));
        $statusOptions = OrderStep::get();
        if ($statusOptions && $statusOptions->count()) {
            $createdOrderStatusID = 0;
            $preSelected = array();
            $createdOrderStatus = $statusOptions->First();
            if ($createdOrderStatus) {
                $createdOrderStatusID = $createdOrderStatus->ID;
            }
            $arrayOfStatusOptions = clone $statusOptions->map('ID', 'Title');
            $arrayOfStatusOptionsFinal = array();
            if (count($arrayOfStatusOptions)) {
                foreach ($arrayOfStatusOptions as $key => $value) {
                    if (isset($_GET['q']['OrderStepID'][$key])) {
                        $preSelected[$key] = $key;
                    }
                    $count = OrderEmailRecord::get()
                        ->Filter(array('OrderStepID' => intval($key)))
                        ->count();
                    if ($count < 1) {
                        //do nothing
                    } else {
                        $arrayOfStatusOptionsFinal[$key] = $value." ($count)";
                    }
                }
            }
            $statusField = new CheckboxSetField(
                'OrderStepID',
                Injector::inst()->get('OrderStep')->i18n_singular_name(),
                $arrayOfStatusOptionsFinal,
                $preSelected
            );
            $fieldList->push($statusField);
        }

        return $fieldList;
    }

    /**
     * casted variable.
     *
     *@ return String
     **/
    public function Title()
    {
        return $this->getTitle();
    }
    public function getTitle()
    {
        $str = 'TO: '.$this->To;
        if ($order = $this->Order()) {
            $str .= ' - '.$this->Order()->getTitle();
            $str .= ' - '.$this->OrderStepNice();
        }

        return $str;
    }

    /**
     * casted variable.
     *
     *@ return String
     **/
    public function OrderStepNice()
    {
        return $this->getOrderStepNice();
    }
    public function getOrderStepNice()
    {
        if ($this->OrderStepID) {
            $orderStep = OrderStep::get()->byID($this->OrderStepID);
            if ($orderStep) {
                return $orderStep->Name;
            }
        }
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
