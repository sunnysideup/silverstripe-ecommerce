<?php

use SilverStripe\Admin\CMSMenu;
use Sunnysideup\Ecommerce\Cms\CMSPageAddControllerProducts;

// @author: Nicolaas - modules [at] sunnysideup.co.nz

// optional settings that may be useful
//setlocale (LC_TIME, 'en_NZ@dollar', 'en_NZ.UTF-8', 'en_NZ', 'nz', 'nz');
//date_default_timezone_set("NZ");

// CACHING RECOMMENDATION - you can overrule that in the mysite _config.php file...
//one week = 604800 (60 * 60 * 24 * 7)
//last param is priority

// CMSMenu::add_menu_item('refresh', 'Refresh Website', 'shoppingcart/clear/?flush=all', $controllerClass = null, $priority = 2.9, array('target' => '_blank'));
if (isset($_SERVER['REQUEST_URI']) && 0 === strpos($_SERVER['REQUEST_URI'], '/admin/')) {
    CMSMenu::remove_menu_class(CMSPageAddControllerProducts::class);
}
