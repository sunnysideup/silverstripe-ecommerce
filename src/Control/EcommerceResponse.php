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
    /**
     * @param string[] $messages       the status to return
     * @param array    $additionalData the message to return with the retur
     * @param string   $status         that should be included
     */
    public function ReturnCartData(array $messages = [], array $additionalData = null, $status = 'success')
    {
        user_error('Make sure to extend the EcommerceResponse::ReturnCartData class for your own purposes.', E_USER_NOTICE);
    }
}
