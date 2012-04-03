<?php

/**
 * This class returns the Ajax Definitions class.
 */


class EcommerceConfigAjax extends Object {

	/**
	 * implements singleton pattern so that there is only ever one instance
	 * of this class.
	 * @static object
	 */
	private static $singleton = array();


	/**
	 * Returns the singleton instance of the Ajax Config definitions class.
	 * This class basically contains a bunch of methods that return
	 * IDs and Classes for use with AJAX.
	 *
	 * @param DataObject $requestor the object requesting the Object
	 * @return EcommerceConfigAjaxDefinitions (or other object)
	 */
	public static function get_one($requestor) {
		if(!isset(self::$singleton[$requestor->ClassName][$requestor->ID])) {
			$className = EcommerceConfig::get("EcommerceConfigAjax", "definitions_class_name");
			self::$singleton[$requestor->ClassName][$requestor->ID] = new $className();
		}
		self::$singleton[$requestor->ClassName][$requestor->ID]->setRequestor($requestor);
		return self::$singleton[$requestor->ClassName][$requestor->ID];
	}

}
