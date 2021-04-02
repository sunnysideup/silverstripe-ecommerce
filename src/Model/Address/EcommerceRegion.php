<?php

namespace Sunnysideup\Ecommerce\Model\Address;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use Sunnysideup\CmsEditLinkField\Api\CMSEditLinkAPI;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Dev\EcommerceCodeFilter;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;

/**
 * @description: This class helps you to manage regions within the context of e-commerce.
 * The regions can be states (e.g. we only sell within New York and Penn State), suburbs (pizza delivery place),
 * or whatever other geographical borders you are using.
 * Each region has one country, so a region can not span more than one country.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: address

 **/
class EcommerceRegion extends DataObject implements EditableEcommerceObject
{
    /**
     * these variables and methods allow to to "dynamically limit the regions available, based on, for example: ordermodifiers, item selection, etc....
     * for example, if hot delivery of a catering item is only available in a certain region, then the regions can be limited with the methods below.
     * NOTE: these methods / variables below are IMPORTANT, because they allow the dropdown for the region to be limited for just that order.
     *
     * @var array of regions codes, e.g. ("NSW", "WA", "VIC");
     **/
    protected static $_for_current_order_only_show_regions = [];

    /**
     * what variables are accessible through  http://mysite.com/api/ecommerce/v1/EcommerceRegion/.
     *
     * @var array
     */
    private static $api_access = [
        'view' => [
            'Code',
            'Name',
        ],
    ];

    /**
     * @var string
     */
    private static $visitor_region_provider = 'EcommerceRegion_VisitorRegionProvider';

    /**
     * @var bool
     */
    private static $show_freetext_region_field = true;

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $table_name = 'EcommerceRegion';

