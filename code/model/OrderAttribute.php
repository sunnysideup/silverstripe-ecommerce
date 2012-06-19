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
			'TableSubTitleNOHTML',
			'CartTitle',
			'CartSubTitle',
			'Order'
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
		'TableSubTitleNOHTML' => 'Text',
		'CartTitle' => 'HTMLText',
		'CartSubTitle' => 'HTMLText'
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

	public static $singular_name = "Order Entry";
		function i18n_singular_name() { return _t("OrderAttribute.ORDERENTRY", "Order Entry");}

	public static $plural_name = "Order Extra Descriptions";
		function i18n_plural_name() { return _t("OrderAttribute.ORDERENTRIES", "Order Entries");}

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
	 * returns the instance of EcommerceConfigAjax for use in templates.
	 * In templates, it is used like this:
	 * $EcommerceConfigAjax.TableID
	 *
	 * @return EcommerceConfigAjax
	 **/
	public function AJAXDefinitions() {
		return EcommerceConfigAjax::get_one($this);
	}

	/**
	 * returns the instance of EcommerceDBConfig
	 *
	 * @return EcommerceDBConfig
	 **/
	public function EcomConfig(){
		return EcommerceDBConfig::current_ecommerce_db_config();
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
		return $this->i18n_singular_name();
	}

	/**
	 * Return a name of what this attribute is
	 * called e.g. "Product 21" or "Discount"
	 * Cart is a short version of table
	 * @return string
	 */
	public function CartTitle() {return $this->getCartTitle();}
	function getCartTitle() {
		return $this->TableTitle();
	}

	/**
	 * the sub title for the order item or order modifier
	 * @return String
	  **/
	function TableSubTitle() {return $this->getTableSubTitle();}
	function getTableSubTitleNOHTML() {return strip_tags($this->getTableSubTitle());}
	function getTableSubTitle() {
		return "";
	}

	/**
	 * the sub title for the order item or order modifier.
	 * Cart is a short version of table
	 * @return String
	  **/
	function CartSubTitle() {return $this->getCartSubTitle();}
	function getCartSubTitle() {
		return $this->TableSubTitle();
	}

	function onBeforeWrite() {
		parent::onBeforeWrite();
		if($this->OrderAttribute_GroupID) {
			if($group = $this->OrderAttribute_GroupID()) {
				$this->GroupSort = $group->Sort;
			}
		}
	}

	/**
	 * Debug helper method.
	 */
	public function debug() {
		$html =  "
			<h2>".$this->ClassName."</h2><ul>";
		$fields = Object::get_static($this->ClassName, "db");
		foreach($fields as  $key => $type) {
			$html .= "<li><b>$key ($type):</b> ".$this->$key."</li>";
		}
		$fields = Object::get_static($this->ClassName, "casting");
		foreach($fields as  $key => $type) {
			$method = "get".$key;
			$html .= "<li><b>$key ($type):</b> ".$this->$method()." </li>";
		}
		$html .= "</ul>";
		return $html;
	}

}


