<?php

namespace Sunnysideup\Ecommerce\Email;
use Sunnysideup\Ecommerce\Email\OrderReceiptEmail;





/**
 * @Description: This class handles the receipt email which gets sent once an order is made.
 * You can call it by issuing sendReceipt() in the Order class.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms

 **/
class OrderReceiptEmail extends OrderEmail
{
    /**
     * @param string $ss_template The name of the used template (without *.ss extension)
     */
    protected $ss_template = OrderReceiptEmail::class;
}

