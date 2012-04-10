<?php
/**
 * This is a standard Product page-type with fields like
 * Price, Weight, Model and basic management of
 * groups.
 *
 * It also has an associated Product_OrderItem class,
 * an extension of OrderItem, which is the mechanism
 * that links this page type class to the rest of the
 * eCommerce platform. This means you can add an instance
 * of this page type to the shopping cart.
 *
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: products
 *
 **/


class Product extends Page {


	public static $api_access = array(
		'view' => array(
				"MenuTitle",
				"AllowPurchase",
				"InternalItemID"
			)
	 );

	public static $db = array(
		'Price' => 'Currency',
		'Weight' => 'Decimal(9,4)',
		'Model' => 'Varchar(30)',
		'Quantifier' => 'Varchar(255)',
		'FeaturedProduct' => 'Boolean',
		'AllowPurchase' => 'Boolean',
		'InternalItemID' => 'Varchar(30)', //ie SKU, ProductID etc (internal / existing recognition of product)
		'NumberSold' => 'Int' //store number sold, so it doesn't have to be computed on the fly. Used for determining popularity.
	);

	public static $has_one = array(
		'Image' => 'Product_Image'
	);

	public static $many_many = array(
		'ProductGroups' => 'ProductGroup'
	);

	public static $defaults = array(
		'AllowPurchase' => true
	);

	public static $summary_fields = array(
		'ID',
		'InternalItemID',
		'Title',
		'Price',
		'NumberSold'
	);

	public static $searchable_fields = array(
		'Title' => "PartialMatchFilter",
		'InternalItemID' => "PartialMatchFilter",
		'ShowInSearch',
		'AllowPurchase',
		'FeaturedProduct',
		'Price'
	);

	public static $casting = array(
		"CalculatedPrice" => "Currency"
	);

	public static $singular_name = "Product";
		function i18n_singular_name() { return _t("Order.PRODUCT", "Product");}

	public static $plural_name = "Products";
		function i18n_plural_name() { return _t("Order.PRODUCTS", "Products");}

	public static $default_parent = 'ProductGroup';

	public static $default_sort = '"Title" ASC';

	public static $icon = 'ecommerce/images/icons/product';

	function CalculatedPrice() {return $this->getCalculatedPrice();}
	function getCalculatedPrice() {
		$price = $this->Price;
		$this->extend('updateCalculatedPrice',$price);
		return $price;
	}

	function getCMSFields() {
		//prevent calling updateCMSFields extend function too early
		$siteTreeFieldExtensions = $this->get_static('SiteTree','runCMSFieldsExtensions');
		$this->disableCMSFieldsExtensions();
		$fields = parent::getCMSFields();
		if($siteTreeFieldExtensions) {
			$this->enableCMSFieldsExtensions();
		}
		$sc = SiteConfig::current_site_config();
		$fields->replaceField('Root.Content.Main', new HTMLEditorField('Content', _t('Product.DESCRIPTION', 'Product Description'), 3));
		//NOTE: IMAGE FIELD WAS GIVING ERRORS IN ModelAdmin
		//$fields->addFieldToTab('Root.Content.Images', new TreeDropdownField('ImageID', _t('Product.IMAGE', 'Product Image'), "Image"));
		$fields->addFieldToTab('Root.Content.Images', new ImageField('Image', _t('Product.IMAGE', 'Product Image')));
		$fields->addFieldToTab('Root.Content.Details',new CheckboxField('AllowPurchase', _t('Product.ALLOWPURCHASE', 'Allow product to be purchased'), 1));
		$fields->addFieldToTab('Root.Content.Details',new CheckboxField('FeaturedProduct', _t('Product.FEATURED', 'Featured Product')));
		$fields->addFieldToTab('Root.Content.Details',new NumericField('Price', _t('Product.PRICE', 'Price'), '', 12));
		$fields->addFieldToTab('Root.Content.Details',new TextField('InternalItemID', _t('Product.CODE', 'Product Code'), '', 30));
		if($sc->ProductsHaveWeight) {
			$fields->addFieldToTab('Root.Content.Details',new NumericField('Weight', _t('Product.WEIGHT', 'Weight')));
		}
		if($sc->ProductsHaveModelNames) {
			$fields->addFieldToTab('Root.Content.Details',new TextField('Model', _t('Product.MODEL', 'Model')));
		}
		if($sc->ProductsHaveQuantifiers) {
			$fields->addFieldToTab('Root.Content.Details',new TextField('Quantifier', _t('Product.QUANTIFIER', 'Quantifier (e.g. per kilo, per month, per dozen, each)')));
		}
		if($sc->ProductsAlsoInOtherGroups) {
			$fields->addFieldsToTab(
				'Root.Content.AlsoShowHere',
				array(
					new HeaderField('ProductGroupsHeader', _t('Product.ALSOSHOWSIN', 'Also shows in ...')),
					$this->getProductGroupsTable()
				)
			);
		}
		if($siteTreeFieldExtensions) {
			$this->extend('updateCMSFields', $fields);
		}
		return $fields;
	}


