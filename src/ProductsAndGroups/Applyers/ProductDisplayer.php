<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

/**
 * provides data on the user
 */
class ProductDisplayer extends BaseApplyer
{
    /**
     * make sure that these do not exist as a URLSegment
     * @var array
     */
    private static $options = [
        'default' => [
            'Title' => 'Paginated',
            'SQL' => '',
            'IsShowFullList' => false,
        ],
        'all' => [
            'Title' => 'Full List',
            'SQL' => '',
            'IsShowFullList' => true,
        ],
    ];

    /**
     * set display for products
     *
     * @param array|string $display
     *
     * @return SS_List
     */
    public function apply($display = null): self
    {
        return $this;
    }
}
