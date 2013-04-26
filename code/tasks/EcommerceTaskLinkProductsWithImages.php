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
		That is, any image name [InteralItemID]_[two digits].[png/gif/jpg/pdf/(etc)] will automatically be linked to the product.
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
	protected $productManyManyField = "AdditionalFiles";

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
	public $verbose = true;

	protected $productID = 0;

	function run($request){
		if(isset($_REQUEST['start']) && intval($_REQUEST['start']))
			$this->start = intval($_REQUEST['start']);
		if($this->productManyManyField) {
			$products = Product::get()->limit($this->limit, $this->start);
			if($this->productID) {
				$products = $products->filter(array("ID" => $this->productID));
			}
			if($products->count()) {
				foreach($products as $product) {
					if($product->InternalItemID) {
						if($product->hasMethod($this->productManyManyField)) {
							$whereStringArray[] = $product->InternalItemID;
							for($i = 0; $i < 10; $i++) {
								for($j = 0; $j < 10; $j++) {
									$number = strval($i).strval($j);
									$whereStringArray[] = $product->InternalItemID."_".$number;
								}
							}
							$images = File::get()
								->filter(array("Name:PartialMatch" => $whereStringArray));

							if($images->count()) {
								$method = $this->productManyManyField;
								$collection = $product->$method();
								foreach($images as $image) {
									if($image instanceOf Image && $image->ClassName != "Product_Image") {
										$image->ClassName = "Product_Image";
										$image->write();
									}
									$collection->add($image);
									if($this->verbose) { DB::alteration_message("Adding image ".$image->Name." to ".$product->Title, "created"); }
								}
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
				$productCount = Product::get()->count();

				if($this->limit < $productCount) {
					$controller = Controller::curr();
					$controller->redirect($this->nextBatchLink());
				}
			}
		}
		else {
			if($this->verbose) {DB::alteration_message("No product Many-2-Many method specified.  No further action taken.  ");}
		}
	}

	protected function nextBatchLink(){
		return "dev/ecommerce/ecommercetasklinkproductwithimages/?start=". ($this->start + $this->limit);
	}

	public function setProductID($id) {
		$this->productID = $id;
	}

}
