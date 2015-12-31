<?php

/**
 * CartWidget displays the current contents of the user's cart.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: widgets
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class CartWidget extends Widget
{

    private static $title = "Shopping Cart";

    private static $cmsTitle = "Shopping Cart";

    private static $description = "Displays the current contents of the user's cart.";

    public function Cart()
    {
        return ShoppingCart::current_order();
    }
}
