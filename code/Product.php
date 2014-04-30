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
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: buyables
 * @inspiration: Silverstripe Ltd, Jeremy
 * @todo: Ask the silverstripe gods why $default_sort won't work with FullSiteTreeSort
 **/



class Product extends Page implements BuyableModel {

	/**
	 * Standard SS variable.
	 */
	private static $api_access = array(
		'view' => array(
			"Title",
			"Price",
			"Weight",
			"Model",
			"Quantifier",
			"FeaturedProduct",
			"AllowPurchase",
			"InternalItemID", //ie SKU, ProductID etc (internal / existing recognition of product)
			"NumberSold", //store number sold, so it doesn't have to be computed on the fly. Used for determining popularity.
			"Version"
		)
	);

	/**
	 * Standard SS variable.
	 */
	private static $db = array(
		'Price' => 'Currency',
		'Weight' => 'Decimal(9,4)',
		'Model' => 'Varchar(30)',
		'Quantifier' => 'Varchar(30)',
		'FeaturedProduct' => 'Boolean',
		'AllowPurchase' => 'Boolean',
		'InternalItemID' => 'Varchar(30)', //ie SKU, ProductID etc (internal / existing recognition of product)
		'NumberSold' => 'Int', //store number sold, so it doesn't have to be computed on the fly. Used for determining popularity.
		'FullSiteTreeSort' => 'Decimal(64, 0)', //store the complete sort numbers from current page up to level 1 page, for sitetree sorting
		'FullName' => 'Varchar(255)', //Name for look-up lists
		'ShortDescription' => 'Varchar(255)' //For use in lists.
	);

	/**
	 * Standard SS variable.
	 */
	private static $has_one = array(
		'Image' => 'Product_Image'
	);

	/**
	 * Standard SS variable.
	 */
	private static $many_many = array(
		'ProductGroups' => 'ProductGroup',
		'AdditionalFiles' => 'File' //this may include images, pdfs, videos, etc...
	);

	/**
	 * Standard SS variable.
	 */
	private static $casting = array(
		"CalculatedPrice" => "Currency",
		"CalculatedPriceAsMoney" => "Money",
		"AllowPurchaseNice" => "Varchar"
	);

	/**
	 * Standard SS variable.
	 */
	private static $indexes = array(
		"FullSiteTreeSort" => true,
		"FullName" => true,
		"InternalItemID" => true
	);

	/**
	 * Standard SS variable.
	 */
	private static $defaults = array(
		'AllowPurchase' => 1
	);

	/**
	 * Standard SS variable.
	 */
	//private static $default_sort = "\"FullSiteTreeSort\" ASC, \"Sort\" ASC, \"InternalItemID\" ASC, \"Price\" ASC";
	//private static $default_sort = "\"Sort\" ASC, \"InternalItemID\" ASC, \"Price\" ASC";

	/**
	 * Standard SS variable.
	 */
	private static $summary_fields = array(
		'CMSThumbnail' => 'Image',
		'FullName' => 'Description',
		'Price' => 'Price',
		'AllowPurchaseNice' => 'For Sale'
	);

	/**
	 * Standard SS variable.
	 */
	private static $searchable_fields = array(
		"FullName" => array(
			'title' => 'Keyword',
			'field' => 'TextField'
		),
		"Price" => array(
			'title' => 'Price',
			'field' => 'NumericField'
		),
		"InternalItemID" => array(
			'title' => 'Internal Item ID',
			'filter' => 'PartialMatchFilter'
		),
		'AllowPurchase',
		'ShowInSearch',
		'ShowInMenus',
		'FeaturedProduct'
	);

	/**
	 * Standard SS variable.
	 */
	private static $singular_name = "Product";
		function i18n_singular_name() { return _t("Order.PRODUCT", "Product");}

	/**
	 * Standard SS variable.
	 */
	private static $plural_name = "Products";
		function i18n_plural_name() { return _t("Order.PRODUCTS", "Products");}

	/**
	 * Standard SS variable.
	 * @var String
	 */
	private static $description = "A product that is for sale in the shop.";

	/**
	 * Standard SS variable.
	 */
	private static $default_parent = 'ProductGroup';

	/**
	 * Standard SS variable.
	 */
	private static $icon = 'ecommerce/images/icons/product';

	public function getCMSActions() {
		$fields = parent::getCMSActions();
		if(!$this->canEdit()) {
			$editButton = FormAction::create('editinsitetree');
			$editButton->setTitle(_t("Product.EDIT", "Edit"));
			$editButton->setDescription(_t("Product.EDIT_IN_SITETREE", "Edit this record in the site tree"));
			$fields->push($editButton);
		}
		return $fields;
	}

