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

class EcommerceDBConfig extends DataObject implements EditableEcommerceObject {

	/**
	 * Standard SS Variable
	 * @var Array
	 */
	private static $db = array(
		"Title" => "Varchar(30)",
		"UseThisOne" => "Boolean",
		"ShopClosed" => "Boolean",
		"ShopPricesAreTaxExclusive" => "Boolean",
		"InvoiceTitle" => "Varchar(200)",
		"PackingSlipTitle" => "Varchar(200)",
		"PackingSlipNote" => "HTMLText",
		"ShopPhysicalAddress" => "HTMLText",
		"ReceiptEmail" => "Varchar(255)",
		"PostalCodeURL" => "Varchar(255)",
		"PostalCodeLabel" => "Varchar(255)",
		"NumberOfProductsPerPage" => "Int",
		"ProductsAlsoInOtherGroups" => "Boolean",
		"OnlyShowProductsThatCanBePurchased" => "Boolean",
		"NotForSaleMessage" => "HTMLText",
		"ProductsHaveWeight" => "Boolean",
		"ProductsHaveModelNames" => "Boolean",
		"ProductsHaveQuantifiers" => "Boolean",
		//"ProductsHaveVariations" => "Boolean",
		"CurrenciesExplanation" => "HTMLText",
		'AllowFreeProductPurchase' => 'Boolean'
	);

	/**
	 * Standard SS Variable
	 * @var Array
	 */
	private static $has_one = array(
		"EmailLogo" => "Image",
		"DefaultProductImage" => "Product_Image"
	);

	/**
	 * Standard SS Variable
	 * @var Array
	 */
	private static $indexes = array(
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
	private static $casting = array(
		"UseThisOneNice" => "Varchar"
	); //adds computed fields that can also have a type (e.g.

	/**
	 * Standard SS Variable
	 * @var Array
	 */
	private static $searchable_fields = array(
		"Title" => "PartialMatchFilter"
	);

	/**
	 * Standard SS Variable
	 * @var Array
	 */
	private static $field_labels = array();

	/**
	 * Standard SS Variable
	 * @var Array
	 */
	private static $summary_fields = array(
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
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
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
			if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
			return parent::canEdit($member);
		}
	}

	/**
	 * Standard SS variable
	 * @var String
	 */
	private static $default_sort = "\"UseThisOne\" DESC, \"Created\" ASC";

	/**
	 * Standard SS variable
	 * @var Array
	 */
	private static $defaults = array(
		"Title" => "Ecommerce Site Config",
		"UseThisOne" => true,
		"ShopClosed" => false,
		"ShopPricesAreTaxExclusive" => false,
		"InvoiceTitle" => "Invoice",
		"PackingSlipTitle" => "Package Contents",
		"PackingSlipNote" => "Please make sure that all items are contained in this package.",
		"ShopPhysicalAddress" => "<p>Enter your shop address here.</p>",
		//"ReceiptEmail" => "Varchar(255)", - see populate defaults
		"PostalCodeURL" => "",
		"PostalCodeLabel" => "",
		"NumberOfProductsPerPage" => 12,
		"ProductsAlsoInOtherGroups" => false,
		"OnlyShowProductsThatCanBePurchased" => false,
		"NotForSaleMessage" => "<p>Not for sale, please contact us for more information.</p>",
		"ProductsHaveWeight" => false,
		"ProductsHaveModelNames" => false,
		"ProductsHaveQuantifiers" => false,
		//"ProductsHaveVariations" => false,
		"CurrenciesExplanation" => "<p>Apart from our main currency, you can view prices in a number of other currencies. The exchange rate is indicative only.</p>",
		'AllowFreeProductPurchase' => true
	);

	/**
	 * Standard SS Method
	 * @var Array
	 */
	public function populateDefaults() {
		parent::populateDefaults();
		$this->ReceiptEmail = Email::config()->admin_email;
	}

	/**
	 * Standard SS variable
	 * @var String
	 */
	private static $singular_name = "Main E-commerce Configuration";
		function i18n_singular_name() { return _t("EcommerceDBConfig.ECOMMERCECONFIGURATION", "Main E-commerce Configuration");}

