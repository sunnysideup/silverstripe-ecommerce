<?php

namespace Sunnysideup\Ecommerce\Email;

/**
 * @Description: This class handles the receipt email which gets sent once an order is made.
 * You can call it by issuing sendReceipt() in the Order class.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 */
class OrderReceiptEmail extends OrderEmail
{
    /**
     * @param string $ss_template The name of the used template (without *.ss extension)
     */
    protected $ss_template = OrderReceiptEmail::class;
}
