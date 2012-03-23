<?php

/**
 * @Description: This class handles the status email which can be sent
 * after a status update has been made (if told to do so ;-))
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: email
 *
 **/

class Order_StatusEmail extends Order_Email {

	protected $ss_template = 'Order_StatusEmail';

}
