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
class OrderStep extends DataObject implements EditableEcommerceObject
{

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $db = array(
        'Name' => 'Varchar(50)',
        'Code' => 'Varchar(50)',
        'Description' => 'Text',
        'EmailSubject' => 'Varchar(200)',
        'CustomerMessage' => 'HTMLText',
        //customer privileges
        'CustomerCanEdit' => 'Boolean',
        'CustomerCanCancel' => 'Boolean',
        'CustomerCanPay' => 'Boolean',
        //What to show the customer...
        'ShowAsUncompletedOrder' => 'Boolean',
        'ShowAsInProcessOrder' => 'Boolean',
        'ShowAsCompletedOrder' => 'Boolean',
        'HideStepFromCustomer' => 'Boolean',
        //sorting index
        'Sort' => 'Int',
        'DeferTimeInSeconds' => 'Int',
        'DeferFromSubmitTime' => 'Boolean'
    );



    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $indexes = array(
        'Code' => true,
        'Sort' => true,
    );

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $has_many = array(
        'Orders' => 'Order',
        'OrderEmailRecords' => 'OrderEmailRecord',
    );

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $field_labels = array(
        'Sort' => 'Sorting Index',
        'CustomerCanEdit' => 'Customer can edit order',
        'CustomerCanPay' => 'Customer can pay order',
        'CustomerCanCancel' => 'Customer can cancel order',
    );

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $summary_fields = array(
        'NameAndDescription' => 'Step',
        'ShowAsSummary' => 'Phase',
    );

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $casting = array(
        'Title' => 'Varchar',
        'CustomerCanEditNice' => 'Varchar',
        'CustomerCanPayNice' => 'Varchar',
        'CustomerCanCancelNice' => 'Varchar',
        'ShowAsUncompletedOrderNice' => 'Varchar',
        'ShowAsInProcessOrderNice' => 'Varchar',
        'ShowAsCompletedOrderNice' => 'Varchar',
        'HideStepFromCustomerNice' => 'Varchar',
        'HasCustomerMessageNice' => 'Varchar',
        'ShowAsSummary' => 'HTMLText',
        'NameAndDescription' => 'HTMLText'
    );

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $searchable_fields = array(
        'Name' => array(
            'title' => 'Name',
            'filter' => 'PartialMatchFilter',
        ),
        'Code' => array(
            'title' => 'Code',
            'filter' => 'PartialMatchFilter',
        ),
    );


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
        return $this->Name;
    }

    /**
     * casted variable.
     *
     * @return string
     */
    public function CustomerCanEditNice()
    {
        return $this->getCustomerCanEditNice();
    }
    public function getCustomerCanEditNice()
    {
        if ($this->CustomerCanEdit) {
            return _t('OrderStep.YES', 'Yes');
        }

        return _t('OrderStep.NO', 'No');
    }

    /**
     * casted variable.
     *
     * @return string
     */
    public function CustomerCanPayNice()
    {
        return $this->getCustomerCanPayNice();
    }
    public function getCustomerCanPayNice()
    {
        if ($this->CustomerCanPay) {
            return _t('OrderStep.YES', 'Yes');
        }

        return _t('OrderStep.NO', 'No');
    }

    /**
     * casted variable.
     *
     * @return string
     */
    public function CustomerCanCancelNice()
    {
        return $this->getCustomerCanCancelNice();
    }
    public function getCustomerCanCancelNice()
    {
        if ($this->CustomerCanCancel) {
            return _t('OrderStep.YES', 'Yes');
        }

        return _t('OrderStep.NO', 'No');
    }

    public function ShowAsUncompletedOrderNice()
    {
        return $this->getShowAsUncompletedOrderNice();
    }
    public function getShowAsUncompletedOrderNice()
    {
        if ($this->ShowAsUncompletedOrder) {
            return _t('OrderStep.YES', 'Yes');
        }

        return _t('OrderStep.NO', 'No');
    }

    /**
     * casted variable.
     *
     * @return string
     */
    public function ShowAsInProcessOrderNice()
    {
        return $this->getShowAsInProcessOrderNice();
    }
    public function getShowAsInProcessOrderNice()
    {
        if ($this->ShowAsInProcessOrder) {
            return _t('OrderStep.YES', 'Yes');
        }

        return _t('OrderStep.NO', 'No');
    }

    /**
     * casted variable.
     *
     * @return string
     */
    public function ShowAsCompletedOrderNice()
    {
        return $this->getShowAsCompletedOrderNice();
    }
    public function getShowAsCompletedOrderNice()
    {
        if ($this->ShowAsCompletedOrder) {
            return _t('OrderStep.YES', 'Yes');
        }

        return _t('OrderStep.NO', 'No');
    }

    /**
     * do not show in steps at all.
     * @return boolean
     */
    public function HideFromEveryone()
    {
        return false;
    }

    /**
     * casted variable.
     *
     * @return string
     */
    public function HideStepFromCustomerNice()
    {
        return $this->getHideStepFromCustomerNice();
    }

    public function getHideStepFromCustomerNice()
    {
        if ($this->HideStepFromCustomer) {
            return _t('OrderStep.YES', 'Yes');
        }

        return _t('OrderStep.NO', 'No');
    }

    /**
     * standard SS variable.
     *
     * @return string
     */
    private static $singular_name = 'Order Step';
    public function i18n_singular_name()
    {
        return _t('OrderStep.ORDERSTEP', 'Order Step');
    }

    /**
     * standard SS variable.
     *
     * @return string
     */
    private static $plural_name = 'Order Steps';
    public function i18n_plural_name()
    {
        return _t('OrderStep.ORDERSTEPS', 'Order Steps');
    }

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A step that any order goes through.';

    /**
     * SUPER IMPORTANT TO KEEP ORDER!
     * standard SS variable.
     *
     * @return string
     */
    private static $default_sort = '"Sort" ASC';

    /**
     * returns all the order steps
     * that the admin should / can edit....
     *
     * @return DataList
     */
    public static function admin_manageable_steps()
    {
        $lastStep = OrderStep::get()->Last();
        return OrderStep::get()->filter(array('CustomerCanEdit' => 0))->exclude(array('ID' => $lastStep->ID));
    }

    /**
     * return StatusIDs (orderstep IDs) from orders that are bad....
     * (basically StatusID values that do not exist)
     *
     * @return array
     */
    public static function bad_order_step_ids()
    {
        $badorderStatus = Order::get()
            ->leftJoin('OrderStep', '"OrderStep"."ID" = "Order"."StatusID"')
            ->where('"OrderStep"."ID" IS NULL AND "StatusID" > 0')
            ->column('StatusID');
        if (is_array($badorderStatus)) {
            return array_unique(array_values($badorderStatus));
        } else {
            return array(-1);
        }
    }

    /**
     * turns code into ID.
     *
     * @param string $code
     * @param int
     */
    public static function get_status_id_from_code($code)
    {
        $otherStatus = OrderStep::get()
            ->filter(array('Code' => $code))
            ->First();
        if ($otherStatus) {
            return $otherStatus->ID;
        }

        return 0;
    }

    /**
     *@return array
     **/
    public static function get_codes_for_order_steps_to_include()
    {
        $newArray = array();
        $array = EcommerceConfig::get('OrderStep', 'order_steps_to_include');
        if (is_array($array) && count($array)) {
            foreach ($array as $className) {
                $code = singleton($className)->getMyCode();
                $newArray[$className] = strtoupper($code);
            }
        }

        return $newArray;
    }

    /**
     * returns a list of ordersteps that have not been created yet.
     *
     * @return array
     **/
    public static function get_not_created_codes_for_order_steps_to_include()
    {
        $array = EcommerceConfig::get('OrderStep', 'order_steps_to_include');
        if (is_array($array) && count($array)) {
            foreach ($array as $className) {
                $obj = $className::get()->First();
                if ($obj) {
                    unset($array[$className]);
                }
            }
        }

        return $array;
    }

    /**
     *@return string
     **/
    public function getMyCode()
    {
        $array = Config::inst()->get($this->ClassName, 'defaults', Config::UNINHERITED);
        if (!isset($array['Code'])) {
            user_error($this->class.' does not have a default code specified');
        }

        return $array['Code'];
    }

    /**
     * IMPORTANT:: MUST HAVE Code must be defined!!!
     * standard SS variable.
     *
     * @return array
     */
    private static $defaults = array(
        'CustomerCanEdit' => 0,
        'CustomerCanCancel' => 0,
        'CustomerCanPay' => 1,
        'ShowAsUncompletedOrder' => 0,
        'ShowAsInProcessOrder' => 0,
        'ShowAsCompletedOrder' => 0,
        'Code' => 'ORDERSTEP',
    );

    /**
     * standard SS method.
     */
    public function populateDefaults()
    {
        parent::populateDefaults();
        $this->Description = $this->myDescription();
    }

    /**
     *@return FieldList
     **/
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        //replacing
        if ($this->canBeDefered()) {
            if ($this->DeferTimeInSeconds) {
                $fields->addFieldToTab(
                    'Root.Queue',
                    HeaderField::create(
                        'WhenWillThisRun',
                        $this->humanReadeableDeferTimeInSeconds()
                    )
                );
            }
            $fields->addFieldToTab(
                'Root.Queue',
                $deferTimeInSecondsField = TextField::create(
                    'DeferTimeInSeconds',
                    _t('OrderStep.DeferTimeInSeconds', 'Seconds in queue')
                )
                ->setRightTitle(
                    _t(
                        'OrderStep.TIME_EXPLANATION',
                        '86,400 seconds is one day ...
                        <br />To make it easier, you can also enter things like <em>1 week</em>, <em>3 hours</em>, or <em>7 minutes</em>.
                        <br />Non-second entries will automatically be converted to seconds.'
                    )
                )
            );
            if ($this->DeferTimeInSeconds) {
                $fields->addFieldToTab(
                    'Root.Queue',
                    $deferTimeInSecondsField = CheckboxField::create(
                        'DeferFromSubmitTime',
                        _t('OrderStep.DeferFromSubmitTime', 'Calculated from submit time?')
                    )
                    ->setDescription(
                        _t(
                            'OrderStep.DeferFromSubmitTime_HELP',
                            'The time in the queue can be calculated from the moment the current ordersteps starts or from the moment the order was submitted (in this case, check the box above) '
                            )
                        )
                );
            }
        }
        if ($this->hasCustomerMessage()) {
            $rightTitle = _t(
                'OrderStep.EXPLAIN_ORDER_NUMBER_IN_SUBJECT',
                'You can use [OrderNumber] as a tag that will be replaced with the actual Order Number.'
            );
            $fields->addFieldToTab(
                'Root.CustomerMessage',
                TextField::create('EmailSubject', _t('OrderStep.EMAILSUBJECT', 'Email Subject'))
                    ->setRightTitle($rightTitle)
            );
            $fields->addFieldToTab('Root.CustomerMessage', $htmlEditorField = new HTMLEditorField('CustomerMessage', _t('OrderStep.CUSTOMERMESSAGE', 'Customer Message (if any)')));
            if ($testEmailLink = $this->testEmailLink()) {
                $fields->addFieldToTab('Root.CustomerMessage', new LiteralField('testEmailLink', '<p><a href="'.$testEmailLink.'" data-popup="true">'._t('OrderStep.VIEW_EMAIL_EXAMPLE', 'View email example in browser').'</a></p>'));
            }
        } else {
            $fields->removeFieldFromTab('Root', 'OrderEmailRecords');
            $fields->removeFieldFromTab('Root.Main', 'EmailSubject');
            $fields->removeFieldFromTab('Root.Main', 'CustomerMessage');
        }
        //adding
        if (!$this->exists() || !$this->isDefaultStatusOption()) {
            $fields->removeFieldFromTab('Root.Main', 'Code');
            $fields->addFieldToTab('Root.Main', new DropdownField('ClassName', _t('OrderStep.TYPE', 'Type'), self::get_not_created_codes_for_order_steps_to_include()), 'Name');
        }
        if ($this->isDefaultStatusOption()) {
            $fields->replaceField('Code', $fields->dataFieldByName('Code')->performReadonlyTransformation());
        }
        //headers
        $fields->addFieldToTab('Root.Main', new HeaderField('WARNING1', _t('OrderStep.CAREFUL', 'CAREFUL! please edit details below with care'), 2), 'Description');
        $fields->addFieldToTab('Root.Main', new HeaderField('WARNING2', _t('OrderStep.CUSTOMERCANCHANGE', 'What can be changed during this step?'), 3), 'CustomerCanEdit');
        $fields->addFieldToTab('Root.Main', new HeaderField('WARNING5', _t('OrderStep.ORDERGROUPS', 'Order groups for customer?'), 3), 'ShowAsUncompletedOrder');
        $fields->addFieldToTab('Root.Main', new HeaderField('HideStepFromCustomerHeader', _t('OrderStep.HIDE_STEP_FROM_CUSTOMER_HEADER', 'Customer Interaction'), 3), 'HideStepFromCustomer');
        //final cleanup
        $fields->removeFieldFromTab('Root.Main', 'Sort');
        $fields->addFieldToTab('Root.Main', new TextareaField('Description', _t('OrderStep.DESCRIPTION', 'Explanation for internal use only')), 'WARNING1');

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
     * tells the order to display itself with an alternative display page.
     * in that way, orders can be displayed differently for certain steps
     * for example, in a print step, the order can be displayed in a
     * PRINT ONLY format.
     *
     * When the method return null, the order is displayed using the standard display page
     *
     * @see Order::DisplayPage
     *
     * @return null|object (Page)
     **/
    public function AlternativeDisplayPage()
    {
        return;
    }

    /**
     * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
     * Usually this is added before ActionNextStepManually.
     *
     * @param FieldList $fields
     * @param Order     $order
     *
     * @return FieldList
     **/
    public function addOrderStepFields(FieldList $fields, Order $order)
    {
        return $fields;
    }

    /**
     *@return ValidationResult
     **/
    public function validate()
    {
        $result = parent::validate();
        $anotherOrderStepWithSameNameOrCode = OrderStep::get()
            ->filter(
                array(
                    'Name' => $this->Name,
                    'Code' => strtoupper($this->Code),
                )
            )
            ->exclude(array('ID' => intval($this->ID)))
            ->First();
        if ($anotherOrderStepWithSameNameOrCode) {
            $result->error(_t('OrderStep.ORDERSTEPALREADYEXISTS', 'An order status with this name already exists. Please change the name and try again.'));
        }

        return $result;
    }

