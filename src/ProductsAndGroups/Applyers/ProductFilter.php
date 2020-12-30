<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

use Sunnysideup\Ecommerce\Config\EcommerceConfig;

use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * provides data on the user
 */
class ProductFilter extends BaseClass
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
    public function apply($filter = null): SS_List
    {
        $group = $this->findGroupId($filter);
        if($group ) {
            $filter = ['ID' => $group->ProductsShowable()];
        } else {
            $filter = $this->checkOption($filter);
        }
        if (is_array($filter) && count($filter)) {
            $this->products = $this->products->filter($filter);
        } elseif ($filter) {
            $this->products = $this->products->where(Convert::raw2sql($filter));
        }

        return $this->products;
    }

    public function getTitle($param = null) : string
    {
        $group = $this->findGroupId($filter);
        if($group) {
            return $group->MenuTitle;
        }
        return $this->checkOption($param, 'Title');
    }

    protected function findGroup($filter) : ?ProductGroup
    {
        if(is_string($filter) && strpos($filter, ',') !== false) {
            $parts = explode(',', $filter);
            if(count($parts) === 2) {
                $groupId = intval($parts[1]);
                if($groupId) {
                    return ProductGroup::get()->byId($groupId);
                }
            }
        }
        return null;
    }


}
