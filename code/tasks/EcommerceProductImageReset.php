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
		$tables = array("ProductGroup", "ProductGroup_Live","Product", "Product_Live");
		if(class_exists("ProductVariation")) {
			$tables[] = "ProductVariation";
		}
		//todo: make list based on buyables rather than hard-coded.
		foreach($tables as $tableName) {
			$classErrorCount = 0;
			$removeCount = 0;
			$updateClassCount = 0;
			$rowCount = DB::query("SELECT COUNT(\"ImageID\") FROM \"$tableName\" WHERE ImageID > 0;")->value();
			DB::alteration_message("<h2><strong>CHECKING $tableName ( $rowCount records ):</strong></h2>");
			$rows = DB::query("SELECT \"ImageID\", \"$tableName\".\"ID\" FROM \"$tableName\" WHERE ImageID > 0;");
			if($rows) {
				foreach ($rows as $row) {
					$remove = false;
					$classErrorCount += DB::query("
						SELECT COUNT (\"File\".\"ID\")
						FROM \"File\"
						WHERE
							\"File\".\"ID\" = ".$row["ImageID"]."
							AND  (
							 \"ClassName\" = 'Image' OR
							 \"ClassName\" = 'ProductVariation_Image' OR
							 \"ClassName\" = ''
							);
					")->value();
					DB::query("
						UPDATE \"File\"
						SET \"ClassName\" = 'Product_Image'
						WHERE
							\"File\".\"ID\" = ".$row["ImageID"]."
							AND  (
							 \"ClassName\" = 'Image' OR
							 \"ClassName\" = 'ProductVariation_Image' OR
							 \"ClassName\" = ''
							);
					");
					$image = Product_Image::get()->byID($row["ImageID"]);
					if(!$image) {
						$remove = true;
					}
					elseif(!$image->getTag()) {
						$remove = true;
					}
					if($remove) {
						$removeCount++;
						DB::query("UPDATE \"$tableName\" SET \"ImageID\" = 0 WHERE \"$tableName\".\"ID\" = ".$row["ID"]." AND \"$tableName\".\"ImageID\" = ".$row["ImageID"].";");
					}
					elseif(!($image instanceOf Product_Image)) {
						$updateClassCount++;
						$image->ClassName = "Product_Image";
						$image-write();
					}
				}
			}
			if($classErrorCount) {
				DB::alteration_message("<strong>$tableName:</strong> there were $classErrorCount files with the wrong class names.  These have been fixed.", "deleted");
			}
			else {
				DB::alteration_message("<strong>$tableName:</strong> there were no files with the wrong class names. ", "created");
			}
			if($removeCount) {
				DB::alteration_message("<strong>$tableName:</strong> Removed $removeCount image(s) from products and variations because they do not exist in the file-system or database", "deleted");
			}
			else {
				DB::alteration_message("<strong>$tableName:</strong> All product images are accounted for", "created");
			}
			if($updateClassCount) {
				DB::alteration_message("<strong>$tableName:</strong> $removeCount image(s) did not match the requirement 'instanceOF Product_Image', this has been corrected.", "deleted");
			}
			else {
				DB::alteration_message("<strong>$tableName:</strong> All product images instancesOF Product_Image", "created");
			}
		}
	}

}