    private static $db = [
        'Code' => 'Varchar(20)',
        'Name' => 'Varchar(200)',
        'DoNotAllowSales' => 'Boolean',
        'IsDefault' => 'Boolean',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $has_one = [
        'Country' => EcommerceCountry::class,
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $indexes = [
        'Name' => true,
        'Code' => true,
        'DoNotAllowSales' => true,
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $default_sort = [
        'Name' => 'ASC',
        'ID' => 'ASC',
    ];

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Region';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Regions';

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $searchable_fields = [
        'Name' => 'PartialMatchFilter',
        'Code' => 'PartialMatchFilter',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $field_labels = [
        'Name' => 'Region',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $summary_fields = [
        'Name' => 'Name',
        'Country.Title' => 'Country',
    ];

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A region within a country.  This can be a state or a province or the equivalent.';

    /**
     * @var array
     */
    private static $_for_current_order_do_not_show_regions = [];

    public function i18n_singular_name()
    {
        return _t('EcommerceRegion.REGION', 'Region');
    }

    public function i18n_plural_name()
    {
        return _t('EcommerceRegion.REGIONS', 'Regions');
    }

    /**
     * do we use regions at all in this ecommerce application?
     *
     * @return bool
     **/
    public static function show()
    {
        if (Config::inst()->get(EcommerceRegion::class, 'show_freetext_region_field')) {
            return true;
        }
        return (bool) EcommerceRegion::get()->count();
    }

    /**
     * Standard SS FieldList.
     *
     * @return \SilverStripe\Forms\FieldList
     **/
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('CountryID');

        return $fields;
    }

    /**
     * link to edit the record.
     *
     * @param string | Null $action - e.g. edit
     *
     * @return string
     */
    public function CMSEditLink($action = null)
    {
        return CMSEditLinkAPI::find_edit_link_for_object($this, $action);
    }

    /**
     * checks if a code is allowed.
     *
     * @param string $code - e.g. NZ, NSW, or CO
     *
     * @return bool
     */
    public static function code_allowed($code)
    {
        $region = DataObject::get_one(
            EcommerceRegion::class,
            ['Code' => $code]
        );
        if ($region) {
            return self::regionid_allowed($region->ID);
        }

        return false;
    }

    /**
     * checks if a code is allowed.
     *
     * @param string $regionID - e.g. NZ, NSW, or CO
     *
     * @return bool
     */
    public static function regionid_allowed($regionID)
    {
        return array_key_exists($regionID, self::list_of_allowed_entries_for_dropdown());
    }

    /**
     * converts a code into a proper title.
     *
     * @param int $regionID (Code)
     *
     * @return string ( name)
     */
    public static function find_title($regionID)
    {
        $options = self::get_default_array();
        // check if code was provided, and is found in the country array
        if ($options && isset($options[$regionID])) {
            return $options[$regionID];
        }
        return '';
    }

    // DYNAMIC LIMITS.....

    /**
     * takes the defaultArray and limits it with "only show" and "do not show" value, relevant for the current order.
     *
     * @return array (Code, Title)
     **/
    public static function list_of_allowed_entries_for_dropdown()
    {
        $defaultArray = self::get_default_array();
        $onlyShow = self::get_for_current_order_only_show_regions();
        $doNotShow = self::get_for_current_order_do_not_show_regions();
        if (is_array($onlyShow) && count($onlyShow)) {
            foreach (array_keys($defaultArray) as $id) {
                if (! in_array($id, $onlyShow, true)) {
                    unset($defaultArray[$id]);
                }
            }
        }
        if (is_array($doNotShow) && count($doNotShow)) {
            foreach ($doNotShow as $id) {
                if (isset($defaultArray[$id])) {
                    unset($defaultArray[$id]);
                }
            }
        }

        return $defaultArray;
    }

    /**
     * @param int $orderID
     *
     * @return array
     */
    public static function get_for_current_order_only_show_regions($orderID = 0)
    {
        $orderID = ShoppingCart::current_order_id($orderID);

        return isset(self::$_for_current_order_only_show_regions[$orderID]) ? self::$_for_current_order_only_show_regions[$orderID] : [];
    }

    /**
     * merges arrays...
     *
     * @param int   $orderID
     */
    public static function set_for_current_order_only_show_regions(array $a, $orderID = 0)
    {
        //We MERGE here because several modifiers may limit the countries
        $previousArray = self::get_for_current_order_only_show_regions($orderID);
        self::$_for_current_order_only_show_regions[$orderID] = array_intersect($a, $previousArray);
    }

    /**
     * @param int $orderID
     *
     * @return array
     */
    public static function get_for_current_order_do_not_show_regions($orderID = 0)
    {
        $orderID = ShoppingCart::current_order_id($orderID);

        return isset(self::$_for_current_order_do_not_show_regions[$orderID]) ? self::$_for_current_order_do_not_show_regions[$orderID] : [];
    }

    /**
     * merges arrays...
     *
     * @param int   $orderID
     */
    public static function set_for_current_order_do_not_show_regions(array $a, $orderID = 0)
    {
        //We MERGE here because several modifiers may limit the countries
        $previousArray = self::get_for_current_order_do_not_show_regions($orderID);
        self::$_for_current_order_do_not_show_regions[$orderID] = array_merge($a, $previousArray);
    }

    /**
     * This function works out the most likely region for the current order.
     *
     * @return int
     **/
    public static function get_region_id()
    {
        $regionID = 0;
        if ($order = ShoppingCart::current_order()) {
            if ($region = $order->Region()) {
                $regionID = $region->ID;
            }
        }
        //3. check GEOIP information
        if (! $regionID) {
            $regions = EcommerceRegion::get()->filter(['IsDefault' => 1]);
            if ($regions) {
                $regionArray = self::list_of_allowed_entries_for_dropdown();
                foreach ($regions as $region) {
                    if (in_array($region->ID, $regionArray, true)) {
                        return $region->ID;
                    }
                }
            }
            if (is_array($regionArray) && count($regionArray)) {
                $regionID = array_key_first($regionArray);
            }
        }

        return $regionID;
    }

    /**
     * returns the country based on the Visitor Country Provider.
     * this is some sort of IP recogniser system (e.g. Geoip Class).
     *
     * @return int
     **/
    public static function get_region_from_ip()
    {
        $visitorCountryProviderClassName = EcommerceConfig::get(EcommerceCountry::class, 'visitor_region_provider');
        if (! $visitorCountryProviderClassName) {
            $visitorCountryProviderClassName = EcommerceRegionVisitorRegionProvider::class;
        }
        $visitorCountryProvider = new $visitorCountryProviderClassName();

        return $visitorCountryProvider->getRegion();
    }

    /**
     * @alias for get_region_id
     */
    public static function get_region()
    {
        return self::get_region_id();
    }

    /**
     * standard SS method
     * cleans up codes.
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $filter = EcommerceCodeFilter::create();
        $filter->checkCode($this);
    }

    /**
     * This function returns back the default list of regions, filtered by the currently selected country.
     *
     * @return array - array of Region.ID => Region.Name
     **/
    protected static function get_default_array()
    {
        $defaultArray = [];
        $regions = EcommerceRegion::get()
            ->Exclude(['DoNotAllowSales' => 1]);
        $defaultRegion = EcommerceCountry::get_country_id();
        if ($defaultRegion) {
            $regions = $regions->Filter(['CountryID' => EcommerceCountry::get_country_id()]);
        }
        if ($regions && $regions->count()) {
            foreach ($regions as $region) {
                $defaultArray[$region->ID] = $region->Name;
            }
        }

        return $defaultArray;
    }
}