	/**
	 * Standard SS variable
	 * @var String
	 */
	private static $plural_name = "Main E-commerce Configurations";
		function i18n_plural_name() { return _t("EcommerceDBConfig.ECOMMERCECONFIGURATIONS", "Main E-commerce Configurations");}

	/**
	 * Standard SS variable.
	 * @var String
	 */
	private static $description = "A set of configurations for the shop. Each shop needs to have one or more of these settings.";

	/**
	 * static holder for its own (or other EcommerceDBConfig) class.
	 * @var String | NULL
	 */
	private static $my_current_one = null;
		public static function reset_my_current_one() {self::$my_current_one = null;}

	/**
	 * implements singleton pattern.
	 * Gets the current USE THIS ONE e-commerce option.
	 * @return EcommerceDBConfig | Object
	 */
	public static function current_ecommerce_db_config(){
		if(!self::$my_current_one) {
			$className =  EcommerceConfig::get("EcommerceDBConfig", "ecommerce_db_config_class_name");
			if(!class_exists("EcommerceDBConfig")) {
				$class = "EcommerceDBConfig";
			}
			if(self::$my_current_one = $className::get()->filter(array("UseThisOne" => 1))->first()) {
				 //do nothing
			}
			else {
				self::$my_current_one = new $className();
			}
		}
		return self::$my_current_one;
	}

	/**
	 * standard SS method for decorators.
	 * @param Boolean $includerelations
	 * @return Array
	 */
	function fieldLabels($includerelations = true) {
		$defaultLabels = parent::fieldLabels();
		$newLabels = $this->customFieldLabels();
		$labels = array_merge($defaultLabels, $newLabels);
		$extendedLabels = $this->extend('updateFieldLabels', $labels);
		if($extendedLabels !== null && is_array($extendedLabels) && count($extendedLabels)) {
			foreach($extendedLabels as $extendedLabelsUpdate) {
				$labels += $extendedLabelsUpdate;
			}
		}
		return $labels;
	}

	/**
	 * definition of field lables
	 * TODO: is this a common SS method?
	 * @return Array
	 */
	function customFieldLabels(){
		$newLabels = array(
			"Title" => _t("EcommerceDBConfig.TITLE", "Name of settings"),
			"UseThisOne" => _t("EcommerceDBConfig.USETHISONE", "Use these configuration settings"),
			"ShopClosed" => _t("EcommerceDBConfig.SHOPCLOSED", "Shop Closed"),
			"ShopPricesAreTaxExclusive" => _t("EcommerceDBConfig.SHOPPRICESARETAXEXCLUSIVE", "Shop prices are tax exclusive"),
			"InvoiceTitle" => _t("EcommerceDBConfig.INVOICETITLE", "Default Email title"),
			"PackingSlipTitle" => _t("EcommerceDBConfig.PACKING_SLIP_TITLE", "Packing slip title"),
			"PackingSlipNote" => _t("EcommerceDBConfig.PACKING_SLIP_NOTE", "Packing slip notes"),
			"ShopPhysicalAddress" => _t("EcommerceDBConfig.SHOPPHYSICALADDRESS", "Shop physical address"),
			"ReceiptEmail" => _t("EcommerceDBConfig.RECEIPTEMAIL", "Shop Email Address"),
			"PostalCodeURL" => _t("EcommerceDBConfig.POSTALCODEURL", "Postal code link"),
			"PostalCodeLabel" => _t("EcommerceDBConfig.POSTALCODELABEL", "Postal code link label"),
			"NumberOfProductsPerPage" => _t("EcommerceDBConfig.NUMBEROFPRODUCTSPERPAGE", "Number of products per page"),
			"OnlyShowProductsThatCanBePurchased" => _t("EcommerceDBConfig.ONLYSHOWPRODUCTSTHATCANBEPURCHASED", "Only show products that can be purchased."),
			"NotForSaleMessage" => _t("EcommerceDBConfig.NOTFORSALEMESSAGE", "Not for sale message"),
			"ProductsHaveWeight" =>  _t("EcommerceDBConfig.PRODUCTSHAVEWEIGHT", "Products have weight (e.g. 1.2kg)"),
			"ProductsHaveModelNames" =>  _t("EcommerceDBConfig.PRODUCTSHAVEMODELNAMES", "Products have model names / numbers / codes"),
			"ProductsHaveQuantifiers" => _t("EcommerceDBConfig.PRODUCTSHAVEQUANTIFIERS", "Products have quantifiers (e.g. per year, each, per dozen, etc...)"),
			"ProductsAlsoInOtherGroups" => _t("EcommerceDBConfig.PRODUCTSALSOINOTHERGROUPS", "Allow products to show in multiple product groups"),
			//"ProductsHaveVariations" => _t("EcommerceDBConfig.PRODUCTSHAVEVARIATIONS", "Products have variations (e.g. size, colour, etc...)."),
			"CurrenciesExplanation" => _t("EcommerceDBConfig.CURRENCIESEXPLANATION", "Currency explanation"),
			"EmailLogo" => _t("EcommerceDBConfig.EMAILLOGO", "Email Logo"),
			"DefaultProductImage" => _t("EcommerceDBConfig.DEFAULTPRODUCTIMAGE", "Default Product Image"),
			"DefaultThumbnailImageSize" => _t("EcommerceDBConfig.DEFAULTTHUMBNAILIMAGESIZE", "Product Thumbnail Optimised Size"),
			"DefaultSmallImageSize" => _t("EcommerceDBConfig.DEFAULTSMALLIMAGESIZE", "Product Small Image Optimised Size"),
			"DefaultContentImageSize" => _t("EcommerceDBConfig.DEFAULTCONTENTIMAGESIZE", "Product Content Image Optimised Size"),
			"DefaultLargeImageSize" => _t("EcommerceDBConfig.DEFAULTLARGEIMAGESIZE", "Product Large Image Optimised Size"),
			"AllowFreeProductPurchase" => _t("EcommerceDBConfig.ALLOWFREEPRODUCTPURCHASE", "Allow free products to be purchased? ")
		);
		return $newLabels;
	}

