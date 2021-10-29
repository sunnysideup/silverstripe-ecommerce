<?php

namespace Sunnysideup\Ecommerce\Pages;

use SilverStripe\ORM\PaginatedList;

class ProductGroupSearchPageController extends ProductGroupController
{
    /**
     * Return the products for this group.
     *
     * This is the call that is made from the template and has the actual final
     * products being shown.
     *
     * @return \SilverStripe\ORM\PaginatedList
     */
    public function Products(): ?PaginatedList
    {
        if ($this->IsSearchResults()) {
            return parent::Products();
        }

        return null;
    }

    public function getSearchFilterHeader(): string
    {
        return _t('Ecommerce.SEARCH_ALL_PRODUCTS', 'Search all products');
    }
}
