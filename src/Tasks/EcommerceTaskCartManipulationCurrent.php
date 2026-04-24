<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Shows you the link to remove the current cart.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskCartManipulationCurrent extends BuildTask
{
    protected static string $commandName = 'ecommerce-clear-current-cart';

    protected string $title = 'Clear the current Cart';

    protected static string $description = 'Removes the cart that is currently in memory (session) for the current user. It does not delete the order itself.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $output->writeln('To clear the current cart from your session, visit: /shoppingcart/clear/');

        return Command::SUCCESS;
    }
}
