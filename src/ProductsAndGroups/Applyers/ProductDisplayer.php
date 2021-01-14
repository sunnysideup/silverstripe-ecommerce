<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

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
    public function apply($display = null) : self
    {
        return $this;
    }
}
