<?php

namespace Sunnysideup\Ecommerce\Tasks;






use Sunnysideup\Ecommerce\Model\OrderAttribute;
use Sunnysideup\Ecommerce\Model\Address\OrderAddress;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\Ecommerce\Model\Process\OrderEmailRecord;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\OrderItem;
use Sunnysideup\Ecommerce\Model\OrderModifier;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\BuildTask;




/**
 * @description (see $this->description)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks

 **/
class EcommerceTaskDeleteAllOrders extends BuildTask
{
    public $verbose = false;

    protected $title = 'Deletes all orders - CAREFUL!';

    protected $description = 'Deletes all the orders and payments ever placed - CAREFULL!';

    private static $allowed_actions = [
        '*' => 'ADMIN',
    ];

    /**
     *key = table where OrderID is saved
     *value = table where LastEdited is saved.
     **/
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
     **/
    private static $double_check_objects = [
        Order::class,
        OrderItem::class,
        OrderModifier::class,
        EcommercePayment::class,
    ];

    /*******************************************************
         * DELETE OLD SHOPPING CARTS
    *******************************************************/

    /**
     *@return int - number of carts destroyed
     **/
    public function run($request)
    {
        if (! Director::isDev() || Director::isLive()) {
            DB::alteration_message('you can only run this in dev mode!');
        } else {
            if (! isset($_REQUEST['i-am-sure'])) {
                $_REQUEST['i-am-sure'] = '';
            }
            if ($_REQUEST['i-am-sure'] !== 'yes') {
                die("<h1>ARE YOU SURE?</h1><br /><br /><br /> please add the 'i-am-sure' get variable to your request and set it to 'yes' ... e.g. <br />http://www.mysite.com/dev/ecommerce/ecommercetaskdeleteallorders/?i-am-sure=yes");
            }
            $oldCarts = Order::get();
            $count = 0;
            if ($oldCarts->count()) {
                if ($this->verbose) {
                    $totalToDeleteSQLObject = DB::query('SELECT COUNT(*) FROM "Order"');
                    $totalToDelete = $totalToDeleteSQLObject->value();
                    DB::alteration_message('<h2>Total number of orders: ' . $totalToDelete . ' .... now deleting: </h2>', 'deleted');
                }
                foreach ($oldCarts as $oldCart) {
                    ++$count;
                    if ($this->verbose) {
                        DB::alteration_message("${count} ... deleting abandonned order #" . $oldCart->ID, 'deleted');
                    }
                    $oldCart->delete();
                    $oldCart->destroy();
                }
            } else {
                if ($this->verbose) {
                    $count = DB::query('SELECT COUNT("ID") FROM "Order"')->value();
                    DB::alteration_message("There are no abandonned orders. There are ${count} 'live' Orders.", 'created');
                }
            }
            $countCheck = DB::query('Select COUNT(ID) FROM "Order"')->value();
            if ($countCheck) {
                DB::alteration_message('ERROR: in testing <i>Orders</i> it appears there are ' . $countCheck . ' records left.', 'deleted');
            } else {
                DB::alteration_message('PASS: in testing <i>Orders</i> there seem to be no records left.', 'created');
            }
            $this->cleanupUnlinkedOrderObjects();
            $this->doubleCheckModifiersAndItems();

            return $count;
        }
    }

    public function cleanupUnlinkedOrderObjects()
    {
        $classNames = $this->Config()->get('linked_objects_array');
        if (is_array($classNames) && count($classNames)) {
            foreach ($classNames as $classWithOrderID => $classWithLastEdited) {
                if ($this->verbose) {
                    DB::alteration_message("looking for ${classWithOrderID} objects without link to order.", 'deleted');
                }
                $where = '"Order"."ID" IS NULL ';
                // $join = ' LEFT JOIN "Order" ON ';
                //the code below is a bit of a hack, but because of the one-to-one relationship we
                //want to check both sides....
                $unlinkedObjects = $classWithLastEdited::get();
                if ($classWithLastEdited !== $classWithOrderID) {
                    $unlinkedObjects = $unlinkedObjects
                        ->leftJoin($classWithOrderID, "\"OrderAddress\".\"ID\" = \"${classWithOrderID}\".\"ID\"");
                }
                $unlinkedObjects = $unlinkedObjects
                    ->where($where)
                    ->leftJoin(Order::class, "\"Order\".\"ID\" = \"${classWithOrderID}\".\"OrderID\"");

                if ($unlinkedObjects->count()) {
                    foreach ($unlinkedObjects as $unlinkedObject) {
                        if ($this->verbose) {
                            DB::alteration_message('Deleting ' . $unlinkedObject->ClassName . ' with ID #' . $unlinkedObject->ID . ' because it does not appear to link to an order.', 'deleted');
                        }
                        //HACK FOR DELETING
                        $this->deleteObject($unlinkedObject);
                    }
                }
                $countCheck = DB::query("Select COUNT(ID) FROM \"${classWithLastEdited}\"")->value();
                if ($countCheck) {
                    DB::alteration_message('ERROR: in testing <i>' . $classWithOrderID . '</i> it appears there are ' . $countCheck . ' records left.', 'deleted');
                } else {
                    DB::alteration_message('PASS: in testing <i>' . $classWithOrderID . '</i> there seem to be no records left.', 'created');
                }
            }
        }
    }

    private function doubleCheckModifiersAndItems()
    {
        DB::alteration_message('<hr />double-check:</hr />');
        foreach ($this->config()->get('double_check_objects') as $table) {
            $countCheck = DB::query("Select COUNT(ID) FROM \"${table}\"")->value();
            if ($countCheck) {
                DB::alteration_message('ERROR: in testing <i>' . $table . '</i> it appears there are ' . $countCheck . ' records left.', 'deleted');
            } else {
                DB::alteration_message('PASS: in testing <i>' . $table . '</i> there seem to be no records left.', 'created');
            }
        }
    }

    private function deleteObject($unlinkedObject)
    {
        if ($unlinkedObject) {
            if ($unlinkedObject->ClassName) {
                if (class_exists($unlinkedObject->ClassName) && $unlinkedObject instanceof DataObject) {
                    $unlinkedObjectClassName = $unlinkedObject->ClassName;
                    $objectToDelete = $unlinkedObjectClassName::get()->byID($unlinkedObject->ID);
                    if ($objectToDelete) {
                        $objectToDelete->delete();
                        $objectToDelete->destroy();
                    } elseif ($this->verbose) {
                        DB::alteration_message('ERROR: could not find ' . $unlinkedObject->ClassName . ' with ID = ' . $unlinkedObject->ID, 'deleted');
                    }
                } elseif ($this->verbose) {
                    DB::alteration_message('ERROR: trying to delete an object that is not a dataobject: ' . $unlinkedObject->ClassName, 'deleted');
                }
            } elseif ($this->verbose) {
                DB::alteration_message('ERROR: trying to delete object without a class name', 'deleted');
            }
        } elseif ($this->verbose) {
            DB::alteration_message('ERROR: trying to delete non-existing object.', 'deleted');
        }
    }
}

