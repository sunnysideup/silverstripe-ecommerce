<?php

/**
 * which returns an array of IDs
 * SEQUENCE - USE FOR ALL MODIFIERS!!!
 * *** 1. model defining static variables (e.g. $db, $has_one)
 * *** 2. cms variables + functions (e.g. getCMSFields, $searchableFields)
 * *** 3. other (non) static variables (e.g. protected static $special_name_for_something, protected $order)
 * *** 4. CRUD functions (e.g. canEdit)
 * *** 5. init and update functions
 * *** 6. form functions (e. g. Showform and getform)
 * *** 7. template functions (e.g. ShowInTable, TableTitle, etc...) ... USES DB VALUES
 * *** 8. inner calculations.... USES CALCULATED VALUES
 * *** 9. calculate database fields: protected function Live[field name]  ... USES CALCULATED VALUES
 * *** 10. standard database related functions (e.g. onBeforeWrite, onAfterWrite, etc...)
 * *** 11. AJAX related functions
 * *** 12. debug functions
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

class OrderModifier extends OrderAttribute {

	/**
	 * what variables are accessible through  http://mysite.com/api/ecommerce/v1/OrderModifier/
	 * @var array
	 */
	public static $api_access = array(
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
			'Order'
		)
	);

// ########################################  *** 1. model defining static variables (e.g. $db, $has_one)

	/**
	 * @var array
	 * stardard SS definition
	 */
	public static $db = array(
		'Name' => 'HTMLText', // we use this to create the TableTitle, CartTitle and TableSubTitle
		'TableValue' => 'Currency', //the $$ shown in the checkout table
		'HasBeenRemoved' => 'Boolean' // we add this so that we can see what modifiers have been removed
	);

	/**
	 * make sure to choose the right Type and Name for this.
	 * stardard SS variable
	 * @var Array
	 */
	public static $defaults = array(
		'Name' => 'Modifier' //making sure that you choose a different name for any class extensions.
	);

// ########################################  *** 2. cms variables  + functions (e.g. getCMSFields, $searchableFields)

	/**
	 * stardard SS variable
	 * @var array
	 */
	public static $searchable_fields = array(
		'OrderID' => array(
			'field' => 'NumericField',
			'title' => 'Order Number'
		),
		"TableTitle" => "PartialMatchFilter",
		"TableValue",
		"HasBeenRemoved"
	);

	/**
	 * stardard SS definition
	 * @var array
	 */
	public static $summary_fields = array(
		"Order.ID" => "Order ID",
		"TableTitle" => "Table Title",
		"TableValue" => "Value Shown"
	);

	/**
	 * stardard SS definition
	 * @var array
	 */
	public static $casting = array(
		"CalculatedTableValueAsMoney" => "Money"
	);

	/**
	 * stardard SS variable
	 * @var String
	 */
	public static $singular_name = "Order Modifier";
		function i18n_singular_name() { return _t("OrderModifier.ORDERMODIFIER", "Order Modifier");}

	/**
	 * stardard SS variable
	 * @var String
	 */
	public static $plural_name = "Order Modifiers";
		function i18n_plural_name() { return _t("OrderModifier.ORDERMODIFIERS", "Order Modifiers");}

	/**
	 * stardard SS metbod
	 * @return FieldSet
	 */
	function getCMSFields(){
		$fields = parent::getCMSFields();
		$fields->removeByName("Sort");
		$fields->removeByName("GroupSort");
		$fields->replaceField("Name", new ReadonlyField("Name"));
		$fields->removeByName("TableValue");
		$fields->removeByName("CalculatedTotal");
		$fields->removeByName("HasBeenRemoved");
		$fields->addFieldToTab(
			"Root",
			new Tab(
				"Debug",
				new ReadonlyField("ClassName", "Type", $this->ClassName),
				new ReadonlyField("CreatedShown", "Created", $this->Created),
				new ReadonlyField("LastEditedShown", "Last Edited", $this->LastEdited),
				new ReadonlyField("TableValueShown", "Table Value", $this->TableValue),
				new ReadonlyField("CalculatedTotal", "Raw Value", $this->CalculatedTotal)
			)
		);
		$fields->addFieldToTab("Root.Status", new CheckboxField("HasBeenRemoved", "Has been removed"));
		$fields->removeByName("OrderAttribute_GroupID");
		return $fields;
	}

	/**
	 * standard SS method, update search fields for ModelAdmin
	 * @return FieldSet
	 **/
	function scaffoldSearchFields(){
		$fields = parent::scaffoldSearchFields();
		$fields->replaceField("OrderID", new NumericField("OrderID", "Order Number"));
		return $fields;
	}

