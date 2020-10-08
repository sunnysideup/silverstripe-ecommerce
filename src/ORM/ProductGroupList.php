<?php

namespace Sunnysideup\Ecommerce\ORM;

use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\PaginatedList;
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
class ProductGroupList
{
    use Injectable;
    use Extensible;

    /**
     * @var SS_List
     */
    protected $groups;

    /**
     * How deep to go
     *
     * @var int
     */
    protected $maxDepth = 99;

    /**
     * @var ProductGroup
     */
    protected $rootGroup;

    /**
     * @var bool
     */
    protected $includeRoot = true;

    /**
     * @param int $levels
     *
     * @return self
     */
    public function setMaxDepth(int $levels): ProductGroupList
    {
        $this->levelsToShow = $levels;
        $this->groups = [];

        return $this;
    }

    /**
     * @param bool $includeRoot
     *
     * @return self
     */
    public function setIncludeRoot(bool $includeRoot): ProductGroupList
    {
        $this->includeRoot = $includeRoot;

        return $this;
    }

    /**
     * @return SilverStripe\ORM\PaginatedList
     */
    public function getPaginatedList(): PaginatedList
    {
        return PaginatedList::create($this->getGroups());
    }

    /**
     * @param ProductGroup $group
     */
    public function setRootGroup(ProductGroup $group): ProductGroupList
    {
        $this->rootGroup = $group;
        $this->groups = [];

        return $this;
    }

    /**
     * @return \SilverStripe\ORM\DataList
     */
    public function getGroups()
    {
        if ($this->groups) {
            return $this->groups;
        }

        if ($this->rootGroup) {
            $ids = $this->getGroupsRecursive(0, $this->rootGroup->ID, []);

            if ($this->includeRoot) {
                $ids[] = $this->rootGroup->ID;
            }

            if ($ids) {
                $this->groups = ProductGroup::get()->filter([
                    'ID' => $ids,
                ]);
            }

            return $this->groups;
        }
        $this->groups = ProductGroup::get();

        return $this->groups;
    }

    /**
     * Returns all the Group IDs under a given root group to a max depth.
     *
     * @param int $currentDepth
     * @param int $groupId
     * @param int[] $ids
     *
     * @return array
     */
    public function getGroupsRecursive(int $currentDepth, int $groupId, $ids = []): array
    {
        if ($currentDepth > $this->maxDepth) {
            return $ids;
        }

        $children = DB::query('SELECT ID FROM ProductGroup WHERE ParentID = ' . $groupId)->column();

        if ($children) {
            $ids = array_merge($ids, $children);
        }

        foreach ($children as $id) {
            $grandchildren = $this->getGroupsRecursive($currentDepth + 1, $id);

            if ($grandchildren) {
                $ids = array_merge($ids, $grandchildren);
            }
        }

        return $ids;
    }
}
