<?php


/**
 * Define currency formats
 *
 *
 **/


class EcommerceCurrencyFormat extends DataObject {

	static $db = array(
		"FormatName" => "Varchar(30)",
		"Symbol" => "Varchar(1)",
		"Separator" => "Varchar(1)",
		"SeparateEvery" => "Int",
		"DecimalSeparator" => "Varchar(1)",
		"NumberOfDecimals" => "Int",
		"NegativeValuesBefore" => "Varchar(5)",
		"NegativeValuesAfter" => "Varchar(5)"
	);

	public static $has_one = array(
		"EcommerceCurrencyFormat" => "EcommerceCurrencyFormat"
	);

}