/**************************************************
* moving between statusses...
**************************************************/
    /**
     *initStep:
     * makes sure the step is ready to run.... (e.g. check if the order is ready to be emailed as receipt).
     * should be able to run this function many times to check if the step is ready.
     *
     * @see Order::doNextStatus
     *
     * @param Order object
     *
     * @return bool - true if the current step is ready to be run...
     **/
    public function initStep(Order $order)
    {
        user_error('Please implement the initStep method in a subclass ('.get_class().') of OrderStep', E_USER_WARNING);

        return true;
    }

    /**
     *doStep:
     * should only be able to run this function once
     * (init stops you from running it twice - in theory....)
     * runs the actual step.
     *
     * @see Order::doNextStatus
     *
     * @param Order object
     *
     * @return bool - true if run correctly.
     **/
    public function doStep(Order $order)
    {
        user_error('Please implement the initStep method in a subclass ('.get_class().') of OrderStep', E_USER_WARNING);

        return true;
    }

    /**
     * nextStep:
     * returns the next step (after it checks if everything is in place for the next step to run...).
     *
     * @see Order::doNextStatus
     *
     * @param Order $order
     *
     * @return OrderStep | Null (next step OrderStep object)
     **/
    public function nextStep(Order $order)
    {
        $nextOrderStepObject = OrderStep::get()
            ->filter(array('Sort:GreaterThan' => $this->Sort))
            ->First();
        if ($nextOrderStepObject) {
            return $nextOrderStepObject;
        }

        return;
    }

