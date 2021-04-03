<?php

namespace Sunnysideup\Ecommerce\Pages;

use SilverStripe\ORM\PaginatedList;

class ProductGroupSearchPageController extends ProductGroupController
{
    // private static $allowed_actions = [
    //     'debug' => 'ADMIN',
    //     'filterforgroup' => true,
    //     'ProductSearchForm' => true,
    //     'searchresults' => true,
    //     'resetfilter' => true,
    //     'resetsort' => true,
    // ];

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
}
