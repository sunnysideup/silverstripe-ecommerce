<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskReviewReports extends BuildTask
{
    protected static string $commandName = 'ecommerce-review-reports';

    protected string $title = 'Review E-commerce Pages using the Reports interface';

    protected static string $description = 'Review a bunch of reports that provide information on the e-commerce pages created, such as the Products without Images.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $output->writeln('Open the Reports Interface in your browser: /admin/reports/');

        return Command::SUCCESS;
    }
}
