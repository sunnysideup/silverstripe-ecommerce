<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DataList;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldExportSalesButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldPrintAllInvoicesButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldPrintAllPackingSlipsButton;
use Sunnysideup\Ecommerce\Forms\Gridfield\GridFieldPrintInvoiceButton;
use Sunnysideup\Ecommerce\Model\Address\BillingAddress;
use Sunnysideup\Ecommerce\Model\Address\ShippingAddress;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\OrderItem;
use Sunnysideup\Ecommerce\Model\OrderModifier;
use Sunnysideup\Ecommerce\Model\Process\OrderEmailRecord;
use Sunnysideup\Ecommerce\Model\Process\OrderProcessQueue;
use Sunnysideup\Ecommerce\Model\Process\OrderFeedback;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\Ecommerce\Traits\EcommerceModelAdminTrait;

/**
 * @description: CMS management for everything you have sold and all related data (e.g. logs, payments)
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 */
class SalesAdminProcess extends ModelAdmin
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
    private static $url_segment = 'sales-process';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_title = 'Sales Process';

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $managed_models = [
        OrderProcessQueue::class,
        OrderFeedback::class,
    ];

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
    private static $required_permission_codes = 'CMS_ACCESS_SalesAdminProcess';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_icon = 'vendor/sunnysideup/ecommerce/client/images/icons/money-file.gif';


    protected function init()
    {
        parent::init();
        Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomBuyableSelectField.js');
        Requirements::css('sunnysideup/ecommerce: client/css/OrderStepField.css');
    }
}
