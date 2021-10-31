<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

/**
 * provides data on the user.
 */
class ProductDisplayer extends BaseApplyer
{
    /**
     * make sure that these do not exist as a URLSegment.
     *
     * @var array
     */
    private static $options = [
        BaseApplyer::DEFAULT_NAME => [
            'Title' => 'Paginated',
            'SQL' => '',
            'UsesParamData' => false,
            'IsShowFullList' => false,
        ],
        'all' => [
            'Title' => 'Full List',
            'SQL' => '',
            'UsesParamData' => false,
            'IsShowFullList' => true,
        ],
    ];

    /**
     * @param string       $key    optional key
     * @param array|string $params optional params to go with key
     */
    public function apply(?string $key = null, $params = null): self
    {
        if(! $this->applyStart($key, $params)) {
            $this->applyEnd($key, $params);
        }

        return $this;
    }
}
