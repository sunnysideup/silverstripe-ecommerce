<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * provides data on the user
 */
class ProductGroupFilter extends BaseApplyer
{
    protected static $get_group_from_url_segment_store = [];

    /**
     * make sure that these do not exist as a URLSegment
     * @var array
     */
    private static $options = [
        'default' => [
            'Title' => 'Filtered for Category',
            'SQL' => [
                'ShowInSearch' => 1,
            ],
            'RequiresData' => true,
            'IsShowFullList' => false,
        ],
    ];

    /**
     * @param  string             $segment expected format: my-product-category,123 (URLSegment, ID)
     * @return ProductGroup|null
     */
    public static function get_group_from_url_segment(?string $segment): ?ProductGroup
    {
        if (! $segment) {
            return null;
        }
        if (! isset(self::$get_group_from_url_segment_store[$segment])) {
            self::$get_group_from_url_segment_store[$segment] = null;
            $segment = trim($segment, '/');
            if (is_string($segment) && strpos($segment, ',') !== false) {
                $parts = explode(',', $segment);
                if (count($parts) === 3) {
                    $parts = [$parts[1], $parts[2]];
                }
                if (count($parts) === 2) {
                    $groupId = intval($parts[1]);
                    if ($groupId) {
                        self::$get_group_from_url_segment_store[$segment] = ProductGroup::get()->byId($groupId);
                    }
                }
            }
        }
        return self::$get_group_from_url_segment_store[$segment];
    }

    /**
     * @param string         $key     optional key
     * @param string|array   $params  optional params to go with key
     *
     * @return self
     */
    public function apply(string $key = null, $params = null): self
    {
        $this->applyStart($key, $params);
        if ($params instanceof ProductGroup) {
            $group = $params;
        } else {
            $group = $this->findGroup($params);
        }

        $filter = null;
        if ($group && $group->exists()) {
            $filter = ['ID' => $group->getBaseProductList()->getProductIds()];
        }

        if ($filter) {
            if (is_array($filter) && count($filter)) {
                $this->products = $this->products->filter($filter);
            } elseif ($filter) {
                $this->products = $this->products->where($filter);
            }
        }
        $this->applyEnd($key, $params);

        return $this;
    }

    public function getTitle(?string $key = '', $params = null): string
    {
        $groupId = $this->findGroupId($params);
        $group = ProductGroup::get()->byID(intval($groupId) - 0);
        if ($group) {
            return $group->MenuTitle;
        }
        return '';
    }

    protected function findGroupId(?string $filter): int
    {
        return $this->findGroup($filter) ?? 0;
    }

    /**
     * @param  string            $filter
     * @return ProductGroup
     */
    protected function findGroup(?string $filter)
    {
        return self::get_group_from_url_segment($filter);
    }
}
