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


class Product extends Page implements BuyableModel {

	/**
	 * Standard SS variable.
	 */
	public static $api_access = array(
		'view' => array(
			"Title",
			"AllowPurchase",
			"InternalItemID",
			"Price",
			"Weight",
			"Model",
			"Quantifier",
			"FeaturedProduct",
			"InternalItemID", //ie SKU, ProductID etc (internal / existing recognition of product)
			"NumberSold" //store number sold, so it doesn't have to be computed on the fly. Used for determining popularity.
		)
	);

	/**
	 * Standard SS variable.
	 */
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

	/**
	 * Standard SS variable.
	 */
	public static $has_one = array(
		'Image' => 'Product_Image'
	);

	/**
	 * Standard SS variable.
	 */
	public static $many_many = array(
		'ProductGroups' => 'ProductGroup'
	);

	/**
	 * Standard SS variable.
	 */
	public static $casting = array(
		"CalculatedPrice" => "Currency",
		"DisplayPrice" => "Money"
	);

	/**
	 * Standard SS variable.
	 */
	public static $defaults = array(
		'AllowPurchase' => 1
	);

	/**
	 * Standard SS variable.
	 */
	public static $summary_fields = array(
		'ID',
		'InternalItemID',
		'Title',
		'Price',
		'NumberSold'
	);

	/**
	 * Standard SS variable.
	 */
	public static $searchable_fields = array(
		'Title' => "PartialMatchFilter",
		'InternalItemID' => "PartialMatchFilter",
		'ShowInSearch',
		'ShowInMenus',
		'AllowPurchase',
		'FeaturedProduct',
		'Price'
	);

	/**
	 * Standard SS variable.
	 */
	public static $singular_name = "Product";
		function i18n_singular_name() { return _t("Order.PRODUCT", "Product");}

	/**
	 * Standard SS variable.
	 */
	public static $plural_name = "Products";
		function i18n_plural_name() { return _t("Order.PRODUCTS", "Products");}

	/**
	 * Standard SS variable.
	 */
	public static $default_parent = 'ProductGroup';

	/**
	 * Standard SS variable.
	 */
	public static $icon = 'ecommerce/images/icons/product';

	/**
	 * We add all $db fields to MetaKeywords to allow searching products
	 * on more fields than just the standard ones.
	 * This variables tells us what fields to exclude
	 * (either they are being searched already OR they are not relevant)
	 * @var Array
	 */
	protected $fieldsToExcludeFromSearch = array("Title","MenuTitle","Content","MetaTitle","MetaDescription","MetaKeywords", "Status", "ReportClass", "CanViewType", "CanEditType", "ToDo");

