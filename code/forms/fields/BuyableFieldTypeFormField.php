<?php



class BuyableFieldTypeFormField extends DataObject {



}


/**
 *
 *
 */
class BuyableFieldTypeFormField_DataList extends DataObject {

	static $db = array(
		"FullSiteTreeSort" => "Varchar(250)",
		"Title" => "Varchar(250)",
		"Buyable" => "BuyableFieldType"
	);

	static $index = array(
		"FullSiteTreeSort" => "String"
	);

}
