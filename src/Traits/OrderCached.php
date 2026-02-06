<?php

namespace Sunnysideup\Ecommerce\Traits;

use Sunnysideup\Ecommerce\Model\Order;

trait OrderCached
{
    /**
     * @var Order
     */
    protected $orderCached;

    /**
     * @var int
     */
    protected $orderCachedStatusID = -1;

    public function setOrderCached(?Order $order = null)
    {
        if ($order instanceof \Sunnysideup\Ecommerce\Model\Order) {
            $this->orderCached = $order;
            $this->orderCachedStatusID = $order->StatusID;
            $this->setOrderCachedStatically();
        }

        return $this;
    }

    public function getOrderCached(?bool $forceNew = false): ?Order
    {
        $this->getOrderCachedStaticallyIfNeeded($forceNew);
        if (
            ! ($this->orderCached && $this->orderCached->exists())                         // not set yet
            ||                                                                             // or
            ($this->orderCached && $this->orderCachedStatusID !== $this->orderCached->StatusID) // no status yet
        ) {
            //retrieve from Database!
            $this->setOrderCached($this->Order());
        }

        return $this->orderCached;
    }

    private function setOrderCachedStatically()
    {
        if ($this->orderCached && $this->orderCached->exists()) {
            Order::set_order_cached($this->orderCached);
        }
    }

    /**
     * retrieve from static cache if we dont have it.
     */
    private function getOrderCachedStaticallyIfNeeded(?bool $forceNew = false)
    {
        // we need to have an order ID
        if (! $this->orderCached && ! empty($this->OrderID)) {
            $this->orderCached = Order::get_order_cached($this->OrderID, $forceNew);
            // if we have not set it before then we can set statusID
            $this->orderCachedStatusID = (int) $this->orderCached?->StatusID;
        }
    }
}
