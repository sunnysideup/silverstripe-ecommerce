<?php

namespace Sunnysideup\Ecommerce\Cms;

use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * Sales to action, one tab per order step.
 * Each tab has a button to archive (without processing) all orders in that step
 * that were placed more than a month ago.
 *
 * Class \Sunnysideup\Ecommerce\Cms\SalesAdminByOrderStep
 */
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
     * Orders placed before this (strtotime format, relative to now)
     * can be archived from their step tab.
     *
     * @var string
     */
    private static $archive_orders_older_than = '-1 month';

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
        $form->Fields();
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
            $cutoffDate = date('Y-m-d', strtotime((string) $this->Config()->get('archive_orders_older_than')));
            foreach (array_keys($brackets) as $stepID) {
                $this->addArchiveOldOrdersButton($form, (string) $stepID, $cutoffDate);
            }
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
    }
}
