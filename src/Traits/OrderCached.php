<?php

namespace Sunnysideup\Ecommerce\Traits;

use SilverStripe\Core\Injector\Injector;
use Sunnysideup\Ecommerce\Model\Order;

trait OrderCached
{
    /**
     *
     * @var Order
     */
    protected $orderCached = null;

    /**
     *
     * @var int
     */
    protected $orderCachedStatusID = -1;

    public function setOrderCached(?Order $order = null)
    {
        if($order) {
            $this->orderCached = $order;
            $this->orderCachedStatusID = $order->StatusID;
            $this->setOrderCachedStatically();
        }
        return $this;
    }

    /**
     * @return Order|null;
     */
    public function getOrderCached() :?Order
    {
        $this->getOrderCachedStaticallyIfNeeded();
        if(
            !($this->orderCached && $this->orderCached->exists())
            ||
            ($this->orderCached && $this->orderCachedStatusID !== $this->orderCached->StatusID)
        ) {
            $this->setOrderCached($this->Order());
        }
        return $this->orderCached;
    }

    private function setOrderCachedStatically()
    {
        if($this->orderCached && $this->orderCached->exists()) {
            self::$order_cache[$this->orderCached->ID] = $this->orderCached;
        }
    }

    /**
     * retrieve from static cache if we dont have it
     */
    private function getOrderCachedStaticallyIfNeeded()
    {
        if(! $this->orderCached) {
            // we need to have an order ID
            if(! empty($this->OrderID)) {
                $this->orderCached = self::$order_cache[$this->OrderID] ?? null;
                // if we have not set it before then we can set statusID
                if($this->orderCached && $this->orderCached->exists() && $this->orderCachedStatusID === -1 ) {
                    $this->orderCachedStatusID  = $this->orderCached->StatusID;
                }
            }
        }
    }

}
