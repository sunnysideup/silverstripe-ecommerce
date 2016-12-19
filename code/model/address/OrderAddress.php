<?php


/**
 * @description: each order has an address: a Shipping and a Billing address
 * This is a base-class for both.
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: address
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderAddress extends DataObject implements EditableEcommerceObject
{
    /**
     * standard SS static definition.
     */
    private static $singular_name = 'Order Address';
    public function i18n_singular_name()
    {
        return _t('OrderAddress.ORDERADDRESS', 'Order Address');
    }

    /**
     * standard SS static definition.
     */
    private static $plural_name = 'Order Addresses';
    public function i18n_plural_name()
    {
        return _t('OrderAddress.ORDERADDRESSES', 'Order Addresses');
    }

    /**
     * standard SS static definition.
     */
    private static $casting = array(
        'FullName' => 'Text',
        'FullString' => 'Text',
        'JSONData' => 'Text',
    );

    /**
     * returns the id of the MAIN country field for template manipulation.
     * Main means the one that is used as the primary one (e.g. for tax purposes).
     *
     * @see EcommerceConfig::get("OrderAddress", "use_shipping_address_for_main_region_and_country")
     *
     * @return string
     */
    public static function get_country_field_ID()
    {
        if (EcommerceConfig::get('OrderAddress', 'use_shipping_address_for_main_region_and_country')) {
            return 'ShippingCountry';
        } else {
            return 'Country';
        }
    }

    /**
     * returns the id of the MAIN region field for template manipulation.
     * Main means the one that is used as the primary one (e.g. for tax purposes).
     *
     * @return string
     */
    public static function get_region_field_ID()
    {
        if (EcommerceConfig::get('OrderAddress', 'use_shipping_address_for_main_region_and_country')) {
            return 'ShippingRegion';
        } else {
            return 'Region';
        }
    }

    /**
     * There might be times when a modifier needs to make an address field read-only.
     * In that case, this is done here.
     *
     * @var array
     */
    protected $readOnlyFields = array();

    /**
     * sets a field to readonly state
     * we use this when modifiers have been set that require a field to be a certain value
     * for example - a PostalCode field maybe set in the modifier.
     *
     * @param string $fieldName
     */
    public function addReadOnlyField($fieldName)
    {
        $this->readOnlyFields[$fieldName] = $fieldName;
    }

    /**
     * removes a field from the readonly state.
     *
     * @param string $fieldName
     */
    public function removeReadOnlyField($fieldName)
    {
        unset($this->readOnlyFields[$fieldName]);
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
        return Controller::join_links(
            Director::baseURL(),
            '/admin/sales/'.$this->ClassName.'/EditForm/field/'.$this->ClassName.'/item/'.$this->ID.'/',
            $action
        );
    }

    /**
     * save edit status for speed's sake.
     *
     * @var bool
     */
    protected $_canEdit = null;

    /**
     * save view status for speed's sake.
     *
     * @var bool
     */
    protected $_canView = null;

    public function canCreate($member = null)
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
     * Standard SS method
     * This is an important method.
     *
     * @param Member $member
     *
     * @return bool
     **/
    public function canView($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (!$this->exists()) {
            return $this->canCreate($member);
        }
        if ($this->_canView === null) {
            $this->_canView = false;
            if ($this->Order()) {
                if ($this->Order()->exists()) {
                    if ($this->Order()->canView($member)) {
                        $this->_canView = true;
                    }
                }
            }
        }

        return $this->_canView;
    }

    /**
     * Standard SS method
     * This is an important method.
     *
     * @param Member $member
     *
     * @return bool
     **/
    public function canEdit($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (! $this->exists()) {
            return $this->canCreate($member);
        }
        if ($this->_canEdit === null) {
            $this->_canEdit = false;
            if ($this->Order()) {
                if ($this->Order()->exists()) {
                    if ($this->Order()->canEdit($member)) {
                        if (! $this->Order()->IsSubmitted()) {
                            $this->_canEdit = true;
                        }
                    }
                }
            }
        }

        return $this->_canEdit;
    }

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
    }

    /**
     * Determine which properties on the DataObject are
     * searchable, and map them to their default {@link FormField}
     * representations. Used for scaffolding a searchform for {@link ModelAdmin}.
     *
     * Some additional logic is included for switching field labels, based on
     * how generic or specific the field type is.
     *
     * Used by {@link SearchContext}.
     *
     * @param array $_params
     *                       'fieldClasses': Associative array of field names as keys and FormField classes as values
     *                       'restrictFields': Numeric array of a field name whitelist
     *
     * @return FieldList
     */
    public function scaffoldSearchFields($_params = null)
    {
        $fields = parent::scaffoldSearchFields($_params);
        $fields = parent::scaffoldSearchFields();
        $fields->replaceField('OrderID', new NumericField('OrderID', 'Order Number'));

        return $fields;
    }

    /**
     * @return FieldList
     */
    protected function getEcommerceFields()
    {
        return new FieldList();
    }

    /**
     * put together a textfield for a postal code field.
     *
     * @param string $name - name of the field
     *
     * @return TextField
     **/
    protected function getPostalCodeField($name)
    {
        $field = new TextField($name, _t('OrderAddress.POSTALCODE', 'Postal Code'));
        $postalCodeURL = EcommerceDBConfig::current_ecommerce_db_config()->PostalCodeURL;
        $postalCodeLabel = EcommerceDBConfig::current_ecommerce_db_config()->PostalCodeLabel;
        if ($postalCodeURL && $postalCodeLabel) {
            $prefix = EcommerceConfig::get('OrderAddress', 'field_class_and_id_prefix');
            $field->setRightTitle('<a href="'.$postalCodeURL.'" id="'.$prefix.$name.'Link" class="'.$prefix.'postalCodeLink">'.$postalCodeLabel.'</a>');
        }

        return $field;
    }

    /**
     * put together a dropdown for the region field.
     *
     * @param string $name - name of the field
     *
     * @return DropdownField
     **/
    protected function getRegionField($name, $freeTextName = '')
    {
        if (EcommerceRegion::show()) {
            $nameWithoutID = str_replace('ID', '', $name);
            $title = _t('OrderAddress.'.strtoupper($nameWithoutID), 'Region / Province / State');
            $regionsForDropdown = EcommerceRegion::list_of_allowed_entries_for_dropdown();
            $count = count($regionsForDropdown);
            if ($count < 1) {
                if (!$freeTextName) {
                    $freeTextName = $nameWithoutID.'Code';
                }
                $regionField = new TextField($freeTextName, $title);
            } else {
                $regionField = new DropdownField($name, $title, $regionsForDropdown);
                if ($count < 2) {
                    //readonly shows as number (ID), rather than title
                    //$regionField = $regionField->performReadonlyTransformation();
                } else {
                    $regionField->setEmptyString(_t('OrderAdress.PLEASE_SELECT_REGION', '--- Select Region ---'));
                }
            }
        } else {
            //adding region field here as hidden field to make the code easier below...
            $regionField = new HiddenField($name, '', 0);
        }
        $prefix = EcommerceConfig::get('OrderAddress', 'field_class_and_id_prefix');
        $regionField->addExtraClass($prefix.'ajaxRegionField');

        return $regionField;
    }

    /**
     * put together a dropdown for the country field.
     *
     * @param string $name - name of the field
     *
     * @return DropdownField
     **/
    protected function getCountryField($name)
    {
        $countriesForDropdown = EcommerceCountry::list_of_allowed_entries_for_dropdown();
        $title = _t('OrderAddress.'.strtoupper($name), 'Country');
        $countryField = new DropdownField($name, $title, $countriesForDropdown, EcommerceCountry::get_country(false, $this->OrderID));
        $countryField->setRightTitle(_t('OrderAddress.'.strtoupper($name).'_RIGHT', ''));
        if (count($countriesForDropdown) < 2) {
            $countryField = $countryField->performReadonlyTransformation();
            if (count($countriesForDropdown) < 1) {
                $countryField = new HiddenField($name, '', 'not available');
            }
        }
        $prefix = EcommerceConfig::get('OrderAddress', 'field_class_and_id_prefix');
        $countryField->addExtraClass($prefix.'ajaxCountryField');
        //important, otherwise loadData will override the default value....
        $this->$name = EcommerceCountry::get_country(false, $this->OrderID);

        return $countryField;
    }

    /**
     * makes selected fields into read only using the $this->readOnlyFields array.
     *
     * @param FieldList | Composite $fields
     *
     * @return FieldList
     */
    protected function makeSelectedFieldsReadOnly($fields)
    {
        $this->extend('augmentMakeSelectedFieldsReadOnly', $fields);
        if (is_array($this->readOnlyFields) && count($this->readOnlyFields)) {
            foreach ($this->readOnlyFields as $readOnlyField) {
                if ($oldField = $fields->fieldByName($readOnlyField)) {
                    $fields->replaceField($readOnlyField, $oldField->performReadonlyTransformation());
                }
            }
        }

        return $fields;
    }

    /**
     * Saves region - both shipping and billing fields are saved here for convenience sake (only one actually gets saved)
     * NOTE: do not call this method SetCountry as this has a special meaning! *.
     *
     * @param int -  RegionID
     **/
    public function SetRegionFields($regionID)
    {
        $regionField = $this->fieldPrefix().'RegionID';
        $this->$regionField = $regionID;
        $this->write();
    }

    /**
     * Saves country - both shipping and billing fields are saved here for convenience sake (only one actually gets saved)
     * NOTE: do not call this method SetCountry as this has a special meaning!
     *
     * @param string - CountryCode - e.g. NZ
     */
    public function SetCountryFields($countryCode)
    {
        $countryField = $this->fieldPrefix().'Country';
        $this->$countryField = $countryCode;
        $this->write();
    }

    /**
     * Casted variable
     * returns the full name of the person, e.g. "John Smith".
     *
     * @return string
     */
    public function getFullName()
    {
        $fieldNameField = $this->fieldPrefix().'FirstName';
        $fieldFirst = $this->$fieldNameField;
        $lastNameField = $this->fieldPrefix().'Surname';
        $fieldLast = $this->$lastNameField;

        return $fieldFirst.' '.$fieldLast;
    }
    public function FullName()
    {
        return $this->getFullName();
    }

    /**
     * Casted variable
     * returns the full strng of the record.
     *
     * @return string
     */
    public function FullString()
    {
        return $this->getFullString();
    }
    public function getFullString()
    {
        Config::nest();
        Config::inst()->update('SSViewer', 'theme_enabled', true);
        $html = $this->renderWith('Order_Address'.str_replace('Address', '', $this->ClassName).'FullString');
        Config::unnest();

        return $html;
    }

    /**
     * returns a string that can be used to find out if two addresses are the same.
     *
     * @return string
     */
    public function comparisonString()
    {
        $comparisonString = '';
        $excludedFields = array('ID', 'OrderID');
        $fields = $this->stat('db');
        $regionFieldName = $this->fieldPrefix().'RegionID';
        $fields[$regionFieldName] = $regionFieldName;
        if ($fields) {
            foreach ($fields as $field => $useless) {
                if (!in_array($field, $excludedFields)) {
                    $comparisonString .= preg_replace('/\s+/', '', $this->$field);
                }
            }
        }

        return strtolower(trim($comparisonString));
    }

    /**
     * returns the field prefix string for shipping addresses.
     *
     * @return string
     **/
    protected function baseClassLinkingToOrder()
    {
        if (is_a($this, Object::getCustomClass('BillingAddress'))) {
            return 'BillingAddress';
        } elseif (is_a($this, Object::getCustomClass('ShippingAddress'))) {
            return 'ShippingAddress';
        }
    }

    /**
     * returns the field prefix string for shipping addresses.
     *
     * @return string
     **/
    protected function fieldPrefix()
    {
        if ($this->baseClassLinkingToOrder() == Object::getCustomClass('BillingAddress')) {
            return '';
        } else {
            return 'Shipping';
        }
    }

    /**
     *@todo: are there times when the Shipping rather than the Billing address should be linked?
     * Copies the last address used by the member.
     *
     * @param object (Member) $member
     * @param bool            $write  - should the address be written
     *
     * @return OrderAddress | ShippingAddress | BillingAddress
     **/
    public function FillWithLastAddressFromMember(Member $member, $write = false)
    {
        $excludedFields = array('ID', 'OrderID');
        $fieldPrefix = $this->fieldPrefix();
        if ($member && $member->exists()) {
            $oldAddress = $member->previousOrderAddress($this->baseClassLinkingToOrder(), $this->ID);
            if ($oldAddress) {
                $fieldNameArray = array_keys($this->Config()->get('db')) + array_keys($this->Config()->get('has_one'));
                foreach ($fieldNameArray as $field) {
                    if (in_array($field, $excludedFields)) {
                        //do nothing
                    } elseif ($this->$field) {
                        //do nothing
                    } elseif (isset($oldAddress->$field)) {
                        $this->$field = $oldAddress->$field;
                    }
                }
            }
            //copy data from  member
            if (is_a($this, Object::getCustomClass('BillingAddress'))) {
                $this->Email = $member->Email;
            }
            $fieldNameArray = array('FirstName' => $fieldPrefix.'FirstName', 'Surname' => $fieldPrefix.'Surname');
            foreach ($fieldNameArray as $memberField => $fieldName) {
                //NOTE, we always override the Billing Address (which does not have a fieldPrefix)
                if (!$this->$fieldName || (is_a($this, Object::getCustomClass('BillingAddress')))) {
                    $this->$fieldName = $member->$memberField;
                }
            }
        }
        if ($write) {
            $this->write();
        }

        return $this;
    }

    /**
     * find the member associated with the current Order and address.
     *
     * @Note: this needs to be public to give DODS (extensions access to this)
     * @todo: can wre write $this->Order() instead????
     *
     * @return DataObject (Member) | Null
     **/
    public function getMemberFromOrder()
    {
        if ($this->exists()) {
            if ($order = $this->Order()) {
                if ($order->exists()) {
                    if ($order->MemberID) {
                        return Member::get()->byID($order->MemberID);
                    }
                }
            }
        }
    }

    /**
     * make an address obsolete and include all the addresses that are identical.
     *
     * @param Member $member
     */
    public function MakeObsolete(Member $member = null)
    {
        $addresses = $member->previousOrderAddresses($this->baseClassLinkingToOrder(), $this->ID, $onlyLastRecord = false, $keepDoubles = true);
        $comparisonString = $this->comparisonString();
        if ($addresses->count()) {
            foreach ($addresses as $address) {
                if ($address->comparisonString() == $comparisonString) {
                    $address->Obsolete = 1;
                    $address->write();
                }
            }
        }
        $this->Obsolete = 1;
        $this->write();
    }

    /**
     * standard SS method
     * We "hackishly" ensure that the OrderID is set to the right value.
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if ($this->exists()) {
            $order = Order::get()
                ->filter(array($this->ClassName.'ID' => $this->ID))
                ->First();
            if ($order && $order->ID != $this->OrderID) {
                $this->OrderID = $order->ID;
                $this->write();
            }
        }
    }

    /**
     * returns the link that can be used to remove (make Obsolete) an address.
     *
     * @return string
     */
    public function RemoveLink()
    {
        return ShoppingCart_Controller::remove_address_link($this->ID, $this->ClassName);
    }

    /**
     * converts an address into JSON.
     *
     * @return string (JSON)
     */
    public function getJSONData()
    {
        return $this->JSONData();
    }
    public function JSONData()
    {
        $jsArray = array();
        if (!isset($fields)) {
            $fields = $this->stat('db');
            $regionFieldName = $this->fieldPrefix().'RegionID';
            $fields[$regionFieldName] = $regionFieldName;
        }
        if ($fields) {
            foreach ($fields as $name => $field) {
                $jsArray[$name] = $this->$name;
            }
        }

        return Convert::array2json($jsArray);
    }

    /**
     * returns the instance of EcommerceDBConfig.
     *
     * @return EcommerceDBConfig
     **/
    public function EcomConfig()
    {
        return EcommerceDBConfig::current_ecommerce_db_config();
    }

    /**
     * standard SS Method
     * saves the region code.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $fieldPrefix = $this->fieldPrefix();
        $idField = $fieldPrefix.'RegionID';
        if ($this->$idField) {
            $region = EcommerceRegion::get()->byID($this->$idField);
            if ($region) {
                $codeField = $fieldPrefix.'RegionCode';
                $this->$codeField = $region->Code;
            }
        }
    }

    public function debug()
    {
        return EcommerceTaskDebugCart::debug_object($this);
    }
}
