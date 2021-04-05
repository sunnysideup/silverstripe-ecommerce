<?php

namespace Sunnysideup\Ecommerce\Model\Address;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\CmsEditLinkField\Api\CMSEditLinkAPI;
use Sunnysideup\Ecommerce\Api\EcommerceCountryVisitorCountryProvider;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Dev\EcommerceCodeFilter;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Tasks\EcommerceTaskCountryAndRegion;

/**
 * @description: This class helps you to manage countries within the context of e-commerce.
 * For example: To what countries can be sold.
 * /dev/build/?resetecommercecountries=1 will reset the list of countries...
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: address
 */
class EcommerceCountry extends DataObject implements EditableEcommerceObject
{
    /**
     * what variables are accessible through  http://mysite.com/api/ecommerce/v1/EcommerceCountry/.
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
     * @var array
     */
    private static $allowed_country_codes = [];

    /**
     * @var string
     */
    private static $visitor_country_provider = EcommerceCountryVisitorCountryProvider::class;

    /**
     * @var string
     */
    private static $default_country_code = 'NZ';

    /**
     * standard SS static definition.
     */
    private static $table_name = 'EcommerceCountry';

    /**
     * Standard SS Variable.
     *
     * @var array
     */
    private static $db = [
        'Code' => 'Varchar(20)',
        'Name' => 'Varchar(200)',
        'DoNotAllowSales' => 'Boolean',
    ];

