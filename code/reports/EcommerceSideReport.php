<?php
/**
 * EcommerceSideReport classes are to allow quick reports that can be accessed
 * on the Reports tab to the left inside the SilverStripe CMS.
 * Currently there are reports to show products flagged as 'FeatuedProduct',
 * as well as a report on all products within the system.
 *
 *
 *
 *
 */



/**
 * Ecommerce Pages except Products
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceSideReport_EcommercePages extends SS_Report {

	function title() {
		return _t('EcommerceSideReport.ECOMMERCEPAGES',"E-commerce Pages");
	}

	function group() {
		return _t('EcommerceSideReport.ECOMMERCEGROUP', "Ecommerce");
	}

	function sort() {
		return 0;
	}

	function sourceRecords($params = null) {
		$dos = new DataObjectSet();
		$array = array("CartPage", "AccountPage");
		foreach($array as $className) {
			if($add = DataObject::get($className)) {
				if($add->exists()) {
					foreach($add as $page) {
						$dos->push($page);
					}
				}
			}
		}
		if($dos->count()) {
			return $dos;
		}
	}

	function columns() {
		return array(
			"Title" => array(
				"title" => "FullName",
				"link" => true
			)
		);
	}

}



/** @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceSideReport_FeaturedProducts extends SS_Report {

	function title() {
		return _t('EcommerceSideReport.FEATUREDPRODUCTS', "Featured products");
	}

	function group() {
		return _t('EcommerceSideReport.ECOMMERCEGROUP', "Ecommerce");
	}
	function sort() {
		return 0;
	}
	function records() {
		return DataObject::get("Product", "\"FeaturedProduct\" = 1", "\"FullSiteTreeSort\"");
	}

	function columns() {
		return array(
			"Title" => array(
				"title" => "FullName",
				"link" => true
			)
		);
	}
}

/**
 * Selects all products
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceSideReport_AllProducts extends SS_Report {

	function title() {
		return _t('EcommerceSideReport.ALLPRODUCTS', "All products");
	}

	function group() {
		return _t('EcommerceSideReport.ECOMMERCEGROUP', "Ecommerce");
	}
	function sort() {
		return 0;
	}
	function records() {
		return DataObject::get("Product", "", "\"FullSiteTreeSort\"");
	}

	function columns() {
		return array(
			"Title" => array(
				"title" => "FullName",
				"link" => true
			)
		);
	}

}


/**
 * Selects all products without an image.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceSideReport_NoImageProducts extends SS_Report {

	function title() {
		return _t('EcommerceSideReport.NOIMAGE',"Products without image");
	}
	function group() {
		return _t('EcommerceSideReport.ECOMMERCEGROUP', "Ecommerce");
	}
	function sort() {
		return 0;
	}
	function sourceRecords($params = null) {
		return DataObject::get("Product", "\"Product\".\"ImageID\" IS NULL OR \"Product\".\"ImageID\" <= 0", "\"FullSiteTreeSort\" ASC");
	}
	function columns() {
		return array(
			"Title" => array(
				"title" => "FullName",
				"link" => true
			)
		);
	}
}


/**
 * Selects all products without an InternalID
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceSideReport_NoInternalIDProducts extends SS_Report {

	function title() {
		return _t('EcommerceSideReport.NOINTERNALID',"Products without Internal ID (SKU)");
	}

	function group() {
		return _t('EcommerceSideReport.ECOMMERCEGROUP', "Ecommerce");
	}

	function sort() {
		return 0;
	}

	function sourceRecords($params = null) {
		return DataObject::get("Product", "\"Product\".\"InternalID\" IS NULL OR \"Product\".\"InternalID\" = '' ", "\"FullSiteTreeSort\" ASC");
	}

	function columns() {
		return array(
			"Title" => array(
				"title" => "FullName",
				"link" => true
			)
		);
	}
}


/**
 * Selects all products without a price.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceSideReport_NoPriceProducts extends SS_Report {

	function title() {
		return _t('EcommerceSideReport.NOPRICE',"Products without Price");
	}

	function group() {
		return _t('EcommerceSideReport.ECOMMERCEGROUP', "Ecommerce");
	}

	function sort() {
		return 0;
	}

	function sourceRecords($params = null) {
		return DataObject::get("Product", "\"Product\".\"Price\" IS NULL OR \"Product\".\"Price\" = 0 ", "\"FullSiteTreeSort\" ASC");
	}

	function columns() {
		return array(
			"Title" => array(
				"title" => "FullName",
				"link" => true
			)
		);
	}

}


/**
 * Selects all products that are not for sale.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceSideReport_NotForSale extends SS_Report {

	function title() {
		return _t('EcommerceSideReport.NOTFORSALE',"Products not for sale");
	}

	function group() {
		return _t('EcommerceSideReport.ECOMMERCEGROUP', "Ecommerce");
	}

	function sort() {
		return 0;
	}

	function sourceRecords($params = null) {
		return DataObject::get("Product", " \"Product\".\"AllowPurchase\" = 0 ", "\"FullSiteTreeSort\" ASC");
	}

	function columns() {
		return array(
			"Title" => array(
				"title" => "FullName",
				"link" => true
			)
		);
	}

}


/**
 * Products without variations
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceSideReport_ProductsWithVariations extends SS_Report {

	function title() {
		return _t('EcommerceSideReport.PRODUCTSWITHVARIATIONS',"Products without variations");
	}

	function group() {
		return _t('EcommerceSideReport.ECOMMERCEGROUP', "Ecommerce");
	}

	function sort() {
		return 0;
	}

	function sourceRecords($params = null) {
		$stage = '';
		if(Versioned::current_stage() == "Live") {
			$stage = "_Live";
		}
		return DataObject::get("Product", " ProductVariation.ID IS NULL ", "\"FullSiteTreeSort\" ASC", "LEFT JOIN \"ProductVariation\" ON \"ProductVariation\".\"ProductID\" = \"Product".$stage."\".\"ID\"");
	}

	function columns() {
		return array(
			"Title" => array(
				"title" => "FullName",
				"link" => true
			)
		);
	}

}

