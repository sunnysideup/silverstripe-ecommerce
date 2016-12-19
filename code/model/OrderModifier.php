<?php

/**
 * which returns an array of IDs
 * SEQUENCE - USE FOR ALL MODIFIERS!!!
 * *** 1. model defining static variables (e.g. $db, $has_one)
 * *** 2. cms variables + functions (e.g. getCMSFields, $searchableFields)
 * *** 3. other (non) static variables (e.g. private static $special_name_for_something, protected $order)
 * *** 4. CRUD functions (e.g. canEdit)
 * *** 5. init and update functions
 * *** 6. form functions (e. g. Showform and getform)
 * *** 7. template functions (e.g. ShowInTable, TableTitle, etc...) ... USES DB VALUES
 * *** 8. inner calculations.... USES CALCULATED VALUES
 * *** 9. calculate database fields: protected function Live[field name]  ... USES CALCULATED VALUES
 * *** 10. standard database related functions (e.g. onBeforeWrite, onAfterWrite, etc...)
 * *** 11. AJAX related functions
 * *** 12. debug functions.
 *
 * FAQs
 *
 * *** What is the difference between cart and table ***
 * The Cart is a smaller version of the Table. Table is used for Checkout Page + Confirmation page.
 * Cart is used for other pages (pre-checkout for example). At times, the values and names may differ
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderModifier extends OrderAttribute
{
    /**
     * what variables are accessible through  http://mysite.com/api/ecommerce/v1/OrderModifier/.
     *
     * @var array
     */
    private static $api_access = array(
        'view' => array(
            'CalculatedTotal',
            'Sort',
            'GroupSort',
            'TableTitle',
            'TableSubTitle',
            'CartTitle',
            'CartSubTitle',
            'Name',
            'TableValue',
            'HasBeenRemoved',
            'Order',
        ),
    );

// ########################################  *** 1. model defining static variables (e.g. $db, $has_one)

    /**
     * @var array
     *            stardard SS definition
     */
    private static $db = array(
        'Name' => 'HTMLText', // we use this to create the TableTitle, CartTitle and TableSubTitle
        'TableValue' => 'Currency', //the $$ shown in the checkout table
        'HasBeenRemoved' => 'Boolean', // we add this so that we can see what modifiers have been removed
    );

    /**
     * make sure to choose the right Type and Name for this.
     * stardard SS variable.
     *
     * @var array
     */
    private static $defaults = array(
        'Name' => 'Modifier', //making sure that you choose a different name for any class extensions.
    );

