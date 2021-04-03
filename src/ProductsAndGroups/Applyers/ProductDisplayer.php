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
            'RequiresData' => false,
            'IsShowFullList' => false,
        ],
        'all' => [
            'Title' => 'Full List',
            'SQL' => '',
            'RequiresData' => false,
            'IsShowFullList' => true,
        ],
    ];

    /**
     * @param string       $key    optional key
     * @param array|string $params optional params to go with key
     */
    public function apply($key = null, $params = null): self
    {
        $this->applyStart($key, $params);
        $this->applyEnd($key, $params);

        return $this;
    }
}