	/**
	 * Returns all the parent groups for the product.
	 * This function has been added her to contrast it with MainParentGroup (see below).
	  *@return DataObjectSet(ProductGroup) or NULL
	 **/
	function AllParentGroup() {
		return $this->ProductGroups();
	}

	/**
	 * Returns the direct parent group for the product.
	 *
	 * @return DataObject(ProductGroup) or NULL
	 **/
	function MainParentGroup() {
		return DataObject::get_by_id("ProductGroup", $this->ParentID);
	}

	/**
	 *@return TreeMultiselectField
	 **/
	protected function getProductGroupsTable() {
		$field = new TreeMultiselectField(
			$name = "ProductGroups",
			$title = _t("Product.THISPRODUCTSHOULDALSOBELISTEDUNDER", "This product is also listed under ..."),
			$sourceObject = "SiteTree",
			$keyField = "ID",
			$labelField = "MenuTitle"
		);
		if($this->ParentID) {
			$filter = create_function('$obj', 'return ( ( $obj InstanceOf ProductGroup) && ($obj->ID != '.$this->ParentID.'));');
			$field->setFilterFunction($filter);
		}
		return $field;
	}

	/**
	 * Conditions for whether a product can be purchased.
	 *
	 * If it has the checkbox for 'Allow this product to be purchased',
	 * as well as having a price, it can be purchased. Otherwise a user
	 * can't buy it.
	 *
	 * Other conditions may be added by decorating with the canPurcahse function
	 *
	 * @return boolean
	 */
	function canPurchase($member = null) {
		//check DB field...
		if(!$this->dbObject('AllowPurchase')->getValue()) {
			return false;
		}
		$allowpurchase = true;
		// Standard mechanism for accepting permission changes from decorators
		$extended = $this->extendedCan('canPurchase', $member);
		if($allowpurchase && $extended !== null) {
			$allowpurchase = $extended;
		}
		return $allowpurchase;
	}

	/**
	 * Returns a link to a default image.
	 * If a default image is set in the site config then this is returned
	 * Otherwise, a standard link is returned
	 * @return String
	 */
	function DefaultImageLink() {
		$sc = SiteConfig::current_site_config();
		if($sc) {
			if($sc->DefaultProductImageID && $sc->DefaultProductImage() ) {
				if($sc->DefaultProductImage()->exists()) {
					return $sc->DefaultProductImage()->Link();
				}
			}
		}
		return "ecommerce/images/productPlaceHolderThumbnail.gif";
	}


	/**
	 *@description: This is used when you add a product to your cart
	 * if you set it to 1 then you can add 0.1 product to cart.
	 * If you set it to -1 then you can add 10, 20, 30, etc.. products to cart.
	 *
	 * @return Int
	 **/
	function QuantityDecimals(){
		return 0;
	}

