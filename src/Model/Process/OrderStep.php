<?php

namespace Sunnysideup\Ecommerce\Model\Process;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Email\OrderErrorEmail;
use Sunnysideup\Ecommerce\Email\OrderInvoiceEmail;
use Sunnysideup\Ecommerce\Forms\Fields\EcommerceCMSButtonField;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderSteps\OrderStepArchived;
use Sunnysideup\Ecommerce\Model\Process\OrderSteps\OrderStepCreated;
use Sunnysideup\Ecommerce\Model\Process\OrderSteps\OrderStepSubmitted;
use Sunnysideup\Ecommerce\Pages\OrderConfirmationPage;

/**
 * Class \Sunnysideup\Ecommerce\Model\Process\OrderStep
 *
 * @property string $Name
 * @property string $Code
 * @property string $Description
 * @property string $EmailSubject
 * @property string $CustomerMessage
 * @property bool $CustomerCanEdit
 * @property bool $CustomerCanCancel
 * @property bool $CustomerCanPay
 * @property bool $ShowAsUncompletedOrder
 * @property bool $ShowAsInProcessOrder
 * @property bool $ShowAsCompletedOrder
 * @property bool $HideStepFromCustomer
 * @property int $Sort
 * @property int $DeferTimeInSeconds
 * @property bool $DeferFromSubmitTime
 * @method \SilverStripe\ORM\DataList|\Sunnysideup\Ecommerce\Model\Order[] Orders()
 * @method \SilverStripe\ORM\DataList|\Sunnysideup\Ecommerce\Model\Process\OrderEmailRecord[] OrderEmailRecords()
 * @method \SilverStripe\ORM\DataList|\Sunnysideup\Ecommerce\Model\Process\OrderProcessQueue[] OrderProcessQueueEntries()
 */
class OrderStep extends DataObject implements EditableEcommerceObject
{
    // Email

    /**
     * @var string
     */
    protected $emailClassName = OrderInvoiceEmail::class;

    // Order Status Logs

    /**
     * The OrderStatusLog that is relevant to the particular step.
     *
     * @var string
     */
    protected $relevantLogEntryClassName = '';

    /**
     * @var array
     */
    private static $order_steps_to_include = [
        'step1' => OrderStepCreated::class,
        'step2' => OrderStepSubmitted::class,
        'step3' => OrderStepArchived::class,
    ];

    /**
     * ```php
     *     [
     *         'MethodToReturnTrue' => StepClassName
     *     ]
     * ```
     * MethodToReturnTrue must have an $order as a parameter and bool as the return value
     * e.g. MyMethod(Order $order) : bool;.
     *
     * @var array
     */
    private static $step_logic_conditions = [];

    /**
     * @var int
     */
    private static $number_of_days_to_send_update_email = 10;

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $table_name = 'OrderStep';

    private static $db = [
        'Name' => 'Varchar(50)',
        'Code' => 'Varchar(50)',
        'Description' => 'Text',
        // emails
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
        'DeferFromSubmitTime' => 'Boolean',
    ];

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $indexes = [
        'Code' => true,
        'Sort' => true,
        'ShowAsUncompletedOrder' => true,
        'ShowAsInProcessOrder' => true,
        'ShowAsCompletedOrder' => true,
    ];

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $has_many = [
        'Orders' => Order::class,
        'OrderEmailRecords' => OrderEmailRecord::class,
        'OrderProcessQueueEntries' => OrderProcessQueue::class,
    ];

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $field_labels = [
        'Sort' => 'Sorting Index',
        'CustomerCanEdit' => 'Customer can edit order',
        'CustomerCanPay' => 'Customer can pay order',
        'CustomerCanCancel' => 'Customer can cancel order',
    ];

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $summary_fields = [
        'NameAndDescription' => 'Step',
        'ShowAsSummary' => 'Phase',
        'Orders.Count' => 'Orders',
    ];

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $casting = [
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
        'NameAndDescription' => 'HTMLText',
    ];

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $searchable_fields = [
        'Name' => [
            'title' => 'Name',
            'filter' => 'PartialMatchFilter',
        ],
        'Code' => [
            'title' => 'Code',
            'filter' => 'PartialMatchFilter',
        ],
    ];

    /**
     * standard SS variable.
     *
     * @return string
     */
    private static $singular_name = 'Order Step';

    /**
     * standard SS variable.
     *
     * @return string
     */
    private static $plural_name = 'Order Steps';

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

    private static $_last_order_step_cache;

    /**
     * IMPORTANT:: MUST HAVE Code must be defined!!!
     * standard SS variable.
     *
     * @return array
     */
    private static $defaults = [
        'CustomerCanEdit' => 0,
        'CustomerCanCancel' => 0,
        'CustomerCanPay' => 1,
        'ShowAsUncompletedOrder' => 0,
        'ShowAsInProcessOrder' => 1,
        'ShowAsCompletedOrder' => 0,
        'Code' => 'ORDERSTEP',
    ];