/**************************************************
* Boolean checks
**************************************************/

    /**
     * Checks if a step has passed (been completed) in comparison to the current step.
     *
     * @param string $code:       the name of the step to check
     * @param bool   $orIsEqualTo if set to true, this method will return TRUE if the step being checked is the current one
     *
     * @return bool
     **/
    public function hasPassed($code, $orIsEqualTo = false)
    {
        $otherStatus = OrderStep::get()
            ->filter(array('Code' => $code))
            ->First();
        if ($otherStatus) {
            if ($otherStatus->Sort < $this->Sort) {
                return true;
            }
            if ($orIsEqualTo && $otherStatus->Code == $this->Code) {
                return true;
            }
        } else {
            user_error("could not find $code in OrderStep", E_USER_NOTICE);
        }

        return false;
    }

    /**
     * @param string $code
     *
     * @return bool
     **/
    public function hasPassedOrIsEqualTo($code)
    {
        return $this->hasPassed($code, true);
    }

    /**
     * @param string $code
     *
     * @return bool
     **/
    public function hasNotPassed($code)
    {
        return (bool) !$this->hasPassed($code, true);
    }

    /**
     * Opposite of hasPassed.
     *
     * @param string $code
     *
     * @return bool
     **/
    public function isBefore($code)
    {
        return (bool) $this->hasPassed($code, false) ? false : true;
    }

    /**
     *@return bool
     **/
    protected function isDefaultStatusOption()
    {
        return in_array($this->Code, self::get_codes_for_order_steps_to_include());
    }

