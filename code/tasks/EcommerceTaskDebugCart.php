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
		$fields = Config::inst()->get($obj->ClassName, "db", Config::FIRST_SET);

		//db
		if(count($fields)) {
			foreach($fields as  $key => $type) {
				$html .= "<li><b>$key ($type):</b> ".$obj->$key."</li>";
			}
		}

		//casted variables
		$fields = Config::inst()->get($obj->ClassName, "casting", Config::FIRST_SET);
		if(count($fields)) {
			foreach($fields as  $key => $type) {
				$method = "get".$key;
				$html .= "<li><b>$key ($type):</b> ".$obj->$method()." </li>";
			}
		}

		//has_one
		$fields = Config::inst()->get($obj->ClassName, "has_one", Config::FIRST_SET);
		if(count($fields)) {
			foreach($fields as  $key => $type) {
				$value = "";
				$field = $key."ID";
				if($object = $this->$key()){
					if($object && $object->exists()) {
						$value = ", ".$object->Title;
					}
				}
				$html .= "<li><b>$key ($type):</b> ".$this->$field.$value." </li>";
			}
		}
		//to do: has_many and many_many
		$html .= "</ul>";
		return $html;
	}

}
