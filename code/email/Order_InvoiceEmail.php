<?php

/**
 * @Description: This class handles the invoice email which gets sent once an order is made.
 * You can call it like this: $Order->sendInvoice();
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class Order_InvoiceEmail extends Order_Email {

	/**
	 * @param string $ss_template The name of the used template (without *.ss extension)
	 */
	protected $ss_template = "Order_InvoiceEmail";

}

