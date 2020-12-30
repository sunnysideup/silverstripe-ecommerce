<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

/**
 * provides data on the user
 */
class ProductSorter extends BaseClass
{
    /**
     * @var array
     */
    private static $options = [
        'default' => [
            'Title' => 'Default Order',
            'SQL' => '"Sort" ASC, "Title" ASC',
        ],
        'price_low' => [
            'Title' => 'Lowest Price',
            'SQL' => '"Price" ASC, "Sort" ASC, "Title" ASC',
        ],
        'price_high' => [
            'Title' => 'Highest Price',
            'SQL' => '"Price" DESC, "Sort" ASC, "Title" ASC',
        ],
        'name' => [
            'Title' => 'Name',
            'SQL' => '"Title" ASC, "Sort" ASC',
        ],
    ];

    /**
     * Sort the list of products
     *
     * @param array|string $sort
     *
     * @return SS_List
     */
    public function apply($sort = null): SS_List
    {
        $sort = $this->checkOption($sort);
        if (is_array($sort) && count($sort)) {
            $this->products = $this->products->sort($sort);
        } elseif ($sort) {
            $this->products = $this->products->sort(Convert::raw2sql($sort));
        }
        // @todo

        return $this->products;
    }
}