	/**
	 * Standard SS variable.
	 */
	function getCMSFields() {
		//prevent calling updateSettingsFields extend function too early
		//$siteTreeFieldExtensions = $this->get_static('SiteTree','runCMSFieldsExtensions');
		//$this->disableCMSFieldsExtensions();
		$fields = parent::getCMSFields();
		$fields->removeByName("MetaDescription");
		//if($siteTreeFieldExtensions) {
			//$this->enableCMSFieldsExtensions();
		//}
		$fields->replaceField('Root.Main', $htmlEditorField = new HTMLEditorField('Content', _t('Product.DESCRIPTION', 'Product Description')));
		$htmlEditorField->setRows(3);
		$fields->addFieldToTab('Root.Main', new TextField('ShortDescription', _t('Product.SHORT_DESCRIPTION', 'Short Description')), "Content");
		//dirty hack to show images!
		$fields->addFieldToTab('Root.Images', new Product_ProductImageUploadField('Image', _t('Product.IMAGE', 'Product Image')));
		$fields->addFieldToTab('Root.Images', $this->getAdditionalImagesField());
		$fields->addFieldToTab('Root.Images', $this->getAdditionalImagesMessage());
		$fields->addFieldToTab('Root.Details',new ReadonlyField('FullName', _t('Product.FULLNAME', 'Full Name')));
		$fields->addFieldToTab('Root.Details',new ReadOnlyField('FullSiteTreeSort', _t('Product.FULLSITETREESORT', 'Full sort index')));
		$fields->addFieldToTab('Root.Details',new CheckboxField('AllowPurchase', _t('Product.ALLOWPURCHASE', 'Allow product to be purchased'), 1));
		$fields->addFieldToTab('Root.Details',new CheckboxField('FeaturedProduct', _t('Product.FEATURED', 'Featured Product')));
		$fields->addFieldToTab('Root.Details',new NumericField('Price', _t('Product.PRICE', 'Price'), '', 12));
		$fields->addFieldToTab('Root.Details',new TextField('InternalItemID', _t('Product.CODE', 'Product Code'), '', 30));
		if($this->EcomConfig()->ProductsHaveWeight) {
			$fields->addFieldToTab('Root.Details',new NumericField('Weight', _t('Product.WEIGHT', 'Weight')));
		}
		if($this->EcomConfig()->ProductsHaveModelNames) {
			$fields->addFieldToTab('Root.Details',new TextField('Model', _t('Product.MODEL', 'Model')));
		}
		if($this->EcomConfig()->ProductsHaveQuantifiers) {
			$fields->addFieldToTab('Root.Details',new TextField('Quantifier', _t('Product.QUANTIFIER', 'Quantifier (e.g. per kilo, per month, per dozen, each)')));
		}
		if($this->EcomConfig()->ProductsAlsoInOtherGroups) {
			$fields->addFieldsToTab(
				'Root.AlsoShowHere',
				array(
					new HeaderField('ProductGroupsHeader', _t('Product.ALSOSHOWSIN', 'Also shows in ...')),
					$this->getProductGroupsTableField()
				)
			);
		}
		//if($siteTreeFieldExtensions) {
			//$this->extend('updateSettingsFields', $fields);
		//}
		return $fields;
	}

	/**
	 * Used in getCSMFields
	 * @return TreeMultiselectField
	 **/
	protected function getProductGroupsTableField() {

		$field = new TreeMultiselectField(
			$name = "ProductGroups",
			$title = _t("Product.THISPRODUCTSHOULDALSOBELISTEDUNDER", "This product is also listed under ..."),
			$sourceObject = "SiteTree",
			$keyField = "ID",
			$labelField = "MenuTitle"
		);
		if($this->ParentID) {
			$filter = create_function('$obj', 'return ( ( is_a($obj, "'.Object::getCustomClass("ProductGroup").'")) && ($obj->ID != '.$this->ParentID.'));');
			$field->setFilterFunction($filter);
		}
		return $field;
	}

	/**
	 * Used in getCSMFields
	 * @return GridField
	 **/
	protected function getAdditionalImagesMessage() {
		$msg = "";
		if($this->InternalItemID) {
			$findImagesTask = EcommerceTaskLinkProductWithImages::create();
			$findImagesLink = $findImagesTask->Link();
			$findImagesLinkOne = $findImagesLink."?productid=".$this->ID;
			$msg .= "<p>
				To upload additional images and files, please go to the <a href=\"/admin/assets\">Files section</a>, and upload them there.
				Files need to be named in the following way:
				An additional image for your product should be named &lt;Product Code&gt;_(00 to 99).(png/jpg/gif). <br />For example, you may name your image:
				<strong>".$this->InternalItemID."_08.jpg</strong>.
				<br /><br />You can <a href=\"$findImagesLinkOne\" target='_blank'>find images for <i>".$this->Title."</i></a> or
				<a href=\"$findImagesLink\" target='_blank'>images for all products</a> ...
			</p>";
		}
		else {
			$msg .= "<p>For additional images and files, you must first specify a product code.</p>";
		}
		$field = new LiteralField("ImageFileNote", $msg);
		return $field;

	}

