<?php

/**
 * Add any Image (or other file) to a product using the InternalItemID
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/


class EcommerceTaskLinkProductWithImages extends BuildTask {

	protected $title = "Find product images";

	protected $description = "
		Finds product images (or other files) based on their name.
		That is, any image name [InteralItemID]_[two digits].[png/gif/jpg] will automatically be linked to the product.
		For example SKUAAFF_1 or SKU_02.
		All files ending in a number from 00 to 99 will be added (e.g. 02, 5 or 55)
		Also SKUAAFF.jpg (without the standard ending with underscore and number) will be added to the product where InternalItemID equals SKUAAFF.
	";

	/**
	 * In the default e-commerce, each product only has one image.
	 * Many e-commerce sites, however, like to have more than one image per product.
	 *
	 * @var String
	 */
	protected $productManyManyField = "AdditionalImages";

	/**
	 * Starting point for selecting products
	 * Usually starts at zero and goes up to the total number of products
	 *
	 * @var Int
	 */
	protected $start = 0;

	/**
	 * The number of products selected per cycle.
	 *
	 * @var Int
	 */
	protected $limit = 100;

	/**
	 * output messages?
	 *
	 * @var Boolean
	 */
	protected $verbose = true;

	function run($request){
		if($this->productManyManyField) {
			$products = DataObject::get("Product", "", "", "", "$this->start, $this->limit");
			if($products) {
				foreach($products as $product) {
					if($product->InternalItemID) {
						if($product->hasMethod($this->productManyManyField)) {
							$whereStringArray[] = $product->InteralItemID;
							for($i = 0; $i < 10; $i++) {
								for($j = 0; $j < 10; $j++) {
									$number = strval($i).$strval($j);
									$whereStringArray[] = $product->InteralItemID."_".$number;
								}
							}
							$images = DataObject::get("File", "\"Name\" IN ('".implode("', '", $whereStringArray)."')");
							if($images) {
								$imageMap = $images->map("ID", "ID");
								$method = $this->productManyManyField;
								$collection = $product->$method();
								$collection->addFromArray($imageMap);
							}
							else {
								if($this->verbose) {DB::alteration_message("No images where found for product with Title <i>".$product->Title."</i>: no images could be added.");}
							}
						}
						else {
							if($this->verbose) {DB::alteration_message("The method <i>".$this->productManyManyField."</i> does not exist on <i>".$product->Title." (".$product->ClassName.")</i>: no images could be added.");}
						}
					}
					else {
						if($this->verbose) {DB::alteration_message("No InternalItemID set for <i>".$product->Title."</i>: no images could be added.");}
					}
				}
				$productCount = DB::query("SELECT COUNT(\"ID\") FROM \"Product\";")->val();

				if($this->start < $productCount) {
					$this->redirect($this->nextBatchLink());
				}
			}
		}
		else {
			if($this->verbose) {DB::alteration_message("No product Many-2-Many method specified.  No further action taken.  ");}
		}
	}

	protected function nextBatchLink(){
		return "error-not-completed";
	}

}