// ########################################  *** 3. other static variables (e.g. special_name_for_something)

	/**
	 * $doNotAddAutomatically Identifies whether a modifier is NOT automatically added
	 * Most modifiers, such as delivery and GST would be added automatically.
	 * However, there are also ones that are not added automatically.
	 * @var Boolean
	 **/
	protected $doNotAddAutomatically = false;

	/**
	 * $can_be_removed Identifies whether a modifier can be removed by the user.
	 * @var Boolean
	 **/
	protected static $canBeRemoved = false;

	/**
	 * we use this variable to make sure that the parent::runUpdate() is called in all child classes
	 * this is similar to the checks run for parent::init in the controller class.
	 * @var Boolean
	 **/
	protected $baseInitCalled = false;

	/**
	 * This is a flag for running an update.
	 * Running an update means that all fields are (re)set, using the Live{FieldName} methods.
	 * @var Boolean
	 **/
	protected $mustUpdate = false;

	/**
	 * When recalculating all the modifiers, this private variable is added to as a running total
	 * other modifiers can then tap into this to work out their own values.
	 * For example, a tax modifier needs to know the value of the other modifiers before calculating
	 * its own value (i.e. tax is also paid over handling and shipping).
	 * Always consider the "order" (which one first) of the order modifiers when using this variable.
	 * @var Float
	 **/
	private $runningTotal = 0;


// ######################################## *** 4. CRUD functions (e.g. canEdit)



// ########################################  *** 5. init and update functions


	public static function init_for_order($className) {
		user_error("the init_for_order method has been depreciated, instead, use \$myModifier->init()", E_USER_ERROR);
		return false;
	}

	/**
	* This method runs when the OrderModifier is first added to the order.
	**/
	public function init() {
		parent::init();
		$this->write();
		$this->mustUpdate = true;
		$this->runUpdate($force = false);
		return true;
	}

	/**
	* all classes extending OrderModifier must have this method if it has more fields
	 * @param Bool $force - run it, even if it has run already
	**/
	public function runUpdate($force = false) {
		if (isset($_GET['debug_profile'])) Profiler::mark('OrderModifier::runUpdate-for-'.$this->ClassName);
		if(!$this->IsRemoved()) {
			$this->checkField("Name");
			$this->checkField("CalculatedTotal");
			$this->checkField("TableValue");
			if($this->mustUpdate && $this->canBeUpdated()) {
				$this->write();
			}
			$this->runningTotal += $this->CalculatedTotal;
		}
		$this->baseInitCalled = true;
		if (isset($_GET['debug_profile'])) Profiler::unmark('OrderModifier::runUpdate-for-'.$this->ClassName);
	}

	/**
	* You can overload this method as canEdit might not be the right indicator.
	* @return Boolean
	**/
	protected function canBeUpdated() {
		return $this->canEdit();
	}

	/**
	 * @param String $fieldName
	 * This method simply checks if a fields has changed and if it has changed it updates the field.
	 **/
	protected function checkField($fieldName) {
		if (isset($_GET['debug_profile'])) Profiler::mark('OrderModifier::checkField_'.$fieldName.'_'.$this->ClassName);
		if($this->canBeUpdated()) {
			$functionName = "Live".$fieldName;
			$oldValue = $this->$fieldName;
			$newValue = $this->$functionName();
			if($oldValue != $newValue) {
				$this->$fieldName = $newValue;
				$this->mustUpdate = true;
			}
		}
		if (isset($_GET['debug_profile'])) Profiler::unmark('OrderModifier::checkField_'.$fieldName.'_'.$this->ClassName);
	}

	/**
	 * Provides a modifier total that is positive or negative, depending on whether the modifier is chargable or not.
	 * This number is used to work out the order Grand Total.....
	 * It is important to note that this can be positive or negative, while the amount is always positive.
	 * @return float / double
	 */
	public function CalculationTotal() {
		if($this->HasBeenRemoved) {
			return 0;
		}
		return $this->CalculatedTotal;
	}