/**************************************************
* Email
**************************************************/

    /**
     * @var string
     */
    protected $emailClassName = '';

    /**
     * returns the email class used for emailing the
     * customer during a specific step (IF ANY!).
     *
     * @return string
     */
    public function getEmailClassName()
    {
        return $this->emailClassName;
    }

    /**
     * return true if done already or mailed successfully now.
     *
     * @param order         $order
     * @param string        $subject
     * @param string        $message
     * @param bool          $resend
     * @param bool | string $adminOnlyOrToEmail you can set to false = send to customer, true: send to admin, or email = send to email
     * @param string        $emailClassName
     *
     * @return boolean;
     */
    protected function sendEmailForStep(
        $order,
        $subject,
        $message = '',
        $resend = false,
        $adminOnlyOrToEmail = false,
        $emailClassName = ''
    ) {
        if (!$this->hasBeenSent($order) || $resend) {
            if (!$subject) {
                $subject = $this->EmailSubject;
            }
            if (!$emailClassName) {
                $emailClassName = $this->getEmailClassName();
            }
            $adminOnlyOrToEmailIsEmail = $adminOnlyOrToEmail && filter_var($adminOnlyOrToEmail, FILTER_VALIDATE_EMAIL);
            if ($this->hasCustomerMessage() || $adminOnlyOrToEmailIsEmail) {
                return $order->sendEmail(
                    $subject,
                    $message,
                    $resend,
                    $adminOnlyOrToEmail,
                    $emailClassName
                );
            } else {
                if (!$emailClassName) {
                    $emailClassName = 'Order_ErrorEmail';
                }
                //looks like we are sending an error, but we are just using this for notification
                $message = _t('OrderStep.THISMESSAGENOTSENTTOCUSTOMER', 'NOTE: This message was not sent to the customer.').'<br /><br /><br /><br />'.$message;
                $outcome = $order->sendAdminNotification(
                    $subject,
                    $message,
                    $resend,
                    $emailClassName
                );
            }
            if ($outcome || Director::isDev()) {
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * sets the email class used for emailing the
     * customer during a specific step (IF ANY!).
     *
     * @param string
     */
    public function setEmailClassName($s)
    {
        $this->emailClassName = $s;
    }

    /**
     * returns a link that can be used to test
     * the email being sent during this step
     * this method returns NULL if no email
     * is being sent OR if there is no suitable Order
     * to test with...
     *
     * @return string
     */
    protected function testEmailLink()
    {
        if ($this->getEmailClassName()) {
            $orders = Order::get()
                ->where('"OrderStep"."Sort" >= '.$this->Sort)
                ->sort('IF("OrderStep"."Sort" > '.$this->Sort.', 0, 1) ASC, "OrderStep"."Sort" ASC, RAND() ASC')
                ->innerJoin('OrderStep', '"OrderStep"."ID" = "Order"."StatusID"');
            if ($orders->count()) {
                if ($order = $orders->First()) {
                    return OrderConfirmationPage::get_email_link($order->ID, $this->getEmailClassName(), $actuallySendEmail = false, $alternativeOrderStepID = $this->ID);
                }
            }
        }
    }

    /**
     * Has an email been sent to the customer for this
     * order step.
     *"-10 days".
     *
     * @param Order $order
     * @param bool  $checkDateOfOrder
     *
     * @return bool
     **/
    public function hasBeenSent(Order $order, $checkDateOfOrder = true)
    {
        //if it has been more than a XXX days since the order was last edited (submitted) then we do not send emails as
        //this would be embarrasing.
        if ($checkDateOfOrder) {
            if ($log = $order->SubmissionLog()) {
                $lastEditedValue = $log->LastEdited;
            } else {
                $lastEditedValue = $order->LastEdited;
            }
            if ((strtotime($lastEditedValue) < strtotime('-'.EcommerceConfig::get('OrderStep', 'number_of_days_to_send_update_email').' days'))) {
                return true;
            }
        }
        $count = OrderEmailRecord::get()
            ->Filter(array(
                'OrderID' => $order->ID,
                'OrderStepID' => $this->ID,
                'Result' => 1,
            ))
            ->count();

        return $count ? true : false;
    }

    /**
     * For some ordersteps this returns true...
     *
     * @return bool
     **/
    protected function hasCustomerMessage()
    {
        return false;
    }


    /**
     * Formatted answer for "hasCustomerMessage".
     *
     * @return string
     */
    public function HasCustomerMessageNice()
    {
        return $this->getHasCustomerMessageNice();
    }
    public function getHasCustomerMessageNice()
    {
        return $this->hasCustomerMessage() ?  _t('OrderStep.YES', 'Yes') :  _t('OrderStep.NO', 'No');
    }

    /**
     * Formatted answer for "hasCustomerMessage".
     *
     * @return string
     */
    public function ShowAsSummary()
    {
        return $this->getShowAsSummary();
    }

    /**
     *
     *
     * @return string
     */
    public function getShowAsSummary()
    {
        $v = '<strong>';
        if ($this->ShowAsUncompletedOrder) {
            $v .= _t('OrderStep.UNCOMPLETED', 'Uncompleted');
        } elseif ($this->ShowAsInProcessOrder) {
            $v .= _t('OrderStep.INPROCESS', 'In process');
        } elseif ($this->ShowAsCompletedOrder) {
            $v .= _t('OrderStep.COMPLETED', 'Completed');
        }
        $v .= '</strong>';
        $canArray = array();
        if ($this->CustomerCanEdit) {
            $canArray[] = _t('OrderStep.EDITABLE', 'edit');
        }
        if ($this->CustomerCanPay) {
            $canArray[] = _t('OrderStep.PAY', 'pay');
        }
        if ($this->CustomerCanCancel) {
            $canArray[] = _t('OrderStep.CANCEL', 'cancel');
        }
        if (count($canArray)) {
            $v .=  '<br />'._t('OrderStep.CUSTOMER_CAN', 'Customer Can').': '.implode(', ', $canArray).'';
        }
        if ($this->hasCustomerMessage()) {
            $v .= '<br />'._t('OrderStep.CUSTOMER_MESSAGES', 'Includes message to customer');
        }
        if ($this->DeferTimeInSeconds) {
            $v .= '<br />'.$this->humanReadeableDeferTimeInSeconds();
        }

        return DBField::create_field('HTMLText', $v);
    }

    /**
     * @return string
     */
    protected function humanReadeableDeferTimeInSeconds()
    {
        if ($this->canBeDefered()) {
            $field = DBField::create_field('SS_DateTime', strtotime('+ '.$this->DeferTimeInSeconds.' seconds'));
            $descr0 = _t('OrderStep.THE', 'The').' '.'<span style="color: #338DC1">'.$this->getTitle().'</span>';
            $descr1 = _t('OrderStep.DELAY_VALUE', 'Order Step, for any order, will run');
            $descr2 = $field->ago();
            $descr3 = $this->DeferFromSubmitTime ?
                    _t('OrderStep.FROM_ORDER_SUBMIT_TIME', 'from the order being submitted') :
                    _t('OrderStep.FROM_START_OF_ORDSTEP', 'from the order arriving on this step');
            return $descr0. ' ' . $descr1.' <span style="color: #338DC1">'.$descr2.'</span> '.$descr3.'.';
        }
        // $dtF = new \DateTime('@0');
        // $dtT = new \DateTime("@".$this->DeferTimeInSeconds);
        //
        // return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes and %s seconds');
    }

    /**
     * Formatted answer for "hasCustomerMessage".
     *
     * @return string
     */
    public function NameAndDescription()
    {
        return $this->getNameAndDescription();
    }

    public function getNameAndDescription()
    {
        $v = '<strong>'.$this->Name.'</strong><br /><em>'.$this->Description.'</em>';

        return DBField::create_field('HTMLText', $v);
    }

    /**
     * This allows you to set the time to something other than the standard DeferTimeInSeconds
     * value based on the order provided.
     *
     * @param Order
     *
     * @return int
     */
    public function CalculatedDeferTimeInSeconds($order)
    {
        return $this->DeferTimeInSeconds;
    }

    /**
     * can this order step be delayed?
     * in general, if there is a customer message
     * we should be able to delay it
     *
     * This method can be overridden in any orderstep
     * @return bool
     **/
    protected function canBeDefered()
    {
        return $this->hasCustomerMessage();
    }


/**************************************************
* Order Status Logs
**************************************************/

    /**
     * The OrderStatusLog that is relevant to the particular step.
     *
     * @var string
     */
    protected $relevantLogEntryClassName = '';

    /**
     * @return string
     */
    public function getRelevantLogEntryClassName()
    {
        return $this->relevantLogEntryClassName;
    }

    /**
     * @param string
     */
    public function setRelevantLogEntryClassName($s)
    {
        $this->relevantLogEntryClassName = $s;
    }

    /**
     * returns the OrderStatusLog that is relevant to this step.
     *
     * @param Order $order
     *
     * @return OrderStatusLog | null
     */
    public function RelevantLogEntry(Order $order)
    {
        if ($className = $this->getRelevantLogEntryClassName()) {
            return $this->RelevantLogEntries($order)->Last();
        }
    }

    /**
     * returns the OrderStatusLogs that are relevant to this step.
     *
     * @param Order $order
     *
     * @return DataObjectSet | null
     */
    public function RelevantLogEntries(Order $order)
    {
        if ($className = $this->getRelevantLogEntryClassName()) {
            return $className::get()->filter(array('OrderID' => $order->ID));
        }
    }

/**************************************************
* Silverstripe Standard Data Object Methods
**************************************************/

    /**
     * Standard SS method
     * These are only created programmatically.
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

        return parent::canEdit($member);
    }

    /**
     * the default for this is TRUE, but for completed order steps
     *
     * we do not allow this.
     *
     * @param  Order $order
     * @param  Member $member optional
     * @return bool
     */
    public function canOverrideCanViewForOrder($order, $member = null)
    {
        //return true if the order can have customer input
        // orders recently saved can also be views
        return
            $this->CustomerCanEdit ||
            $this->CustomerCanCancel ||
            $this->CustomerCanPay;
    }

    /**
     * standard SS method.
     *
     * @param Member | NULL
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
    public function canDelete($member = null)
    {
        //cant delete last status if there are orders with this status
        $nextOrderStepObject = $this->NextOrderStep();
        if ($nextOrderStepObject) {
            //do nothing
        } else {
            $orderCount = Order::get()
                ->filter(array('StatusID' => intval($this->ID) - 0))
                ->count();
            if ($orderCount) {
                return false;
            }
        }
        if ($this->isDefaultStatusOption()) {
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
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        //make sure only one of three conditions applies ...
        if ($this->ShowAsUncompletedOrder) {
            $this->ShowAsInProcessOrder = false;
            $this->ShowAsCompletedOrder = false;
        } elseif ($this->ShowAsInProcessOrder) {
            $this->ShowAsUncompletedOrder = false;
            $this->ShowAsCompletedOrder = false;
        } elseif ($this->ShowAsCompletedOrder) {
            $this->ShowAsUncompletedOrder = false;
            $this->ShowAsInProcessOrder = false;
        }
        if (! $this->canBeDefered()) {
            $this->DeferTimeInSeconds = 0;
            $this->DeferFromSubmitTime = 0;
        } else {
            if (is_numeric($this->DeferTimeInSeconds)) {
                $this->DeferTimeInSeconds = intval($this->DeferTimeInSeconds);
            } else {
                $this->DeferTimeInSeconds = strtotime('+'.$this->DeferTimeInSeconds);
                if ($this->DeferTimeInSeconds > 0) {
                    $this->DeferTimeInSeconds = $this->DeferTimeInSeconds - time();
                }
            }
        }
        $this->Code = strtoupper($this->Code);
    }

    /**
     * move linked orders to the next status
     * standard SS method.
     */
    public function onBeforeDelete()
    {
        parent::onBeforeDelete();
        $previousOrderStepObject = null;
        $nextOrderStepObject = $this->NextOrderStep();
        //backup
        if ($nextOrderStepObject) {
            //do nothing
        } else {
            $previousOrderStepObject = $this->PreviousOrderStep();
        }
        if ($previousOrderStepObject) {
            $ordersWithThisStatus = Order::get()->filter(array('StatusID' => $this->ID));
            if ($ordersWithThisStatus && $ordersWithThisStatus->count()) {
                foreach ($ordersWithThisStatus as $orderWithThisStatus) {
                    $orderWithThisStatus->StatusID = $previousOrderStepObject->ID;
                    $orderWithThisStatus->write();
                }
            }
        }
    }

    /**
     * standard SS method.
     */
    public function onAfterDelete()
    {
        parent::onAfterDelete();
        $this->requireDefaultRecords();
    }

    protected function NextOrderStep()
    {
        return OrderStep::get()
            ->filter(array('Sort:GreaterThan' => $this->Sort))
            ->First();
    }

    protected function PreviousOrderStep()
    {
        return OrderStep::get()
            ->filter(array('Sort:LessThan' => $this->Sort))
            ->First();
    }

    /**
     * standard SS method
     * USED TO BE: Unpaid,Query,Paid,Processing,Sent,Complete,AdminCancelled,MemberCancelled,Cart.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $orderStepsToInclude = EcommerceConfig::get('OrderStep', 'order_steps_to_include');
        $codesToInclude = self::get_codes_for_order_steps_to_include();
        $indexNumber = 0;
        if ($orderStepsToInclude && count($orderStepsToInclude)) {
            if ($codesToInclude && count($codesToInclude)) {
                foreach ($codesToInclude as $className => $code) {
                    $code = strtoupper($code);
                    $filter = array('ClassName' => $className);
                    $indexNumber += 10;
                    $itemCount = OrderStep::get()->filter($filter)->Count();
                    if ($itemCount) {
                        $obj = OrderStep::get()->filter($filter)->First();
                        if ($obj->Code != $code) {
                            $obj->Code = $code;
                            $obj->write();
                        }
                        $parentObj = singleton('OrderStep');
                        if ($obj->Description == $parentObj->myDescription()) {
                            $obj->Description = $obj->myDescription();
                            $obj->write();
                        }
                    } else {
                        $obj = $className::create();
                        $obj->Code = $code;
                        $obj->Description = $obj->myDescription();
                        $obj->write();
                        DB::alteration_message("Created \"$code\" as $className.", 'created');
                    }
                    $obj = OrderStep::get()
                        ->filter(array('Code' => strtoupper($code)))
                        ->First();
                    if ($obj) {
                        if ($obj->Sort != $indexNumber) {
                            $obj->Sort = $indexNumber;
                            $obj->write();
                        }
                    } else {
                        user_error("There was an error in creating the $code OrderStep");
                    }
                }
            }
        }
        $steps = OrderStep::get();
        foreach ($steps as $step) {
            if (!$step->Description) {
                $step->Description = $step->myDescription();
                $step->write();
            }
        }
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
     * Explains the current order step.
     *
     * @return string
     */
    protected function myDescription()
    {
        return _t('OrderStep.DESCRIPTION', 'No description has been provided for this step.');
    }
}
