<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\ORM\DataList;
use Sunnysideup\Ecommerce\Model\Order;

class SalesAdminLowCost extends SalesAdmin
{
    private static $required_permission_codes = 'CMS_ACCESS_SalesAdminLowCost';
    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $url_segment = 'sales-low-cost';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_title = 'Low Cost Sales';

    /**
     * @var float
     */
    private static $max_sales_total = 200;

    /**
     * @return DataList
     */
    public function getList()
    {
        $list = parent::getList();
        $ids = [0 => 0];
        $max = $this->Config()->get('max_sales_total');
        if (is_subclass_of($this->modelClass, Order::class) || Order::class === $this->modelClass) {
            foreach ($list as $order) {
                if ($order->getTotal() < $max) {
                    $ids[$order->ID] = $order->ID;
                }
            }
            $list = Order::get()->filter(['ID' => $ids]);
        }

        return $list;
    }

    public function getManagedModels()
    {
        $models = parent::getManagedModels();
        $orderModelManagement = isset($models[Order::class]) ? $models[Order::class] : null;
        if ($orderModelManagement) {
            unset($models[Order::class]);

            return [Order::class => $orderModelManagement] + $models;
        }

        return $models;
    }
}