    /**
     * casted variable.
     */
    public function Title(): string
    {
        return $this->getTitle();
    }

    public function getTitle(): string
    {
        return (string) $this->Name;
    }

    /**
     * casted variable.
     */
    public function CustomerCanEditNice(): string
    {
        return $this->getCustomerCanEditNice();
    }

    public function getCustomerCanEditNice(): string
    {
        return $this->yesOrNoNiceHelper($this->CustomerCanEdit);
    }

    /**
     * casted variable.
     */
    public function CustomerCanPayNice(): string
    {
        return $this->getCustomerCanPayNice();
    }

    public function getCustomerCanPayNice(): string
    {
        return $this->yesOrNoNiceHelper($this->CustomerCanPay);
    }

    /**
     * casted variable.
     */
    public function CustomerCanCancelNice(): string
    {
        return $this->getCustomerCanCancelNice();
    }

    public function getCustomerCanCancelNice(): string
    {
        return $this->yesOrNoNiceHelper($this->CustomerCanCancel);
    }

    public function ShowAsUncompletedOrderNice(): string
    {
        return $this->getShowAsUncompletedOrderNice();
    }

    public function getShowAsUncompletedOrderNice(): string
    {
        return $this->yesOrNoNiceHelper($this->ShowAsUncompletedOrder);
    }

    /**
     * casted variable.
     */
    public function ShowAsInProcessOrderNice(): string
    {
        return $this->getShowAsInProcessOrderNice();
    }

    public function getShowAsInProcessOrderNice(): string
    {
        return $this->yesOrNoNiceHelper($this->ShowAsInProcessOrder);
    }

    /**
     * casted variable.
     */
    public function ShowAsCompletedOrderNice(): string
    {
        return $this->getShowAsCompletedOrderNice();
    }

    public function getShowAsCompletedOrderNice(): string
    {
        return $this->yesOrNoNiceHelper($this->ShowAsCompletedOrder);
    }

    /**
     * do not show in steps at all.
     */
    public function HideFromEveryone(): bool
    {
        return false;
    }

    /**
     * casted variable.
     */
    public function HideStepFromCustomerNice(): string
    {
        return $this->getHideStepFromCustomerNice();
    }

    public function getHideStepFromCustomerNice(): string
    {
        return $this->yesOrNoNiceHelper($this->HideStepFromCustomer);
    }

    public function i18n_singular_name()
    {
        return _t('OrderStep.ORDERSTEP', 'Order Step');
    }

    public function i18n_plural_name()
    {
        return _t('OrderStep.ORDERSTEPS', 'Order Steps');
    }

    /**
     * returns all the order steps
     * that the admin should / can edit....
     *
     * @return \SilverStripe\ORM\DataList
     */
    public static function admin_manageable_steps(): DataList
    {
        return OrderStep::get()
            ->filter(['ShowAsInProcessOrder' => 1, 'ShowAsUncompletedOrder' => false, 'ShowAsCompletedOrder' => false]);
    }

    /**
     * returns all the order steps
     * that the admin should / can edit....
     *
     * @return \SilverStripe\ORM\DataList
     */
    public static function non_admin_manageable_steps(): DataList
    {
        return OrderStep::get()
            ->filterAny(['ShowAsInProcessOrder' => false, 'ShowAsUncompletedOrder' => true, 'ShowAsCompletedOrder' => true]);
    }

    /**
     * Basically any order that is submitted.
     *
     * @return \SilverStripe\ORM\DataList
     */
    public static function admin_reviewable_steps(): DataList
    {
        return OrderStep::get()
            ->filter(['ShowAsUncompletedOrder' => false,]);
    }

    /**
     * order steps that the admin generally should not look at.
     *
     * @return \SilverStripe\ORM\DataList
     */
    public static function non_admin_reviewable_steps(): DataList
    {
        return OrderStep::get()
            ->exclude(['ShowAsUncompletedOrder' => true,]);
    }


    /**
     * @param bool $noCacheValues
     *
     * @return OrderStep
     */
    public static function last_order_step($noCacheValues = false)
    {
        if (! self::$_last_order_step_cache || $noCacheValues) {
            self::$_last_order_step_cache = OrderStep::get()->Last();
        }

        return self::$_last_order_step_cache;
    }

    /**
     * return StatusIDs (orderstep IDs) from orders that are bad....
     * (basically StatusID values that do not exist).
     *
     * @return array
     */
    public static function bad_order_step_ids(): array
    {
        $badorderStatus = Order::get()
            ->leftJoin('OrderStep', '"OrderStep"."ID" = "Order"."StatusID"')
            ->where('"OrderStep"."ID" IS NULL AND "StatusID" > 0')
            ->column('StatusID')
        ;
        if (is_array($badorderStatus)) {
            return array_unique(array_values($badorderStatus));
        }

        return [-1];
    }

