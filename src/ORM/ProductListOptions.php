<?php

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;

use Sunnysideup\Ecommerce\Config\EcommerceConfig;

use Sunnysideup\Ecommerce\Pages\ProductGroup;

class ProductListOptions
{
    use Configurable;

    public function getConfigOptionsCache(string $type) : array
    {
        if (! isset($this->configOptionsCache[$type])) {
            $configName = $this->sortFilterDisplayNames[$type]['configName'];

            $this->configOptionsCache[$type] = EcommerceConfig::get(ProductGroup::class, $configName);
        }
        return $this->configOptionsCache[$type];

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
    protected $myUserPreferencesDefaultCache = [];




}
