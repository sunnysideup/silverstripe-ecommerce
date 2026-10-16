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
        if (! array_key_exists($getVar, self::$get_group_from_get_variable_store)) {
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
     * @param null|string $getVar expected format: my-product-category.123 (URLSegment.ID) OR
     *                            a comma separated list of such tokens, e.g.
     *                            my-product-category.123,other-category.456
     *
     * @return ProductGroup[] keyed by group ID
     */
    public static function get_groups_from_get_variable(?string $getVar = null): array
    {
        if (! $getVar) {
            return [];
        }

        $groups = [];
        foreach (explode(',', $getVar) as $onePart) {
            $onePart = trim($onePart);
            if ('' === $onePart) {
                continue;
            }

            $group = self::get_group_from_get_variable($onePart);
            if ($group instanceof ProductGroup) {
                $groups[$group->ID] = $group;
            }
        }

        return $groups;
    }

    /**
     * @param string       $key    optional key
     * @param array|string $params optional params to go with key - may be a single
     *                             group token, or a comma separated list of group tokens,
     *                             to filter by more than one group at once (OR logic)
     */
    public function apply(?string $key = null, $params = null): self
    {
        if (! $this->applyStart($key, $params)) {
            $groups = $params instanceof ProductGroup ? [$params->ID => $params] : $this->findGroups($params);

            $filter = null;
            $allIds = [];
            foreach ($groups as $group) {
                if ($group && $group->exists()) {
                    $allIds = array_merge($allIds, $group->getBaseProductList()->getProductIds());
                }
            }

            if (! empty($allIds)) {
                $allIds = array_unique($allIds);
                $newIDs = array_intersect($allIds, $this->products->columnUnique());
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
        $groups = $this->findGroups($params);
        if (empty($groups)) {
            return '';
        }

        return implode(
            ', ',
            array_map(
                static fn (ProductGroup $group): string => $group->MenuTitle,
                $groups
            )
        );
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

    /**
     * @param null|array|string $filter one group token, a comma separated list of
     *                                  group tokens, or an array of [URLSegment, ID]
     *
     * @return ProductGroup[] keyed by group ID
     */
    protected function findGroups($filter): array
    {
        if (empty($filter)) {
            return [];
        }
        if (is_array($filter)) {
            $filter = implode('.', $filter);
        }

        return self::get_groups_from_get_variable((string) $filter);
    }
}