    /**
     * turns code into ID.
     */
    public static function get_status_id_from_code(string $code): int
    {
        $otherStatus = DataObject::get_one(
            OrderStep::class,
            ['Code' => $code]
        );
        if ($otherStatus) {
            return (int) $otherStatus->ID;
        }

        return 0;
    }

    /**
     * @return array
     */
    public static function get_codes_for_order_steps_to_include()
    {
        $newArray = [];
        $array = EcommerceConfig::get(OrderStep::class, 'order_steps_to_include');
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
     */
    public static function get_not_created_codes_for_order_steps_to_include()
    {
        $array = EcommerceConfig::get(OrderStep::class, 'order_steps_to_include');
        if (is_array($array) && count($array)) {
            foreach ($array as $className) {
                $obj = DataObject::get_one($className);
                if ($obj) {
                    unset($array[$className]);
                }
            }
        }

        return $array;
    }

    /**
     * @return string
     */
    public function getMyCode()
    {
        $array = Config::inst()->get($this->ClassName, 'defaults', Config::UNINHERITED);
        if (! isset($array['Code'])) {
            user_error($this->ClassName . ' does not have a default code specified');
        }

        return $array['Code'];
    }

    /**
     * standard SS method.
     */
    public function populateDefaults()
    {
        $this->Description = $this->myDescription();

        return parent::populateDefaults();
    }

    /**
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        //replacing
        if ($this->canBeDefered()) {
            $queueField = $fields->dataFieldByName('OrderProcessQueueEntries');
            if ($queueField) {
                $config = $queueField->getConfig();
                $config->removeComponentsByType(GridFieldAddExistingAutocompleter::class);
                $config->removeComponentsByType(GridFieldDeleteAction::class);
            }
            if ($this->DeferTimeInSeconds) {
                $fields->addFieldToTab(
                    'Root.Queue',
                    HeaderField::create(
                        'WhenWillThisRun',
                        $this->humanReadeableDeferTimeInSeconds()
                    )
                );
            }

            $fields->addFieldsToTab(
                'Root.Queue',
                [
                    new HeaderField('DeferHeader', _t('OrderStep.DEFER_HEADER', 'Delay'), 3),
                    $deferTimeInSecondsField = TextField::create(
                        'DeferTimeInSeconds',
                        _t('OrderStep.DeferTimeInSeconds', 'Seconds in queue')
                    )
                        ->setDescription(
                            _t(
                                'OrderStep.TIME_EXPLANATION',
                                '86,400 seconds is one day ...
                            <br />To make it easier, you can also enter things like <em>1 week</em>, <em>3 hours</em>, or <em>7 minutes</em>.
                            <br />Non-second entries will automatically be converted to seconds.'
                            )
                        ),
                ]
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
                                'The time in the queue can be calculated from the moment the current orderstep starts or from the moment the order was submitted (in this case, check the box above) '
                            )
                        )
                );
            }
            $fields->addFieldToTab(
                'Root.Queue',
                $queueField
            );
        } else {
            $fields->removeFieldFromTab('Root', 'OrderProcessQueueEntries');
        }
        if ($this->hasCustomerMessage()) {
            $rightTitle = _t(
                'OrderStep.EXPLAIN_ORDER_NUMBER_IN_SUBJECT',
                'You can use [OrderNumber] as a tag that will be replaced with the actual Order Number.'
            );
            $fields->addFieldToTab(
                'Root.CustomerMessage',
                TextField::create('EmailSubject', _t('OrderStep.EMAILSUBJECT', 'Email Subject'))
                    ->setDescription($rightTitle)
            );
            $testEmailLink = $this->testEmailLink();
            if ($testEmailLink) {
                $fields->addFieldToTab(
                    'Root.CustomerMessage',
                    new LiteralField(
                        'testEmailLink',
                        '<h3>
                            <a href="' . $testEmailLink . '" data-popup="true" target"_blank" onclick="emailPrompt(this, event);">
                                ' . _t('OrderStep.VIEW_EMAIL_EXAMPLE', 'Test Email') . '
                            </a>
                        </h3>
                        <script language="javascript">
                            function emailPrompt(caller, event) {
                                event.preventDefault();
                                var href = jQuery(caller).attr("href");
                                var email = prompt("Enter an email address to receive a copy of this example in your inbox, leave blank to view in the browser");
                                if (email) {
                                    href += "&to=" + email;
                                }
                                window.open(href);
                            };
                        </script>'
                    ),
                    'EmailSubject'
                );
            }

            $fields->addFieldToTab('Root.CustomerMessage', $htmlEditorField = new HTMLEditorField('CustomerMessage', _t('OrderStep.CUSTOMERMESSAGE', 'Customer Message (if any)')));
            $htmlEditorField->setRows(3);
        } else {
            $fields->removeFieldFromTab('Root', 'OrderEmailRecords');
            $fields->removeFieldFromTab('Root.Main', 'EmailSubject');
            $fields->removeFieldFromTab('Root.Main', 'CustomerMessage');
        }
        //adding
        if (! $this->exists() || ! $this->isDefaultStatusOption()) {
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
        $fields->addFieldsToTab(
            'Root.Advanced',
            [
                ReadonlyField::create(
                    'ClassName',
                    'ClassName'
                ),
                LiteralField::create(
                    'SpecialConditions',
                    $this->stepExplanations()
                ),
            ]
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
        $sanitisedClassName = ClassHelpers::sanitise_class_name(OrderStep::class);

        return 'admin/shop/' . $sanitisedClassName . '/EditForm/field/' . $sanitisedClassName . '/item/' . $this->ID . '/edit';
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
     */
    public function AlternativeDisplayPage()
    {
        return null;
    }

    /**
     * A form that can be used by the Customer to progress step!
     *
     * @return null|\SilverStripe\Forms\Form (CustomerOrderStepForm)
     */
    public function CustomerOrderStepForm(Controller $controller, string $name, Order $order)
    {
        return null;
    }

    /**
     * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields.
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function addOrderStepFields(FieldList $fields, Order $order, ?bool $nothingToDo = false)
    {
        if ($nothingToDo) {
            $text = _t(
                'OrderStep.NOTHING_TO_DO',
                'Orders should not be stuck in this step and an order being here may indicate an error.
                Please check the Process Tab and see if the order is being queued for the next step.
                In case it is stuck here for a long time, you can move it along using the field below.
                This is not recommended.'
            );
            $fields->addFieldsToTab(
                'Root.Next',
                [
                    ReadonlyField::create('StatusIDNotice', 'Info', $text),
                    DropdownField::create(
                        'StatusID',
                        'Select Status - NOT RECOMMENDED',
                        OrderStep::get()->map()
                    ),
                ]
            );
        }
        $this->addQuickLogEntryButton($fields, $order);
        $this->extend('addOrderStepFieldsAdditional', $fields, $order, $nothingToDo);
        return $fields;
    }

    public function addQuickLogEntryButton($fields, $order)
    {
        if ($this->relevantLogEntryClassName) {
            $log = $this->relevantLogEntryClassName::get()->filter(['OrderID' => $order->ID])->Last();
            if ($log) {
                $link = $log->CMSEditLink();
                $title = _t('Order.EDIT', 'Edit') . ' ' . $log->i18n_singular_name();

                $fields->addFieldsToTab(
                    'Root.Next',
                    [
                        EcommerceCMSButtonField::create(
                            'AddEditNoteButton',
                            $link,
                            $title
                        ),
                    ]
                );
            }
        }
    }

    /**
     * @return \SilverStripe\ORM\ValidationResult
     */
    public function validate()
    {
        $result = parent::validate();
        $anotherOrderStepWithSameNameOrCode = OrderStep::get()
            ->filter(
                [
                    'Name' => $this->Name,
                    'Code' => strtoupper($this->Code),
                ]
            )
            ->exclude(['ID' => (int) $this->ID])
            ->First()
        ;
        if ($anotherOrderStepWithSameNameOrCode) {
            $result->addError(_t('OrderStep.ORDERSTEPALREADYEXISTS', 'An order status with this name already exists. Please change the name and try again.'));
        }

        return $result;
    }

    // moving between statusses...

    /**
     *initStep:
     * makes sure the step is ready to run.... (e.g. check if the order is ready to be emailed as receipt).
     * should be able to run this function many times to check if the step is ready.
     *
     * @see Order::doNextStatus
     *
     * @return bool - true if the current step is ready to be run...
     */
    public function initStep(Order $order): bool
    {
        user_error('Please implement the initStep method in a subclass (' . __CLASS__ . ') of OrderStep', E_USER_WARNING);

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
     * @return bool - true if run correctly
     */
    public function doStep(Order $order): bool
    {
        user_error('Please implement the initStep method in a subclass (' . __CLASS__ . ') of OrderStep', E_USER_WARNING);

        return true;
    }

    /**
     * nextStep:
     * returns the next step (after it checks if everything is in place for the next step to run...).
     *
     * @see Order::doNextStatus
     *
     * @return null|OrderStep (next step OrderStep object)
     */
    public function nextStep(Order $order): ?OrderStep
    {
        $conditions = $this->Config()->get('step_logic_conditions');
        if (count($conditions)) {
            foreach ($conditions as $method => $classNameOrTrue) {
                $outcome = $this->{$method}($order);
                if (true === $outcome) {
                    if (true === $classNameOrTrue) {
                        return $this->nextStepObject();
                    }

                    return $classNameOrTrue::get()->first();
                }
                if (false === $outcome) {
                    // important - do nothing - allow another condition to apply
                } else {
                    user_error($method . ' is required to return TRUE or FALSE, answer is: ' . print_r($outcome, 1));
                }
            }
        } else {
            // if there are no conditions then just return the next step.
            return $this->nextStepObject();
        }

        return null;
    }

    public function nextStepObject(): ?OrderStep
    {
        $sort = (int) $this->Sort;
        if (! $sort) {
            $sort = 0;
        }
        $where = '"OrderStep"."Sort" >  ' . $sort;
        $nextOrderStepObject = DataObject::get_one(
            OrderStep::class,
            $where
        );

        return $nextOrderStepObject ?: null;
    }

    // Boolean checks

    /**
     * Checks if a step has passed (been completed) in comparison to the current step.
     *
     * @param bool  $orIsEqualTo if set to true, this method will return TRUE if the step being checked is the current one
     * @param mixed $code
     *
     * @return bool
     */
    public function hasPassed($code, $orIsEqualTo = false)
    {
        $otherStatus = DataObject::get_one(
            OrderStep::class,
            ['Code' => $code]
        );
        if ($otherStatus) {
            if ($otherStatus->Sort < $this->Sort) {
                return true;
            }
            if ($orIsEqualTo && $otherStatus->Code === $this->Code) {
                return true;
            }
        } else {
            user_error("could not find {$code} in OrderStep", E_USER_NOTICE);
        }

        return false;
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function hasPassedOrIsEqualTo($code)
    {
        return $this->hasPassed($code, true);
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function hasNotPassed($code)
    {
        return (bool) ! $this->hasPassed($code, true);
    }

    /**
     * Opposite of hasPassed.
     */
    public function isBefore(string $code): bool
    {
        return ! (bool) $this->hasPassed($code);
    }

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
     * sets the email class used for emailing the
     * customer during a specific step (IF ANY!).
     */
    public function setEmailClassName(string $s): self
    {
        $this->emailClassName = $s;

        return $this;
    }

    /**
     * Has an email been sent to the customer for this
     * order step.
     *"-10 days".
     *
     * @param bool $checkDateOfOrder
     *
     * @return bool
     */
    public function hasBeenSent(Order $order, ?bool $checkDateOfOrder = true)
    {
        //if it has been more than a XXX days since the order was last edited (submitted) then we do not send emails as
        //this would be embarrasing.
        $log = $order->SubmissionLog();
        if($log && $log->BypassEmailing) {
            return true;
        }
        if ($checkDateOfOrder) {
            $lastEditedValue = $log ? $log->LastEdited : $order->LastEdited;
            $orderTs = strtotime((string) $lastEditedValue);
            $days = EcommerceConfig::get(OrderStep::class, 'number_of_days_to_send_update_email');
            $daysAgoTs = strtotime('-' . $days . ' days');
            if ($orderTs < $daysAgoTs) {
                return true;
            }
        }

        $exists = OrderEmailRecord::get()
            ->filter(
                [
                    'OrderID' => $order->ID,
                    'OrderStepID' => $this->ID,
                    'Result' => 1,
                ]
            )
            ->exists()
        ;
        if ($exists) {
            return true;
        }

        $count = OrderEmailRecord::get()
            ->filter(
                [
                    'OrderID' => $order->ID,
                    'OrderStepID' => $this->ID,
                ]
            )
            ->count()
        ;
        //tried it twice - abandon to avoid being stuck in a loop!
        return $count > 2;
    }

    /**
     * Formatted answer for "hasCustomerMessage".
     */
    public function HasCustomerMessageNice(): string
    {
        return $this->getHasCustomerMessageNice();
    }

    public function getHasCustomerMessageNice(): string
    {
        return $this->hasCustomerMessage() ? _t('OrderStep.YES', 'Yes') : _t('OrderStep.NO', 'No');
    }

    public function CalculatedEmailSubject(?Order $order = null): string
    {
        return (string) $this->EmailSubject;
    }

    public function CalculatedCustomerMessage(?Order $order = null): string
    {
        return (string) $this->CustomerMessage;
    }

    /**
     * Formatted answer for "hasCustomerMessage".
     */
    public function ShowAsSummary()
    {
        return $this->getShowAsSummary();
    }

    public function getShowAsSummary(): DBHTMLText
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
        $canArray = [];
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
            $v .= '<br />' . _t('OrderStep.CUSTOMER_CAN', 'Customer Can') . ': ' . implode(', ', $canArray) . '.';
        }
        if ($this->hasCustomerMessage()) {
            $v .= '<br />' . _t('OrderStep.CUSTOMER_MESSAGES', 'Includes message to customer / admin.');
        }
        if ($this->hasStepConditions()) {
            $v .= '<br />' . _t('OrderStep.HAS_CONDITIONS', 'Conditions must be met before order can progress.');
        }
        if ($this->DeferTimeInSeconds) {
            $v .= '<br />' . $this->humanReadeableDeferTimeInSeconds();
        }

        // @return DBHTMLText
        return DBField::create_field('HTMLText', $v);
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
        $v = '<strong>' . $this->Name . '</strong><br /><em>' . $this->Description . '</em>';

        return DBField::create_field('HTMLText', $v);
    }

    /**
     * This allows you to set the time to something other than the standard DeferTimeInSeconds
     * value based on the order provided.
     *
     * @param Order $order (optional)
     *
     * @return int
     */
    public function CalculatedDeferTimeInSeconds(?Order $order = null)
    {
        return $this->DeferTimeInSeconds;
    }

    public function getRelevantLogEntryClassName(): string
    {
        return $this->relevantLogEntryClassName;
    }

    public function setRelevantLogEntryClassName(string $s): self
    {
        $this->relevantLogEntryClassName = $s;

        return $this;
    }

    /**
     * returns the OrderStatusLog that is relevant to this step.
     *
     * @return null|OrderStatusLog
     */
    public function RelevantLogEntry(Order $order)
    {
        if ($this->getRelevantLogEntryClassName()) {
            return $this->RelevantLogEntries($order)
                ->sort(['ID' => 'DESC'])
                ->first();
        }

        return null;
    }

    /**
     * returns the OrderStatusLog that is relevant to this step, even if non exists.
     *
     * @return OrderStatusLog
     */
    public function RelevantLogEntryFindOrMake(Order $order)
    {
        $log = $this->RelevantLogEntry($order);
        if (! $log) {
            $className = $this->getRelevantLogEntryClassName();
            if (! class_exists($className)) {
                $className = OrderStatusLog::class;
            }
            $log = $className::create();
            $log->OrderID = $order->ID;
            $log->write();
        }

        return $log;
    }

    /**
     * returns the OrderStatusLogs that are relevant to this step.
     * It is important that getRelevantLogEntryClassName returns
     * a specific enough ClassName and not a base class name.
     *
     * @return null|\SilverStripe\ORM\DataList
     */
    public function RelevantLogEntries(Order $order)
    {
        $className = $this->getRelevantLogEntryClassName();
        if ($className) {
            return $className::get()->filter(
                [
                    'OrderID' => $order->ID,
                ]
            );
        }

        return null;
    }

    // Silverstripe Standard Data Object Methods

    /**
     * Standard SS method
     * These are only created programmatically.
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

        return parent::canEdit($member);
    }

    /**
     * the default for this is TRUE, but for completed order steps.
     *
     * we do not allow this.
     *
     * @param Order  $order
     * @param Member $member optional
     */
    public function canOverrideCanViewForOrder($order, $member = null): bool
    {
        //return true if the order can have customer input
        // orders recently saved can also be views
        return $this->CustomerCanEdit ||
            $this->CustomerCanCancel ||
            $this->CustomerCanPay;
    }

    /**
     * standard SS method.
     *
     * @param null|mixed $member
     * @param mixed      $context
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
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
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
            $exists = Order::get()
                ->filter(['StatusID' => (int) $this->ID])
                ->exists()
            ;
            if ($exists) {
                return false;
            }
        }
        if ($this->isDefaultStatusOption()) {
            return false;
        }
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

        return parent::canEdit($member);
    }

    /**
     * standard SS method
     * USED TO BE: Unpaid,Query,Paid,Processing,Sent,Complete,AdminCancelled,MemberCancelled,Cart.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $this->checkValidityOfOrderSteps();
    }

    protected function stepExplanations(): string
    {
        $html = '<h2>Moving to next step</h2><ul>';
        $array = $this->Config()->get('step_logic_conditions');
        if (count($array)) {
            foreach ($array as $method => $classNameOrTrue) {
                $nextStep = true === $classNameOrTrue ? $this->nextStepObject() : $classNameOrTrue::get()->first();
                if ($nextStep) {
                    $html .= '<li>If <strong>' . $method . '</strong> returns TRUE then move order to <a href="' . $nextStep->CMSEditLink() . '">' . $nextStep->getTitle() . '</a></li>';
                } else {
                    $html .= '<li>If <strong>' . $method . '</strong> returns TRUE then <strong>not specified</strong></li>';
                }
            }
        } else {
            $html .= '<li>Rules are not defined here.</li>';
        }

        $html .= '</ul>';

        return $html;
    }

    protected function hasStepConditions(): bool
    {
        if (! empty($this->Config()->get('step_logic_conditions'))) {
            return true;
        }

        return false;
    }

    protected function yesOrNoNiceHelper(?bool $bool): string
    {
        return $bool ? _t('OrderStep.YES', 'Yes') : _t('OrderStep.NO', 'No');
    }

    /**
     * standard SS method.
     */
    protected function onBeforeWrite()
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
        } elseif (is_numeric($this->DeferTimeInSeconds)) {
            $this->DeferTimeInSeconds = (int) $this->DeferTimeInSeconds;
        } else {
            $this->DeferTimeInSeconds = strtotime('+' . $this->DeferTimeInSeconds);
            if ($this->DeferTimeInSeconds > 0) {
                $this->DeferTimeInSeconds -= time();
            }
        }
        $this->Code = strtoupper($this->Code);
    }

