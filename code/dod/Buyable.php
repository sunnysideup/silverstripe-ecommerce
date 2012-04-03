<?php

/**
 * @description: This class extends any DataObject (e.g. Product), including SiteTree items
 * once extended, any "buyable" item can be added to cart.
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: products
 *
 **/

class Buyable extends DataObjectDecorator {

	/**
	 * tells is if a classanme is a buyable
	 * @param String $className - name of the class to be tested
	 * @return Boolean
	 */
	static function is_buyable($className) {
		$buyablesArray = EcommerceConfig::get("Buyable", "array_of_buyables");
		if(in_array($className, $buyablesArray)) {
			return true;
		}
		else {
			$array = array_reverse(ClassInfo::ancestry($className));
			foreach($array as $className) {
				if(in_array($className, $buyablesArray)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * static variable that "remembers" if the shop is closed.
	 * @var Boolean
	 **/
	private static $shop_closed = null;

	/**
	 * Return the currency being used on the site.
	 * @return string Currency code, e.g. "NZD" or "USD"
	 */
	function Currency() {
		if(class_exists('Payment')) {
			return Payment::site_currency();
		}
	}

	/*
	 * @Depreciated - use canPurchase instead
	 */
	function AllowPurchase() {
		user_error("this method has been Depreciated - use canPurchase", E_USER_NOTICE);
		return $this->owner->canPurchase();
	}


	function canPurchase($member = null) {
		if($this->ShopClosed()) {
			return false;
		}
		//IMPORTANT - if it returns null then the product / other buyable will not take notice of this extension.
		return true;
	}

	/**
	 *@return Boolean
	 **/
	function ShopClosed() {
		//CACHING!
		if(self::$shop_closed === null) {
			$sc = DataObject::get_one("SiteConfig");
			if($sc) {
				self::$shop_closed = $sc->ShopClosed;
			}
		}
		return self::$shop_closed;
	}

	/**
	 * alternative method for ShopClosed with the more standard IsBlaBla syntax
	 *@return Boolean
	 **/
	function IsClosedShop() {
		return $this->ClosedShop();
	}

	/**
	 * Returns true if the buyable is already in the shopping cart with a quantity over zero.
	 * Note : This function is usable in the Product context because a
	 * Product_OrderItem only has a Product object in attribute
	 *
	 * @return boolean
	 */
	function IsInCart(){
		return ($this->owner->OrderItem() && $this->owner->OrderItem()->Quantity > 0) ? true : false;
	}

	/**
	 * returns the order item associated with the buyable.
	 * ALWAYS returns one, even if there is none in the cart.
	 * Does not write to database.
	 *@return OrderItem (no kidding)
	 **/
	function OrderItem() {
		$filter = "";
		$className = $this->owner->ClassName;
		$this->owner->extend('updateItemFilter',$filter);
		$item = ShoppingCart::singleton()->findOrMakeItem($this->owner, $filter);
		$this->owner->extend('updateDummyItem',$item);
		return $item; //return dummy item so that we can still make use of Item
	}

	//passing on shopping cart links ...is this necessary?? ...why not just pass the cart?
	function AddLink() {
		return ShoppingCart_Controller::add_item_link($this->owner->ID, $this->owner->ClassName, $this->linkParameters());
	}
	function IncrementLink() {
		//we can do this, because by default add link adds one
		return $this->AddLink();
	}
	function DecrementLink() {
		//we can do this, because by default remove link removes on
		return $this->RemoveLink();
	}

	/**
	 * remove one buyable's orderitem from cart
	 * @return String (Link)
	 */
	function RemoveLink() {
		return ShoppingCart_Controller::remove_item_link($this->owner->ID, $this->owner->ClassName, $this->linkParameters());
	}

	/**
	 * remove all of this buyable's orderitem from cart
	 * @return String (Link)
	 */
	function RemoveAllLink() {
		return ShoppingCart_Controller::remove_all_item_link($this->owner->ID, $this->owner->ClassName, $this->linkParameters());
	}


	/**
	 * remove all of this buyable's orderitem from cart and go through to this buyble to add alternative selection.
	 * @return String (Link)
	 */
	function RemoveAllAndEditLink() {
		return ShoppingCart_Controller::remove_all_item_and_edit_link($this->owner->ID, $this->owner->ClassName, $this->linkParameters());
	}


	/**
	 * set new quantity for buyable's orderitem
	 * @return String (Link)
	 */
	function SetQuantityItemLink() {
		return ShoppingCart_Controller::set_quantity_item_link($this->owner->ID, $this->owner->ClassName, $this->linkParameters());
	}


	/**
	 * returns the instance of EcommerceConfigAjax for use in templates.
	 * In templates, it is used like this:
	 * $EcommerceConfigAjax.TableID
	 *
	 * @return EcommerceConfigAjax
	 **/
	public function AJAXDefinitions() {
		return EcommerceConfigAjax::get_one($this->owner);
	}

	/**
	 * set new specific new quantity for buyable's orderitem
	 * @param double
	 * @return String (Link)
	 */
	function SetSpecificQuantityItemLink($quantity) {
		return ShoppingCart_Controller::set_quantity_item_link($this->owner->ID, $this->owner->ClassName, array_merge($this->linkParameters(), array("quantity" => $quantity)));
	}

	/**
	 *@return Array
	 **/
	protected function linkParameters(){
		$array = array();
		$this->owner->extend('updateLinkParameters',$array);
		return $array;
	}


	/**
	 * you can overwrite this function in your buyable items (such as Product)
	 *@return String
	 **/
	public function classNameForOrderItem($buyableClassName = '') {
		if(!$buyableClassName) {
			$buyableClassName = $this->owner->ClassName;
		}
		$orderItemPostFix = EcommerceConfig::get("Buyable", "order_item_class_name_post_fix");
		$className = $buyableClassName.$orderItemPostFix;
		if(class_exists($className)) {
			return $className;
		}
		else {
			//lets try the parent... in case product has been extended....
			if(class_exists(get_parent_class($this->owner))) {
				$parentClassName = get_parent_class($this->owner);
				return $this->classNameForOrderItem($parentClassName);
			}
		}
	}


	//CRUD SETTINGS
	function canEdit($memberID) {
		if(Permission::checkMember($memberID, "SHOPADMIN")) {
			return true;
		}
		return null;
	}

	function canDelete($memberID) {
		return $this->canEdit($memberID);
	}

	public function canPublish($memberID) {
		return $this->canEdit($memberID);
	}

	public function canDeleteFromLive($memberID) {
		return $this->canEdit($memberID);
	}

	public function canCreate($memberID) {
		return $this->canEdit($memberID);
	}

}
