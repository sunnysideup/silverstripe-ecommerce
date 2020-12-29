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
class ProductSorter extends BaseClass
{
    /**
     * Sort the list of products
     *
     * @param array|string $sort
     *
     * @return self
     */
    public function applySort($sort = null): ProductList
    {
        if (is_array($sort) && count($sort)) {
            $this->filteredSortedProducts = $this->filteredSortedProducts->sort($sort);
        } elseif ($sort) {
            $this->filteredSortedProducts = $this->filteredSortedProducts->sort(Convert::raw2sql($sort));
        }
        // @todo

        return $this;
    }

}
