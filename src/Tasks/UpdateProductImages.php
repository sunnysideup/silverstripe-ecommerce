<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Assets\Image;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

/**
 * works out how many products have been sold, per product.
 *
 * @TODO: consider whether this does not sit better in its own module.
 * @TODO: refactor based on new database fields
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class UpdateProductImages extends BuildTask
{
    protected $title = 'Sets Product Images to a new ClassName compatible with Ecommerce for Silvertripe 4.0';

    protected $description = 'Changes all Images in database from ClassName ProductImage to ClassName SilverStripe\\Assets\\Image';

    public function run($request)
    {
        DB::query('
            UPDATE "File"
            SET "ClassName" = \'' . Image::class . '\'
            WHERE
                "ClassName" =  \'ProductImage\';
        ');

        echo '<h1>DONE!</h1>';
    }
}
