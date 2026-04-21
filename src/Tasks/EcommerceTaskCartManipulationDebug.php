<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Shows debug links for cart manipulation.
 *
 * @author: Nicolaas
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskCartManipulationDebug extends BuildTask
{
    protected static string $commandName = 'ecommerce-debug-cart-links';

    protected string $title = 'Show debug links';

    protected static string $description = 'Use a bunch of debug links to work with various objects such as the cart, the product group and the product page.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $myProductGroup = DataObject::get_one(ProductGroup::class);
        $myProduct = DataObject::get_one(Product::class);

        $output->writeln('Please use the links below:');
        $output->writeln('  - Debug cart: /shoppingcart/debug/');
        $output->writeln('  - View cart response: /shoppingcart/ajaxtest/?ajax=1');

        if ($myProductGroup) {
            $output->writeln('  - Debug product group: ' . $myProductGroup->Link('debug'));
        }

        if ($myProduct) {
            $output->writeln('  - Debug product: ' . $myProduct->Link('debug'));
        }

        return Command::SUCCESS;
    }
}
