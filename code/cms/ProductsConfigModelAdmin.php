<?php


/**
 * @description: Manages stuff related to products,
 * but not the product (groups) themselves
 *
 * Main example is product variations
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: cms
 **/
class ProductConfigModelAdmin extends ModelAdminEcommerceBaseClass
{
    private static $menu_priority = 3.19;

    private static $url_segment = 'product-config';

    private static $menu_title = 'Product Details';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_icon = 'ecommerce/images/icons/product-file.gif';
}