// ########################################  *** 6. form functions (Showform and getform)

	/**
	 * This determines whether the OrderModifierForm is shown or not. {@link OrderModifier::get_form()}.
	 * OrderModifierForms are forms that are added to check out to facilitate the use of the modifier.
	 * An example would be a form allowing the user to select the delivery option.
	 * @return boolean
	 */
	public function ShowForm() {
		return false;
	}

	/**
	 * Should the form be included in the EDITABLE form
	 * on the checkout page?
	 * @return Boolean
	 */
	public function ShowFormInEditableOrderTable() {
		//extend in OrderModifier Extensions
		return false;
	}

	/**
	 * Should the form be shown outside of editable table
	 * on the checkout page (opposite of ShowFormInEditableOrderTable)?
	 * @return Boolean
	 */
	public function ShowFormOutsideEditableOrderTable() {
		//extend in OrderModifier Extensions
		return $this->ShowFormInEditableOrderTable() ? false : true;
	}

	/**
	 * This function returns a form that allows a user
	 * to change the modifier to the order.
	 *
	 * We have mainly added this function as an example!
	 *
	 * @param Controller $optionalController  - optional custom controller class
	 * @param Validator $optionalValidator  - optional custom validator class
	 * @return OrderModifierForm or subclass
	 */
	public function getModifierForm($optionalController = null, $optionalValidator = null) {
		if($this->ShowForm()) {
			$fields = new FieldSet();
			$fields->push($this->headingField());
			$fields->push($this->descriptionField());
			return new OrderModifierForm($optionalController, "ModifierForm", $fields, $actions = new FieldSet(), $optionalValidator);
		}
	}

	/**
	 *
	 * @return Object (HeadingField)
	 */
	protected function headingField(){
		$name = $this->ClassName.'Heading';
		if($this->Heading()) {
			return new HeaderField($name, $this->Heading(), 4);
		}
		return new LiteralField($name, "<!-- EmptyHeading -->", "<!-- EmptyHeading -->");
	}

	/**
	 *
	 * @return Object (LiteralField)
	 */
	protected function descriptionField(){
		$name = $this->ClassName.'Description';
		if($this->Description()) {
			return new LiteralField($name, "<div id=\"".Convert::raw2att($name)."DescriptionHolder\" class=\"descriptionHolder\">".Convert::raw2xml($this->Description())."</div>");
		}
		return new LiteralField($name, "<!-- EmptyDescription -->","<!-- EmptyDescription -->");
	}


