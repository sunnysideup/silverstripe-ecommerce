<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Links order addresses at both the Order and Address sides.
 *
 * @TODO: consider whether this does not sit better in its own module.
 * @TODO: refactor based on new database fields
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskLinkOrderAddressesAtBothEnds extends BuildTask
{
    protected static string $commandName = 'ecommerce:link-order-addresses';

    protected string $title = 'Links the Order Addresses at the Order And Address side';

    protected static string $description = 'This only needs to be run if you have an outdated version of e-commerce where the links seem broken';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->linkOrderWithBillingAndShippingAddress($output);

        return Command::SUCCESS;
    }

    /**
     * Make sure that the link between order and the two addresses is made on
     * both sides.
     *
     * @param PolyOutput $output - output interface
     */
    protected function linkOrderWithBillingAndShippingAddress(PolyOutput $output)
    {
        DB::query('
            UPDATE "Order"
                INNER JOIN "BillingAddress" ON "Order"."BillingAddressID" = "BillingAddress"."ID"
            SET "BillingAddress"."OrderID" = "Order"."ID"
            WHERE
                ("BillingAddress"."OrderID" IS NULL OR "BillingAddress"."OrderID" <> "Order"."ID")
                AND
                ("Order"."BillingAddressID" IS NOT NULL AND "Order"."BillingAddressID" > 0)
        ');
        DB::query('
            UPDATE "Order"
                INNER JOIN "BillingAddress" ON "BillingAddress"."OrderID" = "Order"."ID"
            SET "Order"."BillingAddressID" = "BillingAddress"."ID"
            WHERE
                ("Order"."BillingAddressID" IS NULL OR "Order"."BillingAddressID" <> "BillingAddress"."ID")
                AND
                ("BillingAddress"."OrderID" IS NOT NULL AND "BillingAddress"."OrderID" > 0)
        ');
        DB::query('
            UPDATE "Order"
                INNER JOIN "ShippingAddress" ON "Order"."ShippingAddressID" = "ShippingAddress"."ID"
            SET "ShippingAddress"."OrderID" = "Order"."ID"
            WHERE
                ("ShippingAddress"."OrderID" IS NULL OR "ShippingAddress"."OrderID" <> "Order"."ID")
                AND
                ("Order"."ShippingAddressID" IS NOT NULL AND "Order"."ShippingAddressID" > 0)
        ');
        DB::query('
            UPDATE "Order"
                INNER JOIN "ShippingAddress" ON "ShippingAddress"."OrderID" = "Order"."ID"
            SET "Order"."ShippingAddressID" = "ShippingAddress"."ID"
            WHERE
                ("Order"."ShippingAddressID" IS NULL OR "Order"."ShippingAddressID" <> "ShippingAddress"."ID")
                AND
                ("ShippingAddress"."OrderID" IS NOT NULL AND "ShippingAddress"."OrderID" > 0)
        ');
        $output->writeln('Linking Order to Billing and Shipping Address on both sides');
    }
}