	/**
	 * Used in getCSMFields
	 * @return GridField
	 **/
	protected function getAdditionalImagesField() {
		$gridField = new GridField(
			'AdditionalFiles',
			_t('Product.ADDITIONALIMAGES', 'Additional files and images'),
			$this->AdditionalFiles(),
			GridFieldConfig_RelationEditor::create()
		);
		$config = $gridField->getConfig();
		$components = $gridField->getComponents();
		$config->removeComponentsByType("GridFieldAddNewButton");
		$config->removeComponentsByType("GridFieldAddExistingAutocompleter");
		$gridField->setConfig($config);
		//var_dump($components->items);

		$gridField->setModelClass("Product_Image");
		return $gridField;

	}

	/**
	 * How to view using AJAX
	 * e.g. if you want to load the produyct in a list - using AJAX
	 * then use this link
	 * Opening the link will return a HTML snippet
	 * @return String
	 */
	function AjaxLink(){
		return $this->Link("ajaxview");
	}

	/**
	 * Adds keywords to the MetaKeyword
	 * Standard SS Method
	 */
	function onBeforeWrite(){
		parent::onBeforeWrite();
		$filter = EcommerceCodeFilter::create();
		$filter->checkCode($this, "InternalItemID");
		$this->prepareFullFields();
		//we are adding all the fields to the keyword fields here for searching purposes.
		//because the MetaKeywords Field is being searched.
		$this->MetaDescription = "";
		$fieldsToExclude = Config::inst()->get("SiteTree", "db");
		foreach($this->db() as $fieldName => $fieldType) {
			if(is_string($this->$fieldName) && strlen($this->$fieldName) > 2) {
				if(!in_array($fieldName, $fieldsToExclude)) {
					$this->MetaDescription .= strip_tags($this->$fieldName);
				}
			}
		}
		if($this->hasExtension("ProductWithVariationDecorator")) {
			$variations = $this->Variations();
			if($variations) {
				if($variations->count()) {
					foreach($variations as $variation) {
						$this->MetaDescription .= " - ".$variation->FullName;
					}
				}
			}
		}
	}

	function onAfterWrite() {
		parent::onAfterWrite();
		if($this->ImageID) {
			if($normalImage = Image::get()->exclude(array("ClassName" => "Product_Image"))->byID($this->ImageID)) {
				$normalImage->ClassName = "Product_Image";
				$normalImage->write();
			}
		}
	}

	/**
	 * sets the FullName and FullSiteTreeField to the latest values
	 * This can be useful as you can compare it to the ones saved in the database.
	 * Returns true if the value is different from the one in the database.
	 * @return Boolean
	 */
	public function prepareFullFields(){
		//FullName
		$fullName = "";
		if($this->InternalItemID) {
			$fullName .= $this->InternalItemID.": ";
		}
		$fullName .= $this->Title;
		//FullSiteTreeSort
		$parentSortArray = array(sprintf("%03d", $this->Sort));
		$obj = $this;
		$parentTitleArray = array();
		while($obj && $obj->ParentID) {
			$obj = SiteTree::get()->byID(intval($obj->ParentID)-0);
			if($obj) {
				$parentSortArray[] = sprintf("%03d", $obj->Sort);
				if(is_a($obj, Object::getCustomClass("ProductGroup"))) {
					$parentTitleArray[] = $obj->Title;
				}
			}
		}
		$reverseArray = array_reverse($parentSortArray);
		$parentTitle = "";
		if(count($parentTitleArray)) {
			$parentTitle = " ("._t("product.IN", "in")." ".implode(" / ", $parentTitleArray).")";
		}
		//setting fields with new values!
		$this->FullName = $fullName.$parentTitle;
		$this->FullSiteTreeSort = implode("", array_map($this->numberPad, $reverseArray));
		if(($this->dbObject("FullName") != $this->FullName) || ($this->dbObject("FullSiteTreeSort") != $this->FullSiteTreeSort)) {
			return true;
		}
		return false;
	}


	//GROUPS AND SIBLINGS

	/**
	 * Returns all the parent groups for the product.
	 * This function has been added her to contrast it with MainParentGroup (see below).
	  *@return ManyManyList (ProductGroups)
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
		return ProductGroup::get()->byID($this->ParentID);
	}

	/**
	 * Returns products in the same group
	 * @return DataList (Products)
	 **/
	public function Siblings() {
		if($this->ParentID) {
			$extension = "";
			if(Versioned::current_stage() == "Live") {
				$extension = "_Live";
			}
			return Product::get()
				->filter(array(
					"ShowInMenus" => 1,
					"ParentID" => $this->ParentID
				))
				->exclude(array("ID" => $this->ID));
		}
	}




