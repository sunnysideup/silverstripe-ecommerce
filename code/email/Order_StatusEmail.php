<?php

/**
 * @Description: This class handles the status email which can be sent
 * after a status update has been made (if told to do so ;-))
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: email
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class Order_StatusEmail extends Order_Email {

	/**
	 * @param string $ss_template The name of the used template (without *.ss extension)
	 */
	protected $ss_template = "Order_StatusEmail";


}
