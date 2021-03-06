<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

/**
 * provides data on the user
 */
class ProductFilter extends BaseApplyer
{
    /**
     * make sure that these do not exist as a URLSegment
     * @var array
     */
    private static $options = [
        BaseApplyer::DEFAULT_NAME => [
            'Title' => 'All Products (default)',
            'SQL' => [
                'ShowInSearch' => 1,
            ],
            'RequiresData' => false,
            'IsShowFullList' => false,
        ],
        'featuredonly' => [
            'Title' => 'Featured Only',
            'SQL' => [
                'ShowInSearch' => 1,
                'FeaturedProduct' => 1,
            ],
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
        $filter = $this->getSql($key, $params);
        if (! empty($filter)) {
            $this->products = $this->products->filter($filter);
        }
        $this->applyEnd($key, $params);
        return $this;
    }
}
