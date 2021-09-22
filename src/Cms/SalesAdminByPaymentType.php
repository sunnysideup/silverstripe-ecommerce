<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\ORM\DataList;
use Sunnysideup\Ecommerce\Model\Order;

use Sunnysideup\Ecommerce\Cms\SalesAdmin;

use Sunnysideup\Ecommerce\Money\EcommercePaymentSupportedMethodsProvider;

use Sunnysideup\ModelAdminManyTabs\Api\TabsBuilder;

use Sunnysideup\EcommerceDelivery\Modifiers\PickUpOrDeliveryModifier;

use Sunnysideup\EcommerceDelivery\Model\PickUpOrDeliveryModifierOptions;

class SalesAdminByPaymentType extends SalesAdmin
{
    private static $required_permission_codes = 'CMS_ACCESS_SalesAdminByPaymentType';
    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $url_segment = 'sales-by-payment-type';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_title = 'Sales by Payment Type';

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
            $brackets = $this->getBrackets();
            $arrayOfTabs = array_fill_keys($brackets, ['IDs' => []]);
            $baseList = $this->getList();
            $optionPerOrder = $this->getOptionPerOrder($baseList);
            foreach($baseList as $order) {
                $option = $optionPerOrder[$order->ID] ?? 0;
                foreach($brackets as $key => $bracket) {
                    if($option === $key) {
                        $arrayOfTabs[$bracket]['IDs'][$order->ID] = $order->ID;
                    }
                }
            }
            foreach($brackets as $key => $bracket) {
                if(empty($arrayOfTabs[$bracket]['IDs'])) {
                    $arrayOfTabs[$bracket]['IDs'] = [0 => 0];
                }
                $ids = $arrayOfTabs[$bracket]['IDs'];
                if($key) {
                    $arrayOfTabs[$bracket] = [
                        'TabName' => 'OptionID'.$key,
                        'Title' => $bracket,
                        'List' => Order::get()->filter(['ID' => $ids]),
                    ];
                    unset($arrayOfTabs['IDs']);
                } else {
                    unset($arrayOfTabs[$bracket]);
                }
            }
            TabsBuilder::add_many_tabs(
                $arrayOfTabs,
                $form,
                $this->modelClass
            );
        }
        return $form;
    }

    protected function getBrackets() : array
    {
        $list = EcommercePaymentSupportedMethodsProvider::supported_methods_basic_list();
        foreach($list as $item) {

        }
        return $list;
    }

    protected function getOptionPerOrder($baseList) : array
    {
        if($baseList->exists()) {
            $list = PickUpOrDeliveryModifier::get()->
                filter(['OrderID' => $baseList->columnUnique('ID')]);
            if($list->exists()) {
                return $list->map('OrderID', 'OptionID')->toArray();
            }
        }
        return [];
    }

}
