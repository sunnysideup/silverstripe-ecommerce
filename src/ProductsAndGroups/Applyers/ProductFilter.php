<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

/**
 * provides data on the user.
 */
class ProductFilter extends BaseApplyer
{
    /**
     * make sure that these do not exist as a URLSegment.
     *
     * @var array
     */
    private static $options = [
        BaseApplyer::DEFAULT_NAME => [
            'Title' => 'All Products (default)',
            'SQL' => [
                'ShowInSearch' => 1,
            ],
            'UsesParamData' => false,
            'IsShowFullList' => false,
        ],
        'featuredonly' => [
            'Title' => 'Featured Only',
            'SQL' => [
                'ShowInSearch' => 1,
                'FeaturedProduct' => 1,
            ],
            'UsesParamData' => false,
            'IsShowFullList' => false,
        ],
    ];

    public function apply(?string $key = null, $params = null)
    {
        $this->applyStart($key, $params);
        $filter = $this->getSql($key, $params);
        if (! empty($filter)) {
            if(is_string($filter)) {
                $this->products = $this->products->where($filter);
            } else {
                $this->products = $this->products->filter($filter);
            }
        }
        $this->applyEnd($key, $params);

        return $this;
    }
}
