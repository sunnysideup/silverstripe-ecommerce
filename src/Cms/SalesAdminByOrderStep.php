<?php

namespace Sunnysideup\Ecommerce\Cms;

use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

class SalesAdminByOrderStep extends SalesAdmin
{
    private static $required_permission_codes = 'CMS_ACCESS_SalesAdminByOrderStep';
    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $url_segment = 'sales-by-step';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_title = '... by Step';

    private static $menu_priority = 3.113;
    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $managed_models = [
        Order::class,
    ];

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $fields = $form->Fields();
        if (is_subclass_of($this->modelClass, Order::class) || Order::class === $this->modelClass) {
            $arrayOfTabs = [];
            $brackets = OrderStep::admin_manageable_steps()->map('ID', 'Title')->toArray();
            $arrayOfTabs = array_fill_keys(array_keys($brackets), ['IDs' => []]);
            $baseList = $this->getList();
            foreach ($baseList as $order) {
                foreach (array_keys($brackets) as $key) {
                    if ($order->StatusID === $key) {
                        $arrayOfTabs[$key]['IDs'][$order->ID] = $order->ID;
                    }
                }
            }
            $this->buildTabs($brackets, $arrayOfTabs, $form);
        }

        return $form;
    }

    /**
     * @return array Map of class name to an array of 'title' (see {@link $managed_models})
     *               we make sure that the Order Admin is FIRST
     */
    public function getManagedModels()
    {
        return parent::getManagedModels();

        return [
            Order::class,
        ];
    }
}
