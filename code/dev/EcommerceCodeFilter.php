<?php
/**
 */

class EcommerceCodeFilter extends Object {

	/**
	 * @var Array
	 */
	private $replacements = array(
		'/&amp;/u' => '-and-',
		'/&/u' => '-and-',
		'/\s/u' => '-', // remove whitespace
		'/[^A-Za-z0-9.\-_]+/u' => '', // remove non-ASCII chars, only allow alphanumeric, dashes and dots.
		'/[\-]{2,}/u' => '-', // remove duplicate dashes
		'/[\_]{2,}/u' => '_', // remove duplicate underscores
		'/^[\.\-_]/u' => '', // Remove all leading dots, dashes or underscores
	);

	/**
	 * makes sure that code is unique and gets rid of special characters
	 * should be run in onBeforeWrite
	 *
	 * @param DataObject | String $obj
	 */

	public function checkCode($obj, $fieldName = "Code") {
		//exception dealing with Strings
		$isObject = true;
		if( ! is_object($obj)) {
			$str = $obj;
			$obj = new DataObject();
			$obj->$fieldName = strval($str);
			$isObject = false;
		}
		$obj->$fieldName = trim($obj->$fieldName);
		foreach($this->replacements as $regex => $replace) {
			$obj->$fieldName = preg_replace($regex, $replace, $obj->$fieldName);
		}
		if(!$obj->$fieldName) {
			"CODE-NOT-SET";
		}
		//make upper-case
		$obj->$fieldName = trim(strtoupper($obj->$fieldName));
		//check for other ones.
		$count = 2;
		$code = $obj->$fieldName;
		while($isObject && $obj::get()->filter(array($fieldName => $obj->$fieldName))->exclude(array("ID" => $obj->ID))->Count()) {
			$obj->$fieldName = $code . '-' . $count;
			$count++;
		}
		return $obj->$fieldName;
	}
}
