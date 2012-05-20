<?php

/**
 * CartWidget displays the current contents of the user's cart.
 * @Authors Jeremy + Nicolaas
 */

class CurrencyWidget extends Widget{

	public static $title = "Select Currency";

	public static $cmsTitle = "Select Currency";

	public static $description = "Displays the current contents of the user's cart.";

	function Cart(){
		return ShoppingCart::current_order();
	}

}
