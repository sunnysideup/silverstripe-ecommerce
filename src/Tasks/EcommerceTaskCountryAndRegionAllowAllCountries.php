<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Update EcommerceCountry.DoNotAllowSales to 0 so that you can sell to all countries.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskCountryAndRegionAllowAllCountries extends BuildTask
{
    protected static string $commandName = 'ecommerce-allow-all-countries';

    protected string $title = 'Allows sale to all countries';

    protected static string $description = 'Reset all countries from Disallow Sales to Allow Sales - as a good starting point when selling to all countries.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $allowedArray = EcommerceCountry::get()
            ->filter(['DoNotAllowSales' => 1]);
        if ($allowedArray->exists()) {
            foreach ($allowedArray as $obj) {
                $obj->DoNotAllowSales = false;
                $obj->write();
                $output->writeln('Allowing sales to ' . $obj->Name);
            }
        } else {
            $output->writeln('Could not find any countries that are not allowed');
        }

        return Command::SUCCESS;
    }
}
