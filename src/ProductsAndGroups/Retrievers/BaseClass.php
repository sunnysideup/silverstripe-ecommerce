<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Retrievers;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

use Sunnysideup\Ecommerce\Config\EcommerceConfig;

use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * provides data on the user
 */
class BaseClass
{
    use Injectable;

    /**
     *
     * @var SS_List
     */
    protected $productList = null;

    /**
     *
     * @var SS_List
     */
    protected $filteredSortedProducts = null;

    public function __construct($productList)
    {
        $this->productList = $productList;
        $this->filteredSortedProducts = $productList->getProducts();
    }

}
