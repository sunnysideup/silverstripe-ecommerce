<?php

/**
 * Allows the customer to select a currency
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: widgets
 * @inspiration: Silverstripe Ltd, Jeremy
 **/


class CurrencyWidget extends Widget{

	public static $title = "Select Currency";

	public static $cmsTitle = "Select Currency";

	public static $description = "Displays the current contents of the user's cart.";

	function Currencies(){
		return EcommerceCurrency::get_list();
	}

}