    /**
     * move linked orders to the next status
     * standard SS method.
     */
    protected function onBeforeDelete()
    {
        $ordersWithThisStatus = Order::get()->filter(['StatusID' => $this->ID]);
        if ($ordersWithThisStatus->exists()) {
            $bestOrderStep = $this->NextOrderStep();
            //backup
            if ($bestOrderStep && $bestOrderStep->exists()) {
                //do nothing
            } else {
                $bestOrderStep = $this->PreviousOrderStep();
            }
            if ($bestOrderStep) {
                foreach ($ordersWithThisStatus as $orderWithThisStatus) {
                    $orderWithThisStatus->StatusID = $bestOrderStep->ID;
                    $orderWithThisStatus->write();
                }
            }
        }
        parent::onBeforeDelete();
    }

    /**
     * standard SS method.
     */
    protected function onAfterDelete()
    {
        parent::onAfterDelete();
        $this->checkValidityOfOrderSteps();
    }

    /**
     * @return bool
     */
    protected function isDefaultStatusOption()
    {
        return in_array($this->Code, self::get_codes_for_order_steps_to_include(), true);
    }

    /**
     * return true if done already or mailed successfully now.
     *
     * @param order       $order
     * @param string      $subject
     * @param string      $message
     * @param bool        $resend
     * @param bool|string $adminOnlyOrToEmail you can set to false = send to customer, true: send to admin, or email = send to email
     * @param string      $emailClassName
     *
     * @return bool
     */
    protected function sendEmailForStep(
        $order,
        $subject,
        $message = '',
        $resend = false,
        $adminOnlyOrToEmail = false,
        $emailClassName = ''
    ): bool {
        if (false === (bool) $this->hasBeenSent($order) || true === (bool) $resend) {
            if (! $subject) {
                $subject = $this->CalculatedEmailSubject($order);
            }
            $useAlternativeEmail = $adminOnlyOrToEmail && filter_var($adminOnlyOrToEmail, FILTER_VALIDATE_EMAIL);

            //this is NOT an admin EMAIL
            if ($this->hasCustomerMessage() || $useAlternativeEmail) {
                if (! $emailClassName) {
                    $emailClassName = $this->getEmailClassName();
                }
                $outcome = $order->sendEmail(
                    $emailClassName,
                    $subject,
                    $message,
                    $resend,
                    $adminOnlyOrToEmail
                );
                //ADMIN ONLY ....
            } else {
                if (! $emailClassName) {
                    $emailClassName = OrderErrorEmail::class;
                }
                //looks like we are sending an error, but we are just using this for notification
                $message = _t('OrderStep.THISMESSAGENOTSENTTOCUSTOMER', 'NOTE: This message was not sent to the customer.') . '<br /><br /><br /><br />' . $message;
                $outcome = $order->sendAdminNotification(
                    $emailClassName,
                    $subject,
                    $message,
                    $resend
                );
            }

            return (bool) ($outcome || Director::isDev());
        }

        return true;
    }

