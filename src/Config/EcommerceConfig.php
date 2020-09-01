<?php

namespace Sunnysideup\Ecommerce\Config;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;

/**
 * Proxy for `Config::inst()->get()`
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: configuration
 **/
class EcommerceConfig
{
    use Configurable;

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
