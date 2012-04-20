<?php

/**
 * Setting for Ecommerce
 *
 *
 *
 *
 */

class EcommerceDBConfig extends DataObject {


	/**
	 * Standard SS Method
	 */
	public static $db = array(
		"Title" => "Varchar(30)",
		"UseThisOne" => "Boolean",
		"ShopClosed" => "Boolean",
		"ShopPricesAreTaxExclusive" => "Boolean",
		"ShopPhysicalAddress" => "HTMLText",
		"ReceiptEmail" => "Varchar(255)",
		"PostalCodeURL" => "Varchar(255)",
		"PostalCodeLabel" => "Varchar(255)",
		"NumberOfProductsPerPage" => "Int",
		"OnlyShowProductsThatCanBePurchased" => "Int",
		"ProductsHaveWeight" => "Boolean",
		"ProductsHaveModelNames" => "Boolean",
		"ProductsHaveQuantifiers" => "Boolean",
		"ProductsAlsoInOtherGroups" => "Boolean",
		"ProductsHaveVariations" => "Boolean"
	);

	/**
	 * Standard SS Method
	 */
	public static $has_one = array(
		"EmailLogo" => "Image",
		"DefaultProductImage" => "Product_Image"
	);


	//database

	//todo: would it be faster if we index everything?
	static $indexes = array(
	);
	//formatting

	public static $casting = array(
		"UseThisOneNice" => "Varchar"
	); //adds computed fields that can also have a type (e.g.

	public static $searchable_fields = array(
		"Title" => "PartialMatchFilter"
	);

	public static $field_labels = array();

	public static $summary_fields = array(
		"Title" => "Title",
		"UseThisOneNice" => "Use this configuration set"
	); //note no => for relational fields

	//CRUD settings

	public function canCreate($member = null) {
		return $this->canEdit($member);
	}

	public function canView($member = null) {
		return $this->canEdit($member);
	}

	public function canEdit($member = null) {
		if(!$member) {
			$member == Member::currentUser();
		}
		$shopAdminCode = EcommerceConfig::get("EcommerceRole", "admin_permission_code");
		if($member && Permission::checkMember($member, $shopAdminCode)) {
			return true;
		}
		return parent::canEdit($member);
	}

	public function canDelete($member = null) {return false;}
	//defaults

