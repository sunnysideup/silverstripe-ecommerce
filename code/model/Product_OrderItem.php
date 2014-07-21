<?php

class Product_OrderItem extends OrderItem {

	/**
	 * standard SS method
	 * @var Array
	 */
	private static $api_access = array(
		'view' => array(
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
			'Order',
			'InternalItemID'
		)
	);


	/**
	 * Overloaded Product accessor method.
	 *
	 * Overloaded from the default has_one accessor to
	 * retrieve a product by it's version, this is extremely
	 * useful because we can set in stone the version of
	 * a product at the time when the user adds the item to
	 * their cart, so if the CMS admin changes the price, it
	 * remains the same for this order.
	 *
	 * @param boolean $current If set to TRUE, returns the latest published version of the Product,
	 * 								If set to FALSE, returns the set version number of the Product
	 * 						 		(instead of the latest published version)
	 * @return Product object
	 */
	public function Product($current = false) {
		return $this->Buyable($current);
	}

	/**
	 * @param OrderItem $orderItem
	 * @return Boolean
	 **/
	function hasSameContent(OrderItem $orderItem) {
		$parentIsTheSame = parent::hasSameContent($orderItem);
		return $parentIsTheSame && is_a($orderItem, $this->class);
	}

	/**
	 * @param Boolean $recalculate
	 * @return Float
	 **/
	function UnitPrice($recalculate = false) {return $this->getUnitPrice($recalculate);}
	function getUnitPrice($recalculate = false) {
		$unitPrice = 0;
		if($this->priceHasBeenFixed($recalculate) && !$recalculate) {
			$unitPrice = parent::getUnitPrice($recalculate);
		}
		elseif($product = $this->Product()){
			if(!isset(self::$calculated_buyable_price[$this->ID]) || $recalculate) {
				self::$calculated_buyable_price[$this->ID] = $product->getCalculatedPrice();
			}
			$unitPrice = self::$calculated_buyable_price[$this->ID];
		}
		else {
			$unitPrice = 0;
		}
		$updatedUnitPrice = $this->extend('updateUnitPrice',$unitPrice);
		if($updatedUnitPrice !== null) {
			if(is_array($updatedUnitPrice) && count($updatedUnitPrice)) {
				$unitPrice = $updatedUnitPrice[0];
			}
		}
		return $unitPrice;
	}

	/**
	 *@return String
	 **/
	function TableTitle() {return $this->getTableTitle();}
	function getTableTitle() {
		$tableTitle = _t("Product.UNKNOWN", "Unknown Product");
		if($product = $this->Product()) {
			Config::nest();
			Config::inst()->update('SSViewer', 'theme_enabled', true);
			$tableTitle = strip_tags($product->renderWith("ProductTableTitle"));
			Config::unnest();
		}
		$updatedTableTitle = $this->extend('updateTableTitle',$tableTitle);
		if($updatedTableTitle) {
			if(is_array($updatedTableTitle)) {
				$tableTitle = implode($updatedTableTitle);
			}
			else {
				$tableTitle = $updatedTableTitle;
			}
		}
		return $tableTitle;
	}

	/**
	 *@return String
	 **/
	function TableSubTitle() {return $this->getTableSubTitle();}
	function getTableSubTitle() {
		$tableSubTitle = '';
		if($product = $this->Product()) {
			$tableSubTitle = $product->Quantifier;
		}
		$updatedSubTableTitle = $this->extend('updateSubTableTitle',$tableSubTitle);
		if($updatedSubTableTitle) {
			if(is_array($updatedSubTableTitle)) {
				$tableSubTitle = implode($updatedSubTableTitle);
			}
			else {
				$tableSubTitle = $updatedSubTableTitle;
			}
		}
		return $tableSubTitle;
	}



	/**
	 * method for developers only
	 * you can access it like this: /shoppingcart/debug/
	 * @return String
	 */
	public function debug() {
		$title = $this->TableTitle();
		$productID = $this->BuyableID;
		$productVersion = $this->Version;
		$html = parent::debug() .<<<HTML
			<h3>Product_OrderItem class details</h3>
			<p>
				<b>Title : </b>$title<br/>
				<b>Product ID : </b>$productID<br/>
				<b>Product Version : </b>$productVersion
			</p>
HTML;
		$this->extend('updateDebug',$html);
		return $html;
	}

}



