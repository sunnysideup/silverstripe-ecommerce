<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DataList;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Api\EcommerceCache;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldArchiveOldOrdersButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldExportSalesButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldPrintAllInvoicesButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldPrintAllPackingSlipsButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldPrintInvoiceButton;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderProcessQueue;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;
use Sunnysideup\Ecommerce\Traits\EcommerceModelAdminTrait;
use Sunnysideup\ModelAdminManyTabs\Api\TabsBuilder;

/**
 * Class \Sunnysideup\Ecommerce\Cms\SalesAdmin
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

    protected static $_list_cache_orders;

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $url_segment = 'current-sales';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_title = 'Sales to Action';

    /**
     * @var int
     */
    private static $max_entries_for_processing = 500;

    /**
     * @var int
     */
    private static $cache_seconds = 30;

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $managed_models = [
        Order::class,
    ];

    /**
     * standard SS variable.
     *
     * @var float
     */
    private static $menu_priority = 3.12;

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

    /**
     * Forget the (short lived) list of orders so that, for example,
     * orders that have just been archived disappear straight away.
     */
    public static function reset_list_cache(): void
    {
        self::$_list_cache_orders = null;
        EcommerceCache::inst()->save(self::get_time_based_cache_key(), []);
    }

    protected static function get_time_based_cache_key(): string
    {
        $seconds = (int) Config::inst()->get(self::class, 'cache_seconds');

        return 'order_cache_' . round(time() / ($seconds + 1));
    }

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
            if (null === self::$_list_cache_orders) {
                $ids = EcommerceCache::inst()->retrieve($this->getTimeBasedCacheKey());
                if (! empty($ids) && count($ids)) {
                    self::$_list_cache_orders = Order::get()->filter(['ID' => $ids]);
                } else {
                    $queueObjectSingleton = Injector::inst()->get(OrderProcessQueue::class);
                    $ordersinQueue = $queueObjectSingleton->OrdersInQueueThatAreNotReady();

                    $list = $list
                        ->filter(['StatusID' => ArrayMethods::filter_array(OrderStep::admin_manageable_steps()->columnUnique())])
                        ->excludeAny(
                            [
                                'ID' => ArrayMethods::filter_array($ordersinQueue->columnUnique()),
                                'CancelledByID:GreaterThan' => 0,
                            ]
                        );

                    // $list = $list->sort(['OrderStatusLog.ID' => 'DESC']);
                    self::$_list_cache_orders = $list;
                    EcommerceCache::inst()->save($this->getTimeBasedCacheKey(), $list->columnUnique());
                }
            }
            $list = self::$_list_cache_orders;
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
        // If not supplied, look up the ID from the request
        $id = 0;
        if (null === $id && is_numeric($this->getRequest()->param('ID'))) {
            $id = (int) $this->getRequest()->param('ID');
        }
        $form = parent::getEditForm($id, $fields);
        if (is_subclass_of($this->modelClass, Order::class) || Order::class === $this->modelClass) {
            $gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass));
            if ($gridField && $gridField instanceof GridField) {
                $config = $gridField->getConfig();
                // export button
                $exportButton = new GridFieldExportSalesButton('buttons-before-left');
                $exportButton->setExportColumns($this->getExportFields());
                $config->addComponent($exportButton);
                //print invoices
                $printAllInvoices = new GridFieldPrintAllInvoicesButton('buttons-before-left');
                $config->addComponent($printAllInvoices);
                //print packing slip
                $printAllPackingSlips = new GridFieldPrintAllPackingSlipsButton('buttons-before-left');
                $config->addComponent($printAllPackingSlips);
                //per row ...
                $config->addComponent(new GridFieldPrintInvoiceButton());
                // $config->addComponent(new GridFieldPrintPackingSlipButton());
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

    protected function getTimeBasedCacheKey()
    {
        return self::get_time_based_cache_key();
    }

    protected function buildTabs(array $brackets, array $arrayOfTabs, $form)
    {
        foreach ($brackets as $key => $bracket) {
            if ($key) {
                $ids = $arrayOfTabs[$key]['IDs'] ?? [];

                $id = $this->getCurrentRecordId();
                //todo: find actual id.
                if ($id !== 0) {
                    $ids[$id] = $id;
                }
                if (count($ids) > 0) {
                    $arrayOfTabs[$key] = [
                        'TabName' => 'tab' . $key,
                        'Title' => $bracket,
                        'List' => Order::get()->filter(['ID' => $ids]),
                    ];
                } else {
                    unset($arrayOfTabs[$key]);
                }
            } else {
                unset($arrayOfTabs[$key]);
            }
        }
        TabsBuilder::add_many_tabs(
            $arrayOfTabs,
            $form,
            $this->modelClass
        );
    }

    /**
     * Adds a button to the tab that archives (without processing) all the orders
     * in that tab that were placed before the cut-off date.
     *
     * @param string $tabKey     the key used in buildTabs()
     * @param string $cutoffDate Y-m-d
     */
    protected function addArchiveOldOrdersButton(Form $form, string $tabKey, string $cutoffDate): void
    {
        $name = 'tab' . $tabKey;
        // walk the fields (rather than dataFieldByName) as that one uses a cache
        // that may have been built before the tabs were added.
        $form->Fields()->recursiveWalk(function ($field) use ($name, $cutoffDate) {
            if ($field instanceof GridField && $field->getName() === $name) {
                $field->getConfig()->addComponent(new GridFieldArchiveOldOrdersButton($cutoffDate));
            }
        });
    }

    protected function getCurrentRecordId(): int
    {
        $remaining = $this->getRequest()->remaining();
        $items = explode('/', $remaining);
        foreach ($items as $key => $item) {
            if ('item' === $item) {
                return (int) ($items[$key + 1] ?? 0);
            }
        }

        return 0;
    }
}
