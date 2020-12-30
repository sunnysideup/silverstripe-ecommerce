<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

/**
 * provides data on the user
 */
class ProductListUserPreference extends BaseClass
{
    protected $userPreferences = [];

    /**
     * sets a user preference.  This is typically used by the controller
     * to set filter and sort.
     *
     * @param string $type  SORT | FILTER | DISPLAY
     * @param string $value
     */
    public function setCurrentUserPreference(string $type, string $value)
    {
        $this->userPreferences[$type] = $value;
    }

    /**
     * Get a user preference.
     * This value can be updated by the controller
     * For example, the filter can be changed, based on a session value.
     *
     * @param string $type SORT | FILTER | DISPLAY
     *
     * @return string
     */
    public function getCurrentUserPreferences(string $type): string
    {
        return $this->userPreferences[$type];
    }

    /**
     * Sort the list of products
     *
     * @param array|string $param
     *
     * @return SS_List
     */
    public function apply($param = null): SS_List
    {
        $param = $this->checkOption($param);
        return $this->products;
    }
}