	/**
	 * definition of field lables
	 * TODO: is this a common SS method?
	 * @return Array
	 */
	function customDescriptionsForFields(){
		$newLabels = array(
			"Title" => _t("EcommerceDBConfig.TITLE_DESCRIPTION", "For internal use only."),
			"UseThisOne" => _t("EcommerceDBConfig.USETHISONE_DESCRIPTION", "You can create several setting records so that you can switch between configurations."),
			"ShopPricesAreTaxExclusive" => _t("EcommerceDBConfig.SHOPPRICESARETAXEXCLUSIVE_DESCRIPTION", "If this option is NOT ticked, it is assumed that prices are tax inclusive."),
			"ReceiptEmail" => _t("EcommerceDBConfig.RECEIPTEMAIL_DESCRIPTION_DESCRIPTION", "e.g. sales@mysite.com, you can also use something like: \"Our Shop Name Goes Here\" &lt;sales@mysite.com&gt;"),
			"AllowFreeProductPurchase" => _t("EcommerceDBConfig.ALLOWFREEPRODUCTPURCHASE_DESCRIPTION", "This is basically a protection to disallow sales of products that do not have a price entered yet. "),
			"CurrenciesExplanation" => _t("EcommerceDBConfig.CURRENCIESEXPLANATION_DESCRIPTION", "Explain how the user can switch between currencies and how the exchange rates are worked out."),
			"PackingSlipTitle" => _t("EcommerceDBConfig.PACKINGSLIPTITLE", "e.g. Package Contents"),
			"PackingSlipNote" => _t("EcommerceDBConfig.PACKING_SLIP_NOTE", "e.g. a disclaimer"),
			"InvoiceTitle" => _t("EcommerceDBConfig.INVOICETITLE", "e.g. Tax Invoice or Update for your recent order on www.yoursite.co.nz"),
		);
		return $newLabels;
	}

