<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Builders;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataList;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * A wrapper for a list of {@link Sunnysideup\Ecommerce\Pages\ProductGroup}
 * instances.
 *
 * Provides short cuts for ways to retrieve the nested structure or related
 * groups
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @subpackage: Pages
 */
class RelatedProductGroups
{
    use Injectable;
    use Configurable;

    /**
     * list that a Product Page can choose from ...
     *
     * @var array
     */
    protected const SHOW_PRODUCT_LEVELS = [
        99 => 'All Child Products (default)',
        -2 => 'None at all',
        -1 => 'All products',
        0 => 'Direct Child Products (exclude otherwise linked)',
        1 => 'Direct Child Products',
        2 => 'Upto Two Levels Down Products',
        3 => 'Upto Three Levels Down Products',
        4 => 'Upto Four Levels Down Products',
        5 => 'Upto Five Levels Down Products',
    ];

    /**
     * @var null|DataList
     */
    protected $groups;

    /**
     * How deep to go
     * special cases:
     *         -2 => 'None',
     *         -1 => 'All products',.
     *
     * @var int
     */
    protected $levelsToShow = 0;

    /**
     * @var ProductGroup
     */
    protected $rootGroup;

    /**
     * @var bool
     */
    protected $includeRoot = true;

    /**
     * default filter.
     *
     * @var array
     */
    private static $default_product_group_filter = ['ShowInSearch' => 1];

    /**
     * @param ProductGroup $productGroup
     */
    public function __construct($productGroup, ?int $levels = 99)
    {
        $this->setRootGroup($productGroup);
        $this->setLevelOfProductsToShow($levels);
    }

    /**
     * @param DataList $list
     *
     * @return DataList
     */
    public static function apply_default_filter_to_groups($list)
    {
        ClassHelpers::check_for_instance_of($list, DataList::class, true);
        $filter = Config::inst()->get(self::class, 'default_product_group_filter');

        return $list->filter($filter);
    }

    public function getShowProductLevelsArray(): array
    {
        return self::SHOW_PRODUCT_LEVELS;
    }

    /**
     * what is the the product group we are working with?
     */
    public function setRootGroup(ProductGroup $group): self
    {
        $this->rootGroup = $group;
        $this->groups = null;

        return $this;
    }

    /**
     * how many levels deep do we go?
     */
    public function setLevelOfProductsToShow(int $levels): self
    {
        $this->levelsToShow = $levels;
        $this->groups = null;

        return $this;
    }

    /**
     * do we include the root?
     */
    public function setIncludeRoot(bool $includeRoot): self
    {
        $this->includeRoot = $includeRoot;
        $this->groups = null;

        return $this;
    }

    public function getLevelOfProductsToShow(): int
    {
        return $this->levelsToShow;
    }

    public function getParentGroupIds(): array
    {
        return $this->getGroups()->columnUnique();
    }

    /**
     * @param null|mixed $filter
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function getGroups(?int $maxRecursiveLevel = 0, $filter = null)
    {
        if (! $maxRecursiveLevel) {
            $maxRecursiveLevel = $this->levelsToShow;
        }
        if (empty($this->groups) || ! empty($filter)) {
            if (-2 === $maxRecursiveLevel) {
                // NONE !
                $this->groups = ProductGroup::get()->filter(['ID' => -1]);
            // ALL !
            } elseif (-1 === $maxRecursiveLevel) {
                $this->groups = ProductGroup::get();
            } elseif ($this->rootGroup) {
                $ids = $this->getGroupsRecursive(0, $this->rootGroup->ID);
                if ($this->includeRoot) {
                    $ids[] = $this->rootGroup->ID;
                }
                $ids = ArrayMethods::filter_array($ids);
                $this->groups = ProductGroup::get()->filter(['ID' => $ids]);
            } else {
                $this->groups = ProductGroup::get();
            }
            if ($filter) {
                $this->groups = is_array($filter) ? $this->groups->filter($filter) : $this->groups->where($filter);
            }
            self::apply_default_filter_to_groups($this->groups);
        }

        return $this->groups;
    }

    /**
     * Returns all the Group IDs under a given root group to a max depth.
     *
     * @param int[] $ids
     */
    protected function getGroupsRecursive(int $currentDepth, int $groupId, $ids = []): array
    {
        if ($currentDepth > $this->levelsToShow) {
            return $ids;
        }

        $children = ProductGroup::get()->filter(['ParentID' => $groupId])->columnUnique();

        if ($children) {
            $ids = array_merge($ids, $children);
        }

        foreach ($children as $id) {
            $grandchildren = $this->getGroupsRecursive($currentDepth + 1, $id);

            if ($grandchildren !== []) {
                $ids = array_merge($ids, $grandchildren);
            }
        }

        return $ids;
    }
}
