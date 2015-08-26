<?php

/**
 * This class returns the Ajax Definitions class.
 * The Ajax Definitions class is an object that contains all the values
 * for ajax references in the templates.
 *
 * We need to have one per classname (e.g. Product)and requestor (Product A with ID = 1)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: configuration
 * @inspiration: Silverstripe Ltd, Jeremy
 **/


class EcommerceConfigAjax extends Object {

	/**
	 * implements singleton pattern so that there is only ever one instance
	 * of this class.
	 * This is usually defined as $singleton[$ClassName][$Requestor->ID]
	 *
	 * @static object
	 */
	private static $singleton = array();


	/**
	 * Returns the singleton instance of the Ajax Config definitions class.
	 * This class basically contains a bunch of methods that return
	 * IDs and Classes for use with AJAX.
	 *
	 * @param DataObject $requestor the object requesting the Ajax Config Definitions
	 * @return EcommerceConfigAjaxDefinitions (or other object)
	 */
	public static function get_one($requestor) {
		if(!isset(self::$singleton[$requestor->ClassName][$requestor->ID])) {
			$className = EcommerceConfig::get("EcommerceConfigAjax", "definitions_class_name");
			self::$singleton[$requestor->ClassName][$requestor->ID] = new $className();
			self::$singleton[$requestor->ClassName][$requestor->ID]->setRequestor($requestor);
		}
		return self::$singleton[$requestor->ClassName][$requestor->ID];
	}

}
