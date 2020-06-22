<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\Core\Convert;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DataList;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldExportSalesButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldPrintAllInvoicesButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldPrintAllPackingSlipsButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldPrintInvoiceButton;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;

/**
 * @description: CMS management for everything you have sold and all related data (e.g. logs, payments)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms

 **/
class SalesAdminExtras extends ModelAdminEcommerceBaseClass
{
    /**
     * Change this variable if you don't want the Import from CSV form to appear.
     * This variable can be a boolean or an array.
     * If array, you can list className you want the form to appear on. i.e. array('myClassOne','myClasstwo').
     */
    public $showImportForm = false;

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $url_segment = 'sales-advanced';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_title = 'Sales Details';

    /**
     * standard SS variable.
     *
     * @var float
     */
    private static $menu_priority = 3.11;

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $required_permission_codes = 'CMS_ACCESS_SalesAdminExtras';

    /**
     * standard SS variable.
     *
     * @var string
     */
    /* TODO UPGRADE: fix the following line */
    //private static $menu_icon = 'ecommerce/client/images/icons/money-file.gif';

    public function init()
    {
        parent::init();
        Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomBuyableSelectField.js');
    }

    /**
     * @return array Map of class name to an array of 'title' (see {@link $managed_models})
     *               we make sure that the Order Admin is FIRST
     */
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

    /**
     * @return DataList
     */
    public function getList()
    {
        $list = parent::getList();
        if (is_subclass_of($this->modelClass, Order::class) || $this->modelClass === Order::class) {
            $submittedOrderStatusLogClassName = EcommerceConfig::get(OrderStatusLog::class, 'order_status_log_class_used_for_submitting_order');
            $submittedOrderStatusLogTableName = OrderStatusLog::getSchema()->tableName($submittedOrderStatusLogClassName);
            $list = $list
                ->LeftJoin('OrderStatusLog', '"Order"."ID" = "OrderStatusLog"."OrderID"')
                ->LeftJoin($submittedOrderStatusLogTableName, '"OrderStatusLog"."ID" = "' . $submittedOrderStatusLogTableName . '"."ID"')
                ->where('"OrderStatusLog"."ClassName" = ' . Convert::raw2sql($submittedOrderStatusLogClassName, true));
        }
        $newLists = $this->extend('updateGetList', $list);
        if (is_array($newLists) && count($newLists)) {
            foreach ($newLists as $newList) {
                if ($newList instanceof DataList) {
                    $list = $newList;
                }
            }
        }

        return $list;
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        if (is_subclass_of($this->modelClass, Order::class) || $this->modelClass === Order::class) {
            if ($gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass))) {
                if ($gridField instanceof GridField) {
                    $config = $gridField->getConfig();
                    $exportButton = new GridFieldExportSalesButton('buttons-before-left');
                    $exportButton->setExportColumns($this->getExportFields());
                    $config->addComponent($exportButton);
                    $printAllInvoices = new GridFieldPrintAllInvoicesButton('buttons-before-left');
                    $config->addComponent($printAllInvoices);
                    $printAllPackingSlips = new GridFieldPrintAllPackingSlipsButton('buttons-before-left');
                    $config->addComponent($printAllPackingSlips);
                    //per row ...
                    $config->addComponent(new GridFieldPrintInvoiceButton());
                    // $config->addComponent(new GridFieldPrintPackingSlipButton());
                }
            }
        }
        return $form;
    }
}