	//IMAGE
	/**
	 * returns a "BestAvailable" image if the current one is not available
	 * In some cases this is appropriate and in some cases this is not.
	 * For example, consider the following setup
	 * - product A with three variations
	 * - Product A has an image, but the variations have no images
	 * With this scenario, you want to show ONLY the product image
	 * on the product page, but if one of the variations is added to the
	 * cart, then you want to show the product image.
	 * This can be achieved bu using the BestAvailable image.
	 * @return Image | Null
	 */
	public function BestAvailableImage() {
		$image = $this->Image();
		if($image && $image->exists()) {
			return $image;
		}
		elseif($parent = $this->MainParentGroup()) {
			return $parent->BestAvailableImage();
		}
	}

	/**
	 * Little hack to show thumbnail in summary fields in modeladmin in CMS.
	 * @return String (HTML = formatted image)
	 */
	function CMSThumbnail(){
		if($image = $this->Image()) {
			if($image->exists()) {
				return $image->Thumbnail();
			}
		}
		return "["._t("product.NOIMAGE", "no image")."]";
	}

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
	 * @return DataList (CHECK!)
	 */
	public function getVersionedComponents($component = "ProductVariations") {
		return;
		$baseTable = ClassInfo::baseDataClass(self::$has_many[$component]);
		$query = singleton(self::$has_many[$component])->buildVersionSQL("\"{$baseTable}\".ProductID = {$this->ID} AND \"{$baseTable}\".Version = {$this->Version}");
		$result = singleton(self::$has_many[$component])->buildDataObjectSet($query->execute());
		return $result;
	}

	/**
	 * Action to return specific version of a specific product.
	 * This can be any product to enable the retrieval of deleted products.
	 * This is really useful for sold products where you want to retrieve the actual version that you sold.
	 * @param Int $id
	 * @param Int $version
	 * @return DataObject | Null
	 */
	function getVersionOfBuyable($id = 0, $version = 0){
		if(!$id) {
			$id = $this->ID;
		}
		if(!$version) {
			$version = $this->Version;
		}
		return OrderItem::get_version($this->ClassName, $id, $version);
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
		return ShoppingCart_Controller::add_item_link($this->ID, $this->ClassName, $this->linkParameters("add"));
	}

	/**
	 * link use to add (one) to cart
	 *@return String
	 */
	function IncrementLink() {
		//we can do this, because by default add link adds one
		return ShoppingCart_Controller::add_item_link($this->ID, $this->ClassName, $this->linkParameters("increment"));
	}

	/**
	 * Link used to remove one from cart
	 * we can do this, because by default remove link removes one
	 * @return String
	 */
	function DecrementLink() {
		return ShoppingCart_Controller::remove_item_link($this->ID, $this->ClassName, $this->linkParameters("decrement"));
	}

	/**
	 * remove one buyable's orderitem from cart
	 * @return String (Link)
	 */
	function RemoveLink() {
		return ShoppingCart_Controller::remove_item_link($this->ID, $this->ClassName, $this->linkParameters("remove"));
	}

	/**
	 * remove all of this buyable's orderitem from cart
	 * @return String (Link)
	 */
	function RemoveAllLink() {
		return ShoppingCart_Controller::remove_all_item_link($this->ID, $this->ClassName, $this->linkParameters("removeall"));
	}

	/**
	 * remove all of this buyable's orderitem from cart and go through to this buyble to add alternative selection.
	 * @return String (Link)
	 */
	function RemoveAllAndEditLink() {
		return ShoppingCart_Controller::remove_all_item_and_edit_link($this->ID, $this->ClassName, $this->linkParameters("removeallandedit"));
	}

	/**
	 * set new specific new quantity for buyable's orderitem
	 * @param double
	 * @return String (Link)
	 */
	function SetSpecificQuantityItemLink($quantity) {
		return ShoppingCart_Controller::set_quantity_item_link($this->ID, $this->ClassName, array_merge($this->linkParameters("setspecificquantityitem"), array("quantity" => $quantity)));
	}


	/**
	 * Here you can add additional information to your product
	 * links such as the AddLink and the RemoveLink.
	 * One useful parameter you can add is the BackURL link.
	 * @return Array
	 **/
	protected function linkParameters($type = ""){
		$array = array();
		$this->extend('updateLinkParameters',$array, $type);
		return $array;
	}




	//TEMPLATE STUFF

	/**
	 *
	 * @return boolean
	 */
	public function IsInCart(){
		return ($this->OrderItem() && $this->OrderItem()->Quantity > 0) ? true : false;
	}

