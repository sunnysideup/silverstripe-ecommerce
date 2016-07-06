<?php

/**
 * Allows the customer to select a currency.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: widgets
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class CurrencyWidget extends Widget
{
    private static $title = 'Select Currency';

    private static $cmsTitle = 'Select Currency';

    private static $description = "Displays the current contents of the user's cart.";

    public function Currencies()
    {
        return EcommerceCurrency::get_list();
    }
}
