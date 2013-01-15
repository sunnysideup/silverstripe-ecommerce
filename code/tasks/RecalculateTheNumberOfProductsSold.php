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
		$orderItemSingleton = singleton('OrderItem');
		$query = $orderItemSingleton->buildSQL("\"Quantity\" > 0");
		$select = $query->select;

		$select['NewNumberSold'] = self::$number_sold_calculation_type."(\"OrderItem\".\"Quantity\") AS \"NewNumberSold\"";

		$query->select($select);
		$query->groupby("\"BuyableClassName\", \"BuyableID\" ");
		$query->orderby("\"BuyableClassName\", \"BuyableID\" ");

		//$q->leftJoin('OrderItem','"Product"."ID" = "OrderItem"."BuyableID"');
		//$q->where("\"OrderItem\".\"BuyableClassName\" = 'Product'");
		$records = $query->execute();
		$orderItems = $orderItemSingleton->buildDataObjectSet($records, "DataList", $query, 'OrderItem');
		if($orderItems) {
			foreach($orderItems as $orderItem){
				if(!$orderItem->NewNumberSold) {
					$orderItem->NewNumberSold = 0;
				}
				$buyableClassName = $orderItem->BuyableClassName;
				$buyable = $buyableClassName::get()-byID(intval($orderItem->BuyableID));
				if($buyable) {
					if($orderItem->NewNumberSold != $buyable->NumberSold){
						$buyable->NumberSold = $orderItem->NewNumberSold;
						if($buyable instanceOf SiteTree) {
						}
					}
				}
				else {
					DB::alteration_message("could not find ".$orderItem->BuyableClassName.".".$orderItem->BuyableID." ... ", "deleted" );
				}
			}
		}
	}
}
