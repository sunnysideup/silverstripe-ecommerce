<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * provides data on the user.
 */
class ProductGroupFilter extends BaseApplyer
{
    protected static $get_group_from_get_variable_store = [];

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
            'UsesParamData' => true,
            'IsShowFullList' => false,
        ],
    ];

    /**
     * @param null|string $getVar expected format: my-product-category.123 (URLSegment.ID)
     */
    public static function get_group_from_get_variable(?string $getVar = null): ?ProductGroup
    {
        if (! $getVar) {
            return null;
        }
        if (! isset(self::$get_group_from_get_variable_store[$getVar])) {
            self::$get_group_from_get_variable_store[$getVar] = null;
            if (false !== strpos($getVar, '.')) {
                $parts = explode('.', $getVar);
                $groupId = (int) $parts[1];
                if ($groupId !== 0) {
                    self::$get_group_from_get_variable_store[$getVar] = ProductGroup::get_by_id($groupId);
                }
            }
        }

        return self::$get_group_from_get_variable_store[$getVar];
    }

    /**
     * @param string       $key    optional key
     * @param array|string $params optional params to go with key
     */
    public function apply(?string $key = null, $params = null): self
    {
        if (! $this->applyStart($key, $params)) {
            $group = $params instanceof ProductGroup ? $params : $this->findGroup($params);

            $filter = null;
            if ($group && $group->exists()) {
                $newIDs = array_intersect(
                    $group->getBaseProductList()->getProductIds(),
                    $this->products->columnUnique()
                );
                $filter = ['ID' => ArrayMethods::filter_array($newIDs)];
            }

            if ($filter && $this->products->exists()) {
                $this->products = $this->products->filter($filter);
            }
            $this->applyEnd($key, $params);
        }

        return $this;
    }

    public function getTitle(?string $key = '', $params = null): string
    {
        $groupId = $this->findGroupId($params);
        $group = ProductGroup::get_by_id($groupId);
        if ($group) {
            return $group->MenuTitle;
        }

        return '';
    }

    protected function findGroupId(?string $filter): int
    {
        $group = $this->findGroup($filter);

        return $group && $group->exists() ? $group->ID : 0;
    }

    /**
     * @param null|array|string $filter
     *
     * @return ProductGroup
     */
    protected function findGroup($filter)
    {
        if (empty($filter)) {
            $filter = '';
        }
        if (is_array($filter)) {
            $filter = implode('.', $filter);
        }

        return self::get_group_from_get_variable($filter);
    }
}
