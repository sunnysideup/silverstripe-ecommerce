<?php
/**
 * @description: An order item is a product which has been added to an order.
 * An order item links to a Buyable (product) by class name
 * That is, we only store the BuyableID and the Class name is derived
 * from the name of the class... For example a Product_OrderItem has
 * a Product as Buyable.  A ProductVariation_OrderItem has a
 * Product Variation as Buyable.
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: model
 *
 **/
class OrderItem extends OrderAttribute {

	/**
	 * what variables are accessible through  http://mysite.com/api/v1/OrderItem/
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
				'Quantity',
				'BuyableID',
				'Version',
				'UnitPrice',
				'Total',
				'Order'
			)
	 );

	/**
	 * @var array
	 * stardard SS definition
	 */
	public static $db = array(
		'Quantity' => 'Double',
		'BuyableID' => 'Int',
		'Version' => 'Int'
	);

	/**
	 * @var array
	 * stardard SS definition
	 */
	public static $indexes = array(
		"Quantity" => true,
		"BuyableID" => true
	);

	/**
	 * @var array
	 * stardard SS definition
	 */
	public static $casting = array(
		'UnitPrice' => 'Currency',
		'Total' => 'Currency'
	);

	######################
	## CMS CONFIG ##
	######################


	/**
	 * @var array
	 * stardard SS definition
	 */
	public static $searchable_fields = array(
		'OrderID' => array(
			'field' => 'NumericField',
			'title' => 'Order Number'
		),
		"TableTitle" => "PartialMatchFilter",
		"UnitPrice",
		"Quantity",
		"Total"
	);

	/**
	 * @var array
	 * stardard SS definition
	 */
	public static $field_labels = array(
		//@todo - complete
	);

	/**
	 * @var array
	 * stardard SS definition
	 */
	public static $summary_fields = array(
		"Order.ID" => "Order ID",
		"TableTitle" => "Title",
		"TableSubTitle" => "Sub Title",
		"UnitPrice" => "Unit Price" ,
		"Quantity" => "Quantity" ,
		"Total" => "Total Price" ,
	);

	/**
	 * singular name of the object. it is recommended to override this
	 * in any extensions of this class.
	 * @var String
	 */
	public static $singular_name = "Order Item";
		function i18n_singular_name() { return _t("OrderItem.ORDERITEM", "Order Item");}


	/**
	 * plural name of the object. it is recommended to override this
	 * in any extensions of this class.
	 * @var String
	 */
	public static $plural_name = "Order Items";
		function i18n_plural_name() { return _t("OrderItem.ORDERITEMS", "Order Items");}


