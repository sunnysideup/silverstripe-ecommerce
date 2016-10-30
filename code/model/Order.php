<?php

/**
 * @description:
 * The order class is a databound object for handling Orders within SilverStripe.
 * Note that it works closely with the ShoppingCart class, which accompanies the Order
 * until it has been paid for / confirmed by the user.
 *
 *
 * CONTENTS:
 * ----------------------------------------------
 * 1. CMS STUFF
 * 2. MAIN TRANSITION FUNCTIONS
 * 3. STATUS RELATED FUNCTIONS / SHORTCUTS
 * 4. LINKING ORDER WITH MEMBER AND ADDRESS
 * 5. CUSTOMER COMMUNICATION
 * 6. ITEM MANAGEMENT
 * 7. CRUD METHODS (e.g. canView, canEdit, canDelete, etc...)
 * 8. GET METHODS (e.g. Total, SubTotal, Title, etc...)
 * 9. TEMPLATE RELATED STUFF
 * 10. STANDARD SS METHODS (requireDefaultRecords, onBeforeDelete, etc...)
 * 11. DEBUG
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 *
 * NOTE: This is the SQL for selecting orders in sequence of
 **/
class Order extends DataObject implements EditableEcommerceObject
{
    /**
     * API Control.
     *
     * @var array
     */
    private static $api_access = array(
        'view' => array(
            'OrderEmail',
            'EmailLink',
            'PrintLink',
            'RetrieveLink',
            'Title',
            'Total',
            'SubTotal',
            'TotalPaid',
            'TotalOutstanding',
            'ExchangeRate',
            'CurrencyUsed',
            'TotalItems',
            'TotalItemsTimesQuantity',
            'IsCancelled',
            'Country',
            'FullNameCountry',
            'IsSubmitted',
            'CustomerStatus',
            'CanHaveShippingAddress',
            'CancelledBy',
            'CurrencyUsed',
            'BillingAddress',
            'UseShippingAddress',
            'ShippingAddress',
            'Status',
            'Attributes',
            'OrderStatusLogs',
            'MemberID',
        ),
    );

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $db = array(
        'SessionID' => 'Varchar(32)', //so that in the future we can link sessions with Orders.... One session can have several orders, but an order can onnly have one session
        'UseShippingAddress' => 'Boolean',
        'CustomerOrderNote' => 'Text',
        'ExchangeRate' => 'Double',
        //'TotalItems_Saved' => 'Double',
        //'TotalItemsTimesQuantity_Saved' => 'Double'
    );

    private static $has_one = array(
        'Member' => 'Member',
        'BillingAddress' => 'BillingAddress',
        'ShippingAddress' => 'ShippingAddress',
        'Status' => 'OrderStep',
        'CancelledBy' => 'Member',
        'CurrencyUsed' => 'EcommerceCurrency',
    );

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $has_many = array(
        'Attributes' => 'OrderAttribute',
        'OrderStatusLogs' => 'OrderStatusLog',
        'Payments' => 'EcommercePayment',
        'Emails' => 'OrderEmailRecord',
    );

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $indexes = array(
        'SessionID' => true,
    );

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $default_sort = '"LastEdited" DESC';

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $casting = array(
        'OrderEmail' => 'Text',
        'EmailLink' => 'Text',
        'PrintLink' => 'Text',
        'RetrieveLink' => 'Text',
        'Title' => 'Text',
        'Total' => 'Currency',
        'TotalAsMoney' => 'Money',
        'SubTotal' => 'Currency',
        'SubTotalAsMoney' => 'Money',
        'TotalPaid' => 'Currency',
        'TotalPaidAsMoney' => 'Money',
        'TotalOutstanding' => 'Currency',
        'TotalOutstandingAsMoney' => 'Money',
        'HasAlternativeCurrency' => 'Boolean',
        'TotalItems' => 'Double',
        'TotalItemsTimesQuantity' => 'Double',
        'IsCancelled' => 'Boolean',
        'Country' => 'Varchar(3)', //This is the applicable country for the order - for tax purposes, etc....
        'FullNameCountry' => 'Varchar',
        'IsSubmitted' => 'Boolean',
        'CustomerStatus' => 'Varchar',
        'CanHaveShippingAddress' => 'Boolean',
    );

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Order';
    public function i18n_singular_name()
    {
        return _t('Order.ORDER', 'Order');
    }

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Orders';
    public function i18n_plural_name()
    {
        return _t('Order.ORDERS', 'Orders');
    }

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = "A collection of items that together make up the 'Order'.  An order can be placed.";

    /**
     * Tells us if an order needs to be recalculated
     * can save one for each order...
     *
     * @var array
     */
    private static $_needs_recalculating = array();

    /**
     * @param bool (optional) $b
     * @param int (optional)  $orderID
     *
     * @return bool
     */
    public static function set_needs_recalculating($b = true, $orderID = 0)
    {
        self::$_needs_recalculating[$orderID] = $b;
    }

    /**
     * @param int (optional) $orderID
     *
     * @return bool
     */
    public static function get_needs_recalculating($orderID = 0)
    {
        return isset(self::$_needs_recalculating[$orderID]) ? self::$_needs_recalculating[$orderID] : false;
    }

    /**
     * Total Items : total items in cart
     * We start with -1 to easily identify if it has been run before.
     *
     * @var int
     */
    protected $totalItems = null;

    /**
     * Total Items : total items in cart
     * We start with -1 to easily identify if it has been run before.
     *
     * @var float
     */
    protected $totalItemsTimesQuantity = null;

    /**
     * Returns a set of modifier forms for use in the checkout order form,
     * Controller is optional, because the orderForm has its own default controller.
     *
     * This method only returns the Forms that should be included outside
     * the editable table... Forms within it can be called
     * from through the modifier itself.
     *
     * @param Controller $optionalController
     * @param Validator  $optionalValidator
     *
     * @return ArrayList (ModifierForms) | Null
     **/
    public function getModifierForms(Controller $optionalController = null, Validator $optionalValidator = null)
    {
        $arrayList = new ArrayList();
        $modifiers = $this->Modifiers();
        if ($modifiers->count()) {
            foreach ($modifiers as $modifier) {
                if ($modifier->ShowForm()) {
                    if ($form = $modifier->getModifierForm($optionalController, $optionalValidator)) {
                        $form->ShowFormInEditableOrderTable = $modifier->ShowFormInEditableOrderTable();
                        $form->ShowFormOutsideEditableOrderTable = $modifier->ShowFormOutsideEditableOrderTable();
                        $form->ModifierName = $modifier->ClassName;
                        $arrayList->push($form);
                    }
                }
            }
        }
        if ($arrayList->count()) {
            return $arrayList;
        } else {
            return;
        }
    }

    /**
     * This function returns the OrderSteps.
     *
     * @return ArrayList (OrderSteps)
     **/
    public static function get_order_status_options()
    {
        return OrderStep::get();
    }

    /**
     * Like the standard byID, but it checks whether we are allowed to view the order.
     *
     * @return: Order | Null
     **/
    public static function get_by_id_if_can_view($id)
    {
        $order = Order::get()->byID($id);
        if ($order && $order->canView()) {
            if ($order->IsSubmitted()) {
                // LITTLE HACK TO MAKE SURE WE SHOW THE LATEST INFORMATION!
                $order->tryToFinaliseOrder();
            }

            return $order;
        }

        return;
    }

    /**
     * returns a Datalist with the submitted order log included
     * this allows you to sort the orders by their submit dates.
     * You can retrieve this list and then add more to it (e.g. additional filters, additional joins, etc...).
     *
     * @param bool $onlySubmittedOrders - only include Orders that have already been submitted.
     *
     * @return DataList (Orders)
     */
    public static function get_datalist_of_orders_with_submit_record($onlySubmittedOrders = false)
    {
        $submittedOrderStatusLogClassName = EcommerceConfig::get('OrderStatusLog', 'order_status_log_class_used_for_submitting_order');
        $list = Order::get()
            ->LeftJoin('OrderStatusLog', '"Order"."ID" = "OrderStatusLog"."OrderID"')
            ->LeftJoin($submittedOrderStatusLogClassName, '"OrderStatusLog"."ID" = "'.$submittedOrderStatusLogClassName.'"."ID"')
            ->Sort('OrderStatusLog.Created', 'ASC');
        if ($onlySubmittedOrders) {
            $list = $list->Where("\"OrderStatusLog\".\"ClassName\" = '$submittedOrderStatusLogClassName'");
        } else {
            $list = $list->Where("\"OrderStatusLog\".\"ClassName\" = '$submittedOrderStatusLogClassName' OR \"OrderStatusLog\".\"ClassName\" IS NULL");
        }

        return $list;
    }

/*******************************************************
   * 1. CMS STUFF
*******************************************************/

    /**
     * fields that we remove from the parent::getCMSFields object set.
     *
     * @var array
     */
    protected $fieldsAndTabsToBeRemoved = array(
        'MemberID',
        'Attributes',
        'SessionID',
        'Emails',
        'BillingAddressID',
        'ShippingAddressID',
        'UseShippingAddress',
        'OrderStatusLogs',
        'Payments',
        'OrderDate',
        'ExchangeRate',
        'CurrencyUsedID',
        'StatusID',
        'Currency',
    );

    /**
     * STANDARD SILVERSTRIPE STUFF.
     **/
    private static $summary_fields = array(
        'Title' => 'Title',
        'Status.Title' => 'Next Step',
        'Member.Surname' => 'Name',
        'Member.Email' => 'Email',
        'TotalAsMoney.Nice' => 'Total',
        'TotalItemsTimesQuantity' => 'Units'
    );

