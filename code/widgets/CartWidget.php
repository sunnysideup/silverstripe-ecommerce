<?php

/**
 * CartWidget displays the current contents of the user's cart.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: widgets
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class CartWidget extends Widget{

	public static $title = "Shopping Cart";

	public static $cmsTitle = "Shopping Cart";

	public static $description = "Displays the current contents of the user's cart.";

	function Cart(){
		return ShoppingCart::current_order();
	}

}
