<?php

/**
 * see description in class
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceProductImageReset extends BuildTask{

	protected $title = "Reset all product (variation) images";

	protected $description = "(1) Checks if the image class is ProductImage, (2) Checks if the image exists and remove if it does not exist. NOTE: it is recommended that you update the file system before you run this task.";

	function run($request){
		$removeCount = 0;
		$updateClassCount = 0;
		$tables = array("Product", "Product_Live", "ProductVariation");
		foreach($tables as $tableName) {
			$rows = DB::query("SELECT \"ImageID\" FROM \"$tableName\" WHERE ImageID > 0;");
			if($rows) {
				foreach ($rows as $row) {
					$remove = false;
					$image = DataObject::get_by_id("Image", $row["ImageID"]);
					if(!$image) {
						$remove = true;
					}
					elseif(!$image->getTag()) {
						$remove = true;
					}
					if($remove) {
						$removeCount++;
						DB::query("UPDATE \"$tableName\" SET \"ImageID\" = 0;");
					}
					elseif(!($image instanceOf Product_Image)) {
						$updateClassCount++;
						$image->ClassName = "Product_Image";
						$image-write();
					}
				}
			}
			if($removeCount) {
				DB::alteration_message("$tableName: Removed $removeCount image(s) from products and variations because they do not exist in the file-system or database", "deleted");
			}
			else {
				DB::alteration_message("$tableName: All product images are accounted for", "created");
			}
			if($updateClassCount) {
				DB::alteration_message("$tableName: $removeCount image(s) did not match the requirement 'instanceOF Product_Image', this has been corrected.", "deleted");
			}
			else {
				DB::alteration_message("$tableName: All product images instancesOF Product_Image", "created");
			}
		}
	}

}