// ######################################## *** 7. template functions (e.g. ShowInTable, TableTitle, etc...)

	/**
	 * Casted variable, returns the table title.
	 * @return String
	 */
	public function TableTitle() { return $this->getTableTitle(); }
	public function getTableTitle(){
		return $this->Name;
	}

	/**
	 * returns a heading if there is one.
	 * @return String
	 **/
	public function Heading(){
		if($obj = DataObject::get_one("OrderModifier_Descriptor", "\"ModifierClassName\" = '".$this->ClassName."'")) {
			return $obj->Heading;
		}
		return "";
	}

	/**
	 * returns a description if there is one.
	 * @return String (html)
	 **/
	public function Description(){
		if($obj = DataObject::get_one("OrderModifier_Descriptor", "\"ModifierClassName\" = '".$this->ClassName."'")) {
			return $obj->Description;
		}
		return "";
	}

	/**
	 * returns a page for a more info link... (if there is one)
	 * @return Object (SiteTree)
	 **/
	public function MoreInfoPage(){
		if($obj = DataObject::get_one("OrderModifier_Descriptor", "\"ModifierClassName\" = '".$this->ClassName."'")) {
			return $obj->Link();
		}
		return null;
	}


	/**
	 * tells you whether the modifier shows up on the checkout  / cart form.
	 * this is also the place where we check if the modifier has been updated.
	 * @return Boolean
	 */
	public function ShowInTable() {
		if(!$this->baseInitCalled && $this->canBeUpdated()) {
			user_error("While the order can be edited, you must call the runUpdate method everytime you get the details for this modifier", E_USER_ERROR);
		}
		return false;
	}


	/**
	 * Returns the Money object of the CalculatedTotal
	 * @return Money
	 **/
	function CalculatedTableValueAsMoney() {return $this->getCalculatedTableValueAsMoney();}
	function getCalculatedTableValueAsMoney() {
		return EcommerceCurrency::get_money_object_from_order_currency($this->TableValue, $this->Order());
	}

	/**
	* some modifiers can be hidden after an ajax update (e.g. if someone enters a discount coupon and it does not exist).
	* There might be instances where ShowInTable (the starting point) is TRUE and HideInAjaxUpdate return false.
	*@return Boolean
	**/
	public function HideInAjaxUpdate() {
		if($this->IsRemoved()) {
			return true;
		}
		if($this->ShowInTable()) {
			return false;
		}
		return true;
	}

	/**
	 * Checks if the modifier can be removed.
	 *
	 * @return boolean
	 **/
	public function CanBeRemoved() {
		return $this->canBeRemoved;
	}

	/**
	 * Checks if the modifier can be added manually
	 *
	 * @return boolean
	 **/
	public function CanAdd() {
		return $this->HasBeenRemoved || $this->DoNotAddOnInit();
	}

	/**
	 *Identifier whether a modifier will be added automatically for all new orders.
	 * @return boolean
	 */
	public function DoNotAddAutomatically() {
		return $this->doNotAddAutomatically;
	}

	/**
	 * Actual calculation used
	 *
	 * @return Float / Double
	 **/
	public function CalculatedTotal() {
		return $this->CalculatedTotal;
	}

	/**
	 * This link is for modifiers that have been removed and are being put "back".
	 * @return String
	 **/
	public function AddLink() {
		$param = array();
		$this->extend("ModifierAddLinkUpdate", $param);
		return ShoppingCart_Controller::add_modifier_link($this->ID, $param, $this->ClassName);
	}

	/**
	 * Link that can be used to remove the modifier
	 * @return String
	 **/
	public function RemoveLink() {
		$param = array();
		$this->extend("ModifierRemoveLinkUpdate", $param);
		return ShoppingCart_Controller::remove_modifier_link($this->ID, $param, $this->ClassName);
	}

	/**
	 * retursn and array like this: array(Title => "bla", Link => "/doit/now/");
	 * @return Array
	 */
	public function PostSubmitAction() {
		return array();
	}

// ######################################## ***  8. inner calculations....

	/**
	 * returns the running total variable
	 * @see variable definition for more information
	 * @return Float
	 */
	public function getRunningTotal(){
		return $this->runningTotal;
	}

// ######################################## ***  9. calculate database fields ( = protected function Live[field name]() { ....}

	protected function LiveName() {
		user_error("The \"LiveName\" method has be defined in ...".$this->ClassName, E_USER_NOTICE);
		return self::$defaults["Name"];
	}

	protected function LiveTableValue() {
		return $this->LiveCalculatedTotal();
	}

	/**
	 * This function is always called to determine the
	 * amount this modifier needs to charge or deduct - if any.
	 *
	 *
	 * @return Currency
	 */
	protected function LiveCalculatedTotal() {
		return $this->CalculatedTotal;
	}


