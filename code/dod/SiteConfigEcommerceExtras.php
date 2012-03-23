<?php

/**
 *@description: adds a few parameters for e-commerce to the SiteConfig.
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package ecommerce
 * @sub-package integration
 **/

class SiteConfigEcommerceExtras extends DataObjectDecorator {

	function extraStatics(){
		return array(
			'db' => array(
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
				"ProductsAlsoInOtherGroups" => "Boolean"
			),
			'has_one' => array(
				"EmailLogo" => "Image",
				"DefaultProductImage" => "Image"
			),
			'defaults' =>array(
				'ShopClosed' => false
			)
		);
	}

	/**
	 * standard SS method for decorators.
	 * @param Array - $fields: array of fields to start with
	 * @return null ($fields variable is automatically updated)
	 */
	function updateFieldLabels(& $fields) {
		$newFields = array(
			"ShopClosed" => _t("SiteConfigEcommerceExtras.SHOPCLOSED", "Shop Closed"),
			"ShopPricesAreTaxExclusive" => _t("SiteConfigEcommerceExtras.SHOPPRICESARETAXEXCLUSIVE", "Shop prices are tax exclusive (if this option is not ticked, it is assumed that prices are inclusive of tax)"),
			"ShopPhysicalAddress" => _t("SiteConfigEcommerceExtras.DEFAULTPRODUCTIMAGE", "Shop physical address"),
			"ReceiptEmail" => _t("SiteConfigEcommerceExtras.RECEIPTEMAIL", "Shope Email Address (e.g. sales@mysite.com)"),
			"PostalCodeURL" => _t("SiteConfigEcommerceExtras.POSTALCODEURL", "Postal code link"),
			"PostalCodeLabel" => _t("SiteConfigEcommerceExtras.POSTALCODELABEL", "Postal code link label"),
			"NumberOfProductsPerPage" => _t("SiteConfigEcommerceExtras.NUMBEROFPRODUCTSPERPAGE", "Number of products per page"),
			"OnlyShowProductsThatCanBePurchased" => _t("SiteConfigEcommerceExtras.ONLYSHOWPRODUCTSTHATCANBEPURCHASED", "Only show products that can be purchased"),
			"ProductsHaveWeight" =>  _t("SiteConfigEcommerceExtras.PRODUCTSHAVEWEIGHT", "Products have weight (e.g. 1.2kg) - untick to hide weight field"),
			"ProductsHaveModelNames" =>  _t("SiteConfigEcommerceExtras.PRODUCTSHAVEMODELNAMES", "Products have model names / numbers -  untick to hide model field"),
			"ProductsHaveQuantifiers" => _t("SiteConfigEcommerceExtras.PRODUCTSHAVEQUANTIFIERS", "Products have quantifiers (e.g. per year, each, per dozen, etc...) - untick to hide model field"),
			"ProductsAlsoInOtherGroups" => _t("SiteConfigEcommerceExtras.PRODUCTSALSOINOTHERGROUPS", "Allow products to show in multiple product groups."),
			"EmailLogo" => _t("SiteConfigEcommerceExtras.EMAILLOGO", "Email Logo"),
			"DefaultProductImage" => _t("SiteConfigEcommerceExtras.DEFAULTPRODUCTIMAGE", "Default Product Image")
		);
		$fields = array_merge($newFields, $fields);
	}


	function updateCMSFields(FieldSet &$fields) {
		//new section
		$fieldLabels = $this->owner->fieldLabels();
		$shoptabs = new TabSet('Shop',
			new Tab('General',
				new CheckboxField("ShopClosed", $fieldLabels["ShopClosed"])
			),
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
				new ComplexTableField($this->owner, "OrderSteps", "OrderStep")
			)
			/*$processtab = new Tab('OrderProcess',
				new LiteralField('op','Include a drag-and-drop interface for customising order steps (Like WidgetArea)')
			)*/
		);
		$fields->addFieldToTab('Root',$shoptabs);
		return $fields;
	}

}
