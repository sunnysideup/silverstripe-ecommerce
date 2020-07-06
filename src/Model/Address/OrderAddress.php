<?php

namespace Sunnysideup\Ecommerce\Model\Address;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\View\SSViewer;
use Sunnysideup\CmsEditLinkField\Api\CMSEditLinkAPI;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Control\ShoppingCartController;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;
use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Tasks\EcommerceTaskDebugCart;

/**
 * @description: each order has an address: a Shipping and a Billing address
 * This is a base-class for both.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: address

 **/
class OrderAddress extends DataObject implements EditableEcommerceObject
{
    /**
     * There might be times when a modifier needs to make an address field read-only.
     * In that case, this is done here.
     *
     * @var array
     */
    protected $readOnlyFields = [];

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

    /**
     * @var bool
     */
    private static $use_separate_shipping_address = false;

    /**
     * @var bool
     */
    private static $use_shipping_address_for_main_region_and_country = false;

    /**
     * @var string
     */
    private static $field_class_and_id_prefix = '';

    /**
     * standard SS static definition.
     */
    private static $singular_name = 'Order Address';

    /**
     * standard SS static definition.
     */
    private static $plural_name = 'Order Addresses';

    /**
     * standard SS static definition.
     */
    private static $table_name = 'OrderAddress';

    /**
     * standard SS static definition.
     */
    private static $casting = [
        'FullName' => 'Text',
        'FullString' => 'Text',
        'JSONData' => 'Text',
    ];

    public function i18n_singular_name()
    {
        return _t('OrderAddress.ORDERADDRESS', 'Order Address');
    }