    /**
     * Standard SS Variable.
     *
     * @var array
     */
    private static $has_many = [
        'Regions' => EcommerceRegion::class,
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $summary_fields = [
        'Code' => 'Code',
        'Name' => 'Name',
        'AllowSalesNice' => 'Allow Sales',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $casting = [
        'AllowSales' => 'Boolean',
        'AllowSalesNice' => 'Varchar',
    ];

    /**
     * STANDARD SILVERSTRIPE STUFF.
     *
     * @todo: how to translate this?
     */
    private static $searchable_fields = [
        'Code' => 'PartialMatchFilter',
        'Name' => 'PartialMatchFilter',
        'DoNotAllowSales' => [
            'title' => 'Sales are prohibited',
        ],
    ];

    /**
     * Standard SS Variable.
     *
     * @var array
     */
    private static $indexes = [
        'DoNotAllowSales' => true,
        'Name' => true,
        'Code' => true,
    ];

    /**
     * Standard SS Variable.
     *
     * @var array
     */
    private static $default_sort = [
        'DoNotAllowSales' => 'ASC',
        'Name' => 'ASC',
        'ID' => 'ASC',
    ];

    /**
     * Standard SS Variable.
     *
     * @var string
     */
    private static $singular_name = 'Country';

    /**
     * Standard SS Variable.
     *
     * @var string
     */
    private static $plural_name = 'Countries';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A country.';

    private static $_countries_from_db_cache = [];

    /**
     * Memory for the order's country.
     *
     * @var array
     */
    private static $_country_cache = [];

    /**
     * maps like this
     *     NZ => 1
     *     AU => 2
     *     etc...
     *
     * @var array
     */
    private static $_code_to_id_map = [];

    /**
     * Memory for allow country to check.
     *
     * @var array
     */
    private static $_allow_sales_cache = [];

    //DYNAMIC LIMITATIONS

    /**
     * these variables and methods allow to "dynamically limit the countries available,
     * based on, for example: ordermodifiers, item selection, etc....
     * for example, if a person chooses delivery within Australasia (with modifier) -
     * then you can limit the countries available to "Australasian" countries.
     */

    /**
     * List of countries that should be shown.
     *
     * @param array $a: should be country codes e.g. array("NZ", "NP", "AU");
     *
     * @var array
     */
    private static $for_current_order_only_show_countries = [];

    /**
     * List of countries that should NOT be shown.
     *
     * @param array $a: should be country codes e.g. array("NZ", "NP", "AU");
     *
     * @var array
     */
    private static $for_current_order_do_not_show_countries = [];

    /**
     * @var array
     */
    private static $list_of_allowed_entries_for_dropdown_array = [];

    public function i18n_singular_name()
    {
        return _t('EcommerceCountry.COUNTRY', 'Country');
    }

    public function i18n_plural_name()
    {
        return _t('EcommerceCountry.COUNTRIES', 'Countries');
    }

    /**
     * Standard SS Method.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @var bool
     */
    public function canCreate($member = null, $context = [])
    {
        $can = false;
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        if (EcommerceCountry::get()->count() < 220) {
            $can = parent::canCreate($member);
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }

        return $can;
    }

    /**
     * Standard SS Method.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @var bool
     */
    public function canView($member = null, $context = [])
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS Method.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
     *
     * @var bool
     */
    public function canEdit($member = null, $context = [])
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
            return $extended;
        }
        if (ShippingAddress::get()->filter(['ShippingCountry' => $this->Code])->count()) {
            return false;
        }
        if (BillingAddress::get()->filter(['Country' => $this->Code])->count()) {
            return false;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * returns the country based on the Visitor Country Provider.
     * this is some sort of IP recogniser system (e.g. Geoip Class).
     *
     * @return string (country code)
     */
    public static function get_country_from_ip()
    {
        $visitorCountryProviderClassName = EcommerceConfig::get(EcommerceCountry::class, 'visitor_country_provider');
        if (! $visitorCountryProviderClassName) {
            $visitorCountryProviderClassName = EcommerceCountryVisitorCountryProvider::class;
        }
        $visitorCountryProvider = new $visitorCountryProviderClassName();

        return $visitorCountryProvider->getCountry();
    }

    /**
     * returns the country based on the Visitor Country Provider.
     * this is some sort of IP recogniser system (e.g. Geoip Class).
     *
     * @return string (country code)
     */
    public static function get_ip()
    {
        $visitorCountryProviderClassName = EcommerceConfig::get(EcommerceCountry::class, 'visitor_country_provider');
        if (! $visitorCountryProviderClassName) {
            $visitorCountryProviderClassName = EcommerceCountryVisitorCountryProvider::class;
        }
        $visitorCountryProvider = new $visitorCountryProviderClassName();

        return $visitorCountryProvider->getIP();
    }

    /**
     *               e.g.
     *               "NZ" => "New Zealand".
     *
     * @param mixed $showAllCountries
     * @param mixed $addEmptyString
     * @param mixed $useIDNotCode
     *
     * @return array
     */
    public static function get_country_dropdown($showAllCountries = true, $addEmptyString = false, $useIDNotCode = false)
    {
        $key = ($showAllCountries ? 'all' : 'notall');
        if (isset(self::$_countries_from_db_cache[$key])) {
            $array = self::$_countries_from_db_cache[$key];
        } else {
            $array = [];
            $objects = null;
            $className = '\\Sunnysideup\\Geoip\\Geoip';
            if (class_exists($className) && $showAllCountries && ! $useIDNotCode) {
                $array = $className::getCountryDropDown();
            } elseif ($showAllCountries) {
                $objects = EcommerceCountry::get();
            } else {
                $objects = EcommerceCountry::get()->filter(['DoNotAllowSales' => 0]);
            }
            if ($objects && $objects->count()) {
                $idField = $useIDNotCode ? 'ID' : 'Code';
                $array = $objects->map($idField, 'Name')->toArray();
            }
            self::$_countries_from_db_cache[$key] = $array;
        }
        if (count($array)) {
            if ($addEmptyString) {
                $array = ['', ' -- please select -- '] + $array;
            }
        }

        return $array;
    }

    /**
     * This function exists as a shortcut.
     * If there is only ONE allowed country code
     * then a lot of checking of countries can be avoided.
     *
     * @return null|string - countrycode
     */
    public static function get_fixed_country_code()
    {
        $a = EcommerceConfig::get(EcommerceCountry::class, 'allowed_country_codes');
        if (is_array($a) && 1 === count($a)) {
            return array_shift($a);
        }

        return null;
    }

    /**
     * @alias for EcommerceCountry::find_title
     *
     * @param string $code
     *                     We have this as this is the same as Geoip
     *
     * @return string
     */
    public static function countryCode2name($code)
    {
        return self::find_title($code);
    }

    /**
     * returns the country name from a code.
     *
     * @param mixed $code
     *
     * @return string
     */
    public static function find_title($code)
    {
        $code = strtoupper($code);
        $options = self::get_country_dropdown($showAllCountries = true);
        // check if code was provided, and is found in the country array
        if (isset($options[$code])) {
            return $options[$code];
        }
        if ($code) {
            $obj = DataObject::get_one(
                EcommerceCountry::class,
                ['Code' => $code]
            );
            if ($obj) {
                return $obj->Name;
            }

            return $code;
        }

        return _t('Ecommerce.COUNTRY_NOT_FOUND', '[COUNTRY NOT FOUND]');
    }

    /**
     * @param int $orderID (optional)
     */
    public static function reset_get_country_cache(?int $orderID = 0)
    {
        $orderID = ShoppingCart::current_order_id($orderID);
        unset(self::$_country_cache[$orderID]);
    }

    /**
     * @param int $orderID (optional)
     */
    public static function get_country_cache(?int $orderID = 0)
    {
        $orderID = ShoppingCart::current_order_id($orderID);

        return isset(self::$_country_cache[$orderID]) ? self::$_country_cache[$orderID] : null;
    }

    /**
     * @param string $countryCode
     * @param int    $orderID     (optional)
     */
    public static function set_country_cache($countryCode, $orderID = 0)
    {
        $orderID = ShoppingCart::current_order_id($orderID);
        self::$_country_cache[$orderID] = $countryCode;
    }

    /**
     * This function works out the most likely country for the current order.
     *
     * @param bool $recalculate (optional)
     * @param int  $orderID     (optional)
     *
     * @return string - Country Code - e.g. NZ
     */
    public static function get_country($recalculate = false, $orderID = 0)
    {
        //get order ID
        $orderID = ShoppingCart::current_order_id($orderID);
        $countryCode = self::get_country_cache($orderID);
        if (null === $countryCode || $recalculate) {
            //1. fixed country is first
            $countryCode = self::get_fixed_country_code();
            if (! $countryCode) {
                //2. check order / shipping address / ip address
                //include $countryCode = self::get_country_from_ip();
                $o = ShoppingCart::current_order();
                if ($orderID && $orderID !== $o->ID) {
                    $o = DataObject::get_one(Order::class, ['ID' => $orderID]);
                }
                if ($o && $o->exists()) {
                    $countryCode = $o->getCountry();
                //3 ... if there is no shopping cart, then we still want it from IP
                } else {
                    $countryCode = self::get_country_from_ip();
                }
                //4 check default country set in GEO IP....
                if (! $countryCode) {
                    $countryCode = self::get_country_default();
                }
            }
            self::set_country_cache($countryCode, $orderID);
        }

        return $countryCode;
    }

    /**
     * A bling guess at the best country!
     *
     * @return string
     */
    public static function get_country_default()
    {
        $countryCode = EcommerceConfig::get(EcommerceCountry::class, 'default_country_code');
        //5. take the FIRST country from the get_allowed_country_codes
        if (! $countryCode) {
            $countryArray = self::list_of_allowed_entries_for_dropdown();
            if (is_array($countryArray) && count($countryArray)) {
                $countryCode = array_key_first($countryArray);
            }
        }

        return $countryCode;
    }

    /**
     * @param mixed $var
     * @param mixed $asCode
     *
     * @return null|EcommerceCountry|string
     */
    public static function get_country_from_mixed_var($var, $asCode = false)
    {
        if (is_string($var)) {
            $var = strtoupper($var);
            $var = DataObject::get_one(EcommerceCountry::class, ['Code' => $var]);
        } elseif (is_numeric($var) && is_int($var)) {
            $var = EcommerceCountry::get()->byID($var);
        }
        if ($var instanceof EcommerceCountry) {
            if ($asCode) {
                return $var->Code;
            }

            return $var;
        }

        return null;
    }

    /**
     * This function works out the most likely country for the current order
     * and returns the Country Object, if any.
     *
     * @param bool   $recalculate (optional)
     * @param string $countryCode (optional)
     *
     * @return EcommerceCountry | DataObject|null
     */
    public static function get_country_object($recalculate = false, $countryCode = null)
    {
        if (! $countryCode) {
            $countryCode = self::get_country($recalculate);
        }

        return DataObject::get_one(
            EcommerceCountry::class,
            ['Code' => $countryCode]
        );
    }

    /**
     * returns the ID of the country or 0.
     *
     * @param string $countryCode (optional)
     * @param bool   $recalculate (optional)
     *
     * @return int
     */
    public static function get_country_id($countryCode = null, $recalculate = false)
    {
        if (! $countryCode) {
            $countryCode = self::get_country($recalculate);
        }
        if (isset(self::$_code_to_id_map[$countryCode])) {
            return self::$_code_to_id_map[$countryCode];
        }
        self::$_code_to_id_map[$countryCode] = 0;
        $country = DataObject::get_one(
            EcommerceCountry::class,
            ['Code' => $countryCode]
        );
        if ($country) {
            self::$_code_to_id_map[$countryCode] = $country->ID;

            return $country->ID;
        }

        return 0;
    }

    public static function reset_allow_sales_cache()
    {
        self::$_allow_sales_cache = null;
    }

    /**
     * Checks if we are allowed to sell to the current country.
     *
     * @param int $orderID (optional)
     *
     * @return bool
     */
    public static function allow_sales(?int $orderID = 0)
    {
        $orderID = ShoppingCart::current_order_id($orderID);
        if (! isset(self::$_allow_sales_cache[$orderID])) {
            self::$_allow_sales_cache[$orderID] = true;
            $countryCode = self::get_country(false, $orderID);
            if ($countryCode) {
                $countries = EcommerceCountry::get()
                    ->filter([
                        'DoNotAllowSales' => 1,
                        'Code' => $countryCode,
                    ])
                ;
                if ($countries->count()) {
                    self::$_allow_sales_cache[$orderID] = false;
                }
            }
        }

        return self::$_allow_sales_cache[$orderID];
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab(
            'Root.Main',
            new LiteralField(
                'Add Add Countries',
                '
                <h3>Short-Cuts</h3>
                <h6>
                    <a href="/dev/tasks/EcommerceTaskCountryAndRegionDisallowAllCountries" target="_blank">' . _t('EcommerceCountry.DISALLOW_ALL', 'disallow sales to all countries') . '</a> |||
                    <a href="/dev/tasks/EcommerceTaskCountryAndRegionAllowAllCountries" target="_blank">' . _t('EcommerceCountry.ALLOW_ALL', 'allow sales to all countries') . '</a>
                </h6>
            '
            )
        );

        return $fields;
    }

    /**
     * link to edit the record.
     *
     * @param null|string $action - e.g. edit
     *
     * @return string
     */
    public function CMSEditLink($action = null)
    {
        return CMSEditLinkAPI::find_edit_link_for_object($this, $action);
    }

    /**
     * standard SS method.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        if ((! EcommerceCountry::get()->count()) || isset($_REQUEST['resetecommercecountries'])) {
            $task = new EcommerceTaskCountryAndRegion();
            $task->run(null);
        }
    }

    public static function get_for_current_order_only_show_countries()
    {
        return self::$for_current_order_only_show_countries;
    }

    public static function set_for_current_order_only_show_countries(array $a)
    {
        if (count(self::$for_current_order_only_show_countries)) {
            //we INTERSECT here so that only countries allowed by all forces (modifiers) are added.
            self::$for_current_order_only_show_countries = array_intersect($a, self::$for_current_order_only_show_countries);
        } else {
            self::$for_current_order_only_show_countries = $a;
        }
    }

    public static function get_for_current_order_do_not_show_countries()
    {
        return self::$for_current_order_do_not_show_countries;
    }

    public static function set_for_current_order_do_not_show_countries(array $a)
    {
        //We MERGE here because several modifiers may limit the countries
        self::$for_current_order_do_not_show_countries = array_merge($a, self::$for_current_order_do_not_show_countries);
    }

    /**
     * takes the defaultArray and limits it with "only show" and "do not show" value, relevant for the current order.
     *
     * @return array (Code, Title)
     */
    public static function list_of_allowed_entries_for_dropdown()
    {
        if (! self::$list_of_allowed_entries_for_dropdown_array) {
            $defaultArray = self::get_default_array();
            $onlyShow = self::$for_current_order_only_show_countries;
            $doNotShow = self::$for_current_order_do_not_show_countries;
            if (is_array($onlyShow) && count($onlyShow)) {
                foreach (array_keys($defaultArray) as $key) {
                    if (! in_array($key, $onlyShow, true)) {
                        unset($defaultArray[$key]);
                    }
                }
            }
            if (is_array($doNotShow) && count($doNotShow)) {
                foreach ($doNotShow as $code) {
                    if (isset($defaultArray[$code])) {
                        unset($defaultArray[$code]);
                    }
                }
            }
            self::$list_of_allowed_entries_for_dropdown_array = $defaultArray;
        }

        return self::$list_of_allowed_entries_for_dropdown_array;
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
        return array_key_exists($code, self::list_of_allowed_entries_for_dropdown());
    }

    /**
     * Casted variable to show if sales are allowed to this country.
     *
     * @return bool
     */
    public function AllowSales()
    {
        return $this->getAllowSales();
    }

    public function getAllowSales()
    {
        return ! $this->DoNotAllowSales;
    }

    /**
     * Casted variable to show if sales are allowed to this country.
     *
     * @return string
     */
    public function AllowSalesNice()
    {
        return $this->getAllowSalesNice();
    }

    public function getAllowSalesNice()
    {
        if ($this->AllowSales()) {
            return _t('EcommerceCountry.YES', 'Yes');
        }

        return _t('EcommerceCountry.NO', 'No');
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
     * returns an array of Codes => Names of all countries that can be used.
     * Use "list_of_allowed_entries_for_dropdown" to get the list.
     *
     * @todo add caching
     *
     * @return array
     */
    protected static function get_default_array()
    {
        $defaultArray = [];
        if ($code = self::get_fixed_country_code()) {
            $defaultArray[$code] = self::find_title($code);

            return $defaultArray;
        }
        $countries = EcommerceCountry::get()->exclude(['DoNotAllowSales' => 1]);
        if ($countries && $countries->count()) {
            foreach ($countries as $country) {
                $defaultArray[$country->Code] = $country->Name;
            }
        }

        return $defaultArray;
    }
}