    /**
     * returns a link that can be used to test
     * the email being sent during this step
     * this method returns NULL if no email
     * is being sent OR if there is no suitable Order
     * to test with...
     */
    protected function testEmailLink(): ?string
    {
        if ($this->getEmailClassName()) {
            $order = DataObject::get_one(
                Order::class,
                ['StatusID' => $this->ID],
                $cacheDataObjectGetOne = true,
                'RAND() ASC'
            );
            if (! $order) {
                $order = Order::get()
                    ->where('"OrderStep"."Sort" >= ' . $this->Sort)
                    ->orderBy('IF("OrderStep"."Sort" > ' . $this->Sort . ', 0, 1) ASC, "OrderStep"."Sort" ASC, RAND() ASC')
                    ->innerJoin('OrderStep', '"OrderStep"."ID" = "Order"."StatusID"')
                    ->first()
                ;
            }
            if ($order) {
                return OrderConfirmationPage::get_email_link(
                    $order->ID,
                    $this->getEmailClassName(),
                    $actuallySendEmail = false,
                    $alternativeOrderStepID = $this->ID
                );
            }
        }

        return null;
    }

    /**
     * For some ordersteps this returns true...
     *
     * @return bool
     */
    public function hasCustomerMessage()
    {
        return false;
    }

    protected function humanReadeableDeferTimeInSeconds(): ?string
    {
        if ($this->canBeDefered()) {
            $field = DBField::create_field('DBDatetime', strtotime('+ ' . $this->DeferTimeInSeconds . ' seconds'));
            $descr0 = _t('OrderStep.THE', 'The') . ' ' . '<span style="color: #338DC1">' . $this->getTitle() . '</span>';
            $descr1 = _t('OrderStep.DELAY_VALUE', 'Order Step, for any order, will run');
            $descr2 = $field->ago();
            $descr3 = $this->DeferFromSubmitTime ?
                    _t('OrderStep.FROM_ORDER_SUBMIT_TIME', 'from the order being submitted') :
                    _t('OrderStep.FROM_START_OF_ORDSTEP', 'from the order arriving on this step');

            return $descr0 . ' ' . $descr1 . ' <span style="color: #338DC1">' . $descr2 . '</span> ' . $descr3 . '.';
        }

        return null;
        // $dtF = new \DateTime('@0');
        // $dtT = new \DateTime("@".$this->DeferTimeInSeconds);
        //
        // return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes and %s seconds');
    }