    /**
     * STANDARD SILVERSTRIPE STUFF.
     *
     * @todo: how to translate this?
     **/
    private static $searchable_fields = array(
        'ID' => array(
            'field' => 'NumericField',
            'title' => 'Order Number',
        ),
        'MemberID' => array(
            'field' => 'TextField',
            'filter' => 'OrderFilters_MemberAndAddress',
            'title' => 'Customer Details',
        ),
        'Created' => array(
            'field' => 'TextField',
            'filter' => 'OrderFilters_AroundDateFilter',
            'title' => 'Date (e.g. Today, 1 jan 2007, or last week)',
        ),
        //make sure to keep the items below, otherwise they do not show in form
        'StatusID' => array(
            'filter' => 'OrderFilters_MultiOptionsetStatusIDFilter',
        ),
        'CancelledByID' => array(
            'filter' => 'OrderFilters_HasBeenCancelled',
            'title' => 'Cancelled by ...',
        ),
    );

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
                    if (isset($_GET['q']['StatusID'][$key])) {
                        $preSelected[$key] = $key;
                    }
                    $count = Order::get()
                        ->Filter(array('StatusID' => intval($key)))
                        ->count();
                    if ($count < 1) {
                        //do nothing
                    } else {
                        $arrayOfStatusOptionsFinal[$key] = $value." ($count)";
                    }
                }
            }
            $statusField = new CheckboxSetField(
                'StatusID',
                Injector::inst()->get('OrderStep')->i18n_singular_name(),
                $arrayOfStatusOptionsFinal,
                $preSelected
            );
            $fieldList->push($statusField);
        }
        $fieldList->push(new DropdownField('CancelledByID', 'Cancelled', array(-1 => '(Any)', 1 => 'yes', 0 => 'no')));

        return $fieldList;
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
     * STANDARD SILVERSTRIPE STUFF
     * broken up into submitted and not (yet) submitted.
     **/
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $currentMember = Member::currentUser();
        if (!$this->exists() || !$this->StatusID) {
            $firstStep = OrderStep::get()->First();
            $this->StatusID = $firstStep->ID;
            $this->write();
        }
        $submitted = $this->IsSubmitted() ? true : false;
        if ($submitted) {
            //TODO
            //Having trouble here, as when you submit the form (for example, a payment confirmation)
            //as the step moves forward, meaning the fields generated are incorrect, causing an error
            //"I can't handle sub-URLs of a Form object." generated by the RequestHandler.
            //Therefore we need to try reload the page so that it will be requesting the correct URL to generate the correct fields for the current step
            //Or something similar.
            //why not check if the URL == $this->CMSEditLink()
            //and only tryToFinaliseOrder if this is true....
            if ($_SERVER['REQUEST_URI'] == $this->CMSEditLink() || $_SERVER['REQUEST_URI'] == $this->CMSEditLink('edit')) {
                $this->tryToFinaliseOrder();
            }
        } else {
            $this->init(true);
            $this->calculateOrderAttributes(true);
            Session::set('EcommerceOrderGETCMSHack', $this->ID);
        }
        if ($submitted) {
            $this->fieldsAndTabsToBeRemoved[] = 'CustomerOrderNote';
        } else {
            $this->fieldsAndTabsToBeRemoved[] = 'Emails';
        }
        foreach ($this->fieldsAndTabsToBeRemoved as $field) {
            $fields->removeByName($field);
        }
        $fields->insertBefore(
            new Tab('Next'),
            'Main'
        );

        $nextFieldArray = array(
            LiteralField::create('CssFix', '<style>#Root_Next h2 {padding: 0!important; margin: 0!important; margin-top: 2em!important;}</style>'),
            HeaderField::create('OrderSummaryHeader', _t('Order.THIS_ORDER_HEADER', 'Order Summary')),
            GridField::create(
                'OrderSummary',
                _t('Order.CURRENT_STATUS', 'Summary'),
                ArrayList::create(array($this)),
                $config = GridFieldConfig_Base::create()
            ),
            HeaderField::create('MyOrderStepHeader', _t('Order.CURRENT_STATUS', '1. Current Status')),
            $this->OrderStepField()
        );

         //is the member is a shop admin they can always view it
        if (
            EcommerceRole::current_member_can_process_orders(Member::currentUser())
        ) {
            $nextFieldArray = array_merge(
                $nextFieldArray,
                array(
                    HeaderField::create('OrderStepNextStepHeader', _t('Order.ACTION_NEXT_STEP', '2. Action Next Step')),
                    HeaderField::create('ActionNextStepManually', _t('Order.MANUAL_STATUS_CHANGE', '3. Move Order Along')),
                    LiteralField::create('OrderStepNextStepHeaderExtra', '<p>'._t('Order.NEEDTOREFRESH', 'If you have made any changes to the order then you will have to refresh or save this record to move it along.').'</p>'),
                    EcommerceCMSButtonField::create(
                        'StatusIDExplanation',
                        $this->CMSEditLink(),
                        _t('Order.REFRESH', 'refresh now')
                    )
                )
            );
        }

        $fields->addFieldsToTab(
            'Root.Next',
            $nextFieldArray
        );

        $config->removeComponentsByType('GridFieldToolbarHeader');
        //$config->removeComponentsByType('GridFieldSortableHeader');
        $config->removeComponentsByType('GridFieldFilterHeader');
        $config->removeComponentsByType('GridFieldPageCount');
        $config->removeComponentsByType('GridFieldPaginator');
        $this->MyStep()->addOrderStepFields($fields, $this);

        if ($submitted) {
            //Config::nest();
            //Config::inst()->update('SSViewer', 'theme_enabled', true);
            //$htmlSummary = $this->renderWith("Order");
            //Config::unnest();

            //links
            $js = "window.open(this.href, 'payment', 'toolbar=0,scrollbars=1,location=1,statusbar=1,menubar=0,resizable=1,width=800,height=600'); return false;";
            $link = $this->getPrintLink();
            $label = _t('Order.PRINT_INVOICE', 'invoice');
            $linkHTML = '<a href="'.$link.'" onclick="'.$js.'">'.$label.'</a>';
            $linkHTML .= ' | ';
            $link = $this->getPackingSlipLink();
            $label = _t('Order.PRINT_PACKING_SLIP', 'packing slip');
            $linkHTML .= '<a href="'.$link.'" onclick="'.$js.'">'.$label.'</a>';
            $linkHTML = '<h3>Print: '.$linkHTML.'</h3>';
            $fields->addFieldToTab(
                'Root.Main',
                LiteralField::create('getPrintLinkANDgetPackingSlipLink', $linkHTML)
            );

            //add order here as well.
            $fields->addFieldToTab(
                'Root.Main',
                new LiteralField(
                    'MainDetails',
                    '<iframe src="'.$this->getPrintLink().'" width="100%" height="2500" style="border: 5px solid #2e7ead; border-radius: 2px;"></iframe>')
            );
            $fields->insertAfter(
                new Tab(
                    'Emails',
                    $this->getEmailsTableField()
                ),
                'Main'
            );
            $fields->addFieldToTab(
                "Root.Items",
                GridField::create(
                    'Items_Sold',
                    'Items Sold',
                    $this->Items(),
                    new GridFieldConfig_RecordViewer
                )
            );
            $fields->addFieldToTab(
                "Root.Modifiers",
                GridField::create(
                    'Modifications',
                    'Price (and other) adjustments',
                    $this->Modifiers(),
                    new GridFieldConfig_RecordViewer
                )
            );

            $fields->addFieldsToTab(
                'Root.Payments',
                array(
                    $this->getPaymentsField(),
                    new ReadOnlyField('TotalPaidNice', _t('Order.TOTALPAID', 'Total Paid'), $this->TotalPaidAsCurrencyObject()->Nice()),
                    new ReadOnlyField('TotalOutstandingNice', _t('Order.TOTALOUTSTANDING', 'Total Outstanding'), $this->getTotalOutstandingAsMoney()->Nice())
                )
            );
            if ($this->canPay()) {
                $link = EcommercePaymentController::make_payment_link($this->ID);
                $js = "window.open(this.href, 'payment', 'toolbar=0,scrollbars=1,location=1,statusbar=1,menubar=0,resizable=1,width=800,height=600'); return false;";
                $header = _t('Order.MAKEPAYMENT', 'make payment');
                $label = _t('Order.MAKEADDITIONALPAYMENTNOW', 'make additional payment now');
                $linkHTML = '<a href="'.$link.'" onclick="'.$js.'">'.$label.'</a>';
                $fields->addFieldToTab('Root.Payments', new HeaderField('MakeAdditionalPaymentHeader', $header, 3));
                $fields->addFieldToTab('Root.Payments', new LiteralField('MakeAdditionalPayment', $linkHTML));
            }
            //member
            $member = $this->Member();
            if ($member && $member->exists()) {
                $fields->addFieldToTab('Root.Account', new LiteralField('MemberDetails', $member->getEcommerceFieldsForCMS()));
            } else {
                $fields->addFieldToTab('Root.Customer', new LiteralField('MemberDetails',
                    '<p>'._t('Order.NO_ACCOUNT', 'There is no --- account --- associated with this order').'</p>'
                ));
            }
            $cancelledField = $fields->dataFieldByName('CancelledByID');
            $fields->removeByName('CancelledByID');
            $shopAdminAndCurrentCustomerArray = array_merge(
                EcommerceRole::list_of_admins(true),
                array($member->ID => $member->getName())
            );
            $fields->addFieldToTab(
                'Root.Cancellation',
                DropdownField::create(
                    'CancelledByID',
                    $cancelledField->Title(),
                    $shopAdminAndCurrentCustomerArray
                )
            );
            $fields->addFieldToTab('Root.Log', $this->getOrderStatusLogsTableField_Archived());
            $submissionLog = $this->SubmissionLog();
            if ($submissionLog) {
                $fields->addFieldToTab('Root.Log',
                    new ReadonlyField(
                        'SequentialOrderNumber',
                        _t('Order.SEQUENTIALORDERNUMBER', 'Consecutive order number for submitted orders (e.g. 1,2,3,4,5...)'),
                        $submissionLog->SequentialOrderNumber
                    )
                );
            }
        } else {
            $linkText = _t(
                'Order.LOAD_THIS_ORDER',
                'load this order'
            );
            $message = _t(
                'Order.NOSUBMITTEDYET',
                'No details are shown here as this order has not been submitted yet. You can {link} to submit it... NOTE: For this, you will be logged in as the customer and logged out as (shop)admin .',
                array('link' => '<a href="'.$this->RetrieveLink().'" data-popup="true">'.$linkText.'</a>')
            );
            $fields->addFieldToTab('Root.Next', new LiteralField('MainDetails', '<p>'.$message.'</p>'));
            $fields->addFieldToTab('Root.Items', $this->getOrderItemsField());
            $fields->addFieldToTab('Root.Extras', $this->getModifierTableField());

            //MEMBER STUFF
            $specialOptionsArray = array();
            if ($this->MemberID) {
                $specialOptionsArray[0] = _t('Order.SELECTCUSTOMER', '--- Remover Customer ---');
                $specialOptionsArray[$this->MemberID] = _t('Order.LEAVEWITHCURRENTCUSTOMER', '- Leave with current customer: ').$this->Member()->getTitle();
            } elseif ($currentMember) {
                $specialOptionsArray[0] = _t('Order.SELECTCUSTOMER', '--- Select Customers ---');
                $currentMemberID = $currentMember->ID;
                $specialOptionsArray[$currentMemberID] = _t('Order.ASSIGNTHISORDERTOME', '- Assign this order to me: ').$currentMember->getTitle();
            }
            //MEMBER FIELD!!!!!!!
            $memberArray = $specialOptionsArray + EcommerceRole::list_of_customers(true);
            $fields->addFieldToTab('Root.Next', new DropdownField('MemberID', _t('Order.SELECTCUSTOMER', 'Select Customer'), $memberArray), 'CustomerOrderNote');
            $memberArray = null;
        }
        $fields->addFieldToTab('Root.Addresses', new HeaderField('BillingAddressHeader', _t('Order.BILLINGADDRESS', 'Billing Address')));

        $fields->addFieldToTab('Root.Addresses', $this->getBillingAddressField());

        if (EcommerceConfig::get('OrderAddress', 'use_separate_shipping_address')) {
            $fields->addFieldToTab('Root.Addresses', new HeaderField('ShippingAddressHeader', _t('Order.SHIPPINGADDRESS', 'Shipping Address')));
            $fields->addFieldToTab('Root.Addresses', new CheckboxField('UseShippingAddress', _t('Order.USESEPERATEADDRESS', 'Use separate shipping address?')));
            if ($this->UseShippingAddress) {
                $fields->addFieldToTab('Root.Addresses', $this->getShippingAddressField());
            }
        }
        $currencies = EcommerceCurrency::get_list();
        if ($currencies && $currencies->count()) {
            $currencies = $currencies->map()->toArray();
            $fields->addFieldToTab('Root.Currency', new ReadOnlyField('ExchangeRate ', _t('Order.EXCHANGERATE', 'Exchange Rate'), $this->ExchangeRate));
            $fields->addFieldToTab('Root.Currency', $currencyField = new DropdownField('CurrencyUsedID', _t('Order.CurrencyUsed', 'Currency Used'), $currencies));
            if ($this->IsSubmitted()) {
                $fields->replaceField('CurrencyUsedID', $fields->dataFieldByName('CurrencyUsedID')->performReadonlyTransformation());
            }
        } else {
            $fields->addFieldToTab('Root.Currency', new LiteralField('CurrencyInfo', '<p>You can not change currencies, because no currencies have been created.</p>'));
            $fields->replaceField('CurrencyUsedID', $fields->dataFieldByName('CurrencyUsedID')->performReadonlyTransformation());
        }
        $fields->addFieldToTab('Root.Log', new ReadonlyField('Created', _t('Root.CREATED', 'Created')));
        $fields->addFieldToTab('Root.Log', new ReadonlyField('LastEdited', _t('Root.LASTEDITED', 'Last saved')));
        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    /**
     * Field to add and edit Order Items.
     *
     * @return GridField
     */
    protected function getOrderItemsField()
    {
        $gridFieldConfig = GridFieldConfigForOrderItems::create();
        $source = $this->OrderItems();

        return new GridField('OrderItems', _t('OrderItems.PLURALNAME', 'Order Items'), $source, $gridFieldConfig);
    }

    /**
     * Field to add and edit Modifiers.
     *
     * @return GridField
     */
    public function getModifierTableField()
    {
        $gridFieldConfig = GridFieldConfigForOrderItems::create();
        $source = $this->Modifiers();

        return new GridField('OrderModifiers', _t('OrderItems.PLURALNAME', 'Order Items'), $source, $gridFieldConfig);
    }

    /**
     *@return GridField
     **/
    protected function getBillingAddressField()
    {
        $this->CreateOrReturnExistingAddress('BillingAddress');
        $gridFieldConfig = GridFieldConfig::create()->addComponents(
            new GridFieldToolbarHeader(),
            new GridFieldSortableHeader(),
            new GridFieldDataColumns(),
            new GridFieldPaginator(10),
            new GridFieldEditButton(),
            new GridFieldDetailForm()
        );
        //$source = $this->BillingAddress();
        $source = BillingAddress::get()->filter(array('OrderID' => $this->ID));

        return new GridField('BillingAddress', _t('BillingAddress.SINGULARNAME', 'Billing Address'), $source, $gridFieldConfig);
    }

    /**
     *@return GridField
     **/
    protected function getShippingAddressField()
    {
        $this->CreateOrReturnExistingAddress('ShippingAddress');
        $gridFieldConfig = GridFieldConfig::create()->addComponents(
            new GridFieldToolbarHeader(),
            new GridFieldSortableHeader(),
            new GridFieldDataColumns(),
            new GridFieldPaginator(10),
            new GridFieldEditButton(),
            new GridFieldDetailForm()
        );
        //$source = $this->ShippingAddress();
        $source = ShippingAddress::get()->filter(array('OrderID' => $this->ID));

        return new GridField('ShippingAddress', _t('BillingAddress.SINGULARNAME', 'Shipping Address'), $source, $gridFieldConfig);
    }

    /**
     * Needs to be public because the OrderStep::getCMSFIelds accesses it.
     *
     * @param string    $sourceClass
     * @param string    $title
     *
     * @return GridField
     **/
    public function getOrderStatusLogsTableField(
        $sourceClass = 'OrderStatusLog',
        $title = ''
    ) {
        $gridFieldConfig = GridFieldConfig_RecordViewer::create()->addComponents(
            new GridFieldAddNewButton('toolbar-header-right'),
            new GridFieldDetailForm()
        );
        $title ? $title : $title = _t('OrderStatusLog.PLURALNAME', 'Order Status Logs');
        $source = $this->OrderStatusLogs()->Filter(array('ClassName' => $sourceClass));
        $gf = new GridField($sourceClass, $title, $source, $gridFieldConfig);
        $gf->setModelClass($sourceClass);

        return $gf;
    }

    /**
     * Needs to be public because the OrderStep::getCMSFIelds accesses it.
     *
     * @param string    $sourceClass
     * @param string    $title
     *
     * @return GridField
     **/
    public function getOrderStatusLogsTableFieldEditable(
        $sourceClass = 'OrderStatusLog',
        $title = ''
    ) {
        $gf = $this->getOrderStatusLogsTableField($sourceClass, $title);
        $gf->getConfig()->addComponents(
            new GridFieldEditButton()
        );
        return $gf;
    }

    /**
     * @param string    $sourceClass
     * @param string    $title
     * @param FieldList $fieldList          (Optional)
     * @param FieldList $detailedFormFields (Optional)
     *
     * @return GridField
     **/
    protected function getOrderStatusLogsTableField_Archived($sourceClass = 'OrderStatusLog', $title = '', FieldList $fieldList = null, FieldList $detailedFormFields = null)
    {
//        $gridFieldConfig = GridFieldConfig_RecordEditor::create()->addComponents(
//            new GridFieldDetailForm()
//        );
        $title ? $title : $title = _t('OrderItem.PLURALNAME', 'Order Log');

//        $source = $this->OrderStatusLogs();
        $source = OrderStatusLog::get();

        return new GridField($sourceClass, $title, $source, GridFieldConfig_RecordEditor::create());
    }

    /**
     * @return GridField
     **/
    public function getEmailsTableField()
    {
        $gridFieldConfig = GridFieldConfig_RecordViewer::create()->addComponents(
            new GridFieldDetailForm()
        );

        return new GridField('Emails', _t('Order.CUSTOMER_EMAILS', 'Customer Emails'), $this->Emails(), $gridFieldConfig);
    }

    /**
     * @return GridField
     */
    protected function getPaymentsField()
    {
        $gridFieldConfig = GridFieldConfig_RecordViewer::create()->addComponents(
            new GridFieldDetailForm(),
            new GridFieldEditButton()
        );

        return new GridField('Payments', _t('Order.PAYMENTS', 'Payments'), $this->Payments(), $gridFieldConfig);
    }

    /**
     * @return OrderStepField
     */
    public function OrderStepField()
    {
        return OrderStepField::create($name = 'MyOrderStep', $this, Member::currentUser());
    }

