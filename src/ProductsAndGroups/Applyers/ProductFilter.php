<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

use Sunnysideup\Ecommerce\Pages\ProductGroup;

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
        'default' => [
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
     * @param  string             $segment expected format: my-product-category,123 (URLSegment, ID)
     * @return ProductGroup|null
     */
    public static function get_group_from_url_segment(string $segment): ?ProductGroup
    {
        $segment = trim($segment, '/');
        if (is_string($segment) && strpos($segment, ',') !== false) {
            $parts = explode(',', $segment);
            if (count($parts) === 3) {
                $parts = [$parts[1], $parts[2]];
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
     * @param string         $key     optional key
     * @param string|array   $params  optional params to go with key
     *
     * @return self
     */
    public function apply($key = null, $params = null): self
    {
        $this->selectedOption = $key;
        $this->selectedOptionParams = $params;

        $group = $this->findGroupId(${$key});
        if ($group) {
            $filter = ['ID' => $group->getFinalProductList()->column('ID')];
        } else {
            $filter = $this->getSql($key, $params);
        }
        if (is_array($filter) && count($filter)) {
            $this->products = $this->products->filter($filter);
        } elseif ($filter) {
            $this->products = $this->products->where($filter);
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

    protected function findGroupId(string $filter): int
    {
        return $this->findGroup($filter) ?? 0;
    }

    protected function findGroup(string $filter): ?ProductGroup
    {
        return self::get_group_from_url_segment($filter);
    }
}