	public static $default_sort = "\"UseThisOne\" DESC, \"Created\" ASC";

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
		"ProductsHaveWeight" => false,
		"ProductsHaveModelNames" => false,
		"ProductsHaveQuantifiers" => false,
		"ProductsAlsoInOtherGroups" => false,
		"ProductsHaveVariations" => false
	);//use fieldName => Default Value

	public function populateDefaults() {
		$this->ReceiptEmail = Email::getAdminEmail();
		parent::populateDefaults();
	}


	public static $singular_name = "Ecommerce Configuration";
		function i18n_singular_name() { return _t("EcommerceDBConfig.ECOMMERCECONFIGURATION", "Ecommerce Configuration");}

	public static $plural_name = "Ecommerce Configuration";
		function i18n_plural_name() { return _t("EcommerceDBConfig.ECOMMERCECONFIGURATIONS", "Ecommerce Configurations");}


	/**
	 * static holder for its own (or other EcommerceDBConfig) class.
	 * @var String
	 */
	protected static $my_current_one = null;
	/**
	 * implements singleton pattern
	 * @return EcommerceDBConfig
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

	function customFieldLabels(){
		$newLabels = array(
			"UseThisOne" => _t("EcommerceDBConfig.USETHISONE", "Use these configuration settings (you can create several setting records so that you can switch configurations)."),
			"ShopClosed" => _t("EcommerceDBConfig.SHOPCLOSED", "Shop Closed"),
			"ShopPricesAreTaxExclusive" => _t("EcommerceDBConfig.SHOPPRICESARETAXEXCLUSIVE", "Shop prices are tax exclusive (if this option is not ticked, it is assumed that prices are inclusive of tax)"),
			"ShopPhysicalAddress" => _t("EcommerceDBConfig.SHOPPHYSICALADDRESS", "Shop physical address"),
			"ReceiptEmail" => _t("EcommerceDBConfig.RECEIPTEMAIL", "Shope Email Address (e.g. sales@mysite.com)"),
			"PostalCodeURL" => _t("EcommerceDBConfig.POSTALCODEURL", "Postal code link"),
			"PostalCodeLabel" => _t("EcommerceDBConfig.POSTALCODELABEL", "Postal code link label"),
			"NumberOfProductsPerPage" => _t("EcommerceDBConfig.NUMBEROFPRODUCTSPERPAGE", "Number of products per page"),
			"OnlyShowProductsThatCanBePurchased" => _t("EcommerceDBConfig.ONLYSHOWPRODUCTSTHATCANBEPURCHASED", "Only show products that can be purchased"),
			"ProductsHaveWeight" =>  _t("EcommerceDBConfig.PRODUCTSHAVEWEIGHT", "Products have weight (e.g. 1.2kg) - untick to hide weight field"),
			"ProductsHaveModelNames" =>  _t("EcommerceDBConfig.PRODUCTSHAVEMODELNAMES", "Products have model names / numbers -  untick to hide model field"),
			"ProductsHaveQuantifiers" => _t("EcommerceDBConfig.PRODUCTSHAVEQUANTIFIERS", "Products have quantifiers (e.g. per year, each, per dozen, etc...) - untick to hide model field"),
			"ProductsAlsoInOtherGroups" => _t("EcommerceDBConfig.PRODUCTSALSOINOTHERGROUPS", "Allow products to show in multiple product groups."),
			"ProductsHaveVariations" => _t("EcommerceDBConfig.PRODUCTSHAVEVARIATIONS", "Products have variations (e.g. size, colour, etc...)."),
			"EmailLogo" => _t("EcommerceDBConfig.EMAILLOGO", "Email Logo"),
			"DefaultProductImage" => _t("EcommerceDBConfig.DEFAULTPRODUCTIMAGE", "Default Product Image")
		);
		return $newLabels;
	}


	function getCMSFields() {
		$fields = parent::getCMSFields();
		foreach($this->fieldLabels() as $name => $label) {
			$fields->removeByName($name);
		}
		//new section
		$fieldLabels = $this->fieldLabels();
		$versionInfo = new EcommerceConfigDefinitions();
		$fields->addFieldsToTab("Root",array(
			new Tab('Pricing',
				new CheckboxField("ShopPricesAreTaxExclusive", $fieldLabels["ShopPricesAreTaxExclusive"])
			),
			new Tab('ProductDisplay',
				new NumericField("NumberOfProductsPerPage", $fieldLabels["NumberOfProductsPerPage"]),
				new CheckboxField("OnlyShowProductsThatCanBePurchased", $fieldLabels["OnlyShowProductsThatCanBePurchased"]),
				new CheckboxField("ProductsHaveWeight", $fieldLabels["ProductsHaveWeight"]),
				new CheckboxField("ProductsHaveModelNames",$fieldLabels["ProductsHaveModelNames"]),
				new CheckboxField("ProductsHaveQuantifiers", $fieldLabels["ProductsHaveQuantifiers"]),
				new CheckboxField("ProductsAlsoInOtherGroups", $fieldLabels["ProductsAlsoInOtherGroups"]),
				new ImageField("DefaultProductImage", $fieldLabels["DefaultProductImage"], null, null, null, "default-product-image")
			),
			new Tab('Checkout',
				new TextField("PostalCodeURL", $fieldLabels["PostalCodeURL"]),
				new TextField("PostalCodeLabel", $fieldLabels["PostalCodeLabel"])
			),
			new Tab('Emails',
				new TextField("ReceiptEmail",$fieldLabels["ReceiptEmail"]),
				new ImageField("EmailLogo",$fieldLabels["EmailLogo"] ,  null, null, null, "logos")
			),
			new Tab('Legal',
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
	 * tells is if a classanme is a buyable
	 * @param String $className - name of the class to be tested
	 * @return Boolean
	 */
	static function is_buyable($className) {
		$implementorsArray = class_implements($className);
		if(is_array($implementorsArray) && in_array("BuyableModel", $implementorsArray)) {
			return true;
		}
		return false;
	}


	/**
	 * Return the currency being used on the site.
	 * @return string Currency code, e.g. "NZD" or "USD"
	 */
	function Currency() {
		if(class_exists('Payment')) {
			return Payment::site_currency();
		}
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
		$obj->Link = DefaultImageLink();
		$obj->URL = DefaultImageLink();
		return $obj;
	}

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
	}



	/**
	 *
	 * Casted Variable
	 * @return String
	 */
	function UseThisOneNice(){
		return $this->UseThisOne ? "YES" : "NO";
	}



}


