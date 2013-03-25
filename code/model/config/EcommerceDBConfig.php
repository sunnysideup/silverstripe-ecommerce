<?php

/**
 * Database Settings for E-commerce
 * Similar to SiteConfig but then for E-commerce
 * To access a singleton here, use: EcommerceDBConfig::current_ecommerce_db_config()
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceDBConfig extends DataObject {

	/**
	 * Standard SS Variable
	 * @var Array
	 */
	public static $db = array(
		"Title" => "Varchar(30)",
		"UseThisOne" => "Boolean",
		"ShopClosed" => "Boolean",
		"ShopPricesAreTaxExclusive" => "Boolean",
		"InvoiceTitle" => "Varchar(200)",
		"ShopPhysicalAddress" => "HTMLText",
		"ReceiptEmail" => "Varchar(255)",
		"PostalCodeURL" => "Varchar(255)",
		"PostalCodeLabel" => "Varchar(255)",
		"NumberOfProductsPerPage" => "Int",
		"OnlyShowProductsThatCanBePurchased" => "Boolean",
		"NotForSaleMessage" => "HTMLText",
		"ProductsHaveWeight" => "Boolean",
		"ProductsHaveModelNames" => "Boolean",
		"ProductsHaveQuantifiers" => "Boolean",
		"ProductsAlsoInOtherGroups" => "Boolean",
		"ProductsHaveVariations" => "Boolean",
		"CurrenciesExplanation" => "HTMLText",
		'AllowFreeProductPurchase' => 'Boolean'
	);

	/**
	 * Standard SS Variable
	 * @var Array
	 */
	public static $has_one = array(
		"EmailLogo" => "Image",
		"DefaultProductImage" => "Product_Image"
	);

	/**
	 * Standard SS Variable
	 * @var Array
	 */
	public static $indexes = array(
		"UseThisOne" => true,
		"ShopClosed" => true,
		"ShopPricesAreTaxExclusive" => true,
		"NumberOfProductsPerPage" => true,
		"OnlyShowProductsThatCanBePurchased" => true
	);

	/**
	 * Standard SS Variable
	 * @var Array
	 */
	public static $casting = array(
		"UseThisOneNice" => "Varchar"
	); //adds computed fields that can also have a type (e.g.

	/**
	 * Standard SS Variable
	 * @var Array
	 */
	public static $searchable_fields = array(
		"Title" => "PartialMatchFilter"
	);

	/**
	 * Standard SS Variable
	 * @var Array
	 */
	public static $field_labels = array();

	/**
	 * Standard SS Variable
	 * @var Array
	 */
	public static $summary_fields = array(
		"Title" => "Title",
		"UseThisOneNice" => "Use this configuration set"
	); //note no => for relational fields


	/**
	 * Standard SS Method
	 * @param Member $member
	 * @var Boolean
	 */
	public function canCreate($member = null) {
		return $this->canEdit($member);
	}

	/**
	 * Standard SS Method
	 * @param Member $member
	 * @var Boolean
	 */
	public function canView($member = null) {
		return $this->canEdit($member);
	}

	/**
	 * Standard SS Method
	 * @param Member $member
	 * @var Boolean
	 */
	public function canEdit($member = null) {
		if(!$member) {
			$member = Member::currentUser();
		}
		$shopAdminCode = EcommerceConfig::get("EcommerceRole", "admin_permission_code");
		if($member && Permission::checkMember($member, $shopAdminCode)) {
			return true;
		}
		return parent::canEdit($member);
	}

	/**
	 * Standard SS Method
	 * @param Member $member
	 * @var Boolean
	 */
	public function canDelete($member = null) {
		if($this->UseThisOne) {
			return false;
		}
		else {
			return parent::canDelete($member);
		}
	}

	/**
	 * Standard SS variable
	 * @var String
	 */
	public static $default_sort = "\"UseThisOne\" DESC, \"Created\" ASC";

	/**
	 * Standard SS variable
	 * @var Array
	 */
	public static $defaults = array(
		"Title" => "Ecommerce Site Config",
		"UseThisOne" => true,
		"ShopClosed" => false,
		"ShopPricesAreTaxExclusive" => false,
		"ShopPhysicalAddress" => "<p>Enter your shop address here.</p>",
		//"ReceiptEmail" => "Varchar(255)", - see populate defaults
		"PostalCodeURL" => "",
		"PostalCodeLabel" => "",
		"NumberOfProductsPerPage" => 12,
		"OnlyShowProductsThatCanBePurchased" => false,
		"NotForSaleMessage" => "<p>Not for sale, please contact us for more information.</p>",
		"ProductsHaveWeight" => false,
		"ProductsHaveModelNames" => false,
		"ProductsHaveQuantifiers" => false,
		"ProductsAlsoInOtherGroups" => false,
		"ProductsHaveVariations" => false,
		"CurrenciesExplanation" => "<p>Apart from our main currency, you can view prices in a number of other currencies. The exchange rate is indicative only.</p>",
		'AllowFreeProductPurchase' => true
	);

	/**
	 * Standard SS Method
	 * @var Array
	 */
	public function populateDefaults() {
		parent::populateDefaults();
		$this->ReceiptEmail = Email::getAdminEmail();
	}

	/**
	 * Standard SS variable
	 * @var String
	 */
	public static $singular_name = "Ecommerce Configuration";
		function i18n_singular_name() { return _t("EcommerceDBConfig.ECOMMERCECONFIGURATION", "Ecommerce Configuration");}

	/**
	 * Standard SS variable
	 * @var String
	 */
	public static $plural_name = "Ecommerce Configuration";
		function i18n_plural_name() { return _t("EcommerceDBConfig.ECOMMERCECONFIGURATIONS", "Ecommerce Configurations");}

	/**
	 * static holder for its own (or other EcommerceDBConfig) class.
	 * @var String
	 */
	protected static $my_current_one = null;

	/**
	 * implements singleton pattern
	 * @return EcommerceDBConfig | Object
	 */
	public static function current_ecommerce_db_config(){
		if(!self::$my_current_one) {
			$className =  EcommerceConfig::get("EcommerceDBConfig", "ecommerce_db_config_class_name");
			self::$my_current_one = DataObject::get_one($className);
		}
		return self::$my_current_one;
	}

	/**
	 * standard SS method for decorators.
	 * @param Array - $fields: array of fields to start with
	 * @return null ($fields variable is automatically updated)
	 */
	function fieldLabels($includerelations = true) {
		$defaultLabels = parent::fieldLabels();
		$newLabels = $this->customFieldLabels();
		$labels = array_merge($defaultLabels, $newLabels);
		$this->extend('updateFieldLabels', $labels);
		return $labels;
	}

	/**
	 * definition of field lables
	 * TODO: is this a common SS method?
	 * @return Array
	 */
	function customFieldLabels(){
		$newLabels = array(
			"Title" => _t("EcommerceDBConfig.TITLE", "Name"),
			"UseThisOne" => _t("EcommerceDBConfig.USETHISONE", "Use these configuration settings (you can create several setting records so that you can switch between configurations)."),
			"ShopClosed" => _t("EcommerceDBConfig.SHOPCLOSED", "Shop Closed"),
			"ShopPricesAreTaxExclusive" => _t("EcommerceDBConfig.SHOPPRICESARETAXEXCLUSIVE", "Shop prices are tax exclusive (if this option is NOT ticked, it is assumed that prices are tax inclusive)"),
			"InvoiceTitle" => _t("EcommerceDBConfig.INVOICETITLE", "Invoice title (e.g. Tax Invoice or Update from ...)"),
			"ShopPhysicalAddress" => _t("EcommerceDBConfig.SHOPPHYSICALADDRESS", "Shop physical address"),
			"ReceiptEmail" => _t("EcommerceDBConfig.RECEIPTEMAIL", "Shop Email Address (e.g. sales@mysite.com, you can also use something like: \"Our Shop Name Goes Here\" &lt;sales@mysite.com&gt;)"),
			"PostalCodeURL" => _t("EcommerceDBConfig.POSTALCODEURL", "Postal code link"),
			"PostalCodeLabel" => _t("EcommerceDBConfig.POSTALCODELABEL", "Postal code link label"),
			"NumberOfProductsPerPage" => _t("EcommerceDBConfig.NUMBEROFPRODUCTSPERPAGE", "Number of products per page"),
			"OnlyShowProductsThatCanBePurchased" => _t("EcommerceDBConfig.ONLYSHOWPRODUCTSTHATCANBEPURCHASED", "Only show products that can be purchased"),
			"NotForSaleMessage" => _t("EcommerceDBConfig.NOTFORSALEMESSAGE", "Message shown for products that can not be purchased"),
			"ProductsHaveWeight" =>  _t("EcommerceDBConfig.PRODUCTSHAVEWEIGHT", "Products have weight (e.g. 1.2kg) - untick to hide weight field"),
			"ProductsHaveModelNames" =>  _t("EcommerceDBConfig.PRODUCTSHAVEMODELNAMES", "Products have model names / numbers -  untick to hide model field"),
			"ProductsHaveQuantifiers" => _t("EcommerceDBConfig.PRODUCTSHAVEQUANTIFIERS", "Products have quantifiers (e.g. per year, each, per dozen, etc...) - untick to hide model field"),
			"ProductsAlsoInOtherGroups" => _t("EcommerceDBConfig.PRODUCTSALSOINOTHERGROUPS", "Allow products to show in multiple product groups."),
			"ProductsHaveVariations" => _t("EcommerceDBConfig.PRODUCTSHAVEVARIATIONS", "Products have variations (e.g. size, colour, etc...)."),
			"CurrenciesExplanation" => _t("EcommerceDBConfig.CURRENCIESEXPLANATION", "Explanation on how the currency options work (if any)."),
			"EmailLogo" => _t("EcommerceDBConfig.EMAILLOGO", "Email Logo"),
			"DefaultProductImage" => _t("EcommerceDBConfig.DEFAULTPRODUCTIMAGE", "Default Product Image"),
			"DefaultThumbnailImageSize" => _t("EcommerceDBConfig.DEFAULTTHUMBNAILIMAGESIZE", "Product Thumbnail Optimised Size"),
			"DefaultSmallImageSize" => _t("EcommerceDBConfig.DEFAULTSMALLIMAGESIZE", "Product Small Image Optimised Size"),
			"DefaultContentImageSize" => _t("EcommerceDBConfig.DEFAULTCONTENTIMAGESIZE", "Product Content Image Optimised Size"),
			"DefaultLargeImageSize" => _t("EcommerceDBConfig.DEFAULTLARGEIMAGESIZE", "Product Large Image Optimised Size"),
			'AllowFreeProductPurchase' => _t('EcommerceDBConfig.ALLOWFREEPRODUCTPURCHASE', 'Allow free products to be purchased')
		);
		return $newLabels;
	}

	/**
	 * standard SS method
	 * @return FieldSet
	 */
	function getCMSFields() {
		$fields = parent::getCMSFields();
		foreach($this->fieldLabels() as $name => $label) {
			$fields->removeByName($name);
		}
		//new section
		$fieldLabels = $this->fieldLabels();
		$productImage = new Product_Image();
		$versionInfo = new EcommerceConfigDefinitions();
		$fields->addFieldToTab("Root.Main", new TextField("Title", $fieldLabels["Title"]));
		$fields->addFieldsToTab("Root",array(
			new Tab('Pricing',
				new CheckboxField("ShopPricesAreTaxExclusive", $fieldLabels["ShopPricesAreTaxExclusive"]),
				new HTMLEditorField("CurrenciesExplanation", $fieldLabels["CurrenciesExplanation"], 2, 2)
			),
			new Tab('ProductDisplay',
				new NumericField("NumberOfProductsPerPage", $fieldLabels["NumberOfProductsPerPage"]),
				new CheckboxField("OnlyShowProductsThatCanBePurchased", $fieldLabels["OnlyShowProductsThatCanBePurchased"]),
				new HTMLEditorField("NotForSaleMessage", $fieldLabels["NotForSaleMessage"], 2, 2),
				new CheckboxField("ProductsHaveWeight", $fieldLabels["ProductsHaveWeight"]),
				new CheckboxField("ProductsHaveModelNames",$fieldLabels["ProductsHaveModelNames"]),
				new CheckboxField("ProductsHaveQuantifiers", $fieldLabels["ProductsHaveQuantifiers"]),
				new CheckboxField("ProductsAlsoInOtherGroups", $fieldLabels["ProductsAlsoInOtherGroups"]),
				new CheckboxField("ProductsHaveVariations", $fieldLabels["ProductsHaveVariations"]),
				new CheckboxField('AllowFreeProductPurchase', $fieldLabels['AllowFreeProductPurchase'])
			),
			new Tab('ProductImages',
				new ImageField("DefaultProductImage", $fieldLabels["DefaultProductImage"], null, null, null, "default-product-image"),
				new ReadonlyField("DefaultThumbnailImageSize", $fieldLabels["DefaultThumbnailImageSize"], $productImage->ThumbWidth()."px x ".$productImage->ThumbHeight()."px "),
				new ReadonlyField("DefaultSmallImageSize", $fieldLabels["DefaultSmallImageSize"], $productImage->SmallWidth()."px x ".$productImage->SmallHeight()."px "),
				new ReadonlyField("DefaultContentImageSize", $fieldLabels["DefaultContentImageSize"], $productImage->ContentWidth()."px wide"),
				new ReadonlyField("DefaultLargeImageSize", $fieldLabels["DefaultLargeImageSize"], $productImage->LargeWidth()."px wide")
			),
			new Tab('Checkout',
				new TextField("PostalCodeURL", $fieldLabels["PostalCodeURL"]),
				new TextField("PostalCodeLabel", $fieldLabels["PostalCodeLabel"])
			),
			new Tab('Emails',
				new TextField("ReceiptEmail",$fieldLabels["ReceiptEmail"]),
				new ImageField("EmailLogo",$fieldLabels["EmailLogo"] ,  null, null, null, "logos")
			),
			new Tab('Invoice',
				new TextField("InvoiceTitle",$fieldLabels["InvoiceTitle"]),
				new HTMLEditorField("ShopPhysicalAddress",$fieldLabels["ShopPhysicalAddress"] , 5,5)
			),
			new Tab('Process',
				new ComplexTableField($this, "OrderSteps", "OrderStep")
			),
			new Tab('Advanced',
				new HeaderField("EcommerceVersionHeading", "Version"),
				new LiteralField("EcommerceVersion", "<p><strong>E-commerce</strong>: ".$versionInfo->Version()."</p>"),
				new LiteralField("SVNVersion", "<p><strong>SVN</strong>: ".$versionInfo->SvnVersion()."</p>"),
				new LiteralField("GITVersion", "<p><strong>GIT</strong>: not available yet.</p>"),
				new HeaderField("ReviewHardcodedSettingsHeading", "Hard-coded settings"),
				new LiteralField(
					"ReviewHardcodedSettings",
					"<p>
						Your developer has pre-set some configurations for you.  You can
						<a href=\"/dev/ecommerce/ecommercecheckconfiguration\" target=\"_blank\">review these settings</a>
						but you will need to ask your developer to change them if they are not right.
						The reason they can not be set is that changing them can potentially break your application.
					</p>"
				)
			)
			/*$processtab = new Tab('OrderProcess',
				new LiteralField('op','Include a drag-and-drop interface for customising order steps (Like WidgetArea)')
			)*/
		));
		$fields->addFieldsToTab(
			"Root.Main",
			array(
				new CheckboxField("UseThisOne", $fieldLabels["UseThisOne"]),
				new CheckboxField("ShopClosed", $fieldLabels["ShopClosed"])
			)
		);
		return $fields;
	}


	/**
	 * tells us if a Class Name is a buyable
	 * @todo: consider using Ecomerce Configuration instead?
	 * In EcomConfig we only list base classes.
	 *@param String $className - name of the class to be tested
	 *@return Boolean
	 */
	static function is_buyable($className) {
		$implementorsArray = class_implements($className);
		if(is_array($implementorsArray) && in_array("BuyableModel", $implementorsArray)) {
			return true;
		}
		return false;
	}

	/**
	 * Returns the Current Member
	 * @return Null | Member
	 */
	public function Customer(){
		return Member::currentUser();
	}

	/**
	 * Return the currency being used on the site e.g. "NZD" or "USD"
	 * @return String
	 */
	public function Currency() {
		if(class_exists('Payment')) {
			return Payment::site_currency();
		}
	}

	/**
	 *
	 * return DataObjectSet (list of EcommerceCurrencies)
	 */
	function Currencies() {
		return EcommerceCurrency::get_list();
	}

	/**
	 * @return String (URLSegment)
	 **/
	public function AccountPageLink() {
		return AccountPage::find_link();
	}

	/**
	 * @return String (URLSegment)
	 **/
	public function CheckoutLink() {
		return CheckoutPage::find_link();
	}

	/**
	 *@return String (URLSegment)
	 **/
	public function CartPageLink() {
		return CartPage::find_link();
	}

	/**
	 *@return String (URLSegment)
	 **/
	public function OrderConfirmationPageLink() {
		return OrderConfirmationPage::find_link();
	}


	/**
	 * Returns a link to a default image.
	 * If a default image is set in the site config then this link is returned
	 * Otherwise, a standard link is returned
	 * @return String
	 */
	function DefaultImageLink() {
		if($this->DefaultProductImageID) {
			$defaultImage = $this->DefaultProductImage();
			if($defaultImage && $defaultImage->exists()) {
				return $defaultImage->Link();
			}
		}
		return "ecommerce/images/productPlaceHolderThumbnail.gif";
	}

	/**
	 * Returns the default image or a dummy one if it does not exists.
	 * @return String
	 */
	function DefaultImage() {
		if($this->DefaultProductImageID) {
			if($defaultImage = $this->DefaultProductImage()) {
				if($defaultImage->exists()) {
					return $defaultImage;
				}
			}
		}
		$obj = new Product_Image();
		$obj->Link = $this->DefaultImageLink();
		$obj->URL = $this->DefaultImageLink();
		return $obj;
	}

	/**
	 * standard SS method
	 */
	function onAfterWrite(){
		if($this->UseThisOne) {
			$configs = DataObject::get("EcommerceDBConfig", "\"UseThisOne\" = 1 AND \"ID\" <>".$this->ID);
			if($configs){
				foreach($configs as $config) {
					$config->UseThisOne = 0;
					$config->write();
				}
			}
		}
		$configs = DataObject::get("EcommerceDBConfig", "\"Title\" = '".$this->Title."' AND \"ID\" <>".$this->ID);
		if($configs){
			foreach($configs as $key => $config) {
				$config->Title = $config->Title."_".$config->ID;
				$config->write();
			}
		}
	}

	/**
	 * standard SS Method
	 */
	public function requireDefaultRecords(){
		parent::requireDefaultRecords();
		if(!self::current_ecommerce_db_config()) {
			$obj = new EcommerceDBConfig();
			$obj->write();
		}
		DB::alteration_message("
			<hr /><hr /><hr /><hr /><hr />
			<h1 style=\"color: darkRed\">Please make sure to review your <a href=\"/dev/ecommerce/\">e-commerce settings</a>.</h1>
			<hr /><hr /><hr /><hr /><hr />",
			"edited"
		);
	}

	/**
	 * returns site config
	 * @return SiteConfig
	 */
	public function SiteConfig(){
		return SiteConfig::current_site_config();
	}

	/**
	 *
	 * Casted Variable
	 * @return String
	 */
	public function UseThisOneNice(){
		return $this->UseThisOne ? "YES" : "NO";
	}



}


