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
     * @param string         $key     optional key
     * @param string|array   $params  optional params to go with key
     *
     * @return self
     */
    public function apply($key = null, $params = null): self
    {
        $this->selectedOption = $key;
        $this->selectedOptionParams = $params;

        return $this;
    }
}
