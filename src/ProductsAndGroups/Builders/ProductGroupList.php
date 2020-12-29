<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Helpers;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Config\Configurable;
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

    /**
     * @var array
     *            List of options to show products.
     *            With it, we provide a bunch of methods to access and edit the options.
     *            NOTE: we can not have an option that has a zero key ( 0 => "none"), as this does not work
     *            (as it is equal to not completed yet - not yet entered in the Database).
     */
    protected $showProductLevels = [
        99 => 'All Child Products (default)',
        -2 => 'None',
        -1 => 'All products',
        1 => 'Direct Child Products',
        2 => 'Two Levels Down Products',
        3 => 'Three Levels Down Products',
        4 => 'Four Levels Down Product',
    ];

    /**
     * @var SS_List
     */
    protected $groups;

    /**
     * How deep to go
     * special cases:
     *         -2 => 'None',
     *         -1 => 'All products',
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
     * @param ProductGroup $productGroup
     */
    public function __construct($productGroup, ?int $levels = 99)
    {
        $this->setRootGroup($productGroup);
        $this->setLevelOfProductsToShow($productGroup);
    }

    /**
     * what is the the product group we are working with?
     *
     * @param ProductGroup $group
     */
    public function setRootGroup(ProductGroup $group): ProductGroupList
    {
        $this->rootGroup = $group;
        $this->groups = [];

        return $this;
    }

    /**
     * how many levels deep do we go?
     * @param int $levels
     *
     * @return self
     */
    public function setLevelOfProductsToShow(int $levels): ProductGroupList
    {
        $this->levelsToShow = $levels;
        $this->groups = [];

        return $this;
    }

    /**
     * do we include the root?
     *
     * @param bool $includeRoot
     *
     * @return self
     */
    public function setIncludeRoot(bool $includeRoot): ProductGroupList
    {
        $this->includeRoot = $includeRoot;
        $this->groups = [];

        return $this;
    }

    public function getLevelOfProductsToShow() : int
    {
        return $this->levelsToShow;
    }

    /**
     * @return \SilverStripe\ORM\DataList
     */
    public function getGroups(?int $maxRecursiveLevel = 0, $filter = null)
    {
        if(! $maxRecursiveLevel) {
            $maxRecursiveLevel = $this->levelsToShow;
        }
        if (empty($this->groups) || ! empty($filter)) {
            if ($this->levelsToShow === -2) {
                $this->groups = ProductGroup::get();
            } elseif ($this->levelsToShow === -1) {
                $this->groups = ProductGroup::get()->filter(['ID' => -1]);
            } elseif ($this->rootGroup) {
                $ids = $this->getGroupsRecursive(0, $this->rootGroup->ID, []);

                if ($this->includeRoot) {
                    $ids[$this->rootGroup->ID] = $this->rootGroup->ID;
                }

                if (count($ids) === 0) {
                    $ids = [-1 => -1,];
                }
                $this->groups = ProductGroup::get()->filter(['ID' => $ids,]);
            } else {
                $this->groups = ProductGroup::get();
            }
            if($filter) {
                if(is_array($filter)) {
                    $this->groups = $this->groups->filter($filter);
                } else {
                    $this->groups = $this->groups->where($filter);
                }
            }
        }

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
    protected function getGroupsRecursive(int $currentDepth, int $groupId, $ids = []): array
    {
        if ($currentDepth > $this->levelsToShow) {
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
