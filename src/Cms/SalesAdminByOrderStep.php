<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\ORM\DataList;
use Sunnysideup\Ecommerce\Model\Order;

use Sunnysideup\Ecommerce\Model\Process\OrderStep;

use Sunnysideup\ModelAdminManyTabs\Api\TabsBuilder;

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
    private static $menu_title = 'Sales by Step';

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $fields = $form->Fields();
        if (is_subclass_of($this->modelClass, Order::class) || Order::class === $this->modelClass) {
            $arrayOfTabs = [];
            $ids = OrderStep::admin_manageable_steps()->map('ID', 'Title')->toArray();
            foreach($ids as $id => $title) {
                $arrayOfTabs[$id] = [];
            }
            $baseList = $this->getList();
            foreach($baseList as $order) {
                foreach(array_keys($ids) as $id) {
                    if($order->StatusID === $id) {
                        $arrayOfTabs[$id]['IDs'][$order->ID] = $order->ID;
                    }
                }
            }
            foreach($ids as $id => $title) {
                if(empty($arrayOfTabs[$id]['IDs'])) {
                    $arrayOfTabs[$id]['IDs'] = [0 => 0];
                }
                $ids = $arrayOfTabs[$id]['IDs'];
                $arrayOfTabs[$id] = [
                    'TabName' => 'step'.$id,
                    'Title' => $title,
                    'List' => Order::get()->filter(['ID' => $ids]),
                ];
                unset($arrayOfTabs['IDs']);
            }
            TabsBuilder::add_many_tabs(
                $arrayOfTabs,
                $form,
                $this->modelClass
            );
        }
        return $form;
    }

}
