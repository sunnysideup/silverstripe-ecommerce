<?php

namespace Sunnysideup\Ecommerce\Cms\Dev;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;

/**
 * EcommerceDevelopmentAdminDecorator adds extra functionality to the DevelopmentAdmin
 * It creates a developer view (as in www.mysite.com/dev/) specifically for ecommerce.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms

 **/
class EcommerceDevelopmentAdminDecorator extends Extension
{
    private static $allowed_actions = [
        'ecommerce',
    ];

    /**
     * handles ecommerce request or provide options to run request in the form of HTML output.
     *
     * @param HTTPRequest $request
     *
     * @return EcommerceDatabaseAdmin
     **/
    public function ecommerce(HTTPRequest $request)
    {
        return EcommerceDatabaseAdmin::create();
    }
}
