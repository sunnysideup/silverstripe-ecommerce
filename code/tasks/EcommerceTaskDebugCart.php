<?php


class EcommerceTaskDebugCart extends BuildTask {

	protected $title = "Debug your cart";

	protected $description = "Check all the values in your cart to find any potential errors.";

	function run($request){
		$order = ShoppingCart::current_order();
		self::debug_object($order);
	}

	public static function debug_object($obj){
		$html =  "
			<h2>".$obj->ClassName."</h2><ul>";
		$fields = Config::inst()->get($obj->ClassName, "db", Config::INHERITED);

		//db
		if(count($fields)) {
			foreach($fields as  $key => $type) {
				$value = self::cleanup_value($type, $obj->$key);
				$html .= "<li><b>$key ($type):</b> ".$value."</li>";
			}
		}

		//casted variables
		$fields = Config::inst()->get($obj->ClassName, "casting", Config::FIRST_SET);
		if(count($fields)) {
			foreach($fields as  $key => $type) {
				$method = $key;
				if(method_exists($obj, $method)) {
					$value = $obj->$method();
				}
				else {
					$method = "get".$key;
					if(method_exists($obj, $method)) {
						$value = $obj->$method();
					}
					else{
						$value = $obj->$key;
					}
				}
				$value = self::cleanup_value($type, $value);
				$html .= "<li><b>$key ($type):</b> ".$value."</li>";
			}
		}

		//has_one
		$fields = Config::inst()->get($obj->ClassName, "has_one", Config::FIRST_SET);
		if(count($fields)) {
			foreach($fields as  $key => $type) {
				$value = "";
				$field = $key."ID";
				if($object = $obj->$key()){
					if($object && $object->exists()) {
						$value = ", ".$object->getTitle();
					}
				}
				$html .= "<li><b>$key ($type):</b> ".$obj->$field.$value." </li>";
			}
		}
		//to do: has_many and many_many
		$html .= "</ul>";
		return $html;
	}

	private static function cleanup_value($type, $value) {
		switch ($type) {
			case "HTMLText":
				$value = (substr(strip_tags($value), 0, 100));
				break;
			case "Boolean":
				$value = $value ? "YES" : "NO";
				break;
			default:
				$value = $value;
				break;
		}
		return $value;
	}

}
