<?php

namespace Sunnysideup\Ecommerce\Pages;

use SilverStripe\ORM\DataList;

class ProductGroupSearchPageController extends ProductGroupController
{
    private static $allowed_actions = [
        'debug' => 'ADMIN',
        'filterforgroup' => true,
        'ProductSearchForm' => true,
        'searchresults' => true,
        'resetfilter' => true,
        'resetsort' => true,
    ];

    /**
     * Returns child product groups for use in 'in this section'. For example
     * the vegetable Product Group may have listed here: Carrot, Cabbage, etc...
     */
    public function MenuChildGroups(): ?DataList
    {
        return null;
    }

    protected function init()
    {
        parent::init();

        $this->isSearchResults = true;
    }
}
