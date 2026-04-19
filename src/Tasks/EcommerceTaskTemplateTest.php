<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Get examples for building templates.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 *
 * @internal
 * @coversNothing
 */
class EcommerceTaskTemplateTest extends BuildTask
{
    protected static string $commandName = 'ecommerce:template-test';

    protected string $title = 'Get help with building templates';

    protected static string $description = 'Shows you some of the variables and controls you can use in your templates.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $output->writeln('To view the template test page, visit: /ecommercetemplatetest/?flush=all');

        return Command::SUCCESS;
    }
}
