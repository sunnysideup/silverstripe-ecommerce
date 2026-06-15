<?php

namespace Sunnysideup\Ecommerce\Cms;

use SilverStripe\Admin\SingleRecordAdmin;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;

class EcommerceDBConfigAdmin extends SingleRecordAdmin
{
    private static string $url_segment = 'ecommerce-db-config';

    private static int $menu_priority = 999;

    private static string $menu_title = 'Ecom Basics';

    private static string $menu_icon_class = 'font-icon-cog';

    private static string $model_class = EcommerceDBConfig::class;

    private static array $required_permission_codes = [
        'EDIT_SITECONFIG',
    ];

    public function init()
    {
        parent::init();
        // Add JS required for some aspects of the access tab
        if (class_exists(SiteTree::class)) {
            Requirements::javascript('silverstripe/cms: client/dist/js/bundle.js');
        }
    }
}
