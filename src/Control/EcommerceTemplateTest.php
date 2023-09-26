<?php

namespace Sunnysideup\Ecommerce\Control;

use PageController;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Class \Sunnysideup\Ecommerce\Control\EcommerceTemplateTest
 *
 * @property \Sunnysideup\Ecommerce\Control\EcommerceTemplateTest $dataRecord
 * @method \Sunnysideup\Ecommerce\Control\EcommerceTemplateTest data()
 * @mixin \Sunnysideup\Ecommerce\Control\EcommerceTemplateTest
 */
class EcommerceTemplateTest extends PageController
{
    public function index()
    {
        return $this->renderWith('Sunnysideup\Ecommerce\Layout\EcommerceTemplateTest');
    }

    /**
     * Goes through all products and find one that
     * "canPurchase".
     *
     * @return Product
     */
    public function RandomProduct()
    {
        $offSet = 0;
        $product = true;
        $notForSale = true;
        while ($product && $notForSale) {
            $notForSale = false;
            $product = Product::get()
                ->where('"AllowPurchase" = 1  AND "Price" > 0')
                ->orderBy(DB::get_conn()->random(), 'ASC')
                ->limit(1, $offSet)
                ->First()
            ;
            if ($product) {
                $notForSale = ! (bool) $product->canPurchase();
            }
            ++$offSet;
        }

        return $product;
    }

    public function SubmittedOrder(): ?Order
    {
        $lastStatusOrder = OrderStep::last_order_step();
        if ($lastStatusOrder) {
            return Order::get()
                ->filter(['StatusID' => $lastStatusOrder->ID])
                ->orderBy(DB::get_conn()->random())
                ->first();
        }
        return null;
    }

    /**
     * This is used for template-ty stuff.
     *
     * @return bool
     */
    public function IsEcommercePage(): bool
    {
        return true;
    }
}
