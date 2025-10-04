<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Model\Process\OrderFeedback;
use Sunnysideup\Ecommerce\Model\Process\OrderProcessQueue;
use Sunnysideup\Ecommerce\Model\Process\Referral;
use Sunnysideup\Ecommerce\Traits\EcommerceModelAdminTrait;

/**
 * Class \Sunnysideup\Ecommerce\Cms\SalesAdminProcess
 *
 */
class SalesSources extends ModelAdmin
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
    private static $url_segment = 'sales-sources';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_title = 'Sales Sources';

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $managed_models = [
        Referral::class,
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
    private static $required_permission_codes = 'CMS_ACCESS_SalesSources';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_icon = 'vendor/sunnysideup/ecommerce/client/images/icons/money-file.gif';

    protected function init()
    {
        parent::init();
    }
}
