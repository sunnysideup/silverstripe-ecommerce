<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DataList;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldExportSalesButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldPrintAllInvoicesButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldPrintAllPackingSlipsButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldPrintInvoiceButton;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderFeedback;
use Sunnysideup\Ecommerce\Model\Process\OrderProcessQueue;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * @description: CMS management for everything you have sold and all related data (e.g. logs, payments)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 */
class SalesAdmin extends ModelAdmin
{
    use EcommerceModelAdminTrait;

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
    private static $url_segment = 'sales';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_title = 'Sales to Action';

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $managed_models = [
        Order::class,
        OrderProcessQueue::class,
        OrderFeedback::class,
    ];

    /**
     * standard SS variable.
     *
     * @var float
     */
    private static $menu_priority = 3.1;

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $required_permission_codes = 'CMS_ACCESS_SalesAdmin';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_icon = 'vendor/sunnysideup/ecommerce/client/images/icons/money-file.gif';

    public function urlSegmenter()
    {
        return $this->config()->get('url_segment');
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
     * @return \SilverStripe\ORM\DataList
     */
    public function getList()
    {
        $list = parent::getList();
        if (is_subclass_of($this->modelClass, Order::class) || Order::class === $this->modelClass) {
            $queueObjectSingleton = Injector::inst()->get(OrderProcessQueue::class);
            $ordersinQueue = $queueObjectSingleton->OrdersInQueueThatAreNotReady();
            $list = $list
                ->filter(
                    [
                        'CancelledByID' => 0,
                        'StatusID:GreaterThan' => 0,
                    ]
                )
            ;
            $ids = $ordersinQueue->columnUnique();
            if (! empty($ids)) {
                $list = $list->exclude(
                    [
                        'ID' => $ids,
                    ]
                );
            }
            //you can only do one exclude at the same time.
            $list = $list
                ->exclude(
                    [
                        'StatusID' => ArrayMethods::filter_array(OrderStep::non_admin_manageable_steps()->columnUnique()),
                    ]
                )
            ;
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
        if (is_subclass_of($this->modelClass, Order::class) || Order::class === $this->modelClass) {
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

    protected function init()
    {
        parent::init();
        Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomBuyableSelectField.js');
        Requirements::themedCSS('client/css/OrderStepField');
        Requirements::themedCSS('client/css/OrderReport'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
        Requirements::themedCSS('client/css/Order_Invoice', 'print'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
        Requirements::themedCSS('client/css/Order_PackingSlip', 'print'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE

        //Requirements::javascript("ecommerce/javascript/EcomModelAdminExtensions.js"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
    }
}
