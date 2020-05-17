<?php

/**
 * shows you the link to remove the current cart.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks

 **/
class EcommerceTaskCartManipulation_Current extends BuildTask
{
    protected $title = 'Clear the current Cart';

    protected $description = '
        Removes the cart that is currently in memory (session) for the currrent user.
        It does not delete the order itself.';

    public function run($request)
    {
        DB::alteration_message('<br /><br /><br /><br /><br /><br /><a href="/shoppingcart/clear/" target="_debug">click here to clear the current cart from your session</a>.<br /><br /><br /><br /><br /><br />');
    }
}

/**
 * shows you the link to remove the current cart.
 *
 * @authors: Nicolaas
 * @package: ecommerce
 * @sub-package: tasks

 **/
class EcommerceTaskCartManipulation_Debug extends BuildTask
{
    protected $title = 'Show debug links';

    protected $description = 'Use a bunch of debug links to work with various objects such as the cart, the product group and the product page.';

    public function run($request)
    {
        $myProductGroup = DataObject::get_one('ProductGroup');
        $myProduct = DataObject::get_one('Product');
        $html = '
        Please use the links below:
        <ul>
            <li><a href="/shoppingcart/debug/" target="_debug">debug cart</a></li>
            <li><a href="/shoppingcart/ajaxtest/?ajax=1" target="_debug">view cart response</a></li>';
        if ($myProductGroup) {
            $html .= '
            <li><a href="' . $myProductGroup->Link('debug') . '" target="_debug">debug product group</a></li>';
        }
        if ($myProduct) {
            $html .= '
            <li><a href="' . $myProduct->Link('debug') . '" target="_debug">debug product</a></li>';
        }
        $html .= '
        </ul>';
        DB::alteration_message("${html}");
    }
}
