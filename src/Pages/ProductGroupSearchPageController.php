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

    protected function init()
    {
        parent::init();

        $this->isSearchResults = true;
    }
}
