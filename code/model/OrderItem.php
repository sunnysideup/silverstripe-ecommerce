<?php
/**
 * @description: An order item is a product which has been added to an order.
 * An order item links to a Buyable (product) by class name
 * That is, we only store the BuyableID and the ClassName
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class OrderItem extends OrderAttribute {

	/**
	 * what variables are accessible through  http://mysite.com/api/ecommerce/v1/OrderItem/
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
	 * stardard SS variable
	 * @var array
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
		'UnitPriceAsMoney' => 'Money',
		'Total' => 'Currency',
		'TotalAsMoney' => 'Money',
		'InternalItemID' => 'Varchar',
		'Link' => 'Varchar',
		'AbsoluteLink' => 'Varchar'
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
	 * Standard SS variable.
	 * @var String
	 */
	public static $description = "Any item that is added to an order and sits before the sub-total. ";

	/**
	 * HACK: Versioned is BROKEN this method helps in fixing it.
	 * Basically, in Versioned, you get a hard-coded error
	 * when you retrieve an older version of a DataObject.
	 * This method returns null if it does not exist.
	 *
	 * Idea is from Jeremy: https://github.com/burnbright/silverstripe-shop/blob/master/code/products/FixVersioned.php
	 * @param String $class
	 * @param Int $id
	 * @param Int $version
	 * @return DataObject | Null
	 */

	public static function get_version($class, $id, $version) {
		$oldMode = Versioned::get_reading_mode();
		Versioned::set_reading_mode('');
		$versionedObject = Versioned::get_version($class, $id, $version);
		Versioned::set_reading_mode($oldMode);
		return $versionedObject;
	}

	/**
	 * Standard SS method
	 * @var String
	 */
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->replaceField("BuyableID", new HiddenField("BuyableID"));
		$fields->replaceField("BuyableClassName", new HiddenField("BuyableClassName"));
		$fields->replaceField("Version", new HiddenField("Version"));
		if($this->OrderID && $this->exists()) {
			$fields->replaceField("OrderID", $fields->dataFieldByName("OrderID")->performReadonlyTransformation());
		}
		else {
			$fields->replaceField("OrderID", new NumericField("OrderID", _t("Order.SINGULARNAME", "Order")));
		}
		$fields->removeByName("Sort");
		$fields->removeByName("CalculatedTotal");
		$fields->removeByName("GroupSort");
		$fields->removeByName("OrderAttribute_GroupID");
		if($order = $this->Order()) {
			if(!$order->IsSubmitted()) {
				$fields->addFieldToTab("Root.Main", new BuyableSelectField("FindBuyable", _t("OrderItem.SELECITEM", "Select Item"), $this->Buyable()));
			}
			else {
				$fields->addFieldToTab(
					"Root.Main",
					new ReadonlyField("TableTitle", _t("OrderItem.TITLE", "Title"), $this->TableSubTitle()),
					"Quantity"
				);
				$fields->addFieldToTab(
					"Root.Main",
					new ReadonlyField("TableSubTitleNOHTML", _t("OrderItem.SUB_TITLE", "Sub Title"), $this->TableSubTitleNOHTML()),
					"Quantity"
				);
			}
		}
		return $fields;
	}

	/**
	 * standard SS method
	 * @param Member $member
	 * @return Boolean
	 **/
	function canDelete($member = null) {
		return $this->canEdit($member);
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
	 * 	'fieldClasses': Associative array of field names as keys and FormField classes as values
	 * 	'restrictFields': Numeric array of a field name whitelist
	 * @return FieldList
	 */
	public function scaffoldSearchFields($_params = null) {
		$fields = parent::scaffoldSearchFields($_params);
		$fields->replaceField("OrderID", new NumericField("OrderID", "Order Number"));
		return $fields;
	}

	/**
	 * standard SS method
	 * @param BuyableModel $buyable
	 * @param Double $quantity
	 * @return FieldList
	 **/
	public function addBuyableToOrderItem(BuyableModel $buyable, $quantity = 1) {
		$this->Version = $buyable->Version;
		$this->BuyableID = $buyable->ID;
		$this->BuyableClassName = $buyable->ClassName;
		$this->Quantity = $quantity;
	}

	/**
	 * used to return data for ajax
	 * @param Array
	 * @return Array used to create JSON for AJAX
	  **/
	function updateForAjax(array $js) {
		$function = EcommerceConfig::get('OrderItem', 'ajax_total_format');
		if(is_array($function)) {
			list($function, $format) = $function;
		}
		$total = $this->$function();
		if(isset($format)) {
			$total = $total->$format();
		}
		$ajaxObject = $this->AJAXDefinitions();
		if($this->Quantity) {
			$js[] = array(
				't' => 'id',
				's' => $ajaxObject->TableID(),
				'p' => 'hide',
				'v' => 0
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
		return $js;
	}

	/**
	 * saves details about the Order Item before the order is submittted
	 * @param Bool $recalculate - run it, even if it has run already
	 **/
	function runUpdate($recalculate = false){
		if (isset($_GET['debug_profile'])) Profiler::mark('OrderItem::runUpdate-for-'.$this->ClassName);
		$buyable = $this->Buyable(true);
		if($buyable && $buyable->canPurchase()) {
			$oldValue = $this->CalculatedTotal - 0;
			$newValue = ($this->getUnitPrice() * $this->Quantity) - 0;
			if((round($newValue, 5) != round($oldValue, 5) ) || $recalculate) {
				$this->CalculatedTotal = $newValue;
				$this->write();
			}
		}
		else {
			//if it can not be purchased or it does not exist
			//then we do not accept it!!!!
			$this->delete();
		}
		if (isset($_GET['debug_profile'])) Profiler::unmark('OrderItem::runUpdate-for-'.$this->ClassName);
	}

	/**
	 * Standard SS method.
	 * If the quantity is zero then we set it to 1.
	 * TODO: evaluate this rule.
	 */
	function onBeforeWrite() {
		if(!$this->exists()) {
			if($buyable = $this->Buyable(true)) {
				if($this->ClassName == "OrderItem" && $this->BuyableClassName != "OrderItem") {
					$this->setClassName($buyable->classNameForOrderItem());
				}
			}
		}
		//now we can do the parent thing
		parent::onBeforeWrite();
		//always keep quantity above 0
		if(floatval($this->Quantity) == 0) {
			$this->Quantity = 1;
		}
		if(!$this->Version && $buyable = $this->Buyable(true)) {
			$this->Version = $buyable->Version;
		}
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
				//this adds the modifiers and automatically WRITES AGAIN - WATCH RACING CONDITIONS!
				$order->init(true);
			}
		}
	}


	/**
	 * Check if two Order Items are the same.
	 * Useful when adding two items to cart.
	 *
	 * @param OrderItem $orderItem
	 * @return Boolean
	  **/
	function hasSameContent(OrderItem $orderItem) {
		return
			$orderItem instanceof OrderItem &&
			$this->BuyableID == $orderItem->BuyableID &&
			$this->BuyableClassName == $orderItem->BuyableClassName &&
			$this->Version == $orderItem->Version;
	}




	######################
	## TEMPLATE METHODS ##
	######################

	protected static $calculated_buyable_price = array();
		public static function reset_calculated_buyable_price(){self::$calculated_buyable_price = array(); }

	public function UnitPrice($recalculate = false) {return $this->getUnitPrice($recalculate);}
	public function getUnitPrice($recalculate = false) {
		//to do: what is the logic here???
		if($this->priceHasBeenFixed($recalculate) && !$recalculate) {
			if(!$this->Quantity){
				$this->Quantity = 1;
			}
			return $this->CalculatedTotal / $this->Quantity;
		}
		elseif($buyable = $this->Buyable()){
			if(!isset(self::$calculated_buyable_price[$this->ID]) || $recalculate) {
				self::$calculated_buyable_price[$this->ID] = $buyable->getCalculatedPrice();
			}
			$unitPrice = self::$calculated_buyable_price[$this->ID];
		}
		else{
			$unitPrice = 0;
		}
		$updatedUnitPrice = $this->extend('updateUnitPrice',$price);
		if($updatedUnitPrice !== null) {
			if(is_array($updatedUnitPrice) && count($updatedUnitPrice)) {
				$unitPrice = $updatedUnitPrice[0];
			}
		}
		return $unitPrice;
	}

	public function UnitPriceAsMoney($recalculate = false) {return $this->getUnitPriceAsMoney($recalculate);}
	public function getUnitPriceAsMoney($recalculate = false) {
		return EcommerceCurrency::get_money_object_from_order_currency($this->getUnitPrice($recalculate), $this->Order());
	}


	/**
	 *
	 * @param Boolean $recalculate - forces recalculation of price
	 * @return Float
	 */
	function Total($recalculate = false){return $this->getTotal();}
	function getTotal($recalculate = false) {
		if($this->priceHasBeenFixed()) {
			//get from database
			$total = $this->CalculatedTotal;
		}
		else {
			$total = $this->getUnitPrice($recalculate) * $this->Quantity;
		}
		$updatedTotal = $this->extend('updateTotal', $total);
		if($updatedTotal !== null) {
			if(is_array($updatedTotal) && count($updatedTotal)) {
				$total = $$updatedTotal[0];
			}
		}
		return $total;
	}

	/**
	 *
	 * @param Boolean $recalculate - forces recalculation of price
	 * @return Money
	 */
	public function TotalAsMoney($recalculate = false) {return $this->getTotalAsMoney($recalculate);}
	public function getTotalAsMoney($recalculate = false) {
		return EcommerceCurrency::get_money_object_from_order_currency($this->getTotal($recalculate), $this->Order());
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
		return DBField::create_field('Currency',$this->Total());
	}




	##########################
	## OTHER LOOKUP METHODS ##
	##########################

	/**
	 * Helps in speeding up code.
	 * This can be a static variable as it is the same for all OrderItems for an Order.
	 * @var Boolean
	 */
	protected static $price_has_been_fixed = array();
		public static function reset_price_has_been_fixed() {self::$price_has_been_fixed = array(); }

	/**
	 * @description - tells you if an order item price has been "fixed"
	 * meaning that is has been saved in the CalculatedTotal field so that
	 * it can not be altered.
	 *
	 * Default returns false; this is good for uncompleted orders
	 * but not so good for completed ones.
	 *
	 * @return Boolean
	 **/
	protected function priceHasBeenFixed($recalculate = false){
		if(!isset(self::$price_has_been_fixed[$this->OrderID]) || $recalculate) {
			self::$price_has_been_fixed[$this->OrderID] = false;
			if($order = $this->Order()) {
				if( $order->IsSubmitted()) {
					self::$price_has_been_fixed[$this->OrderID] = true;
					if($recalculate) {
						user_error("You are trying to recalculate an order that is already submitted.", E_USER_NOTICE);
					}
				}
			}
		}
		return self::$price_has_been_fixed[$this->OrderID];
	}


	/**
	 * Store for buyables.
	 * We store this here to speed up things a little
	 * Format is like this
	 * Array(
	 *  0 => Buyable (versioned)
	 *  1 => Buyable (current)
	 * );
	 * @var Array
	 */
	protected $tempBuyableStore = array();

	/**
	 *
	 * @param Boolean $current - is this a current one, or an older VERSION ?
	 * @return DataObject (Any type of Data Object that is buyable)
	  **/
	function Buyable($current = false) {
		$tempBuyableStoreType = $current ? 1 : 0;
		if(!isset($this->tempBuyableStore[$tempBuyableStoreType])) {
			if(!$this->BuyableID) {
				return null;
			}
			//start hack
			if(!$this->BuyableClassName) {
				$this->BuyableClassName = str_replace("_OrderItem", "", $this->ClassName);
			}
			$turnTranslatableBackOn = false;
			$className = $this->BuyableClassName;
			if ($className::has_extension($this->class, 'Translatable')) {
				Translatable::disable_locale_filter();
				$turnTranslatableBackOn = true;
			}
			//end hack!
			$obj = null;
			if($current) {
				$obj = $className::get()->byID($this->BuyableID);
			}
			//run if current not available or current = false

			if(((!$obj) || ($obj->exists())) && $this->Version) {
				/* @TODO: check if the version exists?? - see sample below
				$versionTable = $this->BuyableClassName."_versions";
				$dbConnection = DB::getConn();
				if($dbConnection && $dbConnection instanceOf MySQLDatabase && $dbConnection->hasTable($versionTable)) {
					$result = DB::query("
						SELECT COUNT(\"ID\")
						FROM \"$versionTable\"
						WHERE
							\"RecordID\" = ".intval($this->BuyableID)."
							AND \"Version\" = ".intval($this->Version)."
					");
					if($result->value()) {
				 */
				$obj = OrderItem::get_version($this->BuyableClassName, $this->BuyableID, $this->Version);
			}
			//our last resort
			if((!$obj) || ($obj->exists())) {
				$obj = Versioned::get_latest_version($this->BuyableClassName, $this->BuyableID);
			}
			if ($turnTranslatableBackOn) {
				Translatable::enable_locale_filter();
			}
			$this->tempBuyableStore[$tempBuyableStoreType] = $obj;
		}
		return $this->tempBuyableStore[$tempBuyableStoreType];
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
	function Link() {return $this->getLink();}
	function getLink() {
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
	 * @return String
	 */
	function AbsoluteLink(){return $this->getAbsoluteLink();}
	function getAbsoluteLink(){
		return Director::absoluteURL($this->getLink());
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

	public function debug() {
		$html =  EcommerceTaskDebugCart::debug_object($this);
		$html .= "<ul>";
		$html .= "<li><b>Buyable Price:</b> ".$this->Buyable()->Price." </li>";
		$html .= "<li><b>Buyable Calculated Price:</b> ".$this->Buyable()->CalculatedPrice()." </li>";
		$html .= "</ul>";
		return $html;
	}

}
