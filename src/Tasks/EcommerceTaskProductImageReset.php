<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Filesystem\ProductImage;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
/**
 * see description in class.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks

 **/
class EcommerceTaskProductImageReset extends BuildTask
{
    protected $title = 'Reset all product (variation) images';

    protected $description = '(1) Checks if the image class is ProductImage, (2) Checks if the image exists and remove if it does not exist. NOTE: it is recommended that you update the file system before you run this task.';

    public function run($request)
    {
        $tables = [ProductGroup::class, 'ProductGroup_Live', Product::class, 'Product_Live'];
        if (class_exists('ProductVariation')) {
            $tables[] = 'ProductVariation';
        }
        //todo: make list based on buyables rather than hard-coded.
        foreach ($tables as $tableName) {
            $classErrorCount = 0;
            $removeCount = 0;
            $updateClassCount = 0;
            $rowCount = DB::query("SELECT COUNT(\"ImageID\") FROM \"${tableName}\" WHERE ImageID > 0;")->value();
            DB::alteration_message("<h2><strong>CHECKING ${tableName} ( ${rowCount} records ):</strong></h2>");
            $rows = DB::query("SELECT \"ImageID\", \"${tableName}\".\"ID\" FROM \"${tableName}\" WHERE ImageID > 0;");
            if ($rows) {
                foreach ($rows as $row) {
                    $remove = false;
                    $classErrorCount += DB::query('
						SELECT COUNT ("File"."ID")
						FROM "File"
						WHERE
							"File"."ID" = ' . $row['ImageID'] . "
							AND  (
							 \"ClassName\" = 'Image' OR
							 \"ClassName\" = 'ProductVariation_Image' OR
							 \"ClassName\" = ''
							);
					")->value();
                    DB::query("
						UPDATE \"File\"
						SET \"ClassName\" = 'ProductImage'
						WHERE
							\"File\".\"ID\" = " . $row['ImageID'] . "
							AND  (
							 \"ClassName\" = 'Image' OR
							 \"ClassName\" = 'ProductVariation_Image' OR
							 \"ClassName\" = ''
							);
					");
                    $image = ProductImage::get()->byID($row['ImageID']);
                    if (! $image) {
                        $remove = true;
                    } elseif (! $image->getTag()) {
                        $remove = true;
                    }
                    if ($remove) {
                        ++$removeCount;
                        DB::query("UPDATE \"${tableName}\" SET \"ImageID\" = 0 WHERE \"${tableName}\".\"ID\" = " . $row['ID'] . " AND \"${tableName}\".\"ImageID\" = " . $row['ImageID'] . ';');

                    /**
                      * ### @@@@ START REPLACEMENT @@@@ ###
                      * WHY: automated upgrade
                      * OLD:  Object:: (case sensitive)
                      * NEW:  SilverStripe\\Core\\Injector\\Injector::inst()-> (COMPLEX)
                      * EXP: Check if this is the right implementation, this is highly speculative.
                      * ### @@@@ STOP REPLACEMENT @@@@ ###
                      */
                    } elseif (! is_a($image, EcommerceConfigClassNames::getName(ProductImage::class))) {
                        ++$updateClassCount;
                        $image = $image->newClassInstance(ProductImage::class);
                        $image - write();
                    }
                }
            }
            if ($classErrorCount) {
                DB::alteration_message("<strong>${tableName}:</strong> there were ${classErrorCount} files with the wrong class names.  These have been fixed.", 'deleted');
            } else {
                DB::alteration_message("<strong>${tableName}:</strong> there were no files with the wrong class names. ", 'created');
            }
            if ($removeCount) {
                DB::alteration_message("<strong>${tableName}:</strong> Removed ${removeCount} image(s) from products and variations because they do not exist in the file-system or database", 'deleted');
            } else {
                DB::alteration_message("<strong>${tableName}:</strong> All product images are accounted for", 'created');
            }
            if ($updateClassCount) {
                DB::alteration_message("<strong>${tableName}:</strong> ${removeCount} image(s) did not match the requirement 'instanceOF ProductImage', this has been corrected.", 'deleted');
            } else {
                DB::alteration_message("<strong>${tableName}:</strong> All product images instancesOF ProductImage", 'created');
            }
        }
    }
}
