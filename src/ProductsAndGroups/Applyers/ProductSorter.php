<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

use SilverStripe\Core\Convert;

/**
 * provides data on the user
 */
class ProductSorter extends BaseApplyer
{
    /**
     * @var array
     */
    private static $options = [
        'default' => [
            'Title' => 'Default Order',
            'SQL' => '"Sort" ASC, "Title" ASC',
            'RequiresData' => false,
            'IsShowFullList' => false,
        ],
        'price_low' => [
            'Title' => 'Lowest Price',
            'SQL' => '"Price" ASC, "Sort" ASC, "Title" ASC',
            'RequiresData' => false,
            'IsShowFullList' => false,
        ],
        'price_high' => [
            'Title' => 'Highest Price',
            'SQL' => '"Price" DESC, "Sort" ASC, "Title" ASC',
            'RequiresData' => false,
            'IsShowFullList' => false,
        ],
        'name' => [
            'Title' => 'Name',
            'SQL' => '"Title" ASC, "Sort" ASC',
            'RequiresData' => false,
            'IsShowFullList' => false,
        ],
    ];

    /**
     * @param string         $key     optional key
     * @param string|array   $params  optional params to go with key
     *
     * @return self
     */
    public function apply($key = null, $params = null): self
    {
        $this->applyStart($key, $params);

        $sort = $this->getSql($key, $params);
        if (is_array($sort) && count($sort)) {
            $this->products = $this->products->sort($sort);
        } elseif ($sort) {
            $this->products = $this->products->sort(Convert::raw2sql($sort));
        }
        // @todo

        return $this;
    }
}
