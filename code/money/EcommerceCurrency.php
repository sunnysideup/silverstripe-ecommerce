<?php


/**
 * Object to manage currencies
 *
 *
 **/


class EcommerceCurrency extends DataObject {

	static $db = array(
		"Code" => "Varchar(5)",
		"Name" => "Varchar(100)",
		"ExchangeRate" => "Double",
		"InUse" => "Boolean"
	);

	function requireDefaultRecords(){
		parent::requireDefaultRecords();
	}

}






