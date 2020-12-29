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
class ProductFilter extends BaseClass
{
    /**
     * Filter the list of products
     *
     * @param array|string $filter
     *
     * @return self
     */
    public function applyFilter($filter = null): ProductList
    {
        if (is_array($filter) && count($filter)) {
            $this->filteredSortedProducts = $this->filteredSortedProducts->filter($filter);
        } elseif ($filter) {
            $this->filteredSortedProducts = $this->filteredSortedProducts->where(Convert::raw2sql($filter));
        }

        return $this;
    }
}