// ######################################## ***  10. Type Functions (IsChargeable, IsDeductable, IsNoChange, IsRemoved)

	/**
	 * should be extended if it is true in child class
	 * @return boolean
	 */
	public function IsChargeable() {
		return $this->CalculatedTotal > 0;
	}
	/**
	 * should be extended if it is true in child class
	 * @return boolean
	 */
	public function IsDeductable() {
		return $this->CalculatedTotal < 0;
	}

	/**
	 * should be extended if it is true in child class
	 * @return boolean
	 */
	public function IsNoChange() {
		return $this->CalculatedTotal == 0 ;
	}

	/**
	 * should be extended if it is true in child class
	 * Needs to be a public class
	 * @return boolean
	 */
	public function IsRemoved() {
		return $this->HasBeenRemoved;
	}



// ######################################## ***  11. standard database related functions (e.g. onBeforeWrite, onAfterWrite, etc...)

	/**
	 * standard SS method
	 */
	function onBeforeWrite() {
		parent::onBeforeWrite();
	}

	/**
	 * removing the Order Modifier does not delete it
	 * rather, it ignores it (e.g. remove discount coupon)
	 * We cant delete it, because we need to have a positive record
	 * of it being removed.
	 * Extend on Child Classes
	 */
	function onBeforeRemove(){
		//you can add more stuff here in sub classes
	}

	/**
	 * removing the Order Modifier does not delete it
	 * rather, it ignores it (e.g. remove discount coupon)
	 * We cant delete it, because we need to have a positive record
	 * of it being removed.
	 * Extend on Child Classes
	 */
	function onAfterRemove(){
		//you can add more stuff here in sub classes
	}




// ######################################## ***  11. AJAX related functions

	/**
	 *
	 * @param Array $js javascript array
	 * @return Array for AJAX JSON
	 **/
	function updateForAjax(array &$js) {
		$function = EcommerceConfig::get('OrderModifier', 'ajax_total_format');
		if(is_array($function)) {
			list($function, $format) = $function;
		}
		$total = $this->$function();
		if(isset($format)) {
			$total = $total->$format();
		}
		$ajaxObject = $this->AJAXDefinitions();
		if($this->HideInAjaxUpdate()) {
			$js[] = array(
				't' => 'id',
				's' => $ajaxObject->TableID(),
				'p' => 'hide',
				'v' => 1
			);
		}
		else {
			$js[] = array(
				't' => 'id',
				's' => $ajaxObject->TableID(),
				'p' => 'hide',
				'v' => 0
			);
			$js[] = array(
				't' => 'id',
				's' => $ajaxObject->TableTitleID(),
				'p' => 'innerHTML',
				'v' => $this->TableTitle()
			);
			$js[] = array(
				't' => 'id',
				's' => $ajaxObject->CartTitleID(),
				'p' => 'innerHTML',
				'v' => $this->CartTitle()
			);
			$js[] = array(
				't' => 'id',
				's' => $ajaxObject->TableSubTitleID(),
				'p' => 'innerHTML',
				'v' => $this->TableSubTitle()
			);
			$js[] = array(
				't' => 'id',
				's' => $ajaxObject->CartSubTitleID(),
				'p' => 'innerHTML',
				'v' => $this->CartSubTitle()
			);
			$js[] = array(
				't' => 'id',
				's' => $ajaxObject->TableTotalID(),
				'p' => 'innerHTML',
				'v' => $total
			);
		}
	}


// ######################################## ***  12. debug functions



}

class OrderModifier_Descriptor extends DataObject {