	/**
	 * Standard SS method
	 * @var String
	 */
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->removeByName("Version");
		$fields->removeByName("Sort");
		$fields->removeByName("GroupSort");
		$fields->removeByName("OrderAttribute_GroupID");
		$buyables = EcommerceConfig::get("Buyable", "array_of_buyables");
		$classNameArray = array();
		$buyablesArray = array();
		if($buyables && count($buyables)) {
			foreach($buyables as $buyable) {
				$orderItemPostFix = EcommerceConfig::get("Buyable", "order_item_class_name_post_fix");
				$classNameArray[$buyable.$orderItemPostFix] = $buyable;
				$newObjects = DataObject::get($buyable);
				if($newObjects) {
					foreach($newObjects as $object) {
						if(!$object->canPurchase()) {
							$newObjects->remove($object);
						}
					}
					$buyablesArray = $buyablesArray + $newObjects->toDropDownMap();
				}
			}
		}
		if(count($classNameArray)) {
			$fields->addFieldToTab("Root.Main", new DropdownField("ClassName", _t("OrderItem.TYPE", "Type"), $classNameArray));
			$fields->replaceField("BuyableID", new DropdownField("BuyableID", _t("OrderItem.BOUGHT", "Bought"), $buyablesArray));
		}
		$fields->replaceField("OrderID", new NumericField("OrderID", "Order Number"));
		return $fields;
	}

	/**
	 * standard SS method
	 * @return Boolean
	 **/
	function canDelete($member = null) {
		return $this->canEdit($member);
	}

	/**
	 * standard SS method
	 * @return FieldSet
	  **/
	function scaffoldSearchFields(){
		$fields = parent::scaffoldSearchFields();
		$fields->replaceField("OrderID", new NumericField("OrderID", "Order Number"));
		return $fields;
	}

	/**
	 * standard SS method
	 * @return FieldSet
	 **/
	public function addBuyableToOrderItem($buyable, $quantity = 1) {
		$this->Version = $buyable->Version;
		$this->BuyableID = $buyable->ID;
		$this->Quantity = $quantity;
		//should always come last!
		parent::addBuyableToOrderItem($buyable);
	}

	/**
	 * used to return data for ajax
	 * @return Array used to create JSON for AJAX
	  **/
	function updateForAjax(array &$js) {
		$total = $this->TotalAsCurrencyObject()->Nice();
		$ajaxObject = $this->AJAXDefinitions();
		/* we dont need to show / hide
		$js[] = array(
			'type' => 'id',
			'selector' => $ajaxObject->TableID(),
			'parameter' => 'hide',
			'value' => 0
		);
		*/
		//@TODO: is this correct, seems strange to replce the field with a number!
		$js[] = array(
			'type' => 'id',
			'selector' => $ajaxObject->QuantityFieldName(),
			'parameter' => 'innerHTML',
			'value' => $this->Quantity
		);
		$js[] = array(
			'type' => 'name',
			'selector' => $ajaxObject->QuantityFieldName(),
			'parameter' => 'value',
			'value' => $this->Quantity
		);
		$js[] = array(
			'type' => 'id',
			'selector' => $ajaxObject->TableTitleID(),
			'parameter' => 'innerHTML',
			'value' => $this->TableTitle()
		);
		$js[] = array(
			'type' => 'id',
			'selector' => $ajaxObject->CartTitleID(),
			'parameter' => 'innerHTML',
			'value' => $this->CartTitle()
		);
		$js[] = array(
			'type' => 'id',
			'selector' => $ajaxObject->TableSubTitleID(),
			'parameter' => 'innerHTML',
			'value' => $this->TableSubTitle()
		);
		$js[] = array(
			'type' => 'id',
			'selector' => $ajaxObject->CartSubTitleID(),
			'parameter' => 'innerHTML',
			'value' => $this->CartSubTitle()
		);
		$js[] = array(
			'type' => 'id',
			'selector' => $ajaxObject->TableTotalID(),
			'parameter' => 'innerHTML',
			'value' => $total
		);
	}

	/**
	 * saves details about the Order Item before the order is submittted
	 * @param Bool $force - run it, even if it has run already
	 **/
	function runUpdate($force = false){
		$this->CalculatedTotal = $this->UnitPrice() * $this->Quantity;
		$this->write();
	}

	function onBeforeWrite() {
		parent::onBeforeWrite();
		//always keep quantity above 0
		if(floatval($this->Quantity) == 0) {
			$this->Quantity = 1;
		}
		//product ID and version ID need to be set in subclasses
	}


	/**
	 * Check if two Order Items are the same.
	 * Useful when adding two items to cart.
	 * @return Boolean
	  **/
	function hasSameContent($orderItem) {
		return $orderItem instanceof OrderItem && $this->BuyableID == $orderItem->BuyableID && $this->Version == $orderItem->Version;
	}



	######################
	## TEMPLATE METHODS ##
	######################

	public function UnitPrice($recalculate = false) {return $this->getUnitPrice($recalculate);}
	public function getUnitPrice($recalculate = false) {
		if($this->priceHasBeenFixed() && !$recalculate) {
			return $this->CalculatedTotal / $this->Quantity;
		}
		else {
		//NOTE: user_error("OrderItem::UnitPrice() called. Please implement UnitPrice() and getUnitPrice on $this->class", E_USER_NOTICE);
			return 0;
		}
	}

	/**
	 * Casted Variable
	 * @return Float
	  **/
	function Total(){return $this->getTotal();}
	function getTotal() {
		if($this->priceHasBeenFixed()) {
			//get from database
			return $this->CalculatedTotal;
		}
		$total = $this->UnitPrice() * $this->Quantity;
		$this->extend('updateTotal',$total);
		return $total;
	}



	/**
	 *
	 * @return Field (EcomQuantityField)
	  **/
	function QuantityField(){
		return new EcomQuantityField($this);
	}



	/**
	 *
	 * @return Currency (DB Object)
	  **/
	function TotalAsCurrencyObject() {
		return DBField::create('Currency',$this->Total());
	}


	##########################
	## OTHER LOOKUP METHODS ##
	##########################

	/**
	 * @description - tells you if an order item price has been "fixed"
	 * meaning that is has been saved in the CalculatedTotal field so that
	 * it can not be altered.
	 *
	 * @return Boolean
	 **/
	protected function priceHasBeenFixed(){
		if( $this->Order() && $this->Order()->IsSubmitted() && $this->Quantity ) {
			return true;
		}
		return false;
	}

	/**
	 *
	 * @return DataObject (Any type of Data Object that is buyable)
	 * @param Boolean $current - is this a current one, or an older VERSION ?
	  **/
	function Buyable($current = false) {
		$className = $this->BuyableClassName();
		if($this->BuyableID && $this->Version && !$current) {
			if($obj = Versioned::get_version($className, $this->BuyableID, $this->Version)) {
				return $obj;
			}
		}
		return DataObject::get_by_id($className, $this->BuyableID);
	}

	/**
	 *
	 * @return String
	  **/
	function BuyableClassName() {
		$orderItemPostFix = EcommerceConfig::get("Buyable", "order_item_class_name_post_fix");
		$className = str_replace($orderItemPostFix, "", $this->ClassName);
		if(class_exists($className) && ClassInfo::is_subclass_of($className, "DataObject")) {
			return $className;
		}
		user_error($this->ClassName." does not have an item class: $className", E_USER_WARNING);
	}

	/**
	 *
	 * @return String
	  **/
	function BuyableTitle() {
		if($item = $this->Buyable()) {
			if($title = $item->Title) {
				return $title;
			}
			//This should work in all cases, because ultimately, it will return #ID - see DataObject
			return $item->getTitle();
		}
		user_error("No Buyable could be found for OrderItem with ID: ".$this->ID, E_USER_WARNING);
	}

	/**
	 *
	 * @return String (URLSegment)
	  **/
	function Link() {
		if($item = $this->Buyable()) {
			return $item->Link();
		}
		user_error("No Buyable could be found for OrderItem with ID: ".$this->ID, E_USER_WARNING);
	}

	/**
	 *
	 * @return String
	  **/
	function ProductTitle() {
		user_error("This function has been replaced by BuyableTitle", E_USER_NOTICE);
		return $this->BuyableTitle();
	}


	/**
	 *
	 * @return String (URLSegment)
	  **/
	function CheckoutLink() {
		return CheckoutPage::find_link();
	}

	## Often Overloaded functions ##

	/**
	 *
	 * @return String (URLSegment)
	  **/
	function AddLink() {
		return ShoppingCart_Controller::add_item_link($this->BuyableID, $this->Buyable()->ClassName,$this->linkParameters());
	}

	/**
	 *
	 * @return String (URLSegment)
	  **/
	function IncrementLink() {
		return ShoppingCart_Controller::increment_item_link($this->BuyableID, $this->Buyable()->ClassName,$this->linkParameters());
	}

	/**
	 *
	 * @return String (URLSegment)
	  **/
	function DecrementLink() {
		return ShoppingCart_Controller::decrement_item_link($this->BuyableID, $this->Buyable()->ClassName,$this->linkParameters());
	}

	/**
	 *
	 * @return String (URLSegment)
	  **/
	function RemoveLink() {
		return ShoppingCart_Controller::remove_item_link($this->BuyableID, $this->Buyable()->ClassName,$this->linkParameters());
	}

	/**
	 *
	 * @return String (URLSegment)
	  **/
	function RemoveAllLink() {
		return ShoppingCart_Controller::remove_all_item_link($this->BuyableID, $this->Buyable()->ClassName,$this->linkParameters());
	}
	/**
	 *
	 * @return String (URLSegment)
	  **/
	function RemoveAllAndEditLink() {
		return ShoppingCart_Controller::remove_all_item_and_edit_link($this->BuyableID, $this->Buyable()->ClassName,$this->linkParameters());
	}

	/**
	 *
	 * @return String (URLSegment)
	  **/
	function SetQuantityLink() {
		return ShoppingCart_Controller::set_quantity_item_link($this->BuyableID, $this->Buyable()->ClassName,$this->linkParameters());
	}

	/**
	 *
	 * @return String (URLSegment)
	  **/
	function SetSpecificQuantityItemLink($quantity) {
		return ShoppingCart_Controller::set_quantity_item_link($this->BuyableID, $this->Buyable()->ClassName, array_merge($this->linkParameters(), array("quantity" => $quantity)));
	}

	/**
	 *
	 * @return array for use as get variables in link
	  **/
	protected function linkParameters(){
		$array = array();
		$this->extend('updateLinkParameters',$array);
		return $array;
	}

}
