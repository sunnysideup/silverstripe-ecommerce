<?php

/**
 * @description: This class helps you to manage countries within the context of e-commerce.
 * For example: To what countries can be sold.
 * /dev/build/?resetecommercecountries=1 will reset the list of countries...
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: address
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class EcommerceCountry extends DataObject implements EditableEcommerceObject
{
    /**
     * what variables are accessible through  http://mysite.com/api/ecommerce/v1/EcommerceCountry/.
     *
     * @var array
     */
    private static $api_access = array(
        'view' => array(
            'Code',
            'Name',
        )
     );

    /**
     * Standard SS Variable.
     *
     * @var array
     **/
    private static $db = array(
        'Code' => 'Varchar(20)',
        'Name' => 'Varchar(200)',
        'DoNotAllowSales' => 'Boolean',
    );

    /**
     * Standard SS Variable.
     *
     * @var array
     **/
    private static $has_many = array(
        'Regions' => 'EcommerceRegion',
    );

    /**
     * Standard SS Variable.
     *
     * @var array
     **/
    private static $indexes = array(
        'Code' => true,
        'DoNotAllowSales' => true,
    );

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $summary_fields = array(
        'Code' => 'Code',
        'Name' => 'Name',
        'AllowSalesNice' => 'Allow Sales',
    );

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $casting = array(
        'AllowSales' => 'Boolean',
        'AllowSalesNice' => 'Varchar',
    );

    /**
     * STANDARD SILVERSTRIPE STUFF.
     *
     * @todo: how to translate this?
     **/
    private static $searchable_fields = array(
        'Code' => 'PartialMatchFilter',
        'Name' => 'PartialMatchFilter',
        'DoNotAllowSales' => array(
            'title' => 'Sales are prohibited',
        ),
    );

    /**
     * Standard SS Variable.
     *
     * @var string
     **/
    private static $default_sort = '"DoNotAllowSales" ASC, "Name" ASC';

    /**
     * Standard SS Variable.
     *
     * @var string
     **/
    private static $singular_name = 'Country';
    public function i18n_singular_name()
    {
        return _t('EcommerceCountry.COUNTRY', 'Country');
    }

    /**
     * Standard SS Variable.
     *
     * @var string
     **/
    private static $plural_name = 'Countries';
    public function i18n_plural_name()
    {
        return _t('EcommerceCountry.COUNTRIES', 'Countries');
    }

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'A country.';

    /**
     * Standard SS Method.
     *
     * @param Member $member
     *
     * @var bool
     */
    public function canCreate($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return false;
    }

    /**
     * Standard SS Method.
     *
     * @param Member $member
     *
     * @var bool
     */
    public function canView($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS Method.
     *
     * @param Member $member
     *
     * @var bool
     */
    public function canEdit($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return false;
        if (ShippingAddress::get()->filter(array('ShippingCountry' => $this->Code))->count()) {
            return false;
        }
        if (BillingAddress::get()->filter(array('Country' => $this->Code))->count()) {
            return false;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * returns the country based on the Visitor Country Provider.
     * this is some sort of IP recogniser system (e.g. Geoip Class).
     *
     * @return string (country code)
     **/
    public static function get_country_from_ip()
    {
        $visitorCountryProviderClassName = EcommerceConfig::get('EcommerceCountry', 'visitor_country_provider');
        if (!$visitorCountryProviderClassName) {
            $visitorCountryProviderClassName = 'EcommerceCountry_VisitorCountryProvider';
        }
        $visitorCountryProvider = new $visitorCountryProviderClassName();

        return $visitorCountryProvider->getCountry();
    }

    /**
     * returns the country based on the Visitor Country Provider.
     * this is some sort of IP recogniser system (e.g. Geoip Class).
     *
     * @return string (country code)
     **/
    public static function get_ip()
    {
        $visitorCountryProviderClassName = EcommerceConfig::get('EcommerceCountry', 'visitor_country_provider');
        if (!$visitorCountryProviderClassName) {
            $visitorCountryProviderClassName = 'EcommerceCountry_VisitorCountryProvider';
        }
        $visitorCountryProvider = new $visitorCountryProviderClassName();

        return $visitorCountryProvider->getIP();
    }

    private static $_countries_from_db_cache = array();

    /**
     *               e.g.
     *               "NZ" => "New Zealand"
     * @return array
     */
    public static function get_country_dropdown($showAllCountries = true, $addEmptyString = false, $useIDNotCode = false)
    {
        $key = ($showAllCountries ? "all" : "notall");
        if (isset(self::$_countries_from_db_cache[$key])) {
            $array = self::$_countries_from_db_cache[$key];
        } else {
            $array = array();
            $objects = null;
            if (class_exists('Geoip') && $showAllCountries && ! $useIDNotCode) {
                $array = Geoip::getCountryDropDown();
            } elseif ($showAllCountries) {
                $objects = EcommerceCountry::get();
            } else {
                $objects = EcommerceCountry::get()->filter(array('DoNotAllowSales' => 0));
            }
            if ($objects && $objects->count()) {
                if ($useIDNotCode) {
                    $idField = 'ID';
                } else {
                    $idField = 'Code';
                }
                $array = $objects->map($idField, 'Name')->toArray();
            }
            self::$_countries_from_db_cache[$key] = $array;
        }
        if (count($array)) {
            if ($addEmptyString) {
                $array = array("", " -- please select -- ") + $array;
            }
        }
        return $array;
    }

    /**
     * This function exists as a shortcut.
     * If there is only ONE allowed country code
     * then a lot of checking of countries can be avoided.
     *
     * @return string | null - countrycode
     **/
    public static function get_fixed_country_code()
    {
        $a = EcommerceConfig::get('EcommerceCountry', 'allowed_country_codes');
        if (is_array($a) && count($a) == 1) {
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
     * @return string
     **/
    public static function find_title($code)
    {
        $code = strtoupper($code);
        $options = self::get_country_dropdown($showAllCountries = true);
        // check if code was provided, and is found in the country array
        if (isset($options[$code])) {
            return $options[$code];
        } elseif ($code) {
            $obj = EcommerceCountry::get()->filter(array('Code' => $code))->first();
            if ($obj) {
                return $obj->Name;
            }
            return $code;
        } else {
            return _t('Ecommerce.COUNTRY_NOT_FOUND', '[COUNTRY NOT FOUND]');
        }
    }

    /**
     * Memory for the order's country.
     *
     * @var array
     */
    private static $_country_cache = array();

    /**
     * @param int (optional) $orderID
     */
    public static function reset_get_country_cache($orderID = 0)
    {
        $orderID = ShoppingCart::current_order_id($orderID);
        unset(self::$_country_cache[$orderID]);
    }

    /**
     * @param int (optional) $orderID
     */
    public static function get_country_cache($orderID = 0)
    {
        $orderID = ShoppingCart::current_order_id($orderID);

        return isset(self::$_country_cache[$orderID]) ? self::$_country_cache[$orderID] : null;
    }

    /**
     * @param string         $countryCode
     * @param int (optional) $orderID
     */
    public static function set_country_cache($countryCode, $orderID = 0)
    {
        $orderID = ShoppingCart::current_order_id($orderID);
        self::$_country_cache[$orderID] = $countryCode;
    }

    /**
     * This function works out the most likely country for the current order.
     *
     * @param bool (optional) $recalculate
     * @param int (optional)  $orderID
     *
     * @return string - Country Code - e.g. NZ
     **/
    public static function get_country($recalculate = false, $orderID = 0)
    {
        $orderID = ShoppingCart::current_order_id($orderID);
        $countryCode = self::get_country_cache($orderID);
        if ($countryCode === null || $recalculate) {
            $countryCode = '';
            //1. fixed country is first
            $countryCode = self::get_fixed_country_code();
            if (!$countryCode) {
                //2. check order / shipping address / ip address
                //include $countryCode = self::get_country_from_ip();
                if ($o = ShoppingCart::current_order()) {
                    $countryCode = $o->getCountry();
                //3 ... if there is no shopping cart, then we still want it from IP
                } else {
                    $countryCode = self::get_country_from_ip();
                }
                //4 check default country set in GEO IP....
                if (!$countryCode) {
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
        $countryCode = EcommerceConfig::get('EcommerceCountry', 'default_country_code');
        //5. take the FIRST country from the get_allowed_country_codes
        if (!$countryCode) {
            $countryArray = self::list_of_allowed_entries_for_dropdown();
            if (is_array($countryArray) && count($countryArray)) {
                foreach ($countryArray as $countryCode => $countryName) {
                    //we stop at the first one... as we have no idea which one is the best.
                    break;
                }
            }
        }

        return $countryCode;
    }

    /**
     * This function works out the most likely country for the current order
     * and returns the Country Object, if any.
     *
     * @param bool    (optional)   $recalculate
     * @param string  (optional)   $countryCode
     *
     * @return EcommerceCountry | Null
     **/
    public static function get_country_object($recalculate = false, $countryCode = null)
    {
        if (! $countryCode) {
            $countryCode = self::get_country($recalculate);
        }

        return EcommerceCountry::get()->filter(array('Code' => $countryCode))->First();
    }

    /**
     * maps like this
     *     NZ => 1
     *     AU => 2
     *     etc...
     * @var array
     */
    private static $_code_to_id_map = array();
    /**
     * returns the ID of the country or 0.
     *
     * @param string (optional)   $countryCode
     * @param bool   (optional)   $recalculate
     *
     * @return int
     **/
    public static function get_country_id($countryCode = null, $recalculate = false)
    {
        if (!$countryCode) {
            $countryCode = self::get_country($recalculate);
        }
        if (isset(self::$_code_to_id_map[$countryCode])) {
            return self::$_code_to_id_map[$countryCode];
        }
        self::$_code_to_id_map[$countryCode] = 0;
        $country = EcommerceCountry::get()
            ->filter(array('Code' => $countryCode))
            ->first();
        if ($country) {
            self::$_code_to_id_map[$countryCode] = $country->ID;
            return $country->ID;
        }

        return 0;
    }

    /**
     * Memory for allow country to check.
     *
     * @var null | Boolean
     */
    private static $_allow_sales_cache = array();

    public static function reset_allow_sales_cache()
    {
        self::$_allow_sales_cache = null;
    }

    /**
     * Checks if we are allowed to sell to the current country.
     *
     * @param int (optional) $orderID
     *
     * @return bool
     */
    public static function allow_sales($orderID = 0)
    {
        $orderID = ShoppingCart::current_order_id($orderID);
        if (!isset(self::$_allow_sales_cache[$orderID])) {
            self::$_allow_sales_cache[$orderID] = true;
            $countryCode = self::get_country(false, $orderID);
            if ($countryCode) {
                $countries = EcommerceCountry::get()
                    ->filter(array(
                        'DoNotAllowSales' => 1,
                        'Code' => $countryCode,
                    ));
                if ($countries->count()) {
                    self::$_allow_sales_cache[$orderID] = false;
                }
            }
        }

        return self::$_allow_sales_cache[$orderID];
    }

    /**
     * returns an array of Codes => Names of all countries that can be used.
     * Use "list_of_allowed_entries_for_dropdown" to get the list.
     *
     * @todo add caching
     *
     * @return array
     **/
    protected static function get_default_array()
    {
        $defaultArray = array();
        $countries = null;
        if ($code = self::get_fixed_country_code()) {
            $defaultArray[$code] = self::find_title($code);

            return $defaultArray;
        }
        $countries = EcommerceCountry::get()->exclude(array('DoNotAllowSales' => 1));
        if ($countries && $countries->count()) {
            foreach ($countries as $country) {
                $defaultArray[$country->Code] = $country->Name;
            }
        }

        return $defaultArray;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Main', new LiteralField(
            'Add Add Countries',
            '
                <h3>Short-Cuts</h3>
                <h6>
                    <a href="/dev/tasks/EcommerceTaskCountryAndRegion_DisallowAllCountries" target="_blank">'._t('EcommerceCountry.DISALLOW_ALL', 'disallow sales to all countries').'</a> |||
                    <a href="/dev/tasks/EcommerceTaskCountryAndRegion_AllowAllCountries" target="_blank">'._t('EcommerceCountry.ALLOW_ALL', 'allow sales to all countries').'</a>
                </h6>
            ')
        );

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

    /**
     * standard SS method
     * cleans up codes.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $filter = EcommerceCodeFilter::create();
        $filter->checkCode($this);
    }

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
    private static $for_current_order_only_show_countries = array();
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

    /**
     * List of countries that should NOT be shown.
     *
     * @param array $a: should be country codes e.g. array("NZ", "NP", "AU");
     *
     * @var array
     */
    private static $for_current_order_do_not_show_countries = array();
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
     * @var array
     */
    private static $list_of_allowed_entries_for_dropdown_array = array();

    /**
     * takes the defaultArray and limits it with "only show" and "do not show" value, relevant for the current order.
     *
     * @return array (Code, Title)
     **/
    public static function list_of_allowed_entries_for_dropdown()
    {
        if (!self::$list_of_allowed_entries_for_dropdown_array) {
            $defaultArray = self::get_default_array();
            $onlyShow = self::$for_current_order_only_show_countries;
            $doNotShow = self::$for_current_order_do_not_show_countries;
            if (is_array($onlyShow) && count($onlyShow)) {
                foreach ($defaultArray as $key => $value) {
                    if (!in_array($key, $onlyShow)) {
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
     **/
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
        if ($this->DoNotAllowSales) {
            return false;
        } else {
            return true;
        }
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
        } else {
            return _t('EcommerceCountry.NO', 'No');
        }
    }
}