/*******************************************************
   * 2. MAIN TRANSITION FUNCTIONS
*******************************************************/

    /**
     * init runs on start of a new Order (@see onAfterWrite)
     * it adds all the modifiers to the orders and the starting OrderStep.
     *
     * @param bool $recalculate
     *
     * @return DataObject (Order)
     **/
    public function init($recalculate = false)
    {
        if ($this->IsSubmitted()) {
            user_error('Can not init an order that has been submitted', E_USER_NOTICE);
        } else {
            //to do: check if shop is open....
            if ($this->StatusID || $recalculate) {
                if (!$this->StatusID) {
                    $createdOrderStatus = OrderStep::get()->First();
                    if (!$createdOrderStatus) {
                        user_error('No ordersteps have been created', E_USER_WARNING);
                    }
                    $this->StatusID = $createdOrderStatus->ID;
                }
                $createdModifiersClassNames = array();
                $modifiersAsArrayList = new ArrayList();
                $modifiers = $this->modifiersFromDatabase($includingRemoved = true);
                if ($modifiers->count()) {
                    foreach ($modifiers as $modifier) {
                        $modifiersAsArrayList->push($modifier);
                    }
                }
                if ($modifiersAsArrayList->count()) {
                    foreach ($modifiersAsArrayList as $modifier) {
                        $createdModifiersClassNames[$modifier->ID] = $modifier->ClassName;
                    }
                } else {
                }
                $modifiersToAdd = EcommerceConfig::get('Order', 'modifiers');
                if (is_array($modifiersToAdd) && count($modifiersToAdd) > 0) {
                    foreach ($modifiersToAdd as $numericKey => $className) {
                        if (!in_array($className, $createdModifiersClassNames)) {
                            if (class_exists($className)) {
                                $modifier = new $className();
                                //only add the ones that should be added automatically
                                if (!$modifier->DoNotAddAutomatically()) {
                                    if (is_a($modifier, 'OrderModifier')) {
                                        $modifier->OrderID = $this->ID;
                                        $modifier->Sort = $numericKey;
                                        //init method includes a WRITE
                                        $modifier->init();
                                        //IMPORTANT - add as has_many relationship  (Attributes can be a modifier OR an OrderItem)
                                        $this->Attributes()->add($modifier);
                                        $modifiersAsArrayList->push($modifier);
                                    }
                                }
                            } else {
                                user_error('reference to a non-existing class: '.$className.' in modifiers', E_USER_NOTICE);
                            }
                        }
                    }
                }
                $this->extend('onInit', $this);
                //careful - this will call "onAfterWrite" again
                $this->write();
            }
        }

        return $this;
    }

    /**
     * @var array
     */
    private static $_try_to_finalise_order_is_running = array();

    /**
     * Goes through the order steps and tries to "apply" the next status to the order.
     *
     * @param bool $runAgain
     **/
    public function tryToFinaliseOrder($runAgain = false)
    {
        if (empty(self::$_try_to_finalise_order_is_running[$this->ID]) || $runAgain) {
            self::$_try_to_finalise_order_is_running[$this->ID] = true;
            if ($this->CancelledByID) {
                $this->Archive(true);

                return;
            }
            do {
                //status of order is being progressed
                $nextStatusID = $this->doNextStatus();
                //a little hack to make sure we do not rely on a stored value
                //of "isSubmitted"
                $this->_isSubmittedTempVar = -1;
            } while ($nextStatusID);
            //release ... to run again ...
            self::$_try_to_finalise_order_is_running[$this->ID] = false;
        }
    }

    /**
     * Goes through the order steps and tries to "apply" the next step
     * Step is updated after the other one is completed...
     *
     * @return int (StatusID or false if the next status can not be "applied")
     **/
    public function doNextStatus()
    {
        if ($this->MyStep()->initStep($this)) {
            if ($this->MyStep()->doStep($this)) {
                if ($nextOrderStepObject = $this->MyStep()->nextStep($this)) {
                    $this->StatusID = $nextOrderStepObject->ID;
                    $this->write();

                    return $this->StatusID;
                }
            }
        }

        return 0;
    }

    /**
     * cancel an order.
     *
     * @param Member $member - the user cancelling the order
     * @param string $reason - the reason the order is cancelled
     * @returns OrderStatusLog_Cancel
     */
    public function Cancel(Member $member, $reason = '')
    {
        $this->CancelledByID = $member->ID;
        //archive and write
        $this->Archive($avoidWrites = true);
        //create log ...
        $log = OrderStatusLog_Cancel::create();
        $log->AuthorID = $member->ID;
        $log->OrderID = $this->ID;
        $log->Note = $reason;
        if ($member->IsShopAdmin()) {
            $log->InternalUseOnly = true;
        }

        return $log->write();
    }

    /**
     * returns true if successful.
     *
     * @param bool $avoidWrites
     *
     * @return bool
     */
    public function Archive($avoidWrites = true)
    {
        $lastOrderStep = OrderStep::get()->Last();
        if ($lastOrderStep) {
            if ($avoidWrites) {
                DB::query('
                    UPDATE "Order"
                    SET "Order"."StatusID" = '.$lastOrderStep->ID.'
                    WHERE "Order"."ID" = '.$this->ID.'
                    LIMIT 1
                ');

                return true;
            } else {
                $this->StatusID = $lastOrderStep->ID;
                $this->write();

                return true;
            }
        }

        return false;
    }

/*******************************************************
   * 3. STATUS RELATED FUNCTIONS / SHORTCUTS
*******************************************************/

    /**
     * Avoids caching of $this->Status().
     *
     * @return DataObject (current OrderStep)
     */
    public function MyStep()
    {
        $step = null;
        if ($this->StatusID) {
            $step = OrderStep::get()->byID($this->StatusID);
        }
        if (!$step) {
            $step = OrderStep::get()->First(); //TODO: this could produce strange results
        }
        if (!$step) {
            $step = OrderStep_Created::create();
        }
        if (!$step) {
            user_error('You need an order step in your Database.');
        }

        return $step;
    }

    /**
     * Return the OrderStatusLog that is relevant to the Order status.
     *
     * @return OrderStatusLog
     */
    public function RelevantLogEntry()
    {
        return $this->MyStep()->RelevantLogEntry($this);
    }

    /**
     * @return DataObject (current OrderStep that can be seen by customer)
     */
    public function CurrentStepVisibleToCustomer()
    {
        $obj = $this->MyStep();
        if ($obj->HideStepFromCustomer) {
            $obj = OrderStep::get()->where('"OrderStep"."Sort" < '.$obj->Sort.' AND "HideStepFromCustomer" = 0')->First();
            if (!$obj) {
                $obj = OrderStep::get()->First();
            }
        }

        return $obj;
    }

    /**
     * works out if the order is still at the first OrderStep.
     *
     * @return bool
     */
    public function IsFirstStep()
    {
        $firstStep = OrderStep::get()->First();
        $currentStep = $this->MyStep();
        if ($firstStep && $currentStep) {
            if ($firstStep->ID == $currentStep->ID) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is the order still being "edited" by the customer?
     *
     * @return bool
     */
    public function IsInCart()
    {
        return (bool) $this->IsSubmitted() ? false : true;
    }

    /**
     * The order has "passed" the IsInCart phase.
     *
     * @return bool
     */
    public function IsPastCart()
    {
        return (bool) $this->IsInCart() ? false : true;
    }

    /**
     * Are there still steps the order needs to go through?
     *
     * @return bool
     */
    public function IsUncomplete()
    {
        return (bool) $this->MyStep()->ShowAsUncompletedOrder;
    }

    /**
     * Is the order in the :"processing" phaase.?
     *
     * @return bool
     */
    public function IsProcessing()
    {
        return (bool) $this->MyStep()->ShowAsInProcessOrder;
    }

    /**
     * Is the order completed?
     *
     * @return bool
     */
    public function IsCompleted()
    {
        return (bool) $this->MyStep()->ShowAsCompletedOrder;
    }

    /**
     * Has the order been paid?
     * TODO: why do we check if there is a total at all?
     *
     * @return bool
     */
    public function IsPaid()
    {
        if ($this->IsSubmitted()) {
            return (bool) (($this->Total() >= 0) && ($this->TotalOutstanding() <= 0));
        }

        return false;
    }

    /**
     * Has the order been paid?
     * TODO: why do we check if there is a total at all?
     *
     * @return bool
     */
    public function PaymentIsPending()
    {
        if ($this->IsSubmitted()) {
            if ($this->IsPaid()) {
                //do nothing;
            } elseif (($payments = $this->Payments()) && $payments->count()) {
                foreach ($payments as $payment) {
                    if ('Pending' == $payment->Status) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * shows payments that are meaningfull
     * if the order has been paid then only show successful payments.
     *
     * @return DataList
     */
    public function RelevantPayments()
    {
        if ($this->IsPaid()) {
            return $this->Payments("\"Status\" = 'Success'");
            //EcommercePayment::get()->
            //	filter(array("OrderID" => $this->ID, "Status" => "Success"));
        } else {
            return $this->Payments();
        }
    }

    /**
     * Has the order been cancelled?
     *
     * @return bool
     */
    public function IsCancelled()
    {
        return $this->getIsCancelled();
    }
    public function getIsCancelled()
    {
        return $this->CancelledByID ? true : false;
    }

    /**
     * Has the order been cancelled by the customer?
     *
     * @return bool
     */
    public function IsCustomerCancelled()
    {
        if ($this->MemberID > 0 && $this->MemberID == $this->IsCancelledID) {
            return true;
        }

        return false;
    }

    /**
     * Has the order been cancelled by the  administrator?
     *
     * @return bool
     */
    public function IsAdminCancelled()
    {
        if ($this->IsCancelled()) {
            if (!$this->IsCustomerCancelled()) {
                $admin = Member::get()->byID($this->CancelledByID);
                if ($admin) {
                    if ($admin->IsShopAdmin()) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Is the Shop Closed for business?
     *
     * @return bool
     */
    public function ShopClosed()
    {
        return EcomConfig()->ShopClosed;
    }

/*******************************************************
   * 4. LINKING ORDER WITH MEMBER AND ADDRESS
*******************************************************/

    /**
     * Returns a member linked to the order.
     * If a member is already linked, it will return the existing member.
     * Otherwise it will return a new Member.
     *
     * Any new member is NOT written, because we dont want to create a new member unless we have to!
     * We will not add a member to the order unless a new one is created in the checkout
     * OR the member is logged in / logs in.
     *
     * Also note that if a new member is created, it is not automatically written
     *
     * @param bool $forceCreation - if set to true then the member will always be saved in the database.
     *
     * @return Member
     **/
    public function CreateOrReturnExistingMember($forceCreation = false)
    {
        if ($this->IsSubmitted()) {
            return $this->Member();
        }
        if ($this->MemberID) {
            $member = $this->Member();
        } elseif ($member = Member::currentUser()) {
            if (!$member->IsShopAdmin()) {
                $this->MemberID = $member->ID;
                $this->write();
            }
        }
        $member = $this->Member();
        if (!$member) {
            $member = new Member();
        }
        if ($member && $forceCreation) {
            $member->write();
        }

        return $member;
    }

    /**
     * Returns either the existing one or a new Order Address...
     * All Orders will have a Shipping and Billing address attached to it.
     * Method used to retrieve object e.g. for $order->BillingAddress(); "BillingAddress" is the method name you can use.
     * If the method name is the same as the class name then dont worry about providing one.
     *
     * @param string $className             - ClassName of the Address (e.g. BillingAddress or ShippingAddress)
     * @param string $alternativeMethodName - method to retrieve Address
     **/
    public function CreateOrReturnExistingAddress($className = 'BillingAddress', $alternativeMethodName = '')
    {
        if ($this->exists()) {
            $methodName = $className;
            if ($alternativeMethodName) {
                $methodName = $alternativeMethodName;
            }
            if ($this->IsSubmitted()) {
                return $this->$methodName();
            }
            $variableName = $className.'ID';
            $address = null;
            if ($this->$variableName) {
                $address = $this->$methodName();
            }
            if (!$address) {
                $address = new $className();
                if ($member = $this->CreateOrReturnExistingMember()) {
                    if ($member->exists()) {
                        $address->FillWithLastAddressFromMember($member, $write = false);
                    }
                }
            }
            if ($address) {
                if (!$address->exists()) {
                    $address->write();
                }
                if ($address->OrderID != $this->ID) {
                    $address->OrderID = $this->ID;
                    $address->write();
                }
                if ($this->$variableName != $address->ID) {
                    if (!$this->IsSubmitted()) {
                        $this->$variableName = $address->ID;
                        $this->write();
                    }
                }

                return $address;
            }
        }

        return;
    }

    /**
     * Sets the country in the billing and shipping address.
     *
     * @param string $countryCode            - code for the country e.g. NZ
     * @param bool   $includeBillingAddress
     * @param bool   $includeShippingAddress
     **/
    public function SetCountryFields($countryCode, $includeBillingAddress = true, $includeShippingAddress = true)
    {
        if ($this->IsSubmitted()) {
            user_error('Can not change country in submitted order', E_USER_NOTICE);
        } else {
            if ($includeBillingAddress) {
                if ($billingAddress = $this->CreateOrReturnExistingAddress('BillingAddress')) {
                    $billingAddress->SetCountryFields($countryCode);
                }
            }
            if (EcommerceConfig::get('OrderAddress', 'use_separate_shipping_address')) {
                if ($includeShippingAddress) {
                    if ($shippingAddress = $this->CreateOrReturnExistingAddress('ShippingAddress')) {
                        $shippingAddress->SetCountryFields($countryCode);
                    }
                }
            }
        }
    }

    /**
     * Sets the region in the billing and shipping address.
     *
     * @param int $regionID - ID for the region to be set
     **/
    public function SetRegionFields($regionID)
    {
        if ($this->IsSubmitted()) {
            user_error('Can not change country in submitted order', E_USER_NOTICE);
        } else {
            if ($billingAddress = $this->CreateOrReturnExistingAddress('BillingAddress')) {
                $billingAddress->SetRegionFields($regionID);
            }
            if ($this->CanHaveShippingAddress()) {
                if ($shippingAddress = $this->CreateOrReturnExistingAddress('ShippingAddress')) {
                    $shippingAddress->SetRegionFields($regionID);
                }
            }
        }
    }

    /**
     * Stores the preferred currency of the order.
     * IMPORTANTLY we store the exchange rate for future reference...
     *
     * @param EcommerceCurrency $currency
     */
    public function UpdateCurrency($newCurrency)
    {
        if ($this->IsSubmitted()) {
            user_error('Can not set the currency after the order has been submitted', E_USER_NOTICE);
        } else {
            if (! is_a($newCurrency, Object::getCustomClass('EcommerceCurrency'))) {
                $newCurrency = EcommerceCurrency::default_currency();
            }
            $this->CurrencyUsedID = $newCurrency->ID;
            $this->ExchangeRate = $newCurrency->getExchangeRate();
            $this->write();
        }
    }

    /**
     * alias for UpdateCurrency.
     *
     * @param EcommerceCurrency $currency
     */
    public function SetCurrency($currency)
    {
        $this->UpdateCurrency($currency);
    }

/*******************************************************
   * 5. CUSTOMER COMMUNICATION
*******************************************************/

    /**
     * Send the invoice of the order by email.
     *
     * @param string $subject            - subject for the email
     * @param string $message            - the main message in the email
     * @param bool   $resend             - send the email even if it has been sent before
     * @param bool   $adminOnlyOrToEmail - do not send to customer, only send to shop admin
     * @param string $emailClassName     - class used to send email
     *
     * @return bool TRUE on success, FALSE on failure (in theory)
     */
    public function sendEmail(
        $subject = '',
        $message = '',
        $resend = false,
        $adminOnlyOrToEmail = false,
        $emailClassName = 'Order_InvoiceEmail'
    ) {
        return $this->prepareAndSendEmail(
            $emailClassName,
            $subject,
            $message,
            $resend,
            $adminOnlyOrToEmail
        );
    }

    /**
     * Sends a message to the shop admin ONLY and not to the customer
     * This can be used by ordersteps and orderlogs to notify the admin of any potential problems.
     *
     * @param string $subject - subject for the email
     * @param string $message - message to be added with the email
     *
     * @return bool TRUE for success, FALSE for failure (not tested)
     */
    public function sendError($subject = '', $message = '')
    {
        return $this->prepareAndSendEmail('Order_ErrorEmail', _t('Order.ERROR', 'ERROR').' '.$subject, $message, $resend = true, $adminOnly = true);
    }

    /**
     * Sends a message to the shop admin ONLY and not to the customer
     * This can be used by ordersteps and orderlogs to notify the admin of any potential problems.
     *
     * @param string $subject        - subject for the email
     * @param string $message        - message to be added with the email
     * @param bool   $resend         - can it be sent twice?
     * @param string $emailClassName - template to be used ...
     *
     * @return bool TRUE for success, FALSE for failure (not tested)
     */
    public function sendAdminNotification(
        $subject = '',
        $message = '',
        $resend = false,
        $emailClassName = 'Order_ErrorEmail'
    ) {
        return $this->prepareAndSendEmail($emailClassName, $subject, $message, $resend, $adminOnly = true);
    }

    /**
     * Send a mail of the order to the client (and another to the admin).
     *
     * @param string        $emailClassName     - the class name of the email you wish to send
     * @param string        $subject            - email subject
     * @param bool          $copyToAdmin        - true by default, whether it should send a copy to the admin
     * @param bool          $resend             - sends the email even it has been sent before.
     * @param bool | string $adminOnlyOrToEmail - sends the email to the ADMIN ONLY, if you provide an email, it will go to the email...
     *
     * @return bool TRUE for success, FALSE for failure (not tested)
     */
    protected function prepareAndSendEmail(
        $emailClassName,
        $subject,
        $message,
        $resend = false,
        $adminOnlyOrToEmail = false
    ) {
        $arrayData = $this->createReplacementArrayForEmail($message, $subject);
        $from = Order_Email::get_from_email();
        //why are we using this email and NOT the member.EMAIL?
        //for historical reasons????
        if ($adminOnlyOrToEmail) {
            if (filter_var($adminOnlyOrToEmail, FILTER_VALIDATE_EMAIL)) {
                $to = $adminOnlyOrToEmail;
                // invalid e-mail address
            } else {
                $to = Order_Email::get_from_email();
            }
        } else {
            $to = $this->getOrderEmail();
        }
        if ($from && $to) {
            $email = new $emailClassName();
            if (!(is_a($email, Object::getCustomClass('Email')))) {
                user_error('No correct email class provided.', E_USER_ERROR);
            }
            $email->setFrom($from);
            $email->setTo($to);
            //we take the subject from the Array Data, just in case it has been adjusted.
            $email->setSubject($arrayData->getField('Subject'));
            //we also see if a CC and a BCC have been added
            ;
            if ($cc = $arrayData->getField('CC')) {
                $email->setCc($cc);
            }
            if ($bcc = $arrayData->getField('BCC')) {
                $email->setBcc($bcc);
            }
            $email->populateTemplate($arrayData);
            // This might be called from within the CMS,
            // so we need to restore the theme, just in case
            // templates within the theme exist
            Config::nest();
            Config::inst()->update('SSViewer', 'theme_enabled', true);
            $email->setOrder($this);
            $email->setResend($resend);
            $result = $email->send(null);
            Config::unnest();
            if (Director::isDev()) {
                return true;
            } else {
                return $result;
            }
        }

        return false;
    }

    /**
     * returns the Data that can be used in the body of an order Email
     * we add the subject here so that the subject, for example, can be added to the <title>
     * of the email template.
     * we add the subject here so that the subject, for example, can be added to the <title>
     * of the email template.
     *
     * @param string $message - the additional message
     * @param string $subject - subject for email -
     *
     * @return ArrayData
     *                   - Subject - EmailSubject
     *                   - Message - specific message for this order
     *                   - OrderStepMessage - generic message for step
     *                   - Order
     *                   - EmailLogo
     *                   - ShopPhysicalAddress
     *                   - CurrentDateAndTime
     *                   - BaseURL
     *                   - CC
     *                   - BCC
     */
    public function createReplacementArrayForEmail($message = '', $subject = '')
    {
        $step = $this->MyStep();
        $config = $this->EcomConfig();
        $replacementArray = array();
        //set subject
        if ($subject) {
            $subject = $subject;
        } else {
            $subject = $step->EmailSubject;
        }
        $subject = str_replace('[OrderNumber]', $this->ID, $subject);
        //set other variables
        $replacementArray['Subject'] = $subject;
        $replacementArray['To'] = '';
        $replacementArray['CC'] = '';
        $replacementArray['BCC'] = '';
        $replacementArray['Message'] = $message;
        $replacementArray['OrderStepMessage'] = $step->CustomerMessage;
        $replacementArray['Order'] = $this;
        $replacementArray['EmailLogo'] = $config->EmailLogo();
        $replacementArray['ShopPhysicalAddress'] = $config->ShopPhysicalAddress;
        $replacementArray['CurrentDateAndTime'] = DBField::create_field('SS_Datetime', 'Now');
        $replacementArray['BaseURL'] = Director::baseURL();
        $arrayData = new ArrayData($replacementArray);
        $this->extend('updateReplacementArrayForEmail', $arrayData);
        return $arrayData;
    }

    /**
     * returns the order formatted as an email.
     *
     * @param string $message        - the additional message
     * @param string $emailClassName - template to use.
     *
     * @return array (Message, Order, EmailLogo, ShopPhysicalAddress)
     */
    public function renderOrderInEmailFormat($message = '', $emailClassName)
    {
        $arrayData = $this->createReplacementArrayForEmail($message);
        Config::nest();
        Config::inst()->update('SSViewer', 'theme_enabled', true);
        $html = $arrayData->renderWith($emailClassName);
        Config::unnest();

        return Order_Email::emogrify_html($html);
    }

/*******************************************************
   * 6. ITEM MANAGEMENT
*******************************************************/

    /**
     * returns a list of Order Attributes by type.
     *
     * @param array | String $types
     *
     * @return ArrayList
     */
    public function getOrderAttributesByType($types)
    {
        if (!is_array($types) && is_string($types)) {
            $types = array($types);
        }
        if (!is_array($al)) {
            user_error('wrong parameter (types) provided in Order::getOrderAttributesByTypes');
        }
        $al = new ArrayList();
        $items = $this->Items();
        foreach ($items as $item) {
            if (in_array($item->OrderAttributeType(), $types)) {
                $al->push($item);
            }
        }
        $modifiers = $this->Modifiers();
        foreach ($modifiers as $modifier) {
            if (in_array($modifier->OrderAttributeType(), $types)) {
                $al->push($modifier);
            }
        }

        return $al;
    }

    /**
     * Returns the items of the order.
     * Items are the order items (products) and NOT the modifiers (discount, tax, etc...).
     *
     * N. B. this method returns Order Items
     * also see Buaybles

     *
     * @param string filter - where statement to exclude certain items OR ClassName (e.g. 'TaxModifier')
     *
     * @return DataList (OrderItems)
     */
    public function Items($filterOrClassName = '')
    {
        if (!$this->exists()) {
            $this->write();
        }

        return $this->itemsFromDatabase($filterOrClassName);
    }

    /**
     * @alias function of Items
     *
     * N. B. this method returns Order Items
     * also see Buaybles
     *
     * @param string filter - where statement to exclude certain items.
     * @alias for Items
     * @return DataList (OrderItems)
     */
    public function OrderItems($filterOrClassName = '')
    {
        return $this->Items($filterOrClassName);
    }

    /**
     * returns the buyables asscoiated with the order items.
     *
     * NB. this method retursn buyables
     *
     * @param string filter - where statement to exclude certain items.
     *
     * @return ArrayList (Buyables)
     */
    public function Buyables($filterOrClassName = '')
    {
        $items = $this->Items($filterOrClassName);
        $arrayList = new ArrayList();
        foreach ($items as $item) {
            $arrayList->push($item->Buyable());
        }

        return $arrayList;
    }

    /**
     * Return all the {@link OrderItem} instances that are
     * available as records in the database.
     *
     * @param string filter - where statement to exclude certain items,
     *   you can also pass a classname (e.g. MyOrderItem), in which case only this class will be returned (and any class extending your given class)
     *
     * @return DataList (OrderItems)
     */
    protected function itemsFromDatabase($filterOrClassName = '')
    {
        $className = 'OrderItem';
        $extrafilter = '';
        if ($filterOrClassName) {
            if (class_exists($filterOrClassName)) {
                $className = $filterOrClassName;
            } else {
                $extrafilter = " AND $filterOrClassName";
            }
        }

        return $className::get()->filter(array('OrderID' => $this->ID))->where($extrafilter);
    }

    /**
     * @alias for Modifiers
     *
     * @return DataList (OrderModifiers)
     */
    public function OrderModifiers()
    {
        return $this->Modifiers();
    }

    /**
     * Returns the modifiers of the order, if it hasn't been saved yet
     * it returns the modifiers from session, if it has, it returns them
     * from the DB entry. ONLY USE OUTSIDE ORDER.
     *
     * @param string filter - where statement to exclude certain items OR ClassName (e.g. 'TaxModifier')
     *
     * @return DataList (OrderModifiers)
     */
    public function Modifiers($filterOrClassName = '')
    {
        return $this->modifiersFromDatabase($filterOrClassName);
    }

    /**
     * Get all {@link OrderModifier} instances that are
     * available as records in the database.
     * NOTE: includes REMOVED Modifiers, so that they do not get added again...
     *
     * @param string filter - where statement to exclude certain items OR ClassName (e.g. 'TaxModifier')
     *
     * @return DataList (OrderModifiers)
     */
    protected function modifiersFromDatabase($filterOrClassName = '')
    {
        $className = 'OrderModifier';
        $extrafilter = '';
        if ($filterOrClassName) {
            if (class_exists($filterOrClassName)) {
                $className = $filterOrClassName;
            } else {
                $extrafilter = " AND $filterOrClassName";
            }
        }

        return $className::get()->where('"OrderAttribute"."OrderID" = '.$this->ID." $extrafilter");
    }

    /**
     * Calculates and updates all the order attributes.
     *
     * @param bool $recalculate - run it, even if it has run already
     */
    public function calculateOrderAttributes($recalculate = false)
    {
        if ($this->IsSubmitted()) {
            //submitted orders are NEVER recalculated.
            //they are set in stone.
        } elseif (self::get_needs_recalculating($this->ID) || $recalculate) {
            if ($this->StatusID || $this->TotalItems()) {
                $this->ensureCorrectExchangeRate();
                $this->calculateOrderItems($recalculate);
                $this->calculateModifiers($recalculate);
                $this->extend('onCalculateOrder');
            }
        }
    }

    /**
     * Calculates and updates all the product items.
     *
     * @param bool $recalculate - run it, even if it has run already
     */
    protected function calculateOrderItems($recalculate = false)
    {
        //check if order has modifiers already
        //check /re-add all non-removable ones
        //$start = microtime();
        $orderItems = $this->itemsFromDatabase();
        if ($orderItems->count()) {
            foreach ($orderItems as $orderItem) {
                if ($orderItem) {
                    $orderItem->runUpdate($recalculate);
                }
            }
        }
        $this->extend('onCalculateOrderItems', $orderItems);
    }

    /**
     * Calculates and updates all the modifiers.
     *
     * @param bool $recalculate - run it, even if it has run already
     */
    protected function calculateModifiers($recalculate = false)
    {
        $createdModifiers = $this->modifiersFromDatabase();
        if ($createdModifiers->count()) {
            foreach ($createdModifiers as $modifier) {
                if ($modifier) {
                    $modifier->runUpdate($recalculate);
                }
            }
        }
        $this->extend('onCalculateModifiers', $createdModifiers);
    }

    /**
     * Returns the subtotal of the modifiers for this order.
     * If a modifier appears in the excludedModifiers array, it is not counted.
     *
     * @param string|array $excluded               - Class(es) of modifier(s) to ignore in the calculation.
     * @param bool         $stopAtExcludedModifier - when this flag is TRUE, we stop adding the modifiers when we reach an excluded modifier.
     *
     * @return float
     */
    public function ModifiersSubTotal($excluded = null, $stopAtExcludedModifier = false)
    {
        $total = 0;
        $modifiers = $this->Modifiers();
        if ($modifiers->count()) {
            foreach ($modifiers as $modifier) {
                if (!$modifier->IsRemoved()) { //we just double-check this...
                    if (is_array($excluded) && in_array($modifier->ClassName, $excluded)) {
                        if ($stopAtExcludedModifier) {
                            break;
                        }
                        //do the next modifier
                        continue;
                    } elseif (is_string($excluded) && ($modifier->ClassName == $excluded)) {
                        if ($stopAtExcludedModifier) {
                            break;
                        }
                        //do the next modifier
                        continue;
                    }
                    $total += $modifier->CalculationTotal();
                }
            }
        }

        return $total;
    }

    /**
     * returns a modifier that is an instanceof the classname
     * it extends.
     *
     * @param string $className: class name for the modifier
     *
     * @return DataObject (OrderModifier)
     **/
    public function RetrieveModifier($className)
    {
        $modifiers = $this->Modifiers();
        if ($modifiers->count()) {
            foreach ($modifiers as $modifier) {
                if (is_a($modifier, Object::getCustomClass($className))) {
                    return $modifier;
                }
            }
        }
    }

/*******************************************************
   * 7. CRUD METHODS (e.g. canView, canEdit, canDelete, etc...)
*******************************************************/

    /**
     * @param Member $member
     *
     * @return DataObject (Member)
     **/
     //TODO: please comment why we make use of this function
    protected function getMemberForCanFunctions(Member $member = null)
    {
        if (!$member) {
            $member = Member::currentUser();
        }
        if (!$member) {
            $member = new Member();
            $member->ID = 0;
        }

        return $member;
    }

    /**
     * @param Member $member
     *
     * @return bool
     **/
    public function canCreate($member = null)
    {
        $member = $this->getMemberForCanFunctions($member);
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if ($member->exists()) {
            return $member->IsShopAdmin();
        }
    }

    /**
     * Standard SS method - can the current member view this order?
     *
     * @param Member $member
     *
     * @return bool
     **/
    public function canView($member = null)
    {
        if (!$this->exists()) {
            return true;
        }
        $member = $this->getMemberForCanFunctions($member);
        //check if this has been "altered" in any DataExtension
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        //is the member is a shop admin they can always view it
        if (EcommerceRole::current_member_is_shop_admin($member)) {
            return true;
        }

        //is the member is a shop assistant they can always view it
        if (EcommerceRole::current_member_is_shop_assistant($member)) {
            return true;
        }
        //if the current member OWNS the order, (s)he can always view it.
        if ($member->exists() && $this->MemberID == $member->ID) {
            return true;
        }
        //it is the current order
        if ($this->IsInSession()) {
            //we do some additional CHECKS for session hackings!
            if ($member->exists() && $this->MemberID) {
                //can't view the order of another member!
                //shop admin exemption is already captured.
                //this is always true
                if ($this->MemberID != $member->ID) {
                    return false;
                }
            } else {
                //order belongs to someone, but current user is NOT logged in...
                //this is allowed!
                //the reason it is allowed is because we want to be able to
                //add order to non-existing member
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function IsInSession()
    {
        $orderInSession = ShoppingCart::session_order();

        return $orderInSession && $this->ID && $this->ID == $orderInSession->ID;
    }

    /**
     * returns a pseudo random part of the session id.
     *
     * @param int $size
     *
     * @return string
     */
    public function LessSecureSessionID($size = 7, $start = null)
    {
        if (!$start || $start < 0 || $start > (32 - $size)) {
            $start = 0;
        }

        return substr($this->SessionID, $start, $size);
    }
    /**
     *
     * @param Member (optional) $member
     *
     * @return bool
     **/
    public function canViewAdminStuff($member = null)
    {
        $member = $this->getMemberForCanFunctions($member);
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }
    }

    /**
     * if we set canEdit to false then we
     * can not see the child records
     * Basically, you can edit when you can view and canEdit (even as a customer)
     * Or if you are a Shop Admin you can always edit.
     * Otherwise it is false...
     *
     * @param Member $member
     *
     * @return bool
     **/
    public function canEdit($member = null)
    {
        $member = $this->getMemberForCanFunctions($member);
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if ($this->canView($member) && $this->MyStep()->CustomerCanEdit) {
            return true;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }
        //is the member is a shop assistant they can always view it
        if (EcommerceRole::current_member_is_shop_assistant($member)) {
            return true;
        }
        return false;
    }

    /**
     * is the order ready to go through to the
     * checkout process.
     *
     * This method checks all the order items and order modifiers
     * If any of them need immediate attention then this is done
     * first after which it will go through to the checkout page.
     *
     * @param Member (optional) $member
     *
     * @return bool
     **/
    public function canCheckout(Member $member = null)
    {
        $member = $this->getMemberForCanFunctions($member);
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        $submitErrors = $this->SubmitErrors();
        if ($submitErrors && $submitErrors->count()) {
            return false;
        }

        return true;
    }

    /**
     * Can the order be submitted?
     * this method can be used to stop an order from being submitted
     * due to something not being completed or done.
     *
     * @see Order::SubmitErrors
     *
     * @param Member $member
     *
     * @return bool
     **/
    public function canSubmit(Member $member = null)
    {
        $member = $this->getMemberForCanFunctions($member);
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if ($this->IsSubmitted()) {
            return false;
        }
        $submitErrors = $this->SubmitErrors();
        if ($submitErrors && $submitErrors->count()) {
            return false;
        }

        return true;
    }

    /**
     * Can a payment be made for this Order?
     *
     * @param Member $member
     *
     * @return bool
     **/
    public function canPay(Member $member = null)
    {
        $member = $this->getMemberForCanFunctions($member);
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if ($this->IsPaid() || $this->IsCancelled() || $this->PaymentIsPending()) {
            return false;
        }

        return $this->MyStep()->CustomerCanPay;
    }

    /**
     * Can the given member cancel this order?
     *
     * @param Member $member
     *
     * @return bool
     **/
    public function canCancel(Member $member = null)
    {
        //if it is already cancelled it can be cancelled again
        if ($this->CancelledByID) {
            return false;
        }
        $member = $this->getMemberForCanFunctions($member);
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return $this->MyStep()->CustomerCanCancel && $this->canView($member);
    }

    /**
     * @param Member $member
     *
     * @return bool
     **/
    public function canDelete($member = null)
    {
        $member = $this->getMemberForCanFunctions($member);
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if ($this->IsSubmitted()) {
            return false;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return false;
    }

    /**
     * Returns all the order logs that the current member can view
     * i.e. some order logs can only be viewed by the admin (e.g. suspected fraud orderlog).
     *
     * @return ArrayList (OrderStatusLogs)
     **/
    public function CanViewOrderStatusLogs()
    {
        $canViewOrderStatusLogs = new ArrayList();
        $logs = $this->OrderStatusLogs();
        foreach ($logs as $log) {
            if ($log->canView()) {
                $canViewOrderStatusLogs->push($log);
            }
        }

        return $canViewOrderStatusLogs;
    }

    /**
     * returns all the logs that can be viewed by the customer.
     *
     * @return ArrayList (OrderStausLogs)
     */
    public function CustomerViewableOrderStatusLogs()
    {
        $customerViewableOrderStatusLogs = new ArrayList();
        $logs = $this->OrderStatusLogs();
        if ($logs) {
            foreach ($logs as $log) {
                if (!$log->InternalUseOnly) {
                    $customerViewableOrderStatusLogs->push($log);
                }
            }
        }

        return $customerViewableOrderStatusLogs;
    }

/*******************************************************
   * 8. GET METHODS (e.g. Total, SubTotal, Title, etc...)
*******************************************************/

    /**
     * returns the email to be used for customer communication.
     *
     * @return string
     */
    public function OrderEmail()
    {
        return $this->getOrderEmail();
    }
    public function getOrderEmail()
    {
        $email = '';
        if ($this->BillingAddressID && $this->BillingAddress()) {
            $email = $this->BillingAddress()->Email;
        }
        if (! $email) {
            if ($this->MemberID && $this->Member()) {
                $email = $this->Member()->Email;
            }
        }
        $extendedEmail = $this->extend('updateOrderEmail', $email);
        if ($extendedEmail !== null && is_array($extendedEmail) && count($extendedEmail)) {
            $email = implode(';', $extendedEmail);
        }

        return $email;
    }

    /**
     * Returns true if there is a prink or email link.
     *
     * @return bool
     */
    public function HasPrintOrEmailLink()
    {
        return $this->EmailLink() || $this->PrintLink();
    }

    /**
     * returns the absolute link to the order that can be used in the customer communication (email).
     *
     * @return string
     */
    public function EmailLink($type = 'Order_StatusEmail')
    {
        return $this->getEmailLink();
    }
    public function getEmailLink($type = 'Order_StatusEmail')
    {
        if (!isset($_REQUEST['print'])) {
            if ($this->IsSubmitted()) {
                return Director::AbsoluteURL(OrderConfirmationPage::get_email_link($this->ID, $this->MyStep()->getEmailClassName(), $actuallySendEmail = true));
            }
        }
    }

    /**
     * returns the absolute link to the order for printing.
     *
     * @return string
     */
    public function PrintLink()
    {
        return $this->getPrintLink();
    }
    public function getPrintLink()
    {
        if (!isset($_REQUEST['print'])) {
            if ($this->IsSubmitted()) {
                return Director::AbsoluteURL(OrderConfirmationPage::get_order_link($this->ID)).'?print=1';
            }
        }
    }

    /**
     * returns the absolute link to the order for printing.
     *
     * @return string
     */
    public function PackingSlipLink()
    {
        return $this->getPackingSlipLink();
    }
    public function getPackingSlipLink()
    {
        if ($this->IsSubmitted()) {
            return Director::AbsoluteURL(OrderConfirmationPage::get_order_link($this->ID)).'?packingslip=1';
        }
    }

    /**
     * returns the absolute link that the customer can use to retrieve the email WITHOUT logging in.
     *
     * @todo: is this a security risk?
     *
     * @return string
     */
    public function RetrieveLink()
    {
        return $this->getRetrieveLink();
    }
    public function getRetrieveLink()
    {
        if ($this->IsSubmitted()) {
            //add session ID if not added yet...
            if (!$this->SessionID) {
                $this->write();
            }

            return Director::AbsoluteURL(OrderConfirmationPage::find_link()).'retrieveorder/'.$this->SessionID.'/'.$this->ID.'/';
        } else {
            return Director::AbsoluteURL('/shoppingcart/loadorder/'.$this->ID.'/');
        }
    }

    /**
     * link to delete order.
     *
     * @return string
     */
    public function DeleteLink()
    {
        return $this->getDeleteLink();
    }
    public function getDeleteLink()
    {
        if ($this->canDelete()) {
            return ShoppingCart_Controller::delete_order_link($this->ID);
        } else {
            return '';
        }
    }

    /**
     * link to copy order.
     *
     * @return string
     */
    public function CopyOrderLink()
    {
        return $this->getCopyOrderLink();
    }
    public function getCopyOrderLink()
    {
        if ($this->canView() && $this->IsSubmitted()) {
            return ShoppingCart_Controller::copy_order_link($this->ID);
        } else {
            return '';
        }
    }

    /**
     * A "Title" for the order, which summarises the main details (date, and customer) in a string.
     *
     * @param string $dateFormat  - e.g. "D j M Y, G:i T"
     * @param bool   $includeName - e.g. by Mr Johnson
     *
     * @return string
     **/
    public function Title($dateFormat = null, $includeName = false)
    {
        return $this->getTitle($dateFormat, $includeName);
    }
    public function getTitle($dateFormat = null, $includeName = false)
    {
        if ($this->exists()) {
            if ($dateFormat === null) {
                $dateFormat = EcommerceConfig::get('Order', 'date_format_for_title');
            }
            if ($includeName === null) {
                $includeName = EcommerceConfig::get('Order', 'include_customer_name_in_title');
            }
            $title = $this->i18n_singular_name()." #$this->ID";
            if ($dateFormat) {
                if ($submissionLog = $this->SubmissionLog()) {
                    $dateObject = $submissionLog->dbObject('Created');
                    $placed = _t('Order.PLACED', 'placed');
                } else {
                    $dateObject = $this->dbObject('Created');
                    $placed = _t('Order.STARTED', 'started');
                }
                $title .= ', '.$placed.' '.$dateObject->Format($dateFormat);
            }
            $name = '';
            if ($this->CancelledByID) {
                $name = ' - '._t('Order.CANCELLED', 'CANCELLED');
            }
            if ($includeName) {
                $by = _t('Order.BY', 'by');
                if (!$name) {
                    if ($this->BillingAddressID) {
                        if ($billingAddress = $this->BillingAddress()) {
                            $name = ' - '.$by.' '.$billingAddress->Prefix.' '.$billingAddress->FirstName.' '.$billingAddress->Surname;
                        }
                    }
                }
                if (!$name) {
                    if ($this->MemberID) {
                        if ($member = $this->Member()) {
                            if ($member->exists()) {
                                if ($memberName = $member->getName()) {
                                    if (!trim($memberName)) {
                                        $memberName = _t('Order.ANONYMOUS', 'anonymous');
                                    }
                                    $name = ' - '.$by.' '.$memberName;
                                }
                            }
                        }
                    }
                }
            }
            $title .= $name;
        } else {
            $title = _t('Order.NEW', 'New').' '.$this->i18n_singular_name();
        }
        $extendedTitle = $this->extend('updateTitle', $title);
        if ($extendedTitle !== null && is_array($extendedTitle) && count($extendedTitle)) {
            $title = implode('; ', $extendedTitle);
        }

        return $title;
    }

    /**
     * Returns the subtotal of the items for this order.
     *
     * @return float
     */
    public function SubTotal()
    {
        return $this->getSubTotal();
    }
    public function getSubTotal()
    {
        $result = 0;
        $items = $this->Items();
        if ($items->count()) {
            foreach ($items as $item) {
                if (is_a($item, Object::getCustomClass('OrderAttribute'))) {
                    $result += $item->Total();
                }
            }
        }

        return $result;
    }

    /**
     * @return Currency (DB Object)
     **/
    public function SubTotalAsCurrencyObject()
    {
        return DBField::create_field('Currency', $this->SubTotal());
    }

    /**
     * @return Money
     **/
    public function SubTotalAsMoney()
    {
        return $this->getSubTotalAsMoney();
    }
    public function getSubTotalAsMoney()
    {
        return EcommerceCurrency::get_money_object_from_order_currency($this->SubTotal(), $this);
    }

    /**
     * @param string|array $excluded               - Class(es) of modifier(s) to ignore in the calculation.
     * @param bool         $stopAtExcludedModifier - when this flag is TRUE, we stop adding the modifiers when we reach an excluded modifier.
     *
     * @return Currency (DB Object)
     **/
    public function ModifiersSubTotalAsCurrencyObject($excluded = null, $stopAtExcludedModifier = false)
    {
        return DBField::create_field('Currency', $this->ModifiersSubTotal($excluded, $stopAtExcludedModifier));
    }

    /**
     * @param string|array $excluded               - Class(es) of modifier(s) to ignore in the calculation.
     * @param bool         $stopAtExcludedModifier - when this flag is TRUE, we stop adding the modifiers when we reach an excluded modifier.
     *
     * @return Money (DB Object)
     **/
    public function ModifiersSubTotalAsMoneyObject($excluded = null, $stopAtExcludedModifier = false)
    {
        return EcommerceCurrency::get_money_object_from_order_currency($this->ModifiersSubTotal($excluded, $stopAtExcludedModifier), $this);
    }

    /**
     * Returns the total cost of an order including the additional charges or deductions of its modifiers.
     *
     * @return float
     */
    public function Total()
    {
        return $this->getTotal();
    }
    public function getTotal()
    {
        return $this->SubTotal() + $this->ModifiersSubTotal();
    }

    /**
     * @return Currency (DB Object)
     **/
    public function TotalAsCurrencyObject()
    {
        return DBField::create_field('Currency', $this->Total());
    }

    /**
     * @return Money
     **/
    public function TotalAsMoney()
    {
        return $this->getTotalAsMoney();
    }
    public function getTotalAsMoney()
    {
        return EcommerceCurrency::get_money_object_from_order_currency($this->Total(), $this);
    }

    /**
     * Checks to see if any payments have been made on this order
     * and if so, subracts the payment amount from the order.
     *
     * @return float
     **/
    public function TotalOutstanding()
    {
        return $this->getTotalOutstanding();
    }
    public function getTotalOutstanding()
    {
        if ($this->IsSubmitted()) {
            $total = $this->Total();
            $paid = $this->TotalPaid();
            $outstanding = $total - $paid;
            $maxDifference = EcommerceConfig::get('Order', 'maximum_ignorable_sales_payments_difference');
            if (abs($outstanding) < $maxDifference) {
                $outstanding = 0;
            }

            return floatval($outstanding);
        } else {
            return 0;
        }
    }

    /**
     * @return Currency (DB Object)
     **/
    public function TotalOutstandingAsCurrencyObject()
    {
        return DBField::create_field('Currency', $this->TotalOutstanding());
    }

    /**
     * @return Money
     **/
    public function TotalOutstandingAsMoney()
    {
        return $this->getTotalOutstandingAsMoney();
    }
    public function getTotalOutstandingAsMoney()
    {
        return EcommerceCurrency::get_money_object_from_order_currency($this->TotalOutstanding(), $this);
    }

    /**
     * @return float
     */
    public function TotalPaid()
    {
        return $this->getTotalPaid();
    }
    public function getTotalPaid()
    {
        $paid = 0;
        if ($payments = $this->Payments()) {
            foreach ($payments as $payment) {
                if ($payment->Status == 'Success') {
                    $paid += $payment->Amount->getAmount();
                }
            }
        }
        $reverseExchange = 1;
        if ($this->ExchangeRate && $this->ExchangeRate != 1) {
            $reverseExchange = 1 / $this->ExchangeRate;
        }

        return $paid * $reverseExchange;
    }

    /**
     * @return Currency (DB Object)
     **/
    public function TotalPaidAsCurrencyObject()
    {
        return DBField::create_field('Currency', $this->TotalPaid());
    }

    /**
     * @return Money
     **/
    public function TotalPaidAsMoney()
    {
        return $this->getTotalPaidAsMoney();
    }
    public function getTotalPaidAsMoney()
    {
        return EcommerceCurrency::get_money_object_from_order_currency($this->TotalPaid(), $this);
    }

    /**
     * returns the total number of OrderItems (not modifiers).
     * This is meant to run as fast as possible to quickly check
     * if there is anything in the cart.
     *
     * @param bool $recalculate - do we need to recalculate (value is retained during lifetime of Object)
     *
     * @return int
     **/
    public function TotalItems($recalculate = false)
    {
        return $this->getTotalItems($recalculate);
    }
    public function getTotalItems($recalculate = false)
    {
        if ($this->totalItems === null || $recalculate) {
            $this->totalItems = OrderItem::get()
                ->where('"OrderAttribute"."OrderID" = '.$this->ID.' AND "OrderItem"."Quantity" > 0')
                ->count();
        }

        return $this->totalItems;
    }

    /**
     * Little shorthand.
     *
     * @param bool $recalculate
     *
     * @return bool
     **/
    public function MoreThanOneItemInCart($recalculate = false)
    {
        return $this->TotalItems($recalculate) > 1 ? true : false;
    }

    /**
     * returns the total number of OrderItems (not modifiers) times their respectective quantities.
     *
     * @param bool $recalculate - force recalculation
     *
     * @return float
     **/
    public function TotalItemsTimesQuantity($recalculate = false)
    {
        return $this->getTotalItemsTimesQuantity($recalculate);
    }
    public function getTotalItemsTimesQuantity($recalculate = false)
    {
        if ($this->totalItemsTimesQuantity === null || $recalculate) {
            //to do, why do we check if you can edit ????
            $this->totalItemsTimesQuantity = DB::query('
                SELECT SUM("OrderItem"."Quantity")
                FROM "OrderItem"
                    INNER JOIN "OrderAttribute" ON "OrderAttribute"."ID" = "OrderItem"."ID"
                WHERE
                    "OrderAttribute"."OrderID" = '.$this->ID.'
                    AND "OrderItem"."Quantity" > 0'
            )->value();
        }

        return $this->totalItemsTimesQuantity - 0;
    }

    /**
     *
     * @return string (country code)
     **/
    public function Country()
    {
        return $this->getCountry();
    }

    /**
    * Returns the country code for the country that applies to the order.
    * @alias  for getCountry
    *
    * @return string - country code e.g. NZ
     */
    public function getCountry()
    {
        $countryCodes = array(
            'Billing' => '',
            'Shipping' => '',
        );
        $code = null;
        if ($this->BillingAddressID) {
            $billingAddress = BillingAddress::get()->byID($this->BillingAddressID);
            if ($billingAddress) {
                if ($billingAddress->Country) {
                    $countryCodes['Billing'] = $billingAddress->Country;
                }
            }
        }
        if ($this->ShippingAddressID && $this->UseShippingAddress) {
            $shippingAddress = ShippingAddress::get()->byID($this->ShippingAddressID);
            if ($shippingAddress) {
                if ($shippingAddress->ShippingCountry) {
                    $countryCodes['Shipping'] = $shippingAddress->ShippingCountry;
                }
            }
        }
        if (
            (EcommerceConfig::get('OrderAddress', 'use_shipping_address_for_main_region_and_country') && $countryCodes['Shipping'])
            ||
            (!$countryCodes['Billing'] && $countryCodes['Shipping'])
        ) {
            $code = $countryCodes['Shipping'];
        } elseif ($countryCodes['Billing']) {
            $code = $countryCodes['Billing'];
        } else {
            $code = EcommerceCountry::get_country_from_ip();
        }
        return $code;
    }

    /**
     * @alias for getFullNameCountry
     *
     * @return string - country name
     **/
    public function FullNameCountry()
    {
        return $this->getFullNameCountry();
    }

    /**
     * returns name of coutry.
     *
     * @return string - country name
     **/
    public function getFullNameCountry()
    {
        return EcommerceCountry::find_title($this->Country());
    }

    /**
     * @alis for getExpectedCountryName
     * @return string - country name
     **/
    public function ExpectedCountryName()
    {
        return $this->getExpectedCountryName();
    }

    /**
     * returns name of coutry that we expect the customer to have
     * This takes into consideration more than just what has been entered
     * for example, it looks at GEO IP.
     *
     * @todo: why do we dont return a string IF there is only one item.
     *
     * @return string - country name
     **/
    public function getExpectedCountryName()
    {
        return EcommerceCountry::find_title(EcommerceCountry::get_country(false, $this->ID));
    }

    /**
     * return the title of the fixed country (if any).
     *
     * @return string | empty string
     **/
    public function FixedCountry()
    {
        return $this->getFixedCountry();
    }
    public function getFixedCountry()
    {
        $code = EcommerceCountry::get_fixed_country_code();
        if ($code) {
            return EcommerceCountry::find_title($code);
        }

        return '';
    }

    /**
     * Returns the region that applies to the order.
     * we check both billing and shipping, in case one of them is empty.
     *
     * @return DataObject | Null (EcommerceRegion)
     **/
    public function Region()
    {
        return $this->getRegion();
    }
    public function getRegion()
    {
        $regionIDs = array(
            'Billing' => 0,
            'Shipping' => 0,
        );
        if ($this->BillingAddressID) {
            if ($billingAddress = $this->BillingAddress()) {
                if ($billingAddress->RegionID) {
                    $regionIDs['Billing'] = $billingAddress->RegionID;
                }
            }
        }
        if ($this->CanHaveShippingAddress()) {
            if ($this->ShippingAddressID) {
                if ($shippingAddress = $this->ShippingAddress()) {
                    if ($shippingAddress->ShippingRegionID) {
                        $regionIDs['Shipping'] = $shippingAddress->ShippingRegionID;
                    }
                }
            }
        }
        if (count($regionIDs)) {
            //note the double-check with $this->CanHaveShippingAddress() and get_use_....
            if ($this->CanHaveShippingAddress() && EcommerceConfig::get('OrderAddress', 'use_shipping_address_for_main_region_and_country') && $regionIDs['Shipping']) {
                return EcommerceRegion::get()->byID($regionIDs['Shipping']);
            } else {
                return EcommerceRegion::get()->byID($regionIDs['Billing']);
            }
        } else {
            return EcommerceRegion::get()->byID(EcommerceRegion::get_region_from_ip());
        }
    }

    /**
     * Casted variable
     * Currency is not the same as the standard one?
     *
     * @return bool
     **/
    public function HasAlternativeCurrency()
    {
        return $this->getHasAlternativeCurrency();
    }
    public function getHasAlternativeCurrency()
    {
        if ($currency = $this->CurrencyUsed()) {
            if ($currency->IsDefault()) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Makes sure exchange rate is updated and maintained before order is submitted
     * This method is public because it could be called from a shopping Cart Object.
     **/
    public function EnsureCorrectExchangeRate()
    {
        if (!$this->IsSubmitted()) {
            $oldExchangeRate = $this->ExchangeRate;
            if ($currency = $this->CurrencyUsed()) {
                if ($currency->IsDefault()) {
                    $this->ExchangeRate = 0;
                } else {
                    $this->ExchangeRate = $currency->getExchangeRate();
                }
            } else {
                $this->ExchangeRate = 0;
            }
            if ($this->ExchangeRate != $oldExchangeRate) {
                $this->write();
            }
        }
    }

    /**
     * speeds up processing by storing the IsSubmitted value
     * we start with -1 to know if it has been requested before.
     *
     * @var bool
     */
    protected $_isSubmittedTempVar = -1;

    /**
     * Casted variable - has the order been submitted?
     * alias
     * @param bool $recalculate
     *
     * @return bool
     **/
    public function IsSubmitted($recalculate = false)
    {
        return $this->getIsSubmitted($recalculate);
    }

    /**
     * Casted variable - has the order been submitted?
     *
     * @param bool $recalculate
     *
     * @return bool
     **/
    public function getIsSubmitted($recalculate = false)
    {
        if ($this->_isSubmittedTempVar === -1 || $recalculate) {
            if ($this->SubmissionLog()) {
                $this->_isSubmittedTempVar = true;
            } else {
                $this->_isSubmittedTempVar = false;
            }
        }

        return $this->_isSubmittedTempVar;
    }

    /**
     *
     *
     * @return bool
     */
    public function IsArchived()
    {
        $lastStep = OrderStep::get()->Last();
        if ($lastStep) {
            if ($lastStep->ID == $this->StatusID) {
                return true;
            }
        }
        return false;
    }

    /**
     * Submission Log for this Order (if any).
     *
     * @return Submission Log (OrderStatusLog_Submitted) | Null
     **/
    public function SubmissionLog()
    {
        $className = EcommerceConfig::get('OrderStatusLog', 'order_status_log_class_used_for_submitting_order');

        return $className::get()
            ->Filter(array('OrderID' => $this->ID))
            ->First();
    }

    /**
     * if the order can not be submitted,
     * then the reasons why it can not be submitted
     * will be returned by this method.
     *
     * @see Order::canSubmit
     *
     * @return ArrayList | null
     */
    public function SubmitErrors()
    {
        $al = null;
        $extendedSubmitErrors = $this->extend('updateSubmitErrors');
        if ($extendedSubmitErrors !== null && is_array($extendedSubmitErrors) && count($extendedSubmitErrors)) {
            $al = ArrayList::create();
            foreach ($extendedSubmitErrors as $returnResultArray) {
                foreach ($returnResultArray as $issue) {
                    if ($issue) {
                        $al->push(ArrayData::create(array("Title" => $issue)));
                    }
                }
            }
        }
        return $al;
    }

    /**
     * Casted variable - has the order been submitted?
     *
     * @param bool $withDetail
     *
     * @return string
     **/
    public function CustomerStatus($withDetail = true)
    {
        return $this->getCustomerStatus($withDetail);
    }
    public function getCustomerStatus($withDetail = true)
    {
        $str = '';
        if ($this->MyStep()->ShowAsUncompletedOrder) {
            $str = _t('Order.UNCOMPLETED', 'Uncompleted');
        } elseif ($this->MyStep()->ShowAsInProcessOrder) {
            $str = _t('Order.IN_PROCESS', 'In Process');
        } elseif ($this->MyStep()->ShowAsCompletedOrder) {
            $str = _t('Order.COMPLETED', 'Completed');
        }
        if ($withDetail) {
            if (!$this->HideStepFromCustomer) {
                $str .= ' ('.$this->MyStep()->Name.')';
            }
        }

        return $str;
    }

    /**
     * Casted variable - does the order have a potential shipping address?
     *
     * @return bool
     **/
    public function CanHaveShippingAddress()
    {
        return $this->getCanHaveShippingAddress();
    }
    public function getCanHaveShippingAddress()
    {
        return EcommerceConfig::get('OrderAddress', 'use_separate_shipping_address');
    }

    /**
     * returns the link to view the Order
     * WHY NOT CHECKOUT PAGE: first we check for cart page.
     *
     * @return CartPage | Null
     */
    public function DisplayPage()
    {
        if ($this->MyStep() && $this->MyStep()->AlternativeDisplayPage()) {
            $page = $this->MyStep()->AlternativeDisplayPage();
        } elseif ($this->IsSubmitted()) {
            $page = OrderConfirmationPage::get()->First();
        } else {
            $page = CartPage::get()
                ->Filter(array('ClassName' => 'CartPage'))
                ->First();
            if (!$page) {
                $page = CheckoutPage::get()->First();
            }
        }

        return $page;
    }

    /**
     * returns the link to view the Order
     * WHY NOT CHECKOUT PAGE: first we check for cart page.
     * If a cart page has been created then we refer through to Cart Page.
     * Otherwise it will default to the checkout page.
     *
     * @param string $action - any action that should be added to the link.
     *
     * @return String(URLSegment)
     */
    public function Link($action = null)
    {
        $page = $this->DisplayPage();
        if ($page) {
            return $page->getOrderLink($this->ID, $action);
        } else {
            user_error('A Cart / Checkout Page + an Order Confirmation Page needs to be setup for the e-commerce module to work.', E_USER_NOTICE);
            $page = ErrorPage::get()
                ->Filter(array('ErrorCode' => '404'))
                ->First();
            if ($page) {
                return $page->Link();
            }
        }
    }

    /**
     * Returns to link to access the Order's API.
     *
     * @param string $version
     * @param string $extension
     *
     * @return String(URL)
     */
    public function APILink($version = 'v1', $extension = 'xml')
    {
        return Director::AbsoluteURL("/api/ecommerce/$version/Order/".$this->ID."/.$extension");
    }

    /**
     * returns the link to finalise the Order.
     *
     * @return String(URLSegment)
     */
    public function CheckoutLink()
    {
        $page = CheckoutPage::get()->First();
        if ($page) {
            return $page->Link();
        } else {
            $page = ErrorPage::get()
                ->Filter(array('ErrorCode' => '404'))
                ->First();
            if ($page) {
                return $page->Link();
            }
        }
    }

    /**
     * Converts the Order into HTML, based on the Order Template.
     *
     * @return HTML Object
     **/
    public function ConvertToHTML()
    {
        Config::nest();
        Config::inst()->update('SSViewer', 'theme_enabled', true);
        $html = $this->renderWith('Order');
        Config::unnest();
        $html = preg_replace('/(\s)+/', ' ', $html);

        return DBField::create_field('HTMLText', $html);
    }

    /**
     * Converts the Order into a serialized string
     * TO DO: check if this works and check if we need to use special sapphire serialization code.
     *
     * @return string - serialized object
     **/
    public function ConvertToString()
    {
        return serialize($this->addHasOneAndHasManyAsVariables());
    }

    /**
     * Converts the Order into a JSON object
     * TO DO: check if this works and check if we need to use special sapphire JSON code.
     *
     * @return string -  JSON
     **/
    public function ConvertToJSON()
    {
        return json_encode($this->addHasOneAndHasManyAsVariables());
    }

    /**
     * returns itself wtih more data added as variables.
     * We add has_one and has_many as variables like this: $this->MyHasOne_serialized = serialize($this->MyHasOne()).
     *
     * @return Order - with most important has one and has many items included as variables.
     **/
    protected function addHasOneAndHasManyAsVariables()
    {
        $object = clone $this;
        $object->Member_serialized = serialize($this->Member());
        $object->BillingAddress_serialized = serialize($this->BillingAddress());
        $object->ShippingAddress_serialized = serialize($this->ShippingAddress());
        $object->Attributes_serialized = serialize($this->Attributes());
        $object->OrderStatusLogs_serialized = serialize($this->OrderStatusLogs());
        $object->Payments_serialized = serialize($this->Payments());
        $object->Emails_serialized = serialize($this->Emails());

        return $object;
    }

/*******************************************************
   * 9. TEMPLATE RELATED STUFF
*******************************************************/

    /**
     * returns the instance of EcommerceConfigAjax for use in templates.
     * In templates, it is used like this:
     * $EcommerceConfigAjax.TableID.
     *
     * @return EcommerceConfigAjax
     **/
    public function AJAXDefinitions()
    {
        return EcommerceConfigAjax::get_one($this);
    }

    /**
     * returns the instance of EcommerceDBConfig.
     *
     * @return EcommerceDBConfig
     **/
    public function EcomConfig()
    {
        return EcommerceDBConfig::current_ecommerce_db_config();
    }

    /**
     * Collects the JSON data for an ajax return of the cart.
     *
     * @param array $js
     *
     * @return array (for use in AJAX for JSON)
     **/
    public function updateForAjax(array $js)
    {
        $function = EcommerceConfig::get('Order', 'ajax_subtotal_format');
        if (is_array($function)) {
            list($function, $format) = $function;
        }
        $subTotal = $this->$function();
        if (isset($format)) {
            $subTotal = $subTotal->$format();
            unset($format);
        }
        $function = EcommerceConfig::get('Order', 'ajax_total_format');
        if (is_array($function)) {
            list($function, $format) = $function;
        }
        $total = $this->$function();
        if (isset($format)) {
            $total = $total->$format();
        }
        $ajaxObject = $this->AJAXDefinitions();
        $js[] = array(
            't' => 'id',
            's' => $ajaxObject->TableSubTotalID(),
            'p' => 'innerHTML',
            'v' => $subTotal,
        );
        $js[] = array(
            't' => 'id',
            's' => $ajaxObject->TableTotalID(),
            'p' => 'innerHTML',
            'v' => $total,
        );
        $js[] = array(
            't' => 'class',
            's' => $ajaxObject->TotalItemsClassName(),
            'p' => 'innerHTML',
            'v' => $this->TotalItems($recalculate = true),
        );
        $js[] = array(
            't' => 'class',
            's' => $ajaxObject->TotalItemsTimesQuantityClassName(),
            'p' => 'innerHTML',
            'v' => $this->TotalItemsTimesQuantity(),
        );
        $js[] = array(
            't' => 'class',
            's' => $ajaxObject->ExpectedCountryClassName(),
            'p' => 'innerHTML',
            'v' => $this->ExpectedCountryName(),
        );

        return $js;
    }

    /**
     * @ToDO: move to more appropriate class
     *
     * @return float
     **/
    public function SubTotalCartValue()
    {
        return $this->SubTotal;
    }

/*******************************************************
   * 10. STANDARD SS METHODS (requireDefaultRecords, onBeforeDelete, etc...)
*******************************************************/

    /**
     *standard SS method.
     **/
    public function populateDefaults()
    {
        parent::populateDefaults();
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (! $this->getCanHaveShippingAddress()) {
            $this->UseShippingAddress = false;
        }
        if (!$this->CurrencyUsedID) {
            $this->CurrencyUsedID = EcommerceCurrency::default_currency_id();
        }
        if (!$this->SessionID) {
            $generator = Injector::inst()->create('RandomGenerator');
            $token = $generator->randomToken('sha1');
            $this->SessionID = substr($token, 0, 32);
        }
    }

    /**
     * standard SS method
     * adds the ability to update order after writing it.
     **/
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        //crucial!
        self::set_needs_recalculating(true, $this->ID);
        // quick double-check
        if ($this->IsCancelled() && ! $this->IsArchived()) {
            $this->Archive($avoidWrites = true);
        }
        if ($this->IsSubmitted($recalculate = true)) {
            //do nothing
        } else {
            if ($this->StatusID) {
                $this->calculateOrderAttributes($recalculate = false);
                if (EcommerceRole::current_member_is_shop_admin()) {
                    if (isset($_REQUEST['SubmitOrderViaCMS'])) {
                        $this->tryToFinaliseOrder();
                        //just in case it writes again...
                        unset($_REQUEST['SubmitOrderViaCMS']);
                    }
                }
            }
        }
    }

    /**
     *standard SS method.
     *
     * delete attributes, statuslogs, and payments
     * THIS SHOULD NOT BE USED AS ORDERS SHOULD BE CANCELLED NOT DELETED
     */
    public function onBeforeDelete()
    {
        parent::onBeforeDelete();
        if ($attributes = $this->Attributes()) {
            foreach ($attributes as $attribute) {
                $attribute->delete();
                $attribute->destroy();
            }
        }

        //THE REST WAS GIVING ERRORS - POSSIBLY DUE TO THE FUNNY RELATIONSHIP (one-one, two times...)
        /*
        if($billingAddress = $this->BillingAddress()) {
            if($billingAddress->exists()) {
                $billingAddress->delete();
                $billingAddress->destroy();
            }
        }
        if($shippingAddress = $this->ShippingAddress()) {
            if($shippingAddress->exists()) {
                $shippingAddress->delete();
                $shippingAddress->destroy();
            }
        }

        if($statuslogs = $this->OrderStatusLogs()){
            foreach($statuslogs as $log){
                $log->delete();
                $log->destroy();
            }
        }
        if($payments = $this->Payments()){
            foreach($payments as $payment){
                $payment->delete();
                $payment->destroy();
            }
        }
        if($emails = $this->Emails()) {
            foreach($emails as $email){
                $email->delete();
                $email->destroy();
            }
        }
        */
    }

/*******************************************************
   * 11. DEBUG
*******************************************************/

    /**
     * Debug helper method.
     * Can be called from /shoppingcart/debug/.
     *
     * @return string
     */
    public function debug()
    {
        $this->calculateOrderAttributes(true);

        return EcommerceTaskDebugCart::debug_object($this);
    }
}