	/**
	 * Standard SS variable.
	 */
	function getCMSFields() {
		//prevent calling updateCMSFields extend function too early
		$siteTreeFieldExtensions = $this->get_static('SiteTree','runCMSFieldsExtensions');
		$this->disableCMSFieldsExtensions();
		$fields = parent::getCMSFields();
		if($siteTreeFieldExtensions) {
			$this->enableCMSFieldsExtensions();
		}
		$fields->replaceField('Root.Content.Main', new HTMLEditorField('Content', _t('Product.DESCRIPTION', 'Product Description'), 3));
		//NOTE: IMAGE FIELD WAS GIVING ERRORS IN ModelAdmin
		//$fields->addFieldToTab('Root.Content.Images', new TreeDropdownField('ImageID', _t('Product.IMAGE', 'Product Image'), "Image"));
		$fields->addFieldToTab('Root.Content.Images', new ImageField('Image', _t('Product.IMAGE', 'Product Image')));
		$fields->addFieldToTab('Root.Content.Details',new CheckboxField('AllowPurchase', _t('Product.ALLOWPURCHASE', 'Allow product to be purchased'), 1));
		$fields->addFieldToTab('Root.Content.Details',new CheckboxField('FeaturedProduct', _t('Product.FEATURED', 'Featured Product')));
		$fields->addFieldToTab('Root.Content.Details',new NumericField('Price', _t('Product.PRICE', 'Price'), '', 12));
		$fields->addFieldToTab('Root.Content.Details',new TextField('InternalItemID', _t('Product.CODE', 'Product Code'), '', 30));
		if($this->EcomConfig()->ProductsHaveWeight) {
			$fields->addFieldToTab('Root.Content.Details',new NumericField('Weight', _t('Product.WEIGHT', 'Weight')));
		}
		if($this->EcomConfig()->ProductsHaveModelNames) {
			$fields->addFieldToTab('Root.Content.Details',new TextField('Model', _t('Product.MODEL', 'Model')));
		}
		if($this->EcomConfig()->ProductsHaveQuantifiers) {
			$fields->addFieldToTab('Root.Content.Details',new TextField('Quantifier', _t('Product.QUANTIFIER', 'Quantifier (e.g. per kilo, per month, per dozen, each)')));
		}
		if($this->EcomConfig()->ProductsAlsoInOtherGroups) {
			$fields->addFieldsToTab(
				'Root.Content.AlsoShowHere',
				array(
					new HeaderField('ProductGroupsHeader', _t('Product.ALSOSHOWSIN', 'Also shows in ...')),
					$this->getProductGroupsTable()
				)
			);
		}
		$fields->addFieldToTab('Root.Content.Orders',
			new ComplexTableField(
				$this,
				'OrderItems',
				'OrderItem',
				array(
					'Order.ID' => '#',
					'Order.Created' => 'When',
					'Quantity' => 'Quantity'
				),
				new FieldSet(),
				"\"BuyableID\" = '".$this->ID."' AND \"BuyableClassName\" = '".$this->ClassName."'",
				"\"Created\" DESC"
			)
		);
		if($siteTreeFieldExtensions) {
			$this->extend('updateCMSFields', $fields);
		}
		return $fields;
	}

	/**
	 * Used in getCSMFields
	 * @return TreeMultiselectField
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
	 * Adds keywords to the MetaKeyword
	 * Standard SS Method
	 */
	function onBeforeWrite(){
		parent::onBeforeWrite();
	//we are adding all the fields to the keyword fields here for searching purposes.
	//because the MetaKeywords Field is being searched.
		$this->MetaKeywords = "";
		foreach($this->db() as $fieldName => $fieldType) {
			if(is_string($this->$fieldName) && strlen($this->$fieldName) > 2) {
				//HACK!!
				if(!in_array($fieldName, $this->fieldsToExcludeFromSearch)) {
				//END HACK
					$this->MetaKeywords .= strip_tags($this->$fieldName);
				}
			}
		}
	}




	//GROUPS AND SIBLINGS

