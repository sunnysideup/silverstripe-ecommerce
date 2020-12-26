<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Helpers;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;

use Sunnysideup\Ecommerce\Config\EcommerceConfig;

use Sunnysideup\Ecommerce\Pages\ProductGroup;

class ProductListOptions
{
    use Configurable;
    use Injectable;



    /**
     * @var array
     */
    private static $sort_options = [
        'default' => [
            'Title' => 'Default Order',
            'SQL' => '"Sort" ASC, "Title" ASC',
        ],
        'price' => [
            'Title' => 'Lowest Price',
            'SQL' => '"Price" ASC, "Sort" ASC, "Title" ASC',
        ],
    ];

    /**
     * @var array
     */
    private static $filter_options = [
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
     * @var array
     */
    private static $display_styles = [
        'default' => [
            'Title' => 'default',
        ],
    ];



    /**
     * variable to speed up methods in this class.
     *
     * @var array
     */
    protected $configOptionsCache = [];

    /**
     * cache variable for default preference key.
     *
     * @var array
     */
    protected $productListConfigDefaultValueCache = [];

    /**
     * @var ProductGroup
     */
    protected $rootGroup;

    /**
     * @param ProductGroup $productGroup
     */
    public function __construct($rootGroup)
    {
        $this->setRootGroup($rootGroup);
    }

    /**
     * what is the the product group we are working with?
     *
     * @param ProductGroup $group
     */
    public function setRootGroup(ProductGroup $rootGroup): ProductListOptions
    {
        $this->rootGroup = $rootGroup;

        return $this;
    }

    /**
     * cache of all the data associated with a type
     * @param  string $type
     * @return array
     */
    public function getConfigOptionsCache(string $type) : array
    {
        if (! isset($this->configOptionsCache[$type])) {
            $configName = $this->sortFilterDisplayNames[$type]['configName'];

            $this->configOptionsCache[$type] = EcommerceConfig::get(self::class, $configName);
        }
        return $this->configOptionsCache[$type];

    }

    /**
     * return value for type x key x variable
     *
     * @param  string $type     e.g. SORT | FILTER
     * @param  string $key      e.g. best_match | price | lastest
     * @param  string $variable e.g. SQL | Title

     * @return mixed - empty if not found
     */
    public function getValueForProductListConfigType(string $type, string $key, string $variable)
    {
        $options = $this->getConfigOptionsCache($type);
        //check !!!
        if (isset($options[$key][$variable])) {
            return $options[$key][$variable];
            //all good
        } else {
            $userPreference = $this->getProductListConfigDefaultValue($type);
            if($key !== $userPreference) {
                return $this->getValueForProductListConfigType($type, $userPreference, $variable);
            }
            if($userPreference !== 'default') {
                return $this->getValueForProductListConfigType($type, 'default', $variable);
            }
            // //reset
            // // TODO: what is this for?
            // $this->getSortFilterDisplayNames($type, 'getVariable');
            // //clear bogus value from session ...
            // $sessionName = $this->getSortFilterDisplayNames($type, 'sessionName');
            // Controller::curr()->getRequest()->getSession()->set('ProductGroup_' . $sessionName, '');
        }

        return 'error';

    }


    /**
     * Checks for the most applicable user preferences for this page:
     *
     * 1. what is saved in Database for this page.
     * 2. what the parent product group has saved in the database
     * 3. what the standard default is.
     *
     * @param string $type - FILTER | SORT | DISPLAY
     *
     * @return string - returns the key
     */
    public function getProductListConfigDefaultValue(string $type): string
    {
        if (! isset($this->productListConfigDefaultValueCache[$type])) {
            $options = $this->getConfigOptionsCache($type);
            $dbVariableName = $this->sortFilterDisplayNames[$type]['dbFieldName'];
            $dbValue = $this->rootGroup->{$dbVariableName};
            if ($dbValue === 'inherit' && $parent = $this->rootGroup->ParentGroup()) {
                $this->productListConfigDefaultValueCache[$type] = $parent->getProductListConfigDefaultValue($type);
            } elseif ($dbValue && array_key_exists($dbValue, $options)) {
                $this->productListConfigDefaultValueCache[$type] = $dbValue;
            } else {
                $this->productListConfigDefaultValueCache[$type] = 'default';
            }
        }

        return $this->productListConfigDefaultValueCache[$type] ?? '';
    }

    /**
     * Returns the full sortFilterDisplayNames set, a subset, or one value
     * by either type (e.g. FILER) or variable (e.g dbFieldName)
     * or both.
     *
     * @param string $typeOrVariable FILTER | SORT | DISPLAY or sessionName, getVariable, etc...
     * @param string $variable:          sessionName, getVariable, etc...
     *
     * @return array | String
     */
    protected function getSortFilterDisplayNames(?string $typeOrVariable = '', ?string $variable = '')
    {
        if ($variable) {
            return $this->sortFilterDisplayNames[$typeOrVariable][$variable];
        }

        $data = [];

        if (isset($this->sortFilterDisplayNames[$typeOrVariable])) {
            $data = $this->sortFilterDisplayNames[$typeOrVariable];
        } elseif ($typeOrVariable) {
            foreach ($this->sortFilterDisplayNames as $group) {
                $data[] = $group[$typeOrVariable] ?? 'error';
            }
        } else {
            $data = $this->sortFilterDisplayNames;
        }

        return $data;
    }

    /**
     * list of sort / filter / display variables.
     *
     * @var array
     */
    protected $sortFilterDisplayNames = [
        'SORT' => [
            'value' => 'default',
            'configName' => 'sort_options',
            'sessionName' => 'session_name_for_sort_preference',
            'getVariable' => 'sort',
            'dbFieldName' => 'DefaultSortOrder',
            'translationCode' => 'SORT_BY',
        ],
        'FILTER' => [
            'value' => 'default',
            'configName' => 'filter_options',
            'sessionName' => 'session_name_for_filter_preference',
            'getVariable' => 'filter',
            'dbFieldName' => 'DefaultFilter',
            'translationCode' => 'FILTER_FOR',
        ],
        'DISPLAY' => [
            'value' => 'default',
            'configName' => 'display_styles',
            'sessionName' => 'session_name_for_display_style_preference',
            'getVariable' => 'display',
            'dbFieldName' => 'DisplayStyle',
            'translationCode' => 'DISPLAY_STYLE',
        ],
    ];


}