// ########################################  *** 2. cms variables  + functions (e.g. getCMSFields, $searchableFields)

    /**
     * stardard SS variable.
     *
     * @var array
     */
    private static $searchable_fields = array(
        'OrderID' => array(
            'field' => 'NumericField',
            'title' => 'Order Number',
        ),
        //"TableTitle" => "PartialMatchFilter",
        'TableValue',
        'HasBeenRemoved',
    );

    /**
     * stardard SS definition.
     *
     * @var array
     */
    private static $summary_fields = array(
        'OrderID' => 'Order ID',
        'TableTitle' => 'Table Title',
        'TableSubTitle' => 'More ...',
        'TableValue' => 'Value Shown',
        'CalculatedTotal' => 'Calculation Total',
    );

    /**
     * stardard SS definition.
     *
     * @var array
     */
    private static $casting = array(
        'TableValueAsMoney' => 'Money',
    );

    /**
     * stardard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Order Modifier';
    public function i18n_singular_name()
    {
        return _t('OrderModifier.SINGULARNAME', 'Order Modifier');
    }

    /**
     * stardard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Order Modifiers';
    public function i18n_plural_name()
    {
        return _t('OrderModifier.PLURALNAME', 'Order Modifiers');
    }

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'An addition to the order that sits between the sub-total and the total (e.g. tax, delivery, etc...).';

    /**
     * stardard SS metbod.
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('Sort');
        $fields->removeByName('GroupSort');
        $fields->replaceField('Name', $nameField = new ReadonlyField('Name'));
        $nameField->dontEscape = true;
        $fields->removeByName('TableValue');
        $fields->removeByName('CalculatedTotal');
        $fields->removeByName('HasBeenRemoved');
        $fields->addFieldToTab(
            'Root',
            Tab::create(
                'Debug',
                _t('OrderModifier.DEBUG', 'Debug'),
                new ReadonlyField('CreatedShown', 'Created', $this->Created),
                new ReadonlyField('LastEditedShown', 'Last Edited', $this->LastEdited),
                new ReadonlyField('TableValueShown', 'Table Value', $this->TableValue),
                new ReadonlyField('CalculatedTotal', 'Raw Value', $this->CalculatedTotal)
            )
        );

        $fields->addFieldToTab('Root.Main', new CheckboxField('HasBeenRemoved', 'Has been removed'));
        $fields->removeByName('OrderAttribute_GroupID');

        //OrderID Field
        if ($this->OrderID && $this->exists()) {
            $fields->replaceField('OrderID', $fields->dataFieldByName('OrderID')->performReadonlyTransformation());
        } else {
            $fields->replaceField('OrderID', new NumericField('OrderID'));
        }

        //ClassName Field
        $availableModifiers = EcommerceConfig::get('Order', 'modifiers');

        if ($this->exists()) {
            $fields->addFieldToTab('Root.Main', new LiteralField('MyClassName', '<h2>'.$this->singular_name().'</h2>'), 'Name');
        } else {
            $ecommerceClassNameOrTypeDropdownField = EcommerceClassNameOrTypeDropdownField::create('ClassName', 'Type', 'OrderModifier', $availableModifiers);
            $fields->addFieldToTab('Root.Main', $ecommerceClassNameOrTypeDropdownField, 'Name');
        }

        return $fields;
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

        return $fields;
    }

// ########################################  *** 3. other static variables (e.g. special_name_for_something)

    /**
     * $doNotAddAutomatically Identifies whether a modifier is NOT automatically added
     * Most modifiers, such as delivery and GST would be added automatically.
     * However, there are also ones that are not added automatically.
     *
     * @var bool
     **/
    protected $doNotAddAutomatically = false;

    /**
     * $can_be_removed Identifies whether a modifier can be removed by the user.
     *
     * @var bool
     **/
    protected $canBeRemoved = false;

    /**
     * This is a flag for running an update.
     * Running an update means that all fields are (re)set, using the Live{FieldName} methods.
     *
     * @var bool
     **/
    protected $mustUpdate = false;

    /**
     * When recalculating all the modifiers, this private variable is added to as a running total
     * other modifiers can then tap into this to work out their own values.
     * For example, a tax modifier needs to know the value of the other modifiers before calculating
     * its own value (i.e. tax is also paid over handling and shipping).
     * Always consider the "order" (which one first) of the order modifiers when using this variable.
     *
     * @var float
     **/
    private $runningTotal = 0;

// ######################################## *** 4. CRUD functions (e.g. canEdit)

// ########################################  *** 5. init and update functions

    /**
     *
     */
    public static function init_for_order($className)
    {
        user_error('the init_for_order method has been depreciated, instead, use $myModifier->init()', E_USER_ERROR);

        return false;
    }

    /**
     * This method runs when the OrderModifier is first added to the order.
     **/
    public function init()
    {
        parent::init();
        $this->write();
        $this->mustUpdate = true;
        $this->runUpdate($force = false);

        return true;
    }

    /*
     * all classes extending OrderModifier must have this method if it has more fields
     * @param boolean $recalculate - run it, even if it has run already
     **/
    public function runUpdate($recalculate = false)
    {
        if (!$this->IsRemoved()) {
            $this->checkField('Name');
            $this->checkField('CalculatedTotal');
            $this->checkField('TableValue');
            if ($this->mustUpdate && $this->canBeUpdated()) {
                $this->write();
            }
            $this->runningTotal += $this->CalculatedTotal;
        }
        parent::runUpdate($recalculate);
    }

    /**
     * You can overload this method as canEdit might not be the right indicator.
     *
     * @return bool
     **/
    protected function canBeUpdated()
    {
        return $this->canEdit();
    }

    /**
     * standard SS Method.
     *
     * @return bool
     **/
    public function canCreate($member = null)
    {
        return false;
    }

    /**
     * standard SS Method.
     *
     * @return bool
     **/
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * This method simply checks if a fields has changed and if it has changed it updates the field.
     *
     * @param string $fieldName
     **/
    protected function checkField($fieldName)
    {
        if ($this->canBeUpdated()) {
            $functionName = 'Live'.$fieldName;
            $oldValue = $this->$fieldName;
            $newValue = $this->$functionName();
            if ($oldValue != $newValue) {
                $this->$fieldName = $newValue;
                $this->mustUpdate = true;
            }
        }
    }

    /**
     * Provides a modifier total that is positive or negative, depending on whether the modifier is chargable or not.
     * This number is used to work out the order Grand Total.....
     * It is important to note that this can be positive or negative, while the amount is always positive.
     *
     * @return float / double
     */
    public function CalculationTotal()
    {
        if ($this->HasBeenRemoved) {
            return 0;
        }

        return $this->CalculatedTotal;
    }

