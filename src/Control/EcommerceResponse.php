<?php

namespace Sunnysideup\Ecommerce\Control;

use SilverStripe\Control\HTTPResponse;

/**
 * @description: This class is a base class for Ecommerce Responses such as Cart Response
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: control
 */
abstract class EcommerceResponse extends HTTPResponse
{
    abstract public function ReturnCartData(
        array $messages = [],
        ?array $additionalData = null,
        $status = 'success'
    ): string;
}