	/**
	 * standard SS method
	 * @return FieldList
	 */
	function getCMSFields() {
		$fields = parent::getCMSFields();

		$self = $this;
		$self->beforeUpdateCMSFields(
			function($fields) use ($self) {
				foreach($self->customFieldLabels() as $name => $label) {
					$fields->removeByName($name);
				}
				//new section
				$fieldDescriptions = $self->customDescriptionsForFields();
				$fieldLabels = $self->fieldLabels();
				$productImage = new Product_Image();
				$versionInfo = EcommerceConfigDefinitions::create();
				$fields->addFieldToTab("Root.Main", new TextField("Title", $fieldLabels["Title"]));
				$fields->addFieldsToTab("Root",array(
					new Tab('Pricing',
						new CheckboxField("ShopPricesAreTaxExclusive", $fieldLabels["ShopPricesAreTaxExclusive"]),
						new CheckboxField('AllowFreeProductPurchase', $fieldLabels['AllowFreeProductPurchase']),
						$htmlEditorField1 = new HTMLEditorField("CurrenciesExplanation", $fieldLabels["CurrenciesExplanation"])
					),
					new Tab('Products',
						new NumericField("NumberOfProductsPerPage", $fieldLabels["NumberOfProductsPerPage"]),
						new CheckboxField("ProductsAlsoInOtherGroups", $fieldLabels["ProductsAlsoInOtherGroups"]),
						new CheckboxField("OnlyShowProductsThatCanBePurchased", $fieldLabels["OnlyShowProductsThatCanBePurchased"]),
						$htmlEditorField2 = new HTMLEditorField("NotForSaleMessage", $fieldLabels["NotForSaleMessage"]),
						new CheckboxField("ProductsHaveWeight", $fieldLabels["ProductsHaveWeight"]),
						new CheckboxField("ProductsHaveModelNames",$fieldLabels["ProductsHaveModelNames"]),
						new CheckboxField("ProductsHaveQuantifiers", $fieldLabels["ProductsHaveQuantifiers"])
						//new CheckboxField("ProductsHaveVariations", $fieldLabels["ProductsHaveVariations"])
					),
					new Tab('ProductImages',
						//new Product_ProductImageUploadField("DefaultProductImage", $fieldLabels["DefaultProductImage"], null, null, null, "default-product-image"),
						new ReadonlyField("DefaultThumbnailImageSize", $fieldLabels["DefaultThumbnailImageSize"], $productImage->ThumbWidth()."px x ".$productImage->ThumbHeight()."px "),
						new ReadonlyField("DefaultSmallImageSize", $fieldLabels["DefaultSmallImageSize"], $productImage->SmallWidth()."px x ".$productImage->SmallHeight()."px "),
						new ReadonlyField("DefaultContentImageSize", $fieldLabels["DefaultContentImageSize"], $productImage->ContentWidth()."px wide"),
						new ReadonlyField("DefaultLargeImageSize", $fieldLabels["DefaultLargeImageSize"], $productImage->LargeWidth()."px wide")
					),
					new Tab('AddressAndDelivery',
						new TextField("PostalCodeURL", $fieldLabels["PostalCodeURL"]),
						new TextField("PostalCodeLabel", $fieldLabels["PostalCodeLabel"]),
						$htmlEditorField3 = new HTMLEditorField("ShopPhysicalAddress",$fieldLabels["ShopPhysicalAddress"]),
						new TextField("PackingSlipTitle",$fieldLabels["PackingSlipTitle"]),
						$htmlEditorField4 = new HTMLEditorField("PackingSlipNote",$fieldLabels["PackingSlipNote"])
					),
					new Tab('Emails',
						new TextField("ReceiptEmail",$fieldLabels["ReceiptEmail"]),
						new UploadField("EmailLogo",$fieldLabels["EmailLogo"] ,  null, null, null, "logos"),
						new TextField("InvoiceTitle",$fieldLabels["InvoiceTitle"])
					),
					new Tab('Process',
						$self->getOrderStepsField()
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
								<a href=\"/dev/ecommerce/ecommercetaskcheckconfiguration\" data-popup=\"true\">review these settings</a>
								but you will need to ask your developer to change them if they are not right.
								The reason they can not be set is that changing them can potentially break your application.
							</p>"
						)
					)
					/*$processtab = new Tab('OrderProcess',
						new LiteralField('op','Include a drag-and-drop interface for customising order steps (Like WidgetArea)')
					)*/
				));
				$mappingArray = Config::inst()->get("BillingAddress", "fields_to_google_geocode_conversion");
				if(is_array($mappingArray) && count($mappingArray)) {
					$mappingArray = Config::inst()->get("ShippingAddress", "fields_to_google_geocode_conversion");
					if(is_array($mappingArray) && count($mappingArray)) {
						$fields->removeByName("PostalCodeURL");
						$fields->removeByName("PostalCodeLabel");
					}
				}
				$htmlEditorField1->setRows(3);
				$htmlEditorField2->setRows(3);
				$htmlEditorField3->setRows(3);
				$htmlEditorField4->setRows(3);
				$fields->addFieldsToTab(
					"Root.Main",
					array(
						new CheckboxField("UseThisOne", $fieldLabels["UseThisOne"]),
						new CheckboxField("ShopClosed", $fieldLabels["ShopClosed"])
					)
				);
				//set cols
				if($f = $fields->dataFieldByName("CurrenciesExplanation")) {$f->setRows(2);}
				if($f = $fields->dataFieldByName("NotForSaleMessage")) {$f->setRows(2);}
				if($f = $fields->dataFieldByName("ShopPhysicalAddress")) {$f->setRows(2);}
				foreach($fields->dataFields() as $field) {
					if(isset($fieldDescriptions[$field->getName()])) {
						if($field instanceof CheckboxField) {
							$field->setDescription($fieldDescriptions[$field->Name]);
						}
						else {
							$field->setRightTitle($fieldDescriptions[$field->Name]);
						}
					}
				}
				Requirements::block('ecommerce/javascript/EcomPrintAndMail.js');
				if (strnatcmp(phpversion(),'5.5.1') >= 0) { 
					$fields->addFieldToTab('Root.ProductImages', new Product_ProductImageUploadField("DefaultProductImage", $fieldLabels["DefaultProductImage"], null, null, null, "default-product-image"));
				}
			}
		);
		return parent::getCMSFields();
	}

	/**
	 * link to edit the record
	 * @param String | Null $action - e.g. edit
	 * @return String
	 */
	public function CMSEditLink($action = null) {
		return Controller::join_links(
			Director::baseURL(),
			"/admin/shop/".$this->ClassName."/EditForm/field/".$this->ClassName."/item/".$this->ID."/",
			$action
		);
	}

	public function getOrderStepsField(){
		$gridFieldConfig = GridFieldConfig::create()->addComponents(
			new GridFieldToolbarHeader(),
			new GridFieldSortableHeader(),
			new GridFieldDataColumns(10),
			new GridFieldPaginator(10),
			new GridFieldEditButton(),
			new GridFieldDeleteAction(),
			new GridFieldDetailForm()
		);
		return new GridField("OrderSteps", _t("OrderStep.PLURALNAME", "Order Steps"), OrderStep::get(), $gridFieldConfig);
	}

	/**
	 * tells us if a Class Name is a buyable
	 * @todo: consider using Ecomerce Configuration instead?
	 * In EcomConfig we only list base classes.
	 *
	 * @param String $className - name of the class to be tested
	 * @return Boolean
	 */
	public static function is_buyable($className) {
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
	 * Returns the Current Member
	 * @return Null | Member
	 */
	public function CustomerForOrder(){
		$order = ShoppingCart::current_order();
		return $order->Member();
	}

	/**
	 * Return the currency being used on the site e.g. "NZD" or "USD"
	 * @return String
	 */
	public function Currency() {
		return EcommerceConfig::get("EcommerceCurrency", "default_currency");
	}

	/**
	 * return null if there is less than two currencies in use
	 * on the site.
	 * @return DataList | Null
	 */
	function Currencies() {
		$list = EcommerceCurrency::get_list();
		if($list && $list->count() > 1) {
			return $list;
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
		$obj = Product_Image::create();
		$obj->Link = $this->DefaultImageLink();
		$obj->URL = $this->DefaultImageLink();
		return $obj;
	}

	/**
	 * standard SS method
	 */
	function onAfterWrite(){
		if($this->UseThisOne) {
			$configs = EcommerceDBConfig::get()
				->Filter(array("UseThisOne" => 1))
				->Exclude(array("ID" => $this->ID));
			if($configs->count()){
				foreach($configs as $config) {
					$config->UseThisOne = 0;
					$config->write();
				}
			}
		}
		$configs = EcommerceDBConfig::get()
			->Filter(array("Title" => $this->Title))
			->Exclude(array("ID" => $this->ID));
		if($configs->count()){
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
			$obj = EcommerceDBConfig::create();
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


