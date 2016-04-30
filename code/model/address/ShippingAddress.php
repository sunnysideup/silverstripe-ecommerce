<?php

/**
 * @description: each order has a shipping address.
 *
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: address
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class ShippingAddress extends OrderAddress
{
    /**
     * what variables are accessible through  http://mysite.com/api/ecommerce/v1/ShippingAddress/.
     *
     * @var array
     */
    private static $api_access = array(
        'view' => array(
            'ShippingPrefix',
            'ShippingFirstName',
            'ShippingSurname',
            'ShippingAddress',
            'ShippingAddress2',
            'ShippingCity',
            'ShippingPostalCode',
            'ShippingRegionCode',
            'ShippingCountry',
            'ShippingPhone',
        ),
    );

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $db = array(
        'ShippingPrefix' => 'Varchar(10)',
        'ShippingFirstName' => 'Varchar(100)',
        'ShippingSurname' => 'Varchar(100)',
        'ShippingAddress' => 'Varchar(200)',
        'ShippingAddress2' => 'Varchar(255)',
        'ShippingCity' => 'Varchar(100)',
        'ShippingPostalCode' => 'Varchar(30)',
        'ShippingRegionCode' => 'Varchar(100)',
        'ShippingCountry' => 'Varchar(4)',
        'ShippingPhone' => 'Varchar(100)',
        'Obsolete' => 'Boolean',
        'OrderID' => 'Int', ////NOTE: we have this here for faster look-ups and to make addresses behave similar to has_many dataobjects
    );

    /**
     * standard SS static definition.
     **/
    private static $has_one = array(
        'ShippingRegion' => 'EcommerceRegion',
    );

    /**
     * standard SS static definition.
     **/
    private static $belongs_to = array(
        'Order' => 'Order',
    );

    /**
     * standard SS static definition.
     */
    private static $default_sort = '"ShippingAddress"."ID" DESC';

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $indexes = array(
        'Obsolete' => true,
        'OrderID' => true,
    );

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $casting = array(
        'ShippingFullCountryName' => 'Varchar(200)',
    );

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $searchable_fields = array(
        'OrderID' => array(
            'field' => 'NumericField',
            'title' => 'Order Number',
        ),
        'ShippingSurname' => 'PartialMatchFilter',
        'ShippingAddress' => 'PartialMatchFilter',
        'ShippingCity' => 'PartialMatchFilter',
        'ShippingCountry' => 'PartialMatchFilter',
        'Obsolete',
    );

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $summary_fields = array(
        'Order.Title',
        'Surname',
        'City',
    );

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $field_labels = array(
        'Order.Title' => 'Order',
        'Obsolete' => 'Do not use for future transactions',
    );

    /**
     * standard SS variable.
     *
     * @return string
     */
    private static $singular_name = 'Shipping Address';
    public function i18n_singular_name()
    {
        return _t('OrderAddress.SHIPPINGADDRESS', 'Shipping Address');
    }

    /**
     * standard SS variable.
     *
     * @return string
     */
    private static $plural_name = 'Shipping Addresses';
    public function i18n_plural_name()
    {
        return _t('OrderAddress.SHIPPINGADDRESSES', 'Shipping Addresses');
    }

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'The address for delivery of the order.';

    /**
     *@return FieldList
     **/
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField('OrderID', new ReadonlyField('OrderID'));

        return $fields;
    }

    /**
     * returns the full name for the shipping country code saved.
     *
     * @return string
     **/
    public function ShippingFullCountryName()
    {
        return $this->getShippingFullCountryName();
    }
    public function getShippingFullCountryName()
    {
        return EcommerceCountry::find_title($this->ShippingCountry);
    }

    /**
     * Puts together the fields for the Order Form (and other front-end purposes).
     *
     * @param Member $member
     *
     * @return FieldList
     **/
    public function getFields(Member $member = null)
    {
        $fields = parent::getEcommerceFields();
        $hasPreviousAddresses = false;
        if (EcommerceConfig::get('OrderAddress', 'use_separate_shipping_address')) {
            $shippingFieldsHeader = new CompositeField(
                new HeaderField('SendGoodsToADifferentAddress', _t('OrderAddress.SENDGOODSTODIFFERENTADDRESS', 'Send goods to different address'), 3),
                new LiteralField('ShippingNote', '<p class="message warning" id="ShippingNote">'._t('OrderAddress.SHIPPINGNOTE', 'Your goods will be sent to the address below.').'</p>')
            );

            if ($member) {
                if ($member->exists() && !$member->IsShopAdmin()) {
                    $this->FillWithLastAddressFromMember($member, true);
                    $addresses = $member->previousOrderAddresses($this->baseClassLinkingToOrder(), $this->ID, $onlyLastRecord = false, $keepDoubles = false);
                    //we want MORE than one here not just one.
                    if ($addresses->count() > 1) {
                        $hasPreviousAddresses = true;
                        $shippingFieldsHeader->push(SelectOrderAddressField::create('SelectShippingAddressField', _t('OrderAddress.SELECTBILLINGADDRESS', 'Select Shipping Address'), $addresses));
                    }
                }
                $shippingFields = new CompositeField(
                    new TextField('ShippingFirstName', _t('OrderAddress.FIRSTNAME', 'First Name')),
                    new TextField('ShippingSurname', _t('OrderAddress.SURNAME', 'Surname'))
                );
            } else {
                $shippingFields = new CompositeField(
                    new TextField('ShippingFirstName', _t('OrderAddress.FIRSTNAME', 'First Name')),
                    new TextField('ShippingSurname', _t('OrderAddress.SURNAME', 'Surname'))
                );
            }
            $shippingFields->push(new TextField('ShippingPhone', _t('OrderAddress.PHONE', 'Phone')));
            //$shippingFields->push(new TextField('ShippingMobilePhone', _t('OrderAddress.MOBILEPHONE','Mobile Phone')));
            $mappingArray = $this->Config()->get('fields_to_google_geocode_conversion');
            if (is_array($mappingArray) && count($mappingArray)) {
                if (!class_exists('GoogleAddressField')) {
                    user_error('You must install the Sunny Side Up google_address_field module OR remove entries from: ShippingAddress.fields_to_google_geocode_conversion');
                }
                $shippingFields->push(
                    $shippingEcommerceGeocodingField = new GoogleAddressField(
                        'ShippingEcommerceGeocodingField',
                        _t('OrderAddress.Find_Address', 'Find address'),
                        Session::get('ShippingEcommerceGeocodingFieldValue')
                    )
                );
                $shippingEcommerceGeocodingField->setFieldMap($mappingArray);
                //$shippingFields->push(new HiddenField('ShippingAddress2'));
                //$shippingFields->push(new HiddenField('ShippingCity'));
            } else {
            }
            //$shippingFields->push(new TextField('ShippingPrefix', _t('OrderAddress.PREFIX','Title (e.g. Ms)')));
            $shippingFields->push(new TextField('ShippingAddress', _t('OrderAddress.ADDRESS', 'Address')));
            $shippingFields->push(new TextField('ShippingAddress2', _t('OrderAddress.ADDRESS2', '')));
            $shippingFields->push(new TextField('ShippingCity', _t('OrderAddress.CITY', 'Town')));
            $shippingFields->push($this->getRegionField('ShippingRegionID', 'ShippingRegionCode'));
            $shippingFields->push($this->getPostalCodeField('ShippingPostalCode'));
            $shippingFields->push($this->getCountryField('ShippingCountry'));
            $this->makeSelectedFieldsReadOnly($shippingFields);
            $shippingFieldsHeader->addExtraClass('shippingFieldsHeader');
            $shippingFields->addExtraClass('orderAddressHolder');
            $fields->push($shippingFieldsHeader);
            $shippingFields->addExtraClass('shippingFields');
            $fields->push($shippingFields);
        }
        $this->extend('augmentEcommerceShippingAddressFields', $shippingFields);

        return $fields;
    }

    /**
     * Return which shipping fields should be required on {@link OrderFormAddress}.
     *
     * @return array
     */
    public function getRequiredFields()
    {
        return $this->Config()->get('required_fields');
    }
}