	/**
	 * returns products in the same group
	 *
	 *@return DataObjectSet
	 **/

	function Siblings() {
		if($this->ParentID) {
			$extension = "";
			if(Versioned::current_stage() == "Live") {
				$extension = "_Live";
			}
			return DataObject::get("Product", "\"ShowInMenus\" = 1 AND \"ParentID\" = ".$this->ParentID." AND \"SiteTree{$extension}\".\"ID\" <> ".$this->ID);
		}
	}

	/**
	 * Tells us the link to select variations
	 * If ajaxified, this controller method (selectvariation)
	 * Will return a html snippet for selecting the variation.
	 * This is useful in the Product Group where you can both
	 * non-variation and variation products to have the same
	 * "add to cart" button.  Using this link you can provide a
	 * pop-up select system for selecting a variation.
	 * @return String
	 */
	function AddVariationsLink() {
		return $this->Link("selectvariation");
	}

	/**
	 * @TODO: complete
	 * @param String $compontent - the has many relationship you are looking at, e.g. OrderAttribute
	 * @return DataObjectSet
	 */
	public function getVersionedComponents($component = "ProductVariations") {
		$baseTable = ClassInfo::baseDataClass(self::$has_many[$component]);
		$query = singleton(self::$has_many[$component])->buildVersionSQL("\"{$baseTable}\".ProductID = {$this->ID} AND \"{$baseTable}\".Version = {$this->Version}");
		$result = singleton(self::$has_many[$component])->buildDataObjectSet($query->execute());
		return $result;
	}

	/**
	 * tells us if the current page is part of e-commerce.
	 * @return Boolean
	 */
	function IsEcommercePage () {
		return true;
	}


	function DummyImage(){
		return new Product_Image();
	}

}


class Product_Controller extends Page_Controller {


	/**
	 *
	 * Standard SS method.
	 */
	function init() {
		parent::init();
		Requirements::themedCSS('Products');
	}

	function index(){
		if(Director::is_ajax()) {
			return $this->renderWith("ProductGroupItemMoreDetail");
		}
		return array();
	}

	/**
	 * returns a form for adding products to cart
	 * @return Form
	 */

	function AddProductForm(){
		if($this->canPurchase()) {
			$farray = array();
			$requiredFields = array();
			$fields = new FieldSet($farray);
			$fields->push(new NumericField('Quantity','Quantity',1)); //TODO: perhaps use a dropdown instead (elimiates need to use keyboard)
			$actions = new FieldSet(
				new FormAction('addproductfromform', _t("Product.ADDLINK","Add this item to cart"))
			);
			$requiredfields[] = 'Quantity';
			$validator = new RequiredFields($requiredfields);
			$form = new Form($this,'AddProductForm',$fields,$actions,$validator);
			return $form;
		}
		else {
			return _t("Product.PRODUCTNOTFORSALE", "Product not for sale");
		}
	}



	/**
	 * executes the AddProductForm
	 *
	 * @return void
	 */

	function addproductfromform($data,$form){
		if(!$this->IsInCart()) {
			$quantity = round($data['Quantity'], $this->QuantityDecimals());
			if(!$quantity) {
				$quantity = 1;
			}
			$product = DataObject::get_by_id("Product", $this->ID);
			if($product) {
				ShoppingCart::singleton()->addBuyable($product,$quantity);
			}
			if($this->IsInCart()) {
				$msg = _t("Product.SUCCESSFULLYADDED","Added to cart.");
				$status = "good";
			}
			else {
				$msg = _t("Product.NOTADDEDTOCART","Not added to cart.");
				$status = "bad";
			}
			if(Director::is_ajax()){
				return ShoppingCart::singleton()->setMessageAndReturn($msg, $status);
			}
			else {
				$form->sessionMessage($msg,$status);
				Director::redirectBack();
			}
		}
		else {
			return new EcomQuantityField($this);
		}
	}


