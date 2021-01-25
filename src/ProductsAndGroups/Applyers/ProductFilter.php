<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * provides data on the user
 */
class ProductFilter extends BaseApplyer
{

    /**
     *
     * @param  string             $segment expected format: my-product-category,123 (URLSegment, ID)
     * @return ProductGroup|null
     */
    public static function get_group_from_url_segment(string $segment) : ?ProductGroup
    {
        $segment = trim($segment, '/');
        if (is_string($filter) && strpos($filter, ',') !== false) {
            $parts = explode(',', $filter);
            if (count($parts) === 3) {
                $parts = [$part[1], $part[2]];
            }
            if (count($parts) === 2) {
                $groupId = intval($parts[1]);
                if ($groupId) {
                    return ProductGroup::get()->byId($groupId);
                }
            }
        }
        return null;
    }
    /**
     * make sure that these do not exist as a URLSegment
     * @var array
     */
    private static $options = [
        'default' => [
            'Title' => 'All Products (default)',
            'SQL' => [
                'ShowInSearch' => 1,
            ],
        ],
        'featuredonly' => [
            'Title' => 'Featured Only',
            'SQL' => [
                'ShowInSearch' => 1,
                'FeaturedProduct' => 1,
            ],
        ],
    ];

    /**
     * Filter the list of products
     *
     * @param array|string $filter
     *
     * @return SS_List
     */
    public function apply($filter = null): self
    {
        $group = $this->findGroupId($filter);
        if ($group) {
            $filter = ['ID' => $group->getFinalProductList()->column('ID')];
        } else {
            $filter = $this->checkOption($filter);
        }
        if (is_array($filter) && count($filter)) {
            $this->products = $this->products->filter($filter);
        } elseif ($filter) {
            $this->products = $this->products->where(Convert::raw2sql($filter));
        }

        return $this;
    }

    public function getTitle($param = null): string
    {
        $groupId = $this->findGroupId($param);
        $group = DataObject::get_one(
            ProductGroup::class,
            ['ID' => $groupId - 0]
        );
        if ($group) {
            return $group->MenuTitle;
        }
        return $this->checkOption($param, 'Title');
    }

    protected function findGroupId($filter): int
    {
        return $this->findGroup($filter) ?? 0;
    }

    protected function findGroup($filter): ?ProductGroup
    {
        return self::get_group_from_url_segment($filter);
    }
}
