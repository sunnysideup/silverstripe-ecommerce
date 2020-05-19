<?php

namespace Sunnysideup\Ecommerce\Tasks;




use Sunnysideup\Ecommerce\Pages\ProductGroup;
use SilverStripe\ORM\DataObject;
use Sunnysideup\Ecommerce\Pages\Product;
use SilverStripe\ORM\DB;
use SilverStripe\Dev\BuildTask;





/**
 * shows you the link to remove the current cart.
 *
 * @authors: Nicolaas
 * @package: ecommerce
 * @sub-package: tasks

 **/
class EcommerceTaskCartManipulationDebug extends BuildTask
{
    protected $title = 'Show debug links';

    protected $description = 'Use a bunch of debug links to work with various objects such as the cart, the product group and the product page.';

    public function run($request)
    {
        $myProductGroup = DataObject::get_one(ProductGroup::class);
        $myProduct = DataObject::get_one(Product::class);
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