// ########################################  *** 6. form functions (Showform and getform)

    /**
     * This determines whether the OrderModifierForm is shown or not. {@link OrderModifier::get_form()}.
     * OrderModifierForms are forms that are added to check out to facilitate the use of the modifier.
     * An example would be a form allowing the user to select the delivery option.
     *
     * @return bool
     */
    public function ShowForm()
    {
        return false;
    }

    /**
     * Should the form be included in the EDITABLE form
     * on the checkout page?
     *
     * @return bool
     */
    public function ShowFormInEditableOrderTable()
    {
        //extend in OrderModifier Extensions
        return false;
    }

    /**
     * Should the form be shown outside of editable table
     * on the checkout page (opposite of ShowFormInEditableOrderTable)?
     *
     * @return bool
     */
    public function ShowFormOutsideEditableOrderTable()
    {
        //extend in OrderModifier Extensions
        return $this->ShowFormInEditableOrderTable() ? false : true;
    }

    /**
     * This function returns a form that allows a user
     * to change the modifier to the order.
     *
     * We have mainly added this function as an example!
     *
     * @param Controller $optionalController - optional custom controller class
     * @param Validator  $optionalValidator  - optional custom validator class
     *
     * @return OrderModifierForm or subclass
     */
    public function getModifierForm(Controller $optionalController = null, Validator $optionalValidator = null)
    {
        if ($this->ShowForm()) {
            $fields = new FieldList();
            $fields->push($this->headingField());
            $fields->push($this->descriptionField());

            return OrderModifierForm::create($optionalController, 'ModifierForm', $fields, $actions = new FieldList(), $optionalValidator);
        }
    }

    /**
     * @return object (HeadingField)
     */
    protected function headingField()
    {
        $name = $this->ClassName.'Heading';
        if ($this->Heading()) {
            return new HeaderField($name, $this->Heading(), 4);
        }

        return new LiteralField($name, '<!-- EmptyHeading -->', '<!-- EmptyHeading -->');
    }

    /**
     * @return object (LiteralField)
     */
    protected function descriptionField()
    {
        $name = $this->ClassName.'Description';
        if ($this->Description()) {
            return new LiteralField($name, '<div id="'.Convert::raw2att($name).'DescriptionHolder" class="descriptionHolder">'.Convert::raw2xml($this->Description()).'</div>');
        }

        return new LiteralField($name, '<!-- EmptyDescription -->', '<!-- EmptyDescription -->');
    }

