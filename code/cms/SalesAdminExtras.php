<?php


/**
 * @description: CMS management for everything you have sold and all related data (e.g. logs, payments)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class SalesAdminExtras extends ModelAdminEcommerceBaseClass
{
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
    private static $menu_title = 'All Sales';

    /**
     * standard SS variable.
     *
     * @var int
     */
    private static $menu_priority = 3.11;

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
    private static $menu_icon = 'ecommerce/images/icons/money-file.gif';

    public function init()
    {
        parent::init();
        Requirements::javascript('ecommerce/javascript/EcomBuyableSelectField.js');
    }

    /**
     * @return DataList
     */
    public function getList()
    {
        $list = parent::getList();
        if (singleton($this->modelClass) instanceof Order) {
            $submittedOrderStatusLogClassName = EcommerceConfig::get('OrderStatusLog', 'order_status_log_class_used_for_submitting_order');
            $list = $list
                ->LeftJoin('OrderStatusLog', '"Order"."ID" = "OrderStatusLog"."OrderID"')
                ->LeftJoin($submittedOrderStatusLogClassName, '"OrderStatusLog"."ID" = "'.$submittedOrderStatusLogClassName.'"."ID"')
                ->where('"OrderStatusLog"."ClassName" = \''.$submittedOrderStatusLogClassName.'\'');
        }
        $newLists = $this->extend('updateGetList', $list);
        if(is_array($newLists) && count($newLists)) {
            foreach($newLists as $newList) {
                if($newList instanceof DataList) {
                    $list = $newList;
                }
            }
        }

        return $list;
    }


    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        if (singleton($this->modelClass) instanceof Order) {
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
