<?php

/**
 * shows you the link to remove the current cart
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class CartManipulation_Current extends BuildTask{

	protected $title = "Clear the current Cart";

	protected $description = "
		Removes the cart that is currently in memory (session) for the currrent user.
		It does not delete the order itself.";

	function run($request){
		DB::alteration_message("<br /><br /><br /><br /><br /><br /><a href=\"/shoppingcart/clear/\" target=\"_debug\">click here to clear the current cart from your session</a>.<br /><br /><br /><br /><br /><br />");
	}

}


/**
 * shows you the link to remove the current cart
 *
 * @authors: Nicolaas
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class CartManipulation_Debug extends BuildTask{

	protected $title = "Show the values of the current cart";

	protected $description = "Iterates through all the values related to the current cart and displays them.";

	function run($request){
		DB::alteration_message("<br /><br /><br /><br /><br /><br /><a href=\"/shoppingcart/debug/\" target=\"_debug\">click here to view the debug values in a new window</a>.<br /><br /><br /><br /><br /><br />");
	}

}
