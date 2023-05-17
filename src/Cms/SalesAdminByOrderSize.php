<?php

namespace Sunnysideup\Ecommerce\Cms;

use Sunnysideup\Ecommerce\Model\Order;

/**
 * Class \Sunnysideup\Ecommerce\Cms\SalesAdminByOrderSize
 *
 */
class SalesAdminByOrderSize extends SalesAdmin
{
    private static $required_permission_codes = 'CMS_ACCESS_SalesAdminByOrderSize';
    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $url_segment = 'sales-by-size';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_title = '... by Totals';

    private static $menu_priority = 3.112;

    /**
     * @var float
     */
    private static $brackets = [
        0 => 'n/a',
        100 => 'up to $100',
        200 => '$100 - $200',
        500 => '$200 - $500',
        1000 => '$500 - $1000',
        2500 => '$2,500 - $5,000',
        5000 => '$5,000 - $10,000',
        100000 => '$10,000+',
    ];

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
            $brackets = $this->Config()->get('brackets');
            $arrayOfTabs = array_fill_keys(array_keys($brackets), ['IDs' => []]);
            $baseList = $this->getList();
            foreach ($baseList as $order) {
                $total = $order->getTotal();
                $prevValue = 0;
                foreach (array_keys($brackets) as $value) {
                    if ($value) {
                        if ($total >= $prevValue && $total < $value) {
                            $arrayOfTabs[$value]['IDs'][$order->ID] = $order->ID;
                        }
                    }
                    $prevValue = $value;
                }
            }
            $this->buildTabs($brackets, $arrayOfTabs, $form);
        }

        return $form;
    }
}