	/**
	 * Action to return specific version of a product.
	 * This is really useful for sold products where you want to retrieve the actual version that you sold.
	 * @param HTTPRequest
	 */
	function viewversion($request){
		$version = intval($request->param("ID"));
		$this->record = Versioned::get_version($this->ClassName, $this->ID, $version);
		return array();
	}

}



class Product_Image extends Image {

	/**
	 *
	 * @return Int
	 */
	public function ThumbWidth() {
		return EcommerceConfig::get("Product_Image", "thumbnail_width");
	}

	/**
	 *
	 * @return Int
	 */
	public function ThumbHeight() {
		return EcommerceConfig::get("Product_Image", "thumbnail_height");
	}


	/**
	 *
	 * @return Int
	 */
	public function ContentWidth() {
		return EcommerceConfig::get("Product_Image", "content_image_width");
	}

	/**
	 *
	 * @return Int
	 */
	public function LargeWidth() {
		return EcommerceConfig::get("Product_Image", "large_image_width");
	}


	/**
	 * @usage can be used in a template like this $Image.Thumbnail.Link
	 * @return GD
	 **/
	function generateThumbnail($gd) {
		$gd->setQuality(80);
		return $gd->paddedResize($this->ThumbWidth(), $this->ThumbHeight());
	}

	/**
	 * @usage can be used in a template like this $Image.ContentImage.Link
	 * @return GD
	 **/
	function generateContentImage($gd) {
		$gd->setQuality(90);
		return $gd->resizeByWidth($this->ContentWidth());
	}

	/**
	 * @usage can be used in a template like this $Image.LargeImage.Link
	 * @return GD
	 **/
	function generateLargeImage($gd) {
		$gd->setQuality(90);
		return $gd->resizeByWidth($this->LargeWidth());
	}

}

class Product_OrderItem extends OrderItem {

	function canCreate($member = null) {
		return true;
	}
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
	 *@return Boolean
	 **/
	function hasSameContent($orderItem) {
		$parentIsTheSame = parent::hasSameContent($orderItem);
		return $parentIsTheSame && $orderItem instanceOf $this->class;
	}

	/**
	 *@return Float
	 **/
	function UnitPrice($recalculate = false) {return $this->getUnitPrice($recalculate);}
	function getUnitPrice($recalculate = false) {
		$unitprice = 0;
		if($this->priceHasBeenFixed() && !$recalculate) {
			return parent::getUnitPrice($recalculate);
		}
		elseif($product = $this->Product()){
			$unitprice = $product->getCalculatedPrice();
			$this->extend('updateUnitPrice',$unitprice);
		}
		return $unitprice;
	}


	/**
	 *@return String
	 **/
	function TableTitle() {return $this->getTableTitle();}
	function getTableTitle() {
		$tabletitle = _t("Product.UNKNOWN", "Unknown Product");
		if($product = $this->Product()) {
			$tabletitle = $product->Title;
			$this->extend('updateTableTitle',$tabletitle);
		}
		return $tabletitle;
	}

	/**
	 *@return String
	 **/
	function TableSubTitle() {return $this->getTableSubTitle();}
	function getTableSubTitle() {
		$tablesubtitle = '';
		if($product = $this->Product()) {
			$tablesubtitle = $product->Quantifier;
			$this->extend('updateTableSubTitle',$tablesubtitle);
		}
		return $tablesubtitle;
	}

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

	/**
	 *
	 * This method can be extended to show products in the side bar.
	 *
	 * @return Object DataObjectSet
	 */
	function SidebarProducts(){
		return null;
	}

	/**
	 *
	 * This method can be extended to show products in the side bar.
	 *
	 * @return Object Product
	 */
	function NextProduct(){
		return null;
	}

	/**
	 *
	 * This method can be extended to show products in the side bar.
	 *
	 * @return Object Product
	 */
	function PreviousProduct(){
		return null;
	}



	/**
	 *
	 * This method can be extended to show products in the side bar.
	 *
	 * @return Boolean
	 */
	function HasPreviousOrNextProduct(){
		return null;
	}


}
