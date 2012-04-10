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
		"InUse" => "Boolean",
	);

	public static $has_one = array(
		"EcommerceCurrencyFormat" => "EcommerceCurrencyFormat"
	);

}






