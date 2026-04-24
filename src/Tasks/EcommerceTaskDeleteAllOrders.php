<?php

namespace Sunnysideup\Ecommerce\Tasks;

use Override;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Model\Address\OrderAddress;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\OrderAttribute;
use Sunnysideup\Ecommerce\Model\OrderItem;
use Sunnysideup\Ecommerce\Model\OrderModifier;
use Sunnysideup\Ecommerce\Model\Process\OrderEmailRecord;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class EcommerceTaskDeleteAllOrders extends BuildTask
{
    public bool $verbose = false;

    protected static string $commandName = 'ecommerce-delete-all-orders';

    protected string $title = 'Deletes all orders - CAREFUL!';

    protected static string $description = 'Deletes all the orders and payments ever placed - CAREFUL! This is destructive and irreversible.';

    /**
     * key = table where OrderID is saved
     * value = table where LastEdited is saved.
     */
    private static $linked_objects_array = [
        'OrderAttribute' => OrderAttribute::class,
        'BillingAddress' => OrderAddress::class,
        'ShippingAddress' => OrderAddress::class,
        'OrderStatusLog' => OrderStatusLog::class,
        'OrderEmailRecord' => OrderEmailRecord::class,
        'EcommercePayment' => EcommercePayment::class,
    ];

    /**
     *key = table where OrderID is saved
     *value = table where LastEdited is saved.
     */
    private static $double_check_objects = [
        Order::class,
        OrderItem::class,
        OrderModifier::class,
        EcommercePayment::class,
    ];

    // DELETE OLD SHOPPING CARTS

    #[Override]
    public function getOptions(): array
    {
        return [
            new InputOption('confirm', 'c', InputOption::VALUE_NONE, 'Confirm deletion of all orders'),
            new InputOption('verbose', 'v', InputOption::VALUE_NONE, 'Verbose output'),
        ];
    }

    /**
     * @return int - number of carts destroyed
     */
    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        // @TODO (SS6 upgrade) - Check if dev mode detection is still needed/available
        // Original code checked Director::isDev() and Director::isLive()

        if (!$input->getOption('confirm')) {
            $output->writeln('ERROR: This command will delete ALL orders permanently!');
            $output->writeln('To confirm, run with --confirm flag:');
            $output->writeln('  sake ecommerce:delete-all-orders --confirm');
            return Command::FAILURE;
        }

        $this->verbose = $input->getOption('verbose');

        $oldCarts = Order::get();
        $count = 0;
        if ($oldCarts->exists()) {
            if ($this->verbose) {
                $totalToDeleteSQLObject = DB::query('SELECT COUNT(*) FROM "Order"');
                $totalToDelete = $totalToDeleteSQLObject->value();
                $output->writeln('Total number of orders: ' . $totalToDelete . ' .... now deleting:');
            }

            foreach ($oldCarts as $oldCart) {
                ++$count;
                if ($this->verbose) {
                    $output->writeln($count . ' ... deleting abandonned order #' . $oldCart->ID);
                }

                $oldCart->delete();
                $oldCart->destroy();
            }
        } elseif ($this->verbose) {
            $count = DB::query('SELECT COUNT("ID") FROM "Order"')->value();
            $output->writeln(sprintf("There are no abandonned orders. There are %s 'live' Orders.", $count));
        }

        $countCheck = DB::query('Select COUNT(ID) FROM "Order"')->value();
        if ($countCheck) {
            $output->writeForHtml('ERROR: in testing <i>Orders</i> it appears there are ' . $countCheck . ' records left.');
        } else {
            $output->writeForHtml('PASS: in testing <i>Orders</i> there seem to be no records left.');
        }

        $this->cleanupUnlinkedOrderObjects($output);
        $this->doubleCheckModifiersAndItems($output);

        return Command::SUCCESS;
    }

    public function cleanupUnlinkedOrderObjects(PolyOutput $output)
    {
        $classNames = $this->Config()->get('linked_objects_array');
        if (is_array($classNames) && count($classNames)) {
            foreach ($classNames as $classWithOrderID => $classWithLastEdited) {
                if ($this->verbose) {
                    $output->writeln(sprintf('looking for %s objects without link to order.', $classWithOrderID));
                }

                $where = '"Order"."ID" IS NULL ';
                // $join = ' LEFT JOIN "Order" ON ';
                //the code below is a bit of a hack, but because of the one-to-one relationship we
                //want to check both sides....
                $unlinkedObjects = $classWithLastEdited::get();
                if ($classWithLastEdited !== $classWithOrderID) {
                    $unlinkedObjects = $unlinkedObjects
                        ->leftJoin($classWithOrderID, sprintf('"OrderAddress"."ID" = "%s"."ID"', $classWithOrderID));
                }

                $unlinkedObjects = $unlinkedObjects
                    ->where($where)
                    ->leftJoin('Order', sprintf('"Order"."ID" = "%s"."OrderID"', $classWithOrderID));

                if ($unlinkedObjects->exists()) {
                    foreach ($unlinkedObjects as $unlinkedObject) {
                        if ($this->verbose) {
                            $output->writeln('Deleting ' . $unlinkedObject->ClassName . ' with ID #' . $unlinkedObject->ID . ' because it does not appear to link to an order.');
                        }

                        //HACK FOR DELETING
                        $this->deleteObject($unlinkedObject, $output);
                    }
                }

                $countCheck = DB::query(sprintf('Select COUNT(ID) FROM "%s"', $classWithLastEdited))->value();
                if ($countCheck) {
                    $output->writeForHtml('ERROR: in testing <i>' . $classWithOrderID . '</i> it appears there are ' . $countCheck . ' records left.');
                } else {
                    $output->writeForHtml('PASS: in testing <i>' . $classWithOrderID . '</i> there seem to be no records left.');
                }
            }
        }
    }

    private function doubleCheckModifiersAndItems(PolyOutput $output)
    {
        $output->writeln('--- double-check ---');
        foreach ($this->config()->get('double_check_objects') as $table) {
            $countCheck = DB::query(sprintf('Select COUNT(ID) FROM "%s"', $table))->value();
            if ($countCheck) {
                $output->writeForHtml('ERROR: in testing <i>' . $table . '</i> it appears there are ' . $countCheck . ' records left.');
            } else {
                $output->writeForHtml('PASS: in testing <i>' . $table . '</i> there seem to be no records left.');
            }
        }
    }

    private function deleteObject($unlinkedObject, PolyOutput $output)
    {
        if ($unlinkedObject) {
            if ($unlinkedObject->ClassName) {
                if (class_exists($unlinkedObject->ClassName) && ClassHelpers::check_for_instance_of($unlinkedObject, DataObject::class, false)) {
                    $unlinkedObjectClassName = $unlinkedObject->ClassName;
                    $objectToDelete = $unlinkedObjectClassName::get_by_id($unlinkedObject->ID);
                    if ($objectToDelete) {
                        $objectToDelete->delete();
                        $objectToDelete->destroy();
                    } elseif ($this->verbose) {
                        $output->writeln('ERROR: could not find ' . $unlinkedObject->ClassName . ' with ID = ' . $unlinkedObject->ID);
                    }
                } elseif ($this->verbose) {
                    $output->writeln('ERROR: trying to delete an object that is not a dataobject: ' . $unlinkedObject->ClassName);
                }
            } elseif ($this->verbose) {
                $output->writeln('ERROR: trying to delete object without a class name');
            }
        } elseif ($this->verbose) {
            $output->writeln('ERROR: trying to delete non-existing object.');
        }
    }
}
