<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * provides data on the user.
 */
class ProductSorter extends BaseApplyer
{
    protected static $defaultSortOrderFromFilter = [];

    /**
     * @var array
     */
    private static $options = [
        BaseApplyer::DEFAULT_NAME => [
            'Title' => 'Default Order',
            'SQL' => '"Sort" ASC, "Title" ASC',
            'UsesParamData' => false,
            'IsShowFullList' => false,
        ],
        'new' => [
            'Title' => 'Latest Arrivals',
            'SQL' => '"Created" DESC, "Price" DESC',
            'UsesParamData' => false,
            'IsShowFullList' => false,
        ],
        'lowprice' => [
            'Title' => 'Lowest Price',
            'SQL' => '"Price" ASC, "Sort" ASC, "Title" ASC',
            'UsesParamData' => false,
            'IsShowFullList' => false,
        ],
        'highprice' => [
            'Title' => 'Highest Price',
            'SQL' => '"Price" DESC, "Sort" ASC, "Title" ASC',
            'UsesParamData' => false,
            'IsShowFullList' => false,
        ],
        'name' => [
            'Title' => 'Name',
            'SQL' => '"Title" ASC, "Sort" ASC',
            'UsesParamData' => false,
            'IsShowFullList' => false,
        ],
    ];

    public static function setDefaultSortOrderFromFilter(array $array)
    {
        self::$defaultSortOrderFromFilter = $array;
    }

    /**
     * @param string       $key    optional key
     * @param array|string $params optional params to go with key
     */
    public function apply(?string $key = null, $params = null): self
    {
        if(! $this->applyStart($key, $params)) {

            $sort = $this->getSql($key, $params);
            if (is_array($sort) && count($sort)) {
                $this->products = $this->products->sort($sort);
            } elseif ($sort) {
                $this->products = $this->products->sort($sort);
            }
            // @todo
            $this->applyEnd($key, $params);
        }

        return $this;
    }

    /**
     * if the key is default and you provide a param of IDs then it sort by params.
     *
     * @param string       $key
     * @param array|string $params additional param for sql
     *
     * @return array|string
     */
    public function getSql(?string $key = null, $params = null)
    {
        // if (BaseApplyer::DEFAULT_NAME === $key && self::$defaultSortOrderFromFilter) {
        //     return self::$defaultSortOrderFromFilter;
        // }
        // @todo: make smarter...
        if (is_array($params) && count($params)) {
            return ArrayMethods::create_sort_statement_from_id_array($params, Product::class);
        }

        return parent::getSql($key, $params);
    }

    /**
     * you can add an extra sort (or two), based on filters (or other stuff.).
     */
    public function getOptions(): array
    {
        return self::$defaultSortOrderFromFilter + parent::getOptions();
    }
}