	/**
	 * Returns all the parent groups for the product.
	 * This function has been added her to contrast it with MainParentGroup (see below).
	  *@return DataObjectSet(ProductGroup) or NULL
	 **/
	function AllParentGroups() {
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
	 * Returns products in the same group
	 * @return DataObjectSet
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




	//IMAGE

	/**
	 * Returns a link to a default image.
	 * If a default image is set in the site config then this link is returned
	 * Otherwise, a standard link is returned
	 * @return String
	 */
	function DefaultImageLink() {
		return $this->EcomConfig()->DefaultImageLink();
	}

	/**
	 * returns the default image of the product
	 * @return Image | Null
	 */
	public function DefaultImage() {
		return $this->EcomConfig()->DefaultImage();
	}


	/**
	 * returns a product image for use in templates
	 * e.g. $DummyImage.Width();
	 * @return Product_Image
	 */
	function DummyImage(){
		return new Product_Image();
	}




	// VERSIONING

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
	 * Action to return specific version of a product.
	 * This is really useful for sold products where you want to retrieve the actual version that you sold.
	 * @param Int $versionNumber
	 * @return DataObject | Null
	 */
	function getVersionOfProduct($versionNumber){
		if($versionNumber) {
			return Versioned::get_version($this->ClassName, $this->ID, $versionNumber);
		}
	}




	//ORDER ITEM

	/**
	 * returns the order item associated with the buyable.
	 * ALWAYS returns one, even if there is none in the cart.
	 * Does not write to database.
	 * @return OrderItem (no kidding)
	 **/
	public function OrderItem() {
		//work out the filter
		$filter = "";
		$this->extend('updateItemFilter',$filter);
		//make the item and extend
		$item = ShoppingCart::singleton()->findOrMakeItem($this, $filter);
		$this->extend('updateDummyItem',$item);
		return $item;
	}

	/**
	 *
	 * @var String
	 */
	protected $defaultClassNameForOrderItem = "Product_OrderItem";


	/**
	 * you can overwrite this function in your buyable items (such as Product)
	 * @return String
	 **/
	public function classNameForOrderItem() {
		$className = $this->defaultClassNameForOrderItem;
		$update = $this->extend("updateClassNameForOrderItem", $className);
		if(is_string($update) && class_exists($update)) {
			$className = $update;
		}
		return $className;
	}

	/**
	 * You can set an alternative class name for order item using this method
	 * @param String $ClassName
	 **/
	public function setAlternativeClassNameForOrderItem($className){
		$this->defaultClassNameForOrderItem = $className;
	}

	/**
	 * This is used when you add a product to your cart
	 * if you set it to 1 then you can add 0.1 product to cart.
	 * If you set it to -1 then you can add 10, 20, 30, etc.. products to cart.
	 *
	 * @return Int
	 **/
	function QuantityDecimals(){
		return 0;
	}

	/**
	 * Number of variations sold
	 * @return Int
	 */
	function HasBeenSold() {return $this->getHasBeenSold();}
	function getHasBeenSold() {
		return DB::query("
			SELECT COUNT(*)
			FROM \"OrderItem\"
				INNER JOIN \"OrderAttribute\" ON \"OrderAttribute\".\"ID\" = \"OrderItem\".\"ID\"
			WHERE
				\"BuyableID\" = '".$this->ID."' AND
				\"buyableClassName\" = '".$this->ClassName."'
			LIMIT 1
			"
		)->value();
	}




	//LINKS

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
	 * passing on shopping cart links ...is this necessary?? ...why not just pass the cart?
	 * @return String
	 */
	function AddLink() {
		return ShoppingCart_Controller::add_item_link($this->ID, $this->ClassName, $this->linkParameters());
	}

	/**
	 * link use to add (one) to cart
	 *@return String
	 */
	function IncrementLink() {
		//we can do this, because by default add link adds one
		return ShoppingCart_Controller::add_item_link($this->ID, $this->ClassName, $this->linkParameters());
	}

	/**
	 * Link used to remove one from cart
	 * we can do this, because by default remove link removes one
	 * @return String
	 */
	function DecrementLink() {
		return ShoppingCart_Controller::remove_item_link($this->ID, $this->ClassName, $this->linkParameters());
	}

	/**
	 * remove one buyable's orderitem from cart
	 * @return String (Link)
	 */
	function RemoveLink() {
		return ShoppingCart_Controller::remove_item_link($this->ID, $this->ClassName, $this->linkParameters());
	}

	/**
	 * remove all of this buyable's orderitem from cart
	 * @return String (Link)
	 */
	function RemoveAllLink() {
		return ShoppingCart_Controller::remove_all_item_link($this->ID, $this->ClassName, $this->linkParameters());
	}

	/**
	 * remove all of this buyable's orderitem from cart and go through to this buyble to add alternative selection.
	 * @return String (Link)
	 */
	function RemoveAllAndEditLink() {
		return ShoppingCart_Controller::remove_all_item_and_edit_link($this->ID, $this->ClassName, $this->linkParameters());
	}

	/**
	 * set new specific new quantity for buyable's orderitem
	 * @param double
	 * @return String (Link)
	 */
	function SetSpecificQuantityItemLink($quantity) {
		return ShoppingCart_Controller::set_quantity_item_link($this->ID, $this->ClassName, array_merge($this->linkParameters(), array("quantity" => $quantity)));
	}

	/**
	 * @todo: do we still need this?
	 * @return Array
	 **/
	protected function linkParameters(){
		$array = array();
		$this->extend('updateLinkParameters',$array);
		return $array;
	}




	//TEMPLATE STUFF

	/**
	 *
	 * @return boolean
	 */
	function IsInCart(){
		return ($this->OrderItem() && $this->OrderItem()->Quantity > 0) ? true : false;
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
	 * @return EcommerceDBConfig
	 **/
	function EcomConfig() {
		return EcommerceDBConfig::current_ecommerce_db_config();
	}

	/**
	 * Is it a variation?
	 * @return Boolean
	 */
	function IsProductVariation() {
		return false;
	}

	/**
	 * tells us if the current page is part of e-commerce.
	 * @return Boolean
	 */
	function IsEcommercePage () {
		return true;
	}

	/**
	 * Products have a standard price, but for specific situations they have a calculated price.
	 * The Price can be changed for specific member discounts, etc...
	 * @return Currency
	 */
	function CalculatedPrice() {return $this->getCalculatedPrice();}
	function getCalculatedPrice() {
		$price = $this->Price;
		$this->extend('updateCalculatedPrice',$price);
		return $price;
	}

	/**
	 * How do we display the price?
	 * @return Money
	 */
	function DisplayPrice() {return $this->getDisplayPrice();}
	function getDisplayPrice() {
		$price = $this->CalculatedPrice();
		if($this->Cart()->HasAlternativeCurrency()) {
			$exchangeRate = $this->Cart()->ExchangeRate;
			if($exchangeRate) {
				$price = $exchangeRate * $price;
			}
		}
		$moneyObject = new Money("DisplayPrice");
		$moneyObject->setCurrency($this->Cart()->DisplayCurrency());
		$moneyObject->setValue($price);
		return $moneyObject;
	}




	//CRUD SETTINGS

	/**
	 * Is the product for sale?
	 * @return Boolean
	 */
	function canPurchase($member = null) {
		if($this->EcomConfig()->ShopClosed) {
			return false;
		}
		$allowpurchase = $this->AllowPurchase;
		if(!$allowpurchase) {
			return false;
		}
		// Standard mechanism for accepting permission changes from decorators
		$extended = $this->extendedCan('canPurchase', $member);
		if($extended !== null) {
			$allowpurchase = $extended;
		}
		return $allowpurchase;
	}

	/**
	 * Shop Admins can edit
	 * @return Boolean
	 */
	function canEdit($member = null) {
		if(!$member) {
			$member == Member::currentUser();
		}
		$shopAdminCode = EcommerceConfig::get("EcommerceRole", "admin_permission_code");
		if($member && Permission::checkMember($member, $shopAdminCode)) {
			return true;
		}
		return parent::canEdit($member);
	}

	/**
	 * Once the item has been sold, it can not be deleted.
	 * @return Boolean
	 */
	function canDelete($member = null) {
		//can we delete sold items? or can we only make them invisible
		if($this->HasBeenSold()) {
			return false;
		}
		return parent::canDelete($member);
	}

	/**
	 * Standard SS method
	 * @return Boolean
	 */
	public function canPublish($member = null) {
		return $this->canEdit($member);
	}

	/**
	 * Standard SS method
	 * //check if it is in a current cart?
	 * @return Boolean
	 */
	public function canDeleteFromLive($member = null) {
		return $this->canEdit($member);
	}

	/**
	 * Standard SS method
	 * @return Boolean
	 */
	public function canCreate($member = null) {
		return $this->canEdit($member);
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

	function viewversion($request) {
		$version = intval($request->param("ID"));
		if($record = $this->getVersionOfProduct($version)) {
			$this->record = $record;
		}
		return array();
	}


	/**
	 * Standard SS method
	 * Returns a snippet when requested by ajax.
	 */
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
	 * @return Buyable
	 */
	function NextProduct(){
		return null;
	}

	/**
	 *
	 * This method can be extended to show products in the side bar.
	 *
	 * @return Buyable
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

	/**
	 *
	 * @return Boolean
	 */
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


}
