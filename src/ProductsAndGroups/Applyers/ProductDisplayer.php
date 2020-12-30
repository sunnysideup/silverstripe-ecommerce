<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

use Sunnysideup\Ecommerce\Config\EcommerceConfig;

use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * provides data on the user
 */
class ProductDisplayer extends BaseClass
{

    /**
     * @var array
     */
    private static $options = [
        'default' => [
            'Title' => 'Default',
            'SQL' => '',
        ],
    ];


    /**
     * set display for products
     *
     * @param array|string $display
     *
     * @return SS_List
     */
    public function apply($display = null): SS_List
    {
        return $this->products;
    }

}
