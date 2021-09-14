<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\ORM\DataList;
use Sunnysideup\Ecommerce\Model\Order;

use Sunnysideup\ModelAdminManyTabs\Api\TabsBuilder;

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
    private static $menu_title = 'Sales by Totals';

    /**
     * @var float
     */
    private static $brackets = [
        0,
        100,
        200,
        500,
        1000,
        2500,
        5000,
        100000,
    ];


    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $fields = $form->Fields();
        if (is_subclass_of($this->modelClass, Order::class) || Order::class === $this->modelClass) {
            $arrayOfTabs = [];
            $brackets = $this->Config()->get('brackets');
            $arrayOfTabs = array_fill_keys($brackets, ['IDs' => []]);
            $baseList = $this->getList();
            foreach($baseList as $order) {
                $total = $order->getTotal();
                $prevBracket = 0;
                foreach($brackets as $key => $bracket) {
                    if($key) {
                        if($total >= $prevBracket && $total < $bracket) {
                            $arrayOfTabs[$bracket]['IDs'][$order->ID] = $order->ID;
                        }
                    }
                    $prevBracket = $bracket;
                }
            }
            $prevBracket = 0;
            foreach($brackets as $key => $bracket) {
                if(empty($arrayOfTabs[$bracket]['IDs'])) {
                    $arrayOfTabs[$bracket]['IDs'] = [0 => 0];
                }
                $ids = $arrayOfTabs[$bracket]['IDs'];
                if($key) {
                    $arrayOfTabs[$bracket] = [
                        'TabName' => 'From'.$prevBracket.'To'.$bracket,
                        'Title' => '$'.$prevBracket.' - $'.$bracket,
                        'List' => Order::get()->filter(['ID' => $ids]),
                    ];
                    unset($arrayOfTabs['IDs']);
                } else {
                    unset($arrayOfTabs[$bracket]);
                }
                $prevBracket = $bracket;
            }
            TabsBuilder::add_many_tabs(
                $arrayOfTabs,
                $form,
                $this->modelClass
            );
        }
        return $form;
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
