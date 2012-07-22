<?php

/**
 * works out how many products have been sold, per product.
 *
 * @TODO: consider whether this does not sit better in its own module.
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class RecalculateTheNumberOfProductsSold extends BuildTask{

	protected $title = "Recalculate the number of products solds";

	protected $description = "Works out how many of each product have been sold";

	/**
	 * Should be equal to SUM or COUNT.
	 * Determines if we count of sum up the number of times a product has been sold.
	 * if two people buy product A, person 1 buying two of them and person 1 buying three then
	 * SUM = 5
	 * COUNT = 2
	 *
	 * @var String
	 */
	protected static $number_sold_calculation_type = "SUM"; //SUM or COUNT
		static function set_number_sold_calculation_type($s){self::$number_sold_calculation_type = $s;}
		static function get_number_sold_calculation_type(){return self::$number_sold_calculation_type;}

	function run($request){
		$ps = singleton('Product');
		$q = $ps->buildSQL("\"Product\".\"AllowPurchase\" = 1");
		$select = $q->select;

		$select['NewNumberSold'] = self::$number_sold_calculation_type."(\"OrderItem\".\"Quantity\") AS \"NewNumberSold\"";

		$q->select($select);
		$q->groupby("\"Product\".\"ID\"");
		$q->orderby("\"NewNumberSold\" DESC");

		$q->leftJoin('OrderItem','"Product"."ID" = "OrderItem"."BuyableID"');
		$q->where("\"OrderItem\".\"BuyableClassName\" = 'Product'");
		$records = $q->execute();
		$productssold = $ps->buildDataObjectSet($records, "DataObjectSet", $q, 'Product');

		foreach($productssold as $product){
			if(!$product->NewNumberSold) {
				$product->NewNumberSold = 0;
			}
			if($product->NewNumberSold != $product->NumberSold){
				DB::query("Update \"Product\" SET \"NumberSold\" = ".$product->NewNumberSold." WHERE ID = ".$product->ID);
				DB::query("Update \"Product_Live\" SET \"NumberSold\" = ".$product->NewNumberSold." WHERE ID = ".$product->ID);
			}
		}
	}
}
