<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

/**
 * provides data on the user
 */
class ProductSorter extends BaseApplyer
{
    /**
     * @var array
     */
    private static $options = [
        BaseApplyer::DEFAULT_NAME => [
            'Title' => 'Default Order',
            'SQL' => '"Sort" ASC, "Title" ASC',
            'RequiresData' => false,
            'IsShowFullList' => false,
        ],
        'lowprice' => [
            'Title' => 'Lowest Price',
            'SQL' => '"Price" ASC, "Sort" ASC, "Title" ASC',
            'RequiresData' => false,
            'IsShowFullList' => false,
        ],
        'highprice' => [
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
            $this->products = $this->products->sort($sort);
        }
        // @todo
        $this->applyEnd($key, $params);
        return $this;
    }

    /**
     * if the key is default and you provide a param of IDs then it sort by params
     * @param  string         $key
     * @param  string|array   $params additional param for sql.
     *
     * @return string|array
     */
    public function getSql(?string $key = null, $params = null)
    {
        if ($key === BaseApplyer::DEFAULT_NAME && is_array($params)) {
            return ArrayMethods::create_sort_statement_from_id_array($params);
        }
        return parent::getSql($key, $params);
    }
}
