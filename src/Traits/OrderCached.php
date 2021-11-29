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
        if ($order) {
            $this->orderCached = $order;
            $this->orderCachedStatusID = $order->StatusID;
            $this->setOrderCachedStatically();
        }

        return $this;
    }

    /**
     * @return Order|null;
     */
    public function getOrderCached(?bool $forceNew = false): ?Order
    {
        $this->getOrderCachedStaticallyIfNeeded($forceNew);
        if (
            ! ($this->orderCached && $this->orderCached->exists())
            ||
            ($this->orderCached && $this->orderCachedStatusID !== $this->orderCached->StatusID)
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
        if (! $this->orderCached) {
            // we need to have an order ID
            if (! empty($this->OrderID)) {
                $this->orderCached = Order::get_order_cached($this->OrderID, $forceNew);
                // if we have not set it before then we can set statusID
                if ($this->orderCached && $this->orderCached->exists() && -1 === $this->orderCachedStatusID) {
                    $this->orderCachedStatusID = $this->orderCached->StatusID;
                }
            }
        }
    }
}
