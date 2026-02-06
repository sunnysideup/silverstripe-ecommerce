<?php

namespace Sunnysideup\Ecommerce\Cms;

use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Money\EcommercePaymentSupportedMethodsProvider;

/**
 * Class \Sunnysideup\Ecommerce\Cms\SalesAdminByPaymentType
 *
 */
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
    private static $menu_title = '... by Payment';

    private static $menu_priority = 3.114;
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
            $brackets = $this->getBrackets();
            $arrayOfTabs = array_fill_keys(array_keys($brackets), ['IDs' => []]);
            $baseList = $this->getList();
            $optionPerOrder = $this->getOptionPerOrder($baseList);
            foreach ($baseList as $order) {
                $option = $optionPerOrder[$order->ID] ?? 'ERROR';
                foreach (array_keys($brackets) as $key) {
                    if ($this->classNameConverter($option) === $key) {
                        $arrayOfTabs[$key]['IDs'][$order->ID] = $order->ID;
                    }
                }
            }
            $this->buildTabs($brackets, $arrayOfTabs, $form);
        }

        return $form;
    }

    protected function getBrackets(): array
    {
        $list = EcommercePaymentSupportedMethodsProvider::supported_methods_basic_list();
        $newArray = [];
        foreach ($list as $key => $value) {
            $newArray[$this->classNameConverter($key)] = $value;
        }

        return $newArray;
    }

    protected function classNameConverter(string $className): string
    {
        return str_replace('\\', '-', $className);
    }

    protected function getOptionPerOrder($baseList): array
    {
        if ($baseList->exists()) {
            $list = EcommercePayment::get()->
                filter(['OrderID' => $baseList->columnUnique()]);
            if ($list->exists()) {
                return $list->map('OrderID', 'ClassName')->toArray();
            }
        }

        return [];
    }
}
