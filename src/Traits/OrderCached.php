<?php

namespace Sunnysideup\Ecommerce\Traits;

use SilverStripe\Core\Injector\Injector;
use Sunnysideup\Ecommerce\Model\Order;

trait OrderCached
{

    /**
     *
     * @var array[Order]
     */
    private static $order_cache = [];


    /**
     *
     * @var Order
     */
    protected $orderCached = [];

    /**
     *
     * @var int
     */
    protected $orderCachedStatusID = 0;

    public function setOrderCached(Order $order)
    {
        $this->orderCached = $order;
        return $this;
    }

    /**
     * @return Order;
     */
    public function OrderCached()
    {
        $this->orderCached = $this->getStoreOrderStatically();
        if(
            !($this->orderCached && $this->orderCached->exists())
            || ($this->orderCached && $this->orderCachedStatusID !== $this->orderCached->StatusID)
        ) {
            $this->orderCached = $this->Order();
            $this->orderCachedStatusID = $this->orderCached->StatusID;
        }
        $this->setStoreOrderStatically();
        return $this->orderCached;
    }

    private function setStoreOrderStatically()
    {
        if($this->orderCached && $this->orderCached->StatusID) {
            self::$order_cache[$order->ID] = $this->orderCached;
        } else {
            self::$order_cache[$order->ID] = null;
        }
    }

    private function getStoreOrderStatically()
    {
        if(! $this->orderCached) {
            $this->orderCached = self::$order_cache[$this->OrderID] ?? null;
        }
    }
}
