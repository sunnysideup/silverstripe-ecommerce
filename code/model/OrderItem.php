<?php
/**
 * @description: An order item is a product which has been added to an order.
 * An order item links to a Buyable (product) by class name
 * That is, we only store the BuyableID and the ClassName
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
			'InternalItemID',
			'CalculatedTotal',
			'TableTitle',
			'TableSubTitleNOHTML',
			'Name',
			'TableValue',
			'Quantity',
			'BuyableID',
			'BuyableClassName',
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
		'BuyableClassName' => 'Varchar(60)',
		'Version' => 'Int'
	);

	/**
	 * @var array
	 * stardard SS definition
	 */
	public static $indexes = array(
		"Quantity" => true,
		"BuyableID" => true,
		"BuyableClassName" => true
	);

	/**
	 * @var array
	 * stardard SS definition
	 */
	public static $casting = array(
		'UnitPrice' => 'Currency',
		'Total' => 'Currency',
		'InternalItemID' => 'Varchar'
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
		$buyables = EcommerceConfig::get("EcommerceDBConfig", "array_of_buyables");
		$classNameArray = array();
		$buyablesArray = array();
		if($buyables && count($buyables)) {
			foreach($buyables as $buyable) {
				$classNameArray[$buyable] = $buyable;
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
			$fields->addFieldToTab("Root.Main", new DropdownField("BuyableClassName", _t("OrderItem.TYPE", "Type"), $classNameArray));
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
		$this->BuyableClassName = $buyable->ClassName;
		$this->Quantity = $quantity;
	}

	/**
	 * used to return data for ajax
	 * @return Array used to create JSON for AJAX
	  **/
	function updateForAjax(array &$js) {
		$total = $this->TotalAsCurrencyObject()->Nice();
		$ajaxObject = $this->AJAXDefinitions();
		if($this->Quantity) {
			$js[] = array(
				't' => 'id',
				's' => $ajaxObject->TableID(),
				'p' => 'hide',
				'v' => 0
			);
			//@TODO: is this correct, seems strange to replce the field with a number!
			$js[] = array(
				't' => 'id',
				's' => $ajaxObject->QuantityFieldName(),
				'p' => 'innerHTML',
				'v' => $this->Quantity
			);
			$js[] = array(
				't' => 'name',
				's' => $ajaxObject->QuantityFieldName(),
				'p' => 'value',
				'v' => $this->Quantity
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
		else {
			$js[] = array(
				't' => 'id',
				's' => $ajaxObject->TableID(),
				'p' => 'hide',
				'v' => 1
			);
		}
	}

	/**
	 * saves details about the Order Item before the order is submittted
	 * @param Bool $force - run it, even if it has run already
	 **/
	function runUpdate($force = false){
		$this->CalculatedTotal = $this->UnitPrice() * $this->Quantity;
		$this->write();
	}

	/**
	 * Standard SS method.
	 * If the quantity is zero then we set it to 1.
	 * TODO: evaluate this rule.
	 */
	function onBeforeWrite() {
		parent::onBeforeWrite();
		//always keep quantity above 0
		if(floatval($this->Quantity) == 0) {
			$this->Quantity = 1;
		}
		//product ID and version ID need to be set in subclasses
	}

	/**
	 * Standard SS method
	 * the method below is very important...
	 * We initialise the order once it has an OrderItem
	 */
	function onAfterWrite(){
		parent::onAfterWrite();
		$order = $this->Order();
		if($order) {
			if(!$order->StatusID) {
				$createdOrderStatus = DataObject::get_one("OrderStep");
				$order->StatusID = $createdOrderStatus->ID;
				$order->write();
				//this adds the modifiers
				$order->init();
			}
		}
	}


	/**
	 * Check if two Order Items are the same.
	 * Useful when adding two items to cart.
	 * @return Boolean
	  **/
	function hasSameContent($orderItem) {
		return
			$orderItem instanceof OrderItem &&
			$this->BuyableID == $orderItem->BuyableID &&
			$this->BuyableClassName == $orderItem->BuyableClassName &&
			$this->Version == $orderItem->Version;
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
	 * Casted variable
	 * returns InternalItemID from Buyable
	 * @return NULL | String
	 */
	function InternalItemID() { return $this->getInternalItemID();}
	function getInternalItemID() {
		if($buyable = $this->Buyable()) {
			return $buyable->InternalItemID;
		}
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
		if( $this->Order() && $this->Order()->IsSubmitted() ) {
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
		//start hack
		if(!$this->BuyableClassName) {
			$this->BuyableClassName = str_replace("_OrderItem", "", $this->ClassName);
		}
		$turnTranslatableBackOn = false;
		if (!$current && Object::has_extension($this->BuyableClassName,'Translatable')) {
			Translatable::disable_locale_filter();
			$turnTranslatableBackOn = true;
		}
		//end hack!
		$obj = null;
		if($current) {
			$obj = DataObject::get_by_id($this->BuyableClassName, $this->BuyableID);
			if(!$obj) {
				$obj = Versioned::get_version($this->BuyableClassName, $this->BuyableID, $this->Version);
				$obj->Title .= _t("OrderItem.ORDERITEMNOLONGERAVAILABLE", " - NO LONGER AVAILABLE");
			}
		}
		elseif($this->Version) {
			$obj = Versioned::get_version($this->BuyableClassName, $this->BuyableID, $this->Version);
		}
		if ($turnTranslatableBackOn) {
			Translatable::enable_locale_filter();
		}
		return $obj;
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
	 * @return String
	  **/
	function ProductTitle() {
		user_error("This function has been replaced by BuyableTitle", E_USER_NOTICE);
		return $this->BuyableTitle();
	}



	##########################
	## LINKS                ##
	##########################

	/**
	 *
	 * @return String (URLSegment)
	  **/
	function Link() {
		$item = $this->Buyable();
		if($item) {
			$order = $this->Order();
			if($order && $order->IsSubmitted()) {
				return
					"/". EcommerceConfig::get("ShoppingCart_Controller", "url_segment").
					"/submittedbuyable".
					"/".$item->ClassName.
					"/".$item->ID.
					"/".$item->Version."/";
			}
			else {
				return $item->Link();
			}
		}
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
		return ShoppingCart_Controller::add_item_link($this->BuyableID, $this->BuyableClassName,$this->linkParameters());
	}

	/**
	 *
	 * @return String (URLSegment)
	 **/
	function IncrementLink() {
		return ShoppingCart_Controller::add_item_link($this->BuyableID, $this->BuyableClassName,$this->linkParameters());
	}

	/**
	 *
	 * @return String (URLSegment)
	 **/
	function DecrementLink() {
		return ShoppingCart_Controller::remove_item_link($this->BuyableID, $this->BuyableClassName,$this->linkParameters());
	}

	/**
	 *
	 * @return String (URLSegment)
	 **/
	function RemoveLink() {
		return ShoppingCart_Controller::remove_item_link($this->BuyableID, $this->BuyableClassName,$this->linkParameters());
	}

	/**
	 *
	 * @return String (URLSegment)
	 **/
	function RemoveAllLink() {
		return ShoppingCart_Controller::remove_all_item_link($this->BuyableID, $this->BuyableClassName,$this->linkParameters());
	}

	/**
	 *
	 * @return String (URLSegment)
	 **/
	function RemoveAllAndEditLink() {
		return ShoppingCart_Controller::remove_all_item_and_edit_link($this->BuyableID, $this->BuyableClassName,$this->linkParameters());
	}

	/**
	 *
	 * @return String (URLSegment)
	 **/
	function SetSpecificQuantityItemLink($quantity) {
		return ShoppingCart_Controller::set_quantity_item_link($this->BuyableID, $this->BuyableClassName, array_merge($this->linkParameters(), array("quantity" => $quantity)));
	}

	/**
	 * @Todo: do we still need this?
	 * @return array for use as get variables in link
	 **/
	protected function linkParameters(){
		$array = array();
		$this->extend('updateLinkParameters',$array);
		return $array;
	}

}
