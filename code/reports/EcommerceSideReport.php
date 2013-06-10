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

	/**
	 * The class of object being managed by this report.
	 * Set by overriding in your subclass.
	 */
	protected $dataClass = 'SiteTree';

	/**
	 *
	 * @return String
	 */
	function title() {
		return _t('EcommerceSideReport.ECOMMERCEPAGES',"E-commerce: Non-product e-commerce pages").
		" (".$this->sourceRecords()->count().")";
	}

	/**
	 * not sure if this is used in SS3
	 * @return String
	 */
	function group() {
		return _t('EcommerceSideReport.ECOMMERCEGROUP', "Ecommerce");
	}

	/**
	 *
	 * @return INT - for sorting reports
	 */
	function sort() {
		return 7000;
	}

	/**
	 * working out the items
	 * @return DataList
	 */
	function sourceRecords($params = null) {
		return SiteTree::get()->filter("ClassName", array("CartPage", "AccountPage", "ProductSearchPage"));
	}

	/**
	 * @return Array
	 */
	function columns() {
		return array(
			"Title" => array(
				"title" => "FullName",
				"link" => true
			)
		);
	}

	/**
	 *
	 * @return FieldList
	 */
	public function getParameterFields() {
		return new FieldList();
	}

}



/** @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: reports
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceSideReport_FeaturedProducts extends SS_Report {

	/**
	 * The class of object being managed by this report.
	 * Set by overriding in your subclass.
	 */
	protected $dataClass = 'Product';

	/**
	 *
	 * @return String
	 */
	function title() {
		return _t('EcommerceSideReport.FEATUREDPRODUCTS', "E-commerce: Featured products").
		" (".$this->sourceRecords()->count().")";

	}

	/**
	 * not sure if this is used in SS3
	 * @return String
	 */
	function group() {
		return _t('EcommerceSideReport.ECOMMERCEGROUP', "Ecommerce");
	}

	/**
	 *
	 * @return INT - for sorting reports
	 */
	function sort() {
		return 7000;
	}

	/**
	 * working out the items
	 * @return SS_List
	 */
	function sourceRecords($params = null) {
		return Product::get()
			->filter(array("FeaturedProduct" => 1))
			->sort("FullSiteTreeSort", "ASC");
	}

	/**
	 * @return Array
	 */
	function columns() {
		return array(
			"Title" => array(
				"title" => "FullName",
				"link" => true
			)
		);
	}

	/**
	 *
	 * @return FieldList
	 */
	public function getParameterFields() {
		return new FieldList();
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

	/**
	 * The class of object being managed by this report.
	 * Set by overriding in your subclass.
	 */
	protected $dataClass = 'Product';
	/**
	 *
	 * @return String
	 */
	function title() {
		return _t('EcommerceSideReport.ALLPRODUCTS', "E-commerce: All products").
		" (".$this->sourceRecords()->count().")";
	}

	/**
	 * not sure if this is used in SS3
	 * @return String
	 */
	function group() {
		return _t('EcommerceSideReport.ECOMMERCEGROUP', "Ecommerce");
	}

	/**
	 *
	 * @return INT - for sorting reports
	 */
	function sort() {
		return 7000;
	}

	/**
	 * working out the items
	 * @return DataList
	 */
	function sourceRecords($params = null) {
		return Product::get()->sort("FullSiteTreeSort", "ASC");
	}

	/**
	 * @return Array
	 */
	function columns() {
		return array(
			"Title" => array(
				"title" => "FullName",
				"link" => true
			)
		);
	}

	/**
	 *
	 * @return FieldList
	 */
	public function getParameterFields() {
		return new FieldList();
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

	/**
	 * The class of object being managed by this report.
	 * Set by overriding in your subclass.
	 */
	protected $dataClass = 'Product';
	/**
	 *
	 * @return String
	 */
	function title() {
		return _t('EcommerceSideReport.NOIMAGE',"E-commerce: Products without image").
		" (".$this->sourceRecords()->count().")";
	}

	/**
	 * not sure if this is used in SS3
	 * @return String
	 */
	function group() {
		return _t('EcommerceSideReport.ECOMMERCEGROUP', "Ecommerce");
	}

	/**
	 *
	 * @return INT - for sorting reports
	 */
	function sort() {
		return 7000;
	}

	/**
	 * working out the items
	 * @return DataList
	 */
	function sourceRecords($params = null) {
		return Product::get()
			->where("\"Product\".\"ImageID\" IS NULL OR \"Product\".\"ImageID\" <= 0")
			->sort("FullSiteTreeSort",  "ASC");
	}

	/**
	 * @return Array
	 */
	function columns() {
		return array(
			"Title" => array(
				"title" => "FullName",
				"link" => true
			)
		);
	}

	/**
	 *
	 * @return FieldList
	 */
	public function getParameterFields() {
		return new FieldList();
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

	/**
	 * The class of object being managed by this report.
	 * Set by overriding in your subclass.
	 */
	protected $dataClass = 'Product';

	/**
	 *
	 * @return String
	 */
	function title() {
		return _t('EcommerceSideReport.NOINTERNALID',"E-commerce: Products without Internal ID / SKU ").
		" (".$this->sourceRecords()->count().")";
	}

	/**
	 * not sure if this is used in SS3
	 * @return String
	 */
	function group() {
		return _t('EcommerceSideReport.ECOMMERCEGROUP', "Ecommerce");
	}

	/**
	 *
	 * @return INT - for sorting reports
	 */
	function sort() {
		return 7000;
	}

	/**
	 * working out the items
	 * @return DataList
	 */
	function sourceRecords($params = null) {
		return Product::get()
			->where("\"Product\".\"InternalItemID\" IS NULL OR \"Product\".\"InternalItemID\" = '' ")
			->sort("FullSiteTreeSort", "ASC");
	}

	/**
	 * @return Array
	 */
	function columns() {
		return array(
			"Title" => array(
				"title" => "FullName",
				"link" => true
			)
		);
	}

	/**
	 *
	 * @return FieldList
	 */
	public function getParameterFields() {
		return new FieldList();
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

	/**
	 * The class of object being managed by this report.
	 * Set by overriding in your subclass.
	 */
	protected $dataClass = 'Product';

	/**
	 *
	 * @return String
	 */
	function title() {
		return _t('EcommerceSideReport.NOPRICE',"E-commerce: Products without Price").
		" (".$this->sourceRecords()->count().")";
	}

	/**
	 * not sure if this is used in SS3
	 * @return String
	 */
	function group() {
		return _t('EcommerceSideReport.ECOMMERCEGROUP', "Ecommerce");
	}


	/**
	 *
	 * @return INT - for sorting reports
	 */
	function sort() {
		return 7000;
	}

	/**
	 * working out the items
	 * @return DataList
	 */
	function sourceRecords($params = null) {
		return Product::get()
			->where("\"Product\".\"Price\" IS NULL OR \"Product\".\"Price\" = 0 ")
			->sort("FullSiteTreeSort", "ASC");
	}

	/**
	 *
	 * @return Array
	 */
	function columns() {
		return array(
			"Title" => array(
				"title" => "FullName",
				"link" => true
			)
		);
	}

	/**
	 *
	 * @return FieldList
	 */
	public function getParameterFields() {
		return new FieldList();
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

	/**
	 * The class of object being managed by this report.
	 * Set by overriding in your subclass.
	 */
	protected $dataClass = 'Product';

	/**
	 *
	 * @return String
	 */
	function title() {
		return _t('EcommerceSideReport.NOTFORSALE',"E-commerce: Products not for sale").
		" (".$this->sourceRecords()->count().")";
	}

	/**
	 * not sure if this is used in SS3
	 * @return String
	 */
	function group() {
		return _t('EcommerceSideReport.ECOMMERCEGROUP', "Ecommerce");
	}

	/**
	 *
	 * @return INT - for sorting reports
	 */
	function sort() {
		return 7000;
	}

	/**
	 * working out the items
	 * @return DataList
	 */
	function sourceRecords($params = null) {
		return Product::get("Product")
			->filter(array("AllowPurchase" => 0))
			->sort("FullSiteTreeSort", "ASC");
	}

	/**
	 * @return Array
	 */
	function columns() {
		return array(
			"Title" => array(
				"title" => "FullName",
				"link" => true
			)
		);
	}

	/**
	 *
	 * @return FieldList
	 */
	public function getParameterFields() {
		return new FieldList();
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

	/**
	 * The class of object being managed by this report.
	 * Set by overriding in your subclass.
	 */
	protected $dataClass = 'Product';

	/**
	 *
	 * @return String
	 */
	function title() {
		return _t('EcommerceSideReport.PRODUCTSWITHVARIATIONS',"E-commerce: Products without variations").
		" (".$this->sourceRecords()->count().")";
	}

	/**
	 * not sure if this is used in SS3
	 * @return String
	 */
	function group() {
		return _t('EcommerceSideReport.ECOMMERCEGROUP', "Ecommerce");
	}

	/**
	 *
	 * @return INT - for sorting reports
	 */
	function sort() {
		return 7000;
	}

	/**
	 * working out the items
	 * @return DataList
	 */
	function sourceRecords($params = null) {
		$stage = '';
		if(Versioned::current_stage() == "Live") {
			$stage = "_Live";
		}
		if(class_exists("ProductVariation")) {
			return Product::get()
				->where("\"ProductVariation\".\"ID\" IS NULL ")
				->sort("FullSiteTreeSort")
				->leftJoin("ProductVariation", "\"ProductVariation\".\"ProductID\" = \"Product".$stage."\".\"ID\"");
		}
		else {
			return Product::get();
		}
	}

	/**
	 * @return Array
	 */
	function columns() {
		return array(
			"Title" => array(
				"title" => "FullName",
				"link" => true
			)
		);
	}

	/**
	 *
	 * @return FieldList
	 */
	public function getParameterFields() {
		return new FieldList();
	}
}

