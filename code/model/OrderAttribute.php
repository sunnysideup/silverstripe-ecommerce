<?php
/**
 * @description: base class for OrderItem (item in cart) and OrderModifier (extra - e.g. Tax)
 * @see OrderModifier
 * @see OrderItem
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: model
 **/

class OrderAttribute extends DataObject {

	/**
	 * what variables are accessible through  http://mysite.com/api/v1/ShippingAddress/
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
				"Order"
			)
	 );

	public static $db = array(
		'CalculatedTotal' => 'Currency',
		'Sort' => 'Int',
		'GroupSort' => 'Int'
	);

	public static $has_one = array(
		'Order' => 'Order'
	);

	public static $casting = array(
		'TableTitle' => 'HTMLText',
		'TableSubTitle' => 'HTMLText',
		'CartTitle' => 'HTMLText' //shorter version of table table
	);

	public static $create_table_options = array(
		'MySQLDatabase' => 'ENGINE=InnoDB'
	);

	/**
	* @note: we can add the \"OrderAttribute_Group\".\"Sort\" part because this table is always included (see extendedSQL).
	**/
	public static $default_sort = "\"OrderAttribute\".\"GroupSort\" ASC, \"OrderAttribute\".\"Sort\" ASC, \"OrderAttribute\".\"Created\" ASC";

	public static $indexes = array(
		"Sort" => true,
	);

	/**
	 * save edit status for speed's sake
	 * @var Boolean
	 */
	protected $_canEdit = null;

	/**
	 * save view status for speed's sake
	 * @var Boolean
	 */
	protected $_canView = null;

	function init() {
		return true;
	}


	/**
	 * standard SS method
	 * @return Boolean
	 **/
	function canCreate($member = null) {
		return true;
	}

	/**
	 * Standard SS method
	 * This is an important method.
	 *
	 * @return Boolean
	 **/
	function canView($member = null) {
		if($this->_canView === null) {
			$this->_canView = false;
			if($this->OrderID) {
				if($this->Order()->exists()) {
					if($this->Order()->canView($member)) {
						$this->_canView = true;
					}
				}
			}
		}
		return $this->_canView;
	}

	/**
	 * Standard SS method
	 * This is an important method.
	 *
	 * @return Boolean
	 **/
	function canEdit($member = null) {
		if($this->_canEdit === null) {
			$this->_canEdit = false;
			if($this->OrderID) {
				if($this->Order()->exists()) {
					if($this->Order()->canEdit($member)) {
						$this->_canEdit = true;
					}
				}
			}
		}
		return $this->_canEdit;
	}

	/**
	 * Standard SS method
	 * @return Boolean
	 **/
	function canDelete($member = null) {
		return false;
	}

	/**
	 * @return Boolean (true on success / false on failure)
	 **/
	public function addBuyableToOrderItem($object) {
		//more may be added here in the future
		return true;
	}

	######################
	## TEMPLATE METHODS ##
	######################

	/**
	 * Return a string of class names, in order
	 * of heirarchy from OrderAttribute for the
	 * current attribute.
	 *
	 * e.g.: "product_orderitem orderitem
	 * orderattribute".
	 *
	 * Used by the templates and for ajax updating functionality.
	 *
	 * @return string
	 */
	function Classes() {
		$class = get_class($this);
		$classes = array();
		$classes[] = strtolower($class);
		while(get_parent_class($class) != 'DataObject' && $class = get_parent_class($class)) {
			$classes[] = strtolower($class);
		}
		return implode(' ', $classes);
	}

	/**
	 *@return String for use in the Templates
	 **/
	function MainID() {
		return get_class($this) . '_' .'DB_' . $this->ID;
	}

	/**
	 *@return String for use in the Templates
	 **/
	function TableID() {
		return EcommerceConfig::get("Order", "template_id_prefix") . $this->MainID();
	}

	/**
	 *@return String for use in the Templates
	 **/
	function CartID() {
		return $this->TableID()."_Cart";
	}

	/**
	 *@return String for use in the Templates
	 **/
	function TableTitleID() {
		return $this->TableID() . '_Title';
	}

	/**
	 *@return String for use in the Templates
	 **/
	function CartTitleID() {
		return $this->TableTitleID()."_Cart";
	}

	/**
	 *@return String for use in the Templates
	 **/
	function TableTotalID() {
		return $this->TableID() . '_Total';
	}

	/**
	 *@return String for use in the Templates
	 **/
	function CartTotalID() {
		return $this->TableTotalID()."_Cart";
	}
	/**
	 *Should this item be shown on check out page table?
	 *@return Boolean
	 **/
	function ShowInTable() {
		return true;
	}

	/**
	 *Should this item be shown on in the cart (which is on other pages than the checkout page)
	 *@return Boolean
	 **/
	function ShowInCart() {
		return $this->ShowInTable();
	}

	/**
	 * Return a name of what this attribute is
	 * called e.g. "Product 21" or "Discount"
	 *
	 * @return string
	 */
	function TableTitle(){return $this->getTableTitle();}
	function getTableTitle() {
		return 'Attribute';
	}

	/**
	 * Return a name of what this attribute is
	 * called e.g. "Product 21" or "Discount"
	 *
	 * @return string
	 */
	public function CartTitle() {return $this->getCartTitle();}
	function getCartTitle() {
		return $this->TableTitle();
	}

	function onBeforeWrite() {
		parent::onBeforeWrite();
		if($this->OrderAttribute_GroupID) {
			if($group = $this->OrderAttribute_GroupID()) {
				$this->GroupSort = $group->Sort;
			}
		}
	}
	function onAfterWrite() {
		parent::onAfterWrite();
	}

	function onAfterDelete() {
		parent::onAfterDelete();
	}

}


