<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Assets\Image;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Updates product images class names.
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
    protected static string $commandName = 'ecommerce:update-product-images';

    protected string $title = 'Sets Product Images to a new ClassName compatible with Ecommerce for Silverstripe 4.0';

    protected static string $description = 'Changes all Images in database from ClassName ProductImage to ClassName SilverStripe\\Assets\\Image';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        DB::query('
            UPDATE "File"
            SET "ClassName" = \'' . Image::class . '\'
            WHERE
                "ClassName" =  \'ProductImage\';
        ');

        $output->writeln('Product images updated successfully');

        return Command::SUCCESS;
    }
}
