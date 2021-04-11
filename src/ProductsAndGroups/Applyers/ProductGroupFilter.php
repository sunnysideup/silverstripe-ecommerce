<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * provides data on the user.
 */
class ProductGroupFilter extends BaseApplyer
{
    protected static $get_group_from_url_segment_store = [];

    /**
     * make sure that these do not exist as a URLSegment.
     *
     * @var array
     */
    private static $options = [
        BaseApplyer::DEFAULT_NAME => [
            'Title' => 'Filtered for Category',
            'SQL' => [
                'ShowInSearch' => 1,
            ],
            'RequiresData' => true,
            'IsShowFullList' => false,
        ],
    ];

    /**
     * @param string $segment expected format: my-product-category,123 (URLSegment, ID)
     */
    public static function get_group_from_url_segment(?string $segment): ?ProductGroup
    {
        if (! $segment) {
            return null;
        }
        if (! isset(self::$get_group_from_url_segment_store[$segment])) {
            self::$get_group_from_url_segment_store[$segment] = null;
            $segment = trim($segment, '/');
            if (is_string($segment) && false !== strpos($segment, ',')) {
                $parts = explode(',', $segment);
                if (3 === count($parts)) {
                    $parts = [$parts[1], $parts[2]];
                }
                if (2 === count($parts)) {
                    $groupId = (int) $parts[1];
                    if ($groupId) {
                        self::$get_group_from_url_segment_store[$segment] = ProductGroup::get()->byId($groupId);
                    }
                }
            }
        }

        return self::$get_group_from_url_segment_store[$segment];
    }

    /**
     * @param string       $key    optional key
     * @param array|string $params optional params to go with key
     */
    public function apply(?string $key = null, $params = null): self
    {
        $this->applyStart($key, $params);
        $group = $params instanceof ProductGroup ? $params : $this->findGroup($params);

        $filter = null;
        if ($group && $group->exists()) {
            $newIDs = array_intersect(
                $group->getBaseProductList()->getProductIds(),
                $this->products->columnUnique()
            );
            $filter = ['ID' => $newIDs];
        }

        if ($filter) {
            if (! empty($filter)) {
                $this->products = $this->products->filter($filter);
            }
        }
        $this->applyEnd($key, $params);

        return $this;
    }

    public function getTitle(?string $key = '', $params = null): string
    {
        $groupId = $this->findGroupId($params);
        $group = ProductGroup::get()->byID((int) $groupId - 0);
        if ($group) {
            return $group->MenuTitle;
        }

        return '';
    }

    protected function findGroupId(?string $filter): int
    {
        $group = $this->findGroup($filter);

        return  $group && $group->exits() ? $group->ID : 0;
    }

    /**
     * @param string $filter
     *
     * @return ProductGroup
     */
    protected function findGroup(?string $filter)
    {
        return self::get_group_from_url_segment($filter);
    }
}
