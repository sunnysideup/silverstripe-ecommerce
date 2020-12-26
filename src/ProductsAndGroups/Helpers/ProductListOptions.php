<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;

use Sunnysideup\Ecommerce\Config\EcommerceConfig;

use Sunnysideup\Ecommerce\Pages\ProductGroup;

class ProductListOptions
{
    use Configurable;

    /**
     * variable to speed up methods in this class.
     *
     * @var array
     */
    protected $configOptionsCache = [];

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
     * check if the key is valid.
     *
     * @param  string $type     e.g. SORT | FILTER
     * @param  string $key      e.g. best_match | price | lastest
     * @param  string $variable e.g. SQL | Title

     * @return string - empty if not found
     */
    public function getOption(string $type, string $key, string $variable)
    {
        $options = $this->getConfigOptionsCache($type);
        //check !!!
        if (isset($options[$key][$variable])) {
            return $options[$key][$variable];
            //all good
        } else {
            $userPreference = $this->getMyUserPreferencesDefault($type);
            if($key !== $userPreference) {
                return $this->getOption($type, $userPreference, $variable);
            }
            if($userPreference !== 'default') {
                return $this->getOption($type, 'default', $variable);
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
     * SORT:
     * Returns the sort sql for a particular sorting key.
     * If no key is provided then the default key will be returned.
     *
     * @param string $key
     *
     * @return array (e.g. Array(MyField => "ASC", "MyOtherField" => "DESC")
     *
     * FILTER:
     * Returns the sql associated with a filter option.
     *
     * @param string $type - FILTER | SORT | DISPLAY
     * @param string $key  - the options selected
     *
     * @return array | String (e.g. array("MyField" => 1, "MyOtherField" => 0)) OR STRING
     */
    protected function getUserSettingsOptionSQL($type, ?string $key = 'default', ?string $variable = 'SQL')
    {
        return $this->getOption($type, $key,  'SQL');
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
    public function getMyUserPreferencesDefault($type): string
    {
        if (! isset($this->myUserPreferencesDefaultCache[$type]) || ! $this->myUserPreferencesDefaultCache[$type]) {
            $options = $this->getConfigOptions($type);
            $dbVariableName = $this->sortFilterDisplayNames[$type]['dbFieldName'];
            $defaultOption = '';

            if ($defaultOption === 'inherit' && $parent = $this->ParentGroup()) {
                $defaultOption = $parent->getMyUserPreferencesDefault($type);
            } elseif ($this->{$dbVariableName} && array_key_exists($this->{$dbVariableName}, $options)) {
                $defaultOption = $this->{$dbVariableName};
            }

            if ($defaultOption !== 'default') {
                $this->myUserPreferencesDefaultCache[$type] = $defaultOption;
            }
        }

        if (isset($this->myUserPreferencesDefaultCache[$type])) {
            return $this->myUserPreferencesDefaultCache[$type];
        }

        return '';
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
    protected function getSortFilterDisplayNames($typeOrVariable = '', $variable = '')
    {
        if ($variable) {
            return $this->sortFilterDisplayNames[$typeOrVariable][$variable];
        }

        $data = [];

        if (isset($this->sortFilterDisplayNames[$typeOrVariable])) {
            $data = $this->sortFilterDisplayNames[$typeOrVariable];
        } elseif ($typeOrVariable) {
            foreach ($this->sortFilterDisplayNames as $group) {
                $data[] = $group[$typeOrVariable];
            }
        } else {
            $data = $this->sortFilterDisplayNames;
        }

        return $data;
    }

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
        2 => 'Direct Child Products + Grand Child Products',
        3 => 'Direct Child Products + Grand Child Products + Great Grand Child Products',
        4 => 'Direct Child Products + Grand Child Products + Great Grand Child Products + Great Great Grand Child Products',
    ];


    /**
     * cache variable for default preference key.
     *
     * @var array
     */
    protected $myUserPreferencesDefaultCache = [];




}
