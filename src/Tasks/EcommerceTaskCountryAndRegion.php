<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Address\EcommerceCountry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Create standard country and regions.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskCountryAndRegion extends BuildTask
{
    protected static string $commandName = 'ecommerce-create-countries';

    protected string $title = 'Create standard countries and regions';

    protected static string $description = 'Adds all countries to the EcommerceCountry list';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $count = 0;
        $array = EcommerceCountry::get_country_dropdown();
        $allowedArray = EcommerceConfig::get(EcommerceCountry::class, 'allowed_country_codes');
        foreach ($array as $code => $name) {
            $ecommerceCountry = EcommerceCountry::get()->filter(
                ['Code' => Convert::raw2sql($code)],
            )->first();
            if ($ecommerceCountry) {
                //do nothing
                ++$count;
            } else {
                $output->writeln(sprintf('adding %s to Ecommerce Country', $code));
                $ecommerceCountry = EcommerceCountry::create();
                $ecommerceCountry->Code = $code;
            }

            if ($allowedArray && count($allowedArray)) {
                $ecommerceCountry->DoNotAllowSales = in_array($code, $allowedArray, true) ? 0 : 1;
            }

            $ecommerceCountry->Name = $name;
            $ecommerceCountry->write();
        }

        $output->writeln(sprintf('Created / Checked %d Ecommerce Countries', $count));

        return Command::SUCCESS;
    }
}