// ######################################## *** 7. template functions (e.g. ShowInTable, TableTitle, etc...)

    /**
     * Casted variable, returns the table title.
     *
     * @return string
     */
    public function TableTitle()
    {
        return $this->getTableTitle();
    }
    public function getTableTitle()
    {
        return $this->Name;
    }

    /**
     * caching of relevant OrderModifier_Descriptor.
     *
     * @var OrderModifier_Descriptor
     */
    private $orderModifier_Descriptor = null;

    /**
     * returns the relevant orderModifier_Descriptor.
     *
     * @return OrderModifier_Descriptor | Null
     */
    protected function getOrderModifier_Descriptor()
    {
        if ($this->orderModifier_Descriptor === null) {
            $this->orderModifier_Descriptor = OrderModifier_Descriptor::get()
                ->Filter(array('ModifierClassName' => $this->ClassName))
                ->First();
        }

        return $this->orderModifier_Descriptor;
    }

    /**
     * returns a heading if there is one.
     *
     * @return string
     **/
    public function Heading()
    {
        if ($obj = $this->getOrderModifier_Descriptor()) {
            return $obj->Heading;
        }

        return '';
    }

    /**
     * returns a description if there is one.
     *
     * @return string (html)
     **/
    public function Description()
    {
        if ($obj = $this->getOrderModifier_Descriptor()) {
            return $obj->Description;
        }

        return '';
    }

    /**
     * returns a page for a more info link... (if there is one).
     *
     * @return object (SiteTree)
     **/
    public function MoreInfoPage()
    {
        if ($obj = $this->getOrderModifier_Descriptor()) {
            return $obj->Link();
        }

        return;
    }

    /**
     * tells you whether the modifier shows up on the checkout  / cart form.
     * this is also the place where we check if the modifier has been updated.
     *
     * @return bool
     */
    public function ShowInTable()
    {
        if (!$this->baseRunUpdateCalled) {
            if ($this->canBeUpdated()) {
                user_error('While the order can be edited, you must call the runUpdate method everytime you get the details for this modifier', E_USER_ERROR);
            }
        }

        return false;
    }

    /**
     * Returns the Money object of the Table Value.
     *
     * @return Money
     **/
    public function TableValueAsMoney()
    {
        return $this->getTableValueAsMoney();
    }
    public function getTableValueAsMoney()
    {
        return EcommerceCurrency::get_money_object_from_order_currency($this->TableValue, $this->Order());
    }

    /**
     * some modifiers can be hidden after an ajax update (e.g. if someone enters a discount coupon and it does not exist).
     * There might be instances where ShowInTable (the starting point) is TRUE and HideInAjaxUpdate return false.
     *
     *@return bool
     **/
    public function HideInAjaxUpdate()
    {
        if ($this->IsRemoved()) {
            return true;
        }
        if ($this->ShowInTable()) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the modifier can be removed.
     *
     * @return bool
     **/
    public function CanBeRemoved()
    {
        return $this->canBeRemoved;
    }

    /**
     * Checks if the modifier can be added manually.
     *
     * @return bool
     **/
    public function CanAdd()
    {
        return $this->HasBeenRemoved || $this->DoNotAddOnInit();
    }

    /**
     *Identifier whether a modifier will be added automatically for all new orders.
     *
     * @return bool
     */
    public function DoNotAddAutomatically()
    {
        return $this->doNotAddAutomatically;
    }

    /**
     * Actual calculation used.
     *
     * @return float / Double
     **/
    public function CalculatedTotal()
    {
        return $this->CalculatedTotal;
    }

    /**
     * This link is for modifiers that have been removed and are being put "back".
     *
     * @return string
     **/
    public function AddLink()
    {
        $params = array();
        $updatedLinkParameters = $this->extend('ModifierAddLinkUpdate', $params);
        if ($updatedLinkParameters !== null && is_array($updatedLinkParameters) && count($updatedLinkParameters)) {
            foreach ($updatedLinkParameters as $updatedLinkParametersUpdate) {
                $params = array_merge($params, $updatedLinkParametersUpdate);
            }
        }

        return ShoppingCart_Controller::add_modifier_link($this->ID, $params, $this->ClassName);
    }

    /**
     * Link that can be used to remove the modifier.
     *
     * @return string
     **/
    public function RemoveLink()
    {
        $param = array();
        $updatedLinkParameters = $this->extend('ModifierRemoveLinkUpdate', $param);
        if ($updatedLinkParameters !== null && is_array($updatedLinkParameters) && count($updatedLinkParameters)) {
            foreach ($updatedLinkParameters as $updatedLinkParametersUpdate) {
                $param = array_merge($param, $updatedLinkParametersUpdate);
            }
        }

        return ShoppingCart_Controller::remove_modifier_link($this->ID, $param, $this->ClassName);
    }

    /**
     * retursn and array like this: array(Title => "bla", Link => "/doit/now/");.
     *
     * @return array
     */
    public function PostSubmitAction()
    {
        return array();
    }

// ######################################## ***  8. inner calculations....

    /**
     * returns the running total variable.
     *
     * @see variable definition for more information
     *
     * @return float
     */
    public function getRunningTotal()
    {
        return $this->runningTotal;
    }

// ######################################## ***  9. calculate database fields ( = protected function Live[field name]() { ....}

    protected function LiveName()
    {
        user_error('The "LiveName" method has be defined in ...'.$this->ClassName, E_USER_NOTICE);
        $defaults = $this->config()->get('defaults');

        return $defaults['Name'];
    }

    protected function LiveTableValue()
    {
        return $this->LiveCalculatedTotal();
    }

    /**
     * This function is always called to determine the
     * amount this modifier needs to charge or deduct - if any.
     *
     *
     * @return Currency
     */
    protected function LiveCalculatedTotal()
    {
        return $this->CalculatedTotal;
    }

// ######################################## ***  10. Type Functions (IsChargeable, IsDeductable, IsNoChange, IsRemoved)

    /**
     * should be extended if it is true in child class.
     *
     * @return bool
     */
    public function IsChargeable()
    {
        return $this->CalculatedTotal > 0;
    }
    /**
     * should be extended if it is true in child class.
     *
     * @return bool
     */
    public function IsDeductable()
    {
        return $this->CalculatedTotal < 0;
    }

    /**
     * should be extended if it is true in child class.
     *
     * @return bool
     */
    public function IsNoChange()
    {
        return $this->CalculatedTotal == 0;
    }

    /**
     * should be extended if it is true in child class
     * Needs to be a public class.
     *
     * @return bool
     */
    public function IsRemoved()
    {
        return $this->HasBeenRemoved;
    }

// ######################################## ***  11. standard database related functions (e.g. onBeforeWrite, onAfterWrite, etc...)

    /**
     * standard SS method.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
    }

    /**
     * removing the Order Modifier does not delete it
     * rather, it ignores it (e.g. remove discount coupon)
     * We cant delete it, because we need to have a positive record
     * of it being removed.
     * Extend on Child Classes.
     */
    public function onBeforeRemove()
    {
        //you can add more stuff here in sub classes
    }

    /**
     * removing the Order Modifier does not delete it
     * rather, it ignores it (e.g. remove discount coupon)
     * We cant delete it, because we need to have a positive record
     * of it being removed.
     * Extend on Child Classes.
     */
    public function onAfterRemove()
    {
        //you can add more stuff here in sub classes
    }

// ######################################## ***  11. AJAX related functions

    /**
     * @param array $js javascript array
     *
     * @return array for AJAX JSON
     **/
    public function updateForAjax(array $js)
    {
        $function = EcommerceConfig::get('OrderModifier', 'ajax_total_format');
        if (is_array($function)) {
            list($function, $format) = $function;
        }
        $total = $this->$function();
        if (isset($format)) {
            $total = $total->$format();
        }
        $ajaxObject = $this->AJAXDefinitions();
        //TableValue is a database value
        $tableValue = DBField::create_field('Currency', $this->TableValue)->Nice();
        if ($this->HideInAjaxUpdate()) {
            $js[] = array(
                't' => 'id',
                's' => $ajaxObject->TableID(),
                'p' => 'hide',
                'v' => 1,
            );
        } else {
            $js[] = array(
                't' => 'id',
                's' => $ajaxObject->TableID(),
                'p' => 'hide',
                'v' => 0,
            );
            $js[] = array(
                't' => 'id',
                's' => $ajaxObject->TableTitleID(),
                'p' => 'innerHTML',
                'v' => $this->TableTitle(),
            );
            $js[] = array(
                't' => 'id',
                's' => $ajaxObject->CartTitleID(),
                'p' => 'innerHTML',
                'v' => $this->CartTitle(),
            );
            $js[] = array(
                't' => 'id',
                's' => $ajaxObject->TableSubTitleID(),
                'p' => 'innerHTML',
                'v' => $this->TableSubTitle(),
            );
            $js[] = array(
                't' => 'id',
                's' => $ajaxObject->CartSubTitleID(),
                'p' => 'innerHTML',
                'v' => $this->CartSubTitle(),
            );
            $js[] = array(
                't' => 'id',
                's' => $ajaxObject->TableTotalID(),
                'p' => 'innerHTML',
                'v' => $total,
            );
        }

        return $js;
    }

// ######################################## ***  12. debug functions

    /**
     * Debug helper method.
     * Access through : /shoppingcart/debug/.
     */
    public function debug()
    {
        $html = EcommerceTaskDebugCart::debug_object($this);

        return $html;
    }
}