    public function i18n_plural_name()
    {
        return _t('OrderAddress.ORDERADDRESSES', 'Order Addresses');
    }

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
        if (EcommerceConfig::get(OrderAddress::class, 'use_shipping_address_for_main_region_and_country')) {
            return 'ShippingCountry';
        }
        return 'Country';
    }

    /**
     * returns the id of the MAIN region field for template manipulation.
     * Main means the one that is used as the primary one (e.g. for tax purposes).
     *
     * @return string
     */
    public static function get_region_field_ID()
    {
        if (EcommerceConfig::get(OrderAddress::class, 'use_shipping_address_for_main_region_and_country')) {
            return 'ShippingRegion';
        }
        return 'Region';
    }

    /**
     * sets a field to readonly state
     * we use this when modifiers have been set that require a field to be a certain value
     * for example - a PostalCode field maybe set in the modifier.
     *
     * @param string $fieldName
     */
    public function addReadonlyField($fieldName)
    {
        $this->readOnlyFields[$fieldName] = $fieldName;
    }

    /**
     * removes a field from the readonly state.
     *
     * @param string $fieldName
     */
    public function removeReadonlyField($fieldName)
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
        return CMSEditLinkAPI::find_edit_link_for_object($this, $action);
    }

    public function canCreate($member = null, $context = [])
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * Standard SS method
     * This is an important method.
     *
     * @param \SilverStripe\Security\Member $member
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
        if (! $this->exists()) {
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
     * @param \SilverStripe\Security\Member $member
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
     * @return \SilverStripe\Forms\FieldList
     */
    public function scaffoldSearchFields($_params = null)
    {
        $fields = parent::scaffoldSearchFields($_params);
        $fields->replaceField('OrderID', NumericField::create('OrderID', 'Order Number'));
        return $fields;
    }

    /**
     * Saves region - both shipping and billing fields are saved here for convenience sake (only one actually gets saved)
     * NOTE: do not call this method SetCountry as this has a special meaning! *.
     *
     * @param int $regionID -  RegionID
     **/
    public function SetRegionFields($regionID)
    {
        $regionField = $this->fieldPrefix() . 'RegionID';
        $this->{$regionField} = $regionID;
        $this->write();
    }

    /**
     * Saves country - both shipping and billing fields are saved here for convenience sake (only one actually gets saved)
     * NOTE: do not call this method SetCountry as this has a special meaning!
     *
     * @param string $countryCode - CountryCode - e.g. NZ
     */
    public function SetCountryFields($countryCode)
    {
        $countryField = $this->fieldPrefix() . 'Country';
        $this->{$countryField} = $countryCode;
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
        $fieldNameField = $this->fieldPrefix() . 'FirstName';
        $fieldFirst = $this->{$fieldNameField};
        $lastNameField = $this->fieldPrefix() . 'Surname';
        $fieldLast = $this->{$lastNameField};

        return $fieldFirst . ' ' . $fieldLast;
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
        Config::modify()->update(SSViewer::class, 'theme_enabled', true);
        $html = $this->RenderWith('Sunnysideup\Ecommerce\Includes\Order_Address' . str_replace('Address', '', $this->ClassName) . 'FullString');
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
        $excludedFields = ['ID', 'OrderID'];
        $fields = $this->stat('db');
        $regionFieldName = $this->fieldPrefix() . 'RegionID';
        $fields[$regionFieldName] = $regionFieldName;
        if ($fields) {
            foreach (array_keys($fields) as $field) {
                if (! in_array($field, $excludedFields, true)) {
                    $comparisonString .= preg_replace('/\s+/', '', $this->{$field});
                }
            }
        }

        return strtolower(trim($comparisonString));
    }

    /**
     *@todo: are there times when the Shipping rather than the Billing address should be linked?
     * Copies the last address used by the member.
     *
     * @param SilverStripe\Security\Member $member
     * @param bool            $write  - should the address be written
     *
     * @return OrderAddress | ShippingAddress | BillingAddress
     **/
    public function FillWithLastAddressFromMember(Member $member, $write = false)
    {
        $excludedFields = ['ID', 'OrderID'];
        $fieldPrefix = $this->fieldPrefix();
        if ($member && $member->exists()) {
            $oldAddress = $member->previousOrderAddress($this->baseClassLinkingToOrder(), $this->ID);
            if ($oldAddress) {
                $fieldNameArray = array_keys($this->Config()->get('db')) + array_keys($this->Config()->get('has_one'));
                foreach ($fieldNameArray as $field) {
                    if (in_array($field, $excludedFields, true)) {
                        //do nothing
                    } elseif ($this->{$field}) {
                        //do nothing
                    } elseif (isset($oldAddress->{$field})) {
                        $this->{$field} = $oldAddress->{$field};
                    }
                }
            }
            //copy data from  member
            if (is_a($this, EcommerceConfigClassNames::getName(BillingAddress::class))) {
                $this->Email = $member->Email;
            }
            $fieldNameArray = ['FirstName' => $fieldPrefix . 'FirstName', 'Surname' => $fieldPrefix . 'Surname'];
            foreach ($fieldNameArray as $memberField => $fieldName) {
                //NOTE, we always override the Billing Address (which does not have a fieldPrefix)

                if (! $this->{$fieldName} || (is_a($this, EcommerceConfigClassNames::getName(BillingAddress::class)))) {
                    $this->{$fieldName} = $member->{$memberField};
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
     * @return \SilverStripe\ORM\DataObject (Member) | Null
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
     * @param \SilverStripe\Security\Member $member
     */
    public function MakeObsolete(Member $member = null)
    {
        $addresses = $member->previousOrderAddresses($this->baseClassLinkingToOrder(), $this->ID, $onlyLastRecord = false, $keepDoubles = true);
        $comparisonString = $this->comparisonString();
        if ($addresses->count()) {
            foreach ($addresses as $address) {
                if ($address->comparisonString() === $comparisonString) {
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
            $order = DataObject::get_one(
                Order::class,
                [Config::inst()->get($this->ClassName, 'table_name') . 'ID' => $this->ID],
                $cacheDataObjectGetOne = false
            );
            if ($order && $order->ID !== $this->OrderID) {
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
        return ShoppingCartController::remove_address_link($this->ID, $this->ClassName);
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
        $jsArray = [];
        $fields = $this->stat('db');
        $regionFieldName = $this->fieldPrefix() . 'RegionID';
        $fields[$regionFieldName] = $regionFieldName;

        if ($fields) {
            foreach (array_keys($fields) as $name) {
                $jsArray[$name] = $this->{$name};
            }
        }

        return Convert::array2json($jsArray);
    }

    /**
     * returns the instance of EcommerceDBConfig.
     *
     * @return EcommerceDBConfig | Object
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
        $idField = $fieldPrefix . 'RegionID';
        if ($this->{$idField}) {
            $region = EcommerceRegion::get()->byID($this->{$idField});
            if ($region) {
                $codeField = $fieldPrefix . 'RegionCode';
                $this->{$codeField} = $region->Code;
            }
        }
    }

    public function debug()
    {
        return EcommerceTaskDebugCart::debug_object($this);
    }

    /**
     * @return \SilverStripe\Forms\FieldList
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
            $prefix = EcommerceConfig::get(OrderAddress::class, 'field_class_and_id_prefix');
            $field->setRightTitle('<a href="' . $postalCodeURL . '" id="' . $prefix . $name . 'Link" class="' . $prefix . 'postalCodeLink">' . $postalCodeLabel . '</a>');
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
            $title = _t('OrderAddress.' . strtoupper($nameWithoutID), 'Region / Province / State');
            $regionsForDropdown = EcommerceRegion::list_of_allowed_entries_for_dropdown();
            $count = count($regionsForDropdown);
            if ($count < 1) {
                if (! $freeTextName) {
                    $freeTextName = $nameWithoutID . 'Code';
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
        $prefix = EcommerceConfig::get(OrderAddress::class, 'field_class_and_id_prefix');
        $regionField->addExtraClass($prefix . 'ajaxRegionField');

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
        $title = _t('OrderAddress.' . strtoupper($name), 'Country');
        $order = $this->Order();

        $countryCode = null;
        if ($order && $order->exists()) {
            //if it is the billing country field and we use a shipping address then ignore Order Country
            if ($order->UseShippingAddress && ($this instanceof BillingAddress)) {
                //do nothing
            } else {
                $countryCode = EcommerceCountry::get_country(false, $this->OrderID);
            }
        }
        $countryField = new DropdownField($name, $title, $countriesForDropdown, $countryCode);
        $countryField->setRightTitle(_t('OrderAddress.' . strtoupper($name) . '_RIGHT', ' '));
        if (count($countriesForDropdown) < 2) {
            $countryField = $countryField->performReadonlyTransformation();
            if (count($countriesForDropdown) < 1) {
                $countryField = new HiddenField($name, '', 'not available');
            }
        }
        $prefix = EcommerceConfig::get(OrderAddress::class, 'field_class_and_id_prefix');
        $countryField->addExtraClass($prefix . 'ajaxCountryField');
        //important, otherwise loadData will override the default value....
        if ($countryCode) {
            $this->{$name} = $countryCode;
        }

        return $countryField;
    }

    /**
     * makes selected fields into read only using the $this->readOnlyFields array.
     *
     * @param FieldList | \SilverStripe\Forms\CompositeField $fields
     *
     * @return \SilverStripe\Forms\FieldList
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
     * returns the field prefix string for shipping addresses.
     *
     * @return string
     **/
    protected function baseClassLinkingToOrder()
    {
        if (is_a($this, EcommerceConfigClassNames::getName(BillingAddress::class))) {
            return BillingAddress::class;
        } elseif (is_a($this, EcommerceConfigClassNames::getName(ShippingAddress::class))) {
            return ShippingAddress::class;
        }
    }

    /**
     * returns the field prefix string for shipping addresses.
     *
     * @return string
     **/
    protected function fieldPrefix()
    {
        if ($this->baseClassLinkingToOrder() === EcommerceConfigClassNames::getName(BillingAddress::class)) {
            return '';
        }
        return 'Shipping';
    }
}