    /**
     * can this order step be delayed?
     * in general, if there is a customer message
     * we should be able to delay it.
     *
     * This method can be overridden in any orderstep
     *
     * @return bool
     */
    protected function canBeDefered()
    {
        return $this->hasCustomerMessage();
    }

    protected function NextOrderStep()
    {
        return OrderStep::get()
            ->filter(['Sort:GreaterThan' => $this->Sort])
            ->First()
        ;
    }

    protected function PreviousOrderStep()
    {
        return OrderStep::get()
            ->filter(['Sort:LessThan' => $this->Sort])
            ->First()
        ;
    }

    protected function checkValidityOfOrderSteps()
    {
        $orderStepsToInclude = EcommerceConfig::get(OrderStep::class, 'order_steps_to_include');
        $codesToInclude = self::get_codes_for_order_steps_to_include();
        $indexNumber = 0;
        if ($orderStepsToInclude && count($orderStepsToInclude)) {
            if ($codesToInclude && count($codesToInclude)) {
                foreach ($codesToInclude as $className => $code) {
                    $className = (string) $className;
                    $code = strtoupper($code);
                    $filter = ['ClassName' => $className, 'Code' => $code];
                    $indexNumber += 10;
                    $itemCountCounts = OrderStep::get()->filterAny($filter)->count();
                    if (1 === $itemCountCounts) {
                        //always reset code
                        $obj = DataObject::get_one(
                            OrderStep::class,
                            $filter,
                            $cacheDataObjectGetOne = false
                        );
                        if ($obj && $obj instanceof OrderStep) {
                            if ($obj->Code !== $code) {
                                $obj->Code = $code;
                                $obj->write();
                            }
                            if ($obj->ClassName !== $className) {
                                $obj->ClassName = $className;
                                $obj->write();
                            }
                            //replace default description
                            $parentObj = singleton(OrderStep::class);
                            if ($obj->Description === $parentObj->myDescription()) {
                                $obj->Description = $obj->myDescription();
                                $obj->write();
                            }
                            //check sorting order
                            if ($obj->Sort !== $indexNumber) {
                                $obj->Sort = $indexNumber;
                                $obj->write();
                            }
                        }
                    } else {
                        $oldObjects = OrderStep::get()->filterAny($filter);
                        foreach ($oldObjects as $oldObject) {
                            DB::alteration_message('DELETING ' . $oldObject->Title . ' as this now appears obsolete', 'deleted');
                            $oldObject->delete();
                        }

                        $obj = $className::create($filter);
                        $obj->Code = $code;
                        $obj->Description = $obj->myDescription();
                        $obj->Sort = $indexNumber;
                        $obj->write();
                        DB::alteration_message("Created \"{$code}\" as {$className}.", 'created');
                    }
                    $obj = DataObject::get_one(
                        OrderStep::class,
                        $filter,
                        $cacheDataObjectGetOne = false
                    );
                    if (! $obj) {
                        user_error("There was an error in creating the {$code} OrderStep", E_USER_NOTICE);
                    }
                }
            }
        }
        $steps = OrderStep::get();
        foreach ($steps as $step) {
            if (! $step->Description) {
                $step->Description = $step->myDescription();
                $step->write();
            }
        }
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
