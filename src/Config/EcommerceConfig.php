<?php

namespace Sunnysideup\Ecommerce\Config;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;




/**
 * This Class creates an array of configurations for e-commerce.
 * This class replaces static variables in individual classes, such as Blog::$allow_wysiwyg_editing.
 *
 * @see http://en.wikipedia.org/wiki/YAML#Examples
 * @see thirdparty/spyc/spyc.php
 *
 * # HOW TO USE IT
 *
 * 1. Copy ecommerce/_config/ecommerce.yml and move it your project folder, e.g. mysite/_config/ecommerce.yml
 * In the copied file, set your configs as you see fit, using the YAML format.  E.g.
 *
 * Order:
 *     Test: 1
 *
 * Next, include in ecommerce.yml file:
 * <code yml>
 * EcommerceConfig:
 *   folder_and_file_locations:
 *     - "mysite/_config/ecommerce.yml"
 *     - "myotherconfig.yaml"
 * </code>
 *
 * Then, in individual classes, you can access configs like this:
 *
 * <code>
 * EcommerceConfig::get("OrderAddress", "include_bla_bla_widget");
 * </code>
 *
 * OR
 *
 * <code>
 * EcommerceConfig::get($this->ClassName, "include_bla_bla_widget");
 * </code>
 *
 * if you are using PHP 5.3.0+ then you can write this in a static method
 *
 * <code>
 * EcommerceConfig::get("MyClassName", "include_bla_bla_widget");
 * </code>

 * Even though there is no direct connection, we keep linking statics to invidual classes.
 * We do this to (a) group configs (b) make it more interchangeable with other config systems.
 * One of the problems now is to know what "configs" are used by individual classes.
 * Therefore, it is important to clearly document that at the top of each class.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: configuration

 **/
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use Spyc;

/**
 * This Class creates an array of configurations for e-commerce.
 * This class replaces static variables in individual classes, such as Blog::$allow_wysiwyg_editing.
 *
 * @see http://en.wikipedia.org/wiki/YAML#Examples
 * @see thirdparty/spyc/spyc.php
 *
 * # HOW TO USE IT
 *
 * 1. Copy ecommerce/_config/ecommerce.yml and move it your project folder, e.g. mysite/_config/ecommerce.yml
 * In the copied file, set your configs as you see fit, using the YAML format.  E.g.
 *
 * Order:
 *     Test: 1
 *
 * Next, include in ecommerce.yml file:
 * <code yml>
 * EcommerceConfig:
 *   folder_and_file_locations:
 *     - "mysite/_config/ecommerce.yml"
 *     - "myotherconfig.yaml"
 * </code>
 *
 * Then, in individual classes, you can access configs like this:
 *
 * <code>
 * EcommerceConfig::get("OrderAddress", "include_bla_bla_widget");
 * </code>
 *
 * OR
 *
 * <code>
 * EcommerceConfig::get($this->ClassName, "include_bla_bla_widget");
 * </code>
 *
 * if you are using PHP 5.3.0+ then you can write this in a static method
 *
 * <code>
 * EcommerceConfig::get("MyClassName", "include_bla_bla_widget");
 * </code>
 * Even though there is no direct connection, we keep linking statics to invidual classes.
 * We do this to (a) group configs (b) make it more interchangeable with other config systems.
 * One of the problems now is to know what "configs" are used by individual classes.
 * Therefore, it is important to clearly document that at the top of each class.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: configuration
 **/
class EcommerceConfig
{
    use Extensible;
    use Injectable;
    use Configurable;

    /**
     * The location(s) of the .yaml fixture file, relative to the site base dir.
     *
     * @var array
     */
    private static $folder_and_file_locations = ['ecommerce/_config/ecommerce.yml', 'ecommerce/_config/payment.yml'];

    /**
     * Fetches value for a class, or a property on that class
     *
     * @param string $className Class name to retrieve config for
     * @param string $identifier Optional class property to get
     * @param int|true $excludeMiddleware Optional flag of middleware to disable.
     * Passing in `true` disables all middleware.
     * Can also pass in int flags to specify specific middlewares.
     *
     * @see Config::get()
     */
    public static function get($className, $identifier, $excludeMiddleware = 0)
    {
        return Config::inst()->get($className, $identifier, $excludeMiddleware);
    }
}