	/**
	 * standard SS variable
	 * @var Array
	 */
	static $db = array(
		"ModifierClassName" => "Varchar(100)",
		"Heading" => "Varchar",
		"Description" => "Text"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	static $has_one = array(
		"Link" => "SiteTree"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	static $indexes = array(
		"ModifierClassName" => true
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $searchable_fields = array(
		"Heading" => "PartialMatchFilter",
		"Description" => "PartialMatchFilter"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $field_labels = array(
		"ModifierClassName" => "Code"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $summary_fields = array(
		"RealName" => "Code",
		"Heading" => "Heading",
		"Description" => "Description"
	);

	/**
	 * standard SS variable
	 * @var Array
	 */
	public static $casting = array(
		"RealName" => "Varchar"
	);

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $singular_name = "Order Modifier Description";
		function i18n_singular_name() { return _t("OrderModifier.ORDEREXTRADESCRIPTION", "Order Modifier Description");}

	/**
	 * standard SS variable
	 * @var String
	 */
	public static $plural_name = "Order Modifier Descriptions";
		function i18n_plural_name() { return _t("OrderModifier.ORDEREXTRADESCRIPTIONS", "Order Modifier Descriptions");}

	/**
	 * standard SS variable
	 * @var Boolean
	 */
	static $can_create = false;

	/**
	 * standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canCreate($member = null) {return false;}

	/**
	 * standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canView($member = null) {return true;}

	/**
	 * standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canEdit($member = null) {return true;}

	/**
	 * standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {return false;}

	/**
	 * standard SS method
	 * @return FieldSet
	 */
	function getCMSFields(){
		$fields = parent::getCMSFields();
		$fields->replaceField("ModifierClassName", new ReadonlyField("RealName", "Name"));
		$fields->replaceField("LinkID", new TreeDropdownField("LinkID", "More info link (optional)", "SiteTree"));
		if($this->LinkID) {
			$fields->addFieldToTab("Root", new CheckboxField("NoLinkForOrderModifier_Descriptor", "Remove Link"), "LinkID");
		}
		$fields->replaceField("Description", new TextareaField("Description", "Description", 3));
		return $fields;
	}


	/**
	 * casted Variable
	 * @return String.
	 */
	function RealName(){return $this->getRealName();}
	function getRealName(){
		if(class_exists($this->ModifierClassName)) {
			$singleton = singleton($this->ModifierClassName);
			return $singleton->i18n_singular_name(). " (".$this->ModifierClassName.")";
		}
		return $this->ModifierClassName;
	}

	/**
	 * stardard SS method
	 */
	function onBeforeWrite(){
		parent::onBeforeWrite();
		if(isset($_REQUEST["NoLinkForOrderModifier_Descriptor"]) && $_REQUEST["NoLinkForOrderModifier_Descriptor"]) {
			$this->LinkID = 0;
		}
	}

	/**
	 * stardard SS method
	 */
	function requireDefaultRecords(){
		parent::requireDefaultRecords();
		$arrayOfModifiers = EcommerceConfig::get("Order", "modifiers");
		if(!is_array($arrayOfModifiers)) {
			$arrayOfModifiers = array();
		}
		if(count($arrayOfModifiers)) {
			foreach($arrayOfModifiers as $className) {
				$obj = DataObject::get_one("OrderModifier_Descriptor", "\"ModifierClassName\" = '".$className."'");
				if(!$obj) {
					$modifier = singleton($className);
					$obj = new OrderModifier_Descriptor();
					$obj->ModifierClassName = $className;
					$obj->Heading = $modifier->i18n_singular_name();
					$obj->write();
					DB::alteration_message("Creating description for ".$className, "created");
				}
			}
		}
		$orderModifierDescriptors = DataObject::get("OrderModifier_Descriptor");
		if($orderModifierDescriptors) {
			foreach($orderModifierDescriptors as $orderModifierDescriptor) {
				if(!in_array($orderModifierDescriptor->ModifierClassName, $arrayOfModifiers)) {
					$orderModifierDescriptor->delete();
					DB::alteration_message("Deleting description for ".$orderModifierDescriptor->ModifierClassName, "created");
				}
			}
		}
	}

}