	/**
	 *
	 * @return EcomQuantityField
	 */
	public function EcomQuantityField() {
		return EcomQuantityField::create($this);
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

	function AllowPurchaseNice(){
		return $this->obj("AllowPurchase")->Nice();
	}

	/**
	 * Products have a standard price, but for specific situations they have a calculated price.
	 * The Price can be changed for specific member discounts, etc...
	 * @return Currency
	 */
	function CalculatedPrice() {return $this->getCalculatedPrice();}
	function getCalculatedPrice() {
		$price = $this->Price;
		$updatedPrice = $this->extend('updateCalculatedPrice',$price);
		if($updatedPrice !== null) {
			if(is_array($updatedPrice) && count($updatedPrice)) {
				$price = $updatedPrice[0];
			}
		}
		return $price;
	}

	/**
	 * How do we display the price?
	 * @return Money
	 */
	function CalculatedPriceAsMoney() {return $this->getCalculatedPriceAsMoney();}
	function getCalculatedPriceAsMoney() {
		return EcommerceCurrency::get_money_object_from_order_currency($this->getCalculatedPrice());
	}



	//CRUD SETTINGS

	/**
	 * Is the product for sale?
	 * @param Member $member
	 * @param Boolean $checkPrice
	 * @return Boolean
	 */
	function canPurchase(Member $member = null, $checkPrice = true) {
		$config = $this->EcomConfig();
		//shop closed
		if($config->ShopClosed) {
			return false;
		}
		//not sold at all
		$allowpurchase = $this->AllowPurchase;
		if(! $allowpurchase) {
			return false;
		}
		//check country
		$extended = $this->extendedCan('canPurchaseByCountry', $member);
		if($extended === null) {
			if(! EcommerceCountry::allow_sales()) {
				return false;
			}
		}
		else if($extended === false) {
			return false;
		}
		//price
		if(!$config->AllowFreeProductPurchase) {
			if($checkPrice) {
				$price = $this->getCalculatedPrice();
				if($price == 0) {
					return false;
				}
			}
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
	 * @param Member $member
	 * @return Boolean
	 */
	function canEdit($member = null) {
		if(is_a(Controller::curr(), Object::getCustomClass("ProductsAndGroupsModelAdmin"))) {
			return false;
		}
		if(!$member) {
			$member = Member::currentUser();
		}
		if($member && $member->IsShopAdmin()) {
			return true;
		}
		return parent::canEdit($member);
	}


	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {
		return $this->canEdit($member);
	}


	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canPublish($member = null) {
		return $this->canEdit($member);
	}

	/**
	 * Standard SS method
	 * //check if it is in a current cart?
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDeleteFromLive($member = null) {
		return $this->canEdit($member);
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canCreate($member = null) {
		return $this->canEdit($member);
	}

	public function debug(){
		$html = EcommerceTaskDebugCart::debug_object($this);
		$html .= "<ul>";
		$html .= "<li><hr />Links<hr /></li>";
		$html .= "<li><b>Link:</b> <a href=\"".$this->Link()."\">".$this->Link()."</a></li>";
		$html .= "<li><b>Ajax Link:</b> <a href=\"".$this->AjaxLink()."\">".$this->AjaxLink()."</a></li>";
		$html .= "<li><b>AddVariations Link:</b> <a href=\"".$this->AddVariationsLink()."\">".$this->AddVariationsLink()."</a></li>";
		$html .= "<li><b>Add to Cart Link:</b> <a href=\"".$this->AddLink()."\">".$this->AddLink()."</a></li>";
		$html .= "<li><b>Increment Link:</b> <a href=\"".$this->IncrementLink()."\">".$this->IncrementLink()."</a></li>";
		$html .= "<li><b>Decrement Link:</b> <a href=\"".$this->DecrementLink()."\">".$this->DecrementLink()."</a></li>";
		$html .= "<li><b>Remove Link:</b> <a href=\"".$this->RemoveAllLink()."\">".$this->RemoveLink()."</a></li>";
		$html .= "<li><b>Remove All Link:</b> <a href=\"".$this->RemoveAllLink()."\">".$this->RemoveAllLink()."</a></li>";
		$html .= "<li><b>Remove All and Edit Link:</b> <a href=\"".$this->RemoveAllAndEditLink()."\">".$this->RemoveAllAndEditLink()."</a></li>";
		$html .= "<li><b>Set Specific Quantity Item Link (e.g. 77):</b> <a href=\"".$this->SetSpecificQuantityItemLink(77)."\">".$this->SetSpecificQuantityItemLink(77)."</a></li>";

		$html .= "<li><hr />Cart<hr /></li>";
		$html .= "<li><b>Allow Purchase (DB Value):</b> ".$this->AllowPurchaseNice()." </li>";
		$html .= "<li><b>Can Purchase (overal calculation):</b> ".($this->canPurchase() ? "YES" : "NO")." </li>";
		$html .= "<li><b>Shop Open:</b> ".( $this->EcomConfig() ?  ($this->EcomConfig()->ShopClosed ? "NO" : "YES") : "NO CONFIG")." </li>";
		$html .= "<li><b>Extended Country Can Purchase:</b> ".($this->extendedCan('canPurchaseByCountry', null) === null ? "no applicable" : ($this->extendedCan('canPurchaseByCountry', null) ? "CAN PURCHASE" : "CAN NOT PURCHASE"))." </li>";
		$html .= "<li><b>Allow sales to this country (".EcommerceCountry::get_country()."):</b> ".(EcommerceCountry::allow_sales() ? "YES" : "NO")." </li>";
		$html .= "<li><b>Class Name for OrderItem:</b> ".$this->classNameForOrderItem()." </li>";
		$html .= "<li><b>Quantity Decimals:</b> ".$this->QuantityDecimals()." </li>";
		$html .= "<li><b>Is In Cart:</b> ".($this->IsInCart() ? "YES" : "NO")." </li>";
		$html .= "<li><b>Has Been Sold:</b> ".($this->HasBeenSold() ?  "YES" : "NO")." </li>";
		$html .= "<li><b>Calculated Price:</b> ".$this->CalculatedPrice()." </li>";
		$html .= "<li><b>Calculated Price as Money:</b> ".$this->getCalculatedPriceAsMoney()->Nice()." </li>";

		$html .= "<li><hr />Location<hr /></li>";
		$html .= "<li><b>Main Parent Group:</b> ".$this->MainParentGroup()->Title."</li>";
		$html .= "<li><b>All Others Parent Groups:</b> ".($this->AllParentGroups()->count() ? "<pre>".print_r($this->AllParentGroups()->map()->toArray(), 1)."</pre>" : "none")."</li>";

		$html .= "<li><hr />Image<hr /></li>";
		$html .= "<li><b>Image:</b> ".($this->BestAvailableImage() ? "<img src=".$this->BestAvailableImage()->Link()." />" : "no image")." </li>";
		$productGroup = ProductGroup::get()->byID($this->ParentID);
		if($productGroup) {
			$html .= "<li><hr />Product Example<hr /></li>";
			$html .= "<li><b>Product Group View:</b> <a href=\"".$productGroup->Link()."\">".$productGroup->Title."</a> </li>";
			$html .= "<li><b>Product Group Debug:</b> <a href=\"".$productGroup->Link("debug")."\">".$productGroup->Title."</a> </li>";
			$html .= "<li><b>Product Group Admin:</b> <a href=\""."/admin/pages/edit/show/".$productGroup->ID."\">".$productGroup->Title." Admin</a> </li>";
		}
		$html .= "</ul>";
		return $html;
		$html .= "</ul>";
		return $html;
	}

}


class Product_Controller extends Page_Controller {

	private static $allowed_actions = array(
		"viewversion",
		"ajaxview",
		"addproductfromform",
		"debug" => "ADMIN"
	);

	/**
	 * is this the current version?
	 * @var Boolean
	 */
	protected $isCurrentVersion = true;

	/**
	 *
	 * Standard SS method.
	 */
	function init() {
		parent::init();
		Requirements::themedCSS('Product', 'ecommerce');
		Requirements::javascript('ecommerce/javascript/EcomQuantityField.js');
		Requirements::javascript('ecommerce/javascript/EcomProducts.js');
	}

	/**
	 * view earlier version of a product
	 * returns error or changes datarecord to earlier version
	 * if the ID does not match the Page then we look for the variation
	 * @param SS_HTTPRequest
	 */
	function viewversion(SS_HTTPRequest $request) {
		$id = intval($request->param("ID"))-0;
		$version = intval($request->param("OtherID"))-0;
		$currentVersion = $this->Version;
		if($id != $this->ID) {
			if(class_exists("ProductVariation")) {
				//TO DO: CHECK VERSION!!! IS THIS CODE RIGHT
				if($productVariation = ProductVariation::get()->byID($id)) {
					if($productVariation->Version != $version) {
						$productVariation = $productVariation->getVersionOfBuyable($id, $version);
					}
					///to do: how to add this to product page???
				}
				if(!$productVariation) {
					return $this->httpError(404);
				}
			}
		}
		elseif($currentVersion != $version) {
			if($record = $this->getVersionOfBuyable($id, $version)) {
				$this->record = $record;
				$this->dataRecord->AllowPurchase = false;
				$this->AllowPurchase = false;
				$this->isCurrentVersion = false;
				$this->Title .= _t("Product.OLDERVERSION", " - Older Version");
				$this->MetaTitle .= _t("Product.OLDERVERSION", " - Older Version");
			}
			else {
				return $this->httpError(404);
			}
		}
		return array();
	}



	/**
	 * Standard SS method
	 * Returns a snippet when requested by ajax.
	 */
	function ajaxview(SS_HTTPRequest $request){
		$isThemeEnabled = Config::inst()->get('SSViewer', 'theme_enabled');
		if(!$isThemeEnabled) {
			Config::inst()->update('SSViewer', 'theme_enabled', true);
		}
		$html = $this->renderWith("ProductGroupItemMoreDetail");
		if(!$isThemeEnabled) {
			Config::inst()->update('SSViewer', 'theme_enabled', false);
		}
		return $html;
	}

	/**
	 * returns a form for adding products to cart
	 * @return Form
	 */
	function AddProductForm(){
		if($this->canPurchase()) {
			$farray = array();
			$requiredFields = array();
			$fields = new FieldList($farray);
			$fields->push(new NumericField('Quantity','Quantity',1)); //TODO: perhaps use a dropdown instead (elimiates need to use keyboard)
			$actions = new FieldList(
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
	 * @param Array $data
	 * @param Form $form
	 */
	function addproductfromform(Array $data, Form $form){
		if(!$this->IsInCart()) {
			$quantity = round($data['Quantity'], $this->QuantityDecimals());
			if(!$quantity) {
				$quantity = 1;
			}
			$product = Product::get()->byID($this->ID);
			if($product) {
				ShoppingCart::singleton()->addBuyable($product,$quantity);
			}
			if($this->IsInCart()) {
				$msg = _t("Order.SUCCESSFULLYADDED","Added to cart.");
				$status = "good";
			}
			else {
				$msg = _t("Order.NOTADDEDTOCART","Not added to cart.");
				$status = "bad";
			}
			if(Director::is_ajax()){
				return ShoppingCart::singleton()->setMessageAndReturn($msg, $status);
			}
			else {
				$form->sessionMessage($msg,$status);
				$this->redirectBack();
			}
		}
		else {
			return EcomQuantityField::create($this);
		}
	}

	/**
	 * Is this an older version?
	 * @return Boolean
	 */
	function IsOlderVersion() {
		return $this->isCurrentVersion ? false : true;
	}

	/**
	 *
	 * This method can be extended to show products in the side bar.
	 *
	 * @return DataList (Products)
	 */
	function SidebarProducts(){
		return null;
	}

	/**
	 *
	 * This method can be extended to show products in the side bar.
	 *
	 * @return Product | Null
	 */
	function NextProduct(){
		$array = $this->getListOfIDs();
		$next = 0;
		foreach($array as $key => $id) {
			$id = intval($id);
			if($id == $this->ID) {
				if(isset($array[$key + 1])) {
					return Product::get()->byID(intval($array[$key + 1]));
				}
			}
		}
	}

	/**
	 *
	 * This method can be extended to show products in the side bar.
	 *
	 * @return Product | Null
	 */
	function PreviousProduct(){
		$array = $this->getListOfIDs();
		$previousID = 0;
		foreach($array as $key => $id) {
			$id = intval($id);
			if($id == $this->ID) {
				return Product::get()->byID($previousID);
			}
			$previousID = $id;
		}
		return null;
	}

	/**
	 *
	 * This method can be extended to show products in the side bar.
	 *
	 * @return Boolean
	 */
	function HasPreviousOrNextProduct(){
		return $this->PreviousProduct() || $this->NextProduct() ? true : false;
	}

	/**
	 * returns an array of product IDs, as saved in the last
	 * ProductGroup view (saved using session)
	 * @return Array
	 */
	protected function getListOfIDs(){
		$listOfIDs = Session::get(EcommerceConfig::get("ProductGroup", "session_name_for_product_array"));
		if($listOfIDs) {
			$arrayOfIDs = explode(",", $listOfIDs);
			if(is_array($arrayOfIDs)) {
				return $arrayOfIDs;
			}
		}
		return array();
	}


	public function debug(){
		$member = Member::currentUser();
		if(!$member || !$member->IsShopAdmin()) {
			$messages = array(
				'default' => 'You must login as an admin to access debug functions.'
			);
			Security::permissionFailure($this, $messages);
		}
		return $this->dataRecord->debug();
	}

}



class Product_Image extends Image {

	private static $casting = array(
		"CMSThumbnail" => "HTMLText"
	);

	/**
	 * Fields
	 * @return Array
	 */
	function summaryFields(){
		return array(
			"CMSThumbnail" => "Preview",
			"Title" => "Title"
		);
	}

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
	public function SmallWidth() {
		return EcommerceConfig::get("Product_Image", "small_image_width");
	}

	/**
	 *
	 * @return Int
	 */
	public function SmallHeight() {
		return EcommerceConfig::get("Product_Image", "small_image_height");
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
	 * @param GD $gd
	 * @return GD
	 **/
	function generateThumbnail(GD $gd) {
		$gd->setQuality(90);
		return $gd->paddedResize($this->ThumbWidth(), $this->ThumbHeight());
	}

	public function Thumbnail() {
		return $this->getFormattedImage('Thumbnail');
	}

	/**
	 * @usage can be used in a template like this $Image.SmallImage.Link
	 * @return GD
	 **/
	function generateSmallImage(GD $gd) {
		$gd->setQuality(90);
		return $gd->paddedResize($this->SmallWidth(), $this->SmallHeight());
	}

	public function SmallImage() {
		return $this->getFormattedImage('SmallImage');
	}

	/**
	 * @usage can be used in a template like this $Image.ContentImage.Link
	 * @return GD
	 **/
	function generateContentImage(GD $gd) {
		$gd->setQuality(90);
		return $gd->resizeByWidth($this->ContentWidth());
	}


	public function LargeImage() {
		return $this->getFormattedImage('LargeImage');
	}
	/**
	 * @usage can be used in a template like this $Image.LargeImage.Link
	 * @return GD
	 **/
	function generateLargeImage(GD $gd) {
		$gd->setQuality(90);
		return $gd->resizeByWidth($this->LargeWidth());
	}


	function exists(){
		if(isset($this->ID)) {
			if($this->ID) {
				if(file_exists($this->getFullPath())) {
					return true;
				}
			}
		}
	}

	/**
	 *
	 * @return String HTML
	 */
	function CMSThumbnail(){
		return $this->getCMSThumbnail();
	}


	/**
	 *
	 * @return String HTML
	 */
	function getCMSThumbnail(){
		$smallImage = $this->SmallImage();
		if($smallImage) {
			$icon = "<img src=\"".$smallImage->FileName."\" style=\"border: 1px solid black; height: 100px; \" />";
		}
		else {
			$icon = "[MISSING IMAGE]";
		}
		return DBField::create_field("HTMLText", $icon);
	}


}

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
	 *
	 * @return Boolean
	 */
	function canCreate($member = null) {
		if(is_a(Controller::curr(), Object::getCustomClass("ProductsAndGroupsModelAdmin"))) {
			return false;
		}
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
			$isThemeEnabled = Config::inst()->get('SSViewer', 'theme_enabled');
			if(!$isThemeEnabled) {
				Config::inst()->update('SSViewer', 'theme_enabled', true);
			}
			$tableTitle = strip_tags($product->renderWith("ProductTableTitle"));
			if(!$isThemeEnabled) {
				Config::inst()->update('SSViewer', 'theme_enabled', false);
			}
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



class Product_ProductImageUploadField extends UploadField {

	function getRelationAutosetClass($default ='File'){
		return "Image";
	}


	/**
	 * @var array Config for this field used in both, php and javascript
	 * (will be merged into the config of the javascript file upload plugin).
	 * See framework/_config/uploadfield.yml for configuration defaults and documentation.
	 */
	protected $ufConfig = array(
		/**
		 * @var boolean
		 */
		'autoUpload' => true,
		/**
		 * php validation of allowedMaxFileNumber only works when a db relation is available, set to null to allow
		 * unlimited if record has a has_one and allowedMaxFileNumber is null, it will be set to 1
		 * @var int
		 */
		'allowedMaxFileNumber' => 1,
		/**
		 * @var boolean|string Can the user upload new files, or just select from existing files.
		 * String values are interpreted as permission codes.
		 */
		'canUpload' => true,
		/**
		 * @var boolean|string Can the user attach files from the assets archive on the site?
		 * String values are interpreted as permission codes.
		 */
		'canAttachExisting' => "CMS_ACCESS_AssetAdmin",
		/**
		 * @var boolean If a second file is uploaded, should it replace the existing one rather than throwing an errror?
		 * This only applies for has_one relationships, and only replaces the association
		 * rather than the actual file database record or filesystem entry.
		 */
		'replaceExistingFile' => true,
		/**
		 * @var int
		 */
		'previewMaxWidth' => 80,
		/**
		 * @var int
		 */
		'previewMaxHeight' => 60,
		/**
		 * javascript template used to display uploading files
		 * @see javascript/UploadField_uploadtemplate.js
		 * @var string
		 */
		'uploadTemplateName' => 'ss-uploadfield-uploadtemplate',
		/**
		 * javascript template used to display already uploaded files
		 * @see javascript/UploadField_downloadtemplate.js
		 * @var string
		 */
		'downloadTemplateName' => 'ss-uploadfield-downloadtemplate',
		/**
		 * FieldList $fields or string $name (of a method on File to provide a fields) for the EditForm
		 * @example 'getCMSFields'
		 * @var FieldList|string
		 */
		'fileEditFields' => null,
		/**
		 * FieldList $actions or string $name (of a method on File to provide a actions) for the EditForm
		 * @example 'getCMSActions'
		 * @var FieldList|string
		 */
		'fileEditActions' => null,
		/**
		 * Validator (eg RequiredFields) or string $name (of a method on File to provide a Validator) for the EditForm
		 * @example 'getCMSValidator'
		 * @var string
		 */
		'fileEditValidator' => null
	);

}
