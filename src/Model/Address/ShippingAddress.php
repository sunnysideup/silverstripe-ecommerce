<?php

namespace Sunnysideup\Ecommerce\Model\Address;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\Fields\SelectOrderAddressField;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\GoogleAddressField\GoogleAddressField;

/**
 * @description: each order has a shipping address.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: address

 **/
class ShippingAddress extends OrderAddress
{
    /**
     * what variables are accessible through  http://mysite.com/api/ecommerce/v1/ShippingAddress/.
     *
     * @var array
     */
    private static $api_access = [
        'view' => [
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
        ],
    ];

    /**
     * @var bool
     */
    private static $allow_selection_of_previous_addresses_in_checkout = false;

    /**
     * standard SS variable.
     */
    private static $table_name = 'ShippingAddress';

    private static $db = [
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
    ];

    /**
     * standard SS static definition.
     **/
    private static $has_one = [
        'ShippingRegion' => EcommerceRegion::class,
    ];

    /**
     * standard SS static definition.
     **/
    private static $belongs_to = [
        'Order' => Order::class,
    ];

    /**
     * standard SS static definition.
     */
    private static $default_sort = [
        'ID' => 'DESC',
    ];

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $indexes = [
        'Obsolete' => true,
        'OrderID' => true,
        'ShippingCountry' => true,
    ];

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $casting = [
        'ShippingFullCountryName' => 'Varchar(200)',
    ];

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $searchable_fields = [
        'OrderID' => [
            'field' => NumericField::class,
            'title' => 'Order Number',
        ],
        'ShippingSurname' => 'PartialMatchFilter',
        'ShippingAddress' => 'PartialMatchFilter',
        'ShippingCity' => 'PartialMatchFilter',
        'ShippingCountry' => 'PartialMatchFilter',
        'Obsolete',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $required_fields = [
        'ShippingPhone',
        'ShippingAddress',
        'ShippingCity',
        'ShippingPostalCode',
        'ShippingCountry',
    ];

    /**
     * standard SS variable.
     *
     * @return array
     */
    private static $summary_fields = [
        'Order.Title',
        'ShippingFirstName',
        'ShippingSurname',
        'ShippingCity',
        'ShippingPostalCode',
        'ShippingCountry',
        'ShippingPhone',
    ];

    /**
     * standard SS variable.
     *
     * @return string
     */
    private static $singular_name = 'Shipping Address';

    /**
     * standard SS variable.
     *
     * @return string
     */
    private static $plural_name = 'Shipping Addresses';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'The address for delivery of the order.';

    public function fieldLabels($includerelations = true)
    {
        $billingAddress = Injector::inst()->get(BillingAddress::class);
        $shippingLabels = parent::fieldLabels($includerelations);
        $billingLabels = $billingAddress->fieldLabels($includerelations);
        $summaryFields = $this->stat('field_labels');
        foreach (array_keys($shippingLabels) as $shippingKey) {
            if (! isset($summaryFields[$shippingKey])) {
                $billingKey = str_replace('Shipping', '', $shippingKey);
                if (isset($billingLabels[$billingKey])) {
                    $shippingLabels[$shippingKey] = $billingLabels[$billingKey];
                }
            }
        }

        return $shippingLabels;
    }

    public function i18n_singular_name()
    {
        return _t('ShippingAddress.SHIPPINGADDRESS', 'Shipping Address');
    }

    public function i18n_plural_name()
    {
        return _t('ShippingAddress.SHIPPINGADDRESSES', 'Shipping Addresses');
    }

    /**
     *@return \SilverStripe\Forms\FieldList
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
     * @param \SilverStripe\Security\Member $member
     *
     * @return \SilverStripe\Forms\FieldList
     **/
    public function getFields(Member $member = null)
    {
        $fields = parent::getEcommerceFields();
        if (EcommerceConfig::get(OrderAddress::class, 'use_separate_shipping_address')) {
            $shippingFieldsHeader = new CompositeField(
                new HeaderField('SendGoodsToADifferentAddress', _t('ShippingAddress.SENDGOODSTODIFFERENTADDRESS', 'Delivery Address'), 3),
                new LiteralField('ShippingNote', '<p class="message warning" id="ShippingNote">' . _t('ShippingAddress.SHIPPINGNOTE', 'Your goods will be sent to the address below.') . '</p>')
            );

            if ($member && Member::currentUser()) {
                if ($member->exists() && ! $member->IsShopAdmin()) {
                    $this->FillWithLastAddressFromMember($member, true);
                    if (EcommerceConfig::get(ShippingAddress::class, 'allow_selection_of_previous_addresses_in_checkout')) {
                        $addresses = $member->previousOrderAddresses($this->baseClassLinkingToOrder(), $this->ID, $onlyLastRecord = false, $keepDoubles = false);
                        //we want MORE than one here not just one.
                        if ($addresses->count() > 1) {
                            $shippingFieldsHeader->push(SelectOrderAddressField::create('SelectShippingAddressField', _t('ShippingAddress.SELECTBILLINGADDRESS', 'Select Shipping Address'), $addresses));
                        }
                    }
                }
                $shippingFields = new CompositeField(
                    new TextField('ShippingFirstName', _t('ShippingAddress.FIRSTNAME', 'First Name')),
                    new TextField('ShippingSurname', _t('ShippingAddress.SURNAME', 'Surname'))
                );
            } else {
                $shippingFields = new CompositeField(
                    new TextField('ShippingFirstName', _t('ShippingAddress.FIRSTNAME', 'First Name')),
                    new TextField('ShippingSurname', _t('ShippingAddress.SURNAME', 'Surname'))
                );
            }
            $shippingFields->push(new TextField('ShippingPhone', _t('ShippingAddress.PHONE', 'Phone')));
            $mappingArray = $this->Config()->get('fields_to_google_geocode_conversion');
            if (is_array($mappingArray) && count($mappingArray)) {
                if (! class_exists('GoogleAddressField')) {
                    user_error('You must install the Sunny Side Up google_address_field module OR remove entries from: ShippingAddress.fields_to_google_geocode_conversion');
                }
                $shippingFields->push(
                    $shippingEcommerceGeocodingField = new GoogleAddressField(
                        'ShippingEcommerceGeocodingField',
                        _t('ShippingAddress.Find_Address', 'Find address'),
                        Controller::curr()->getRequest()->getSession()->get('ShippingEcommerceGeocodingFieldValue')
                    )
                );
                $shippingEcommerceGeocodingField->setFieldMap($mappingArray);
                //$shippingFields->push(new HiddenField('ShippingAddress2'));
                //$shippingFields->push(new HiddenField('ShippingCity'));
            }

            $shippingFields->push(new TextField(ShippingAddress::class, _t('ShippingAddress.ADDRESS', 'Address')));
            $shippingFields->push(new TextField('ShippingAddress2', _t('ShippingAddress.ADDRESS2', '')));
            $shippingFields->push(new TextField('ShippingCity', _t('ShippingAddress.CITY', 'Town')));
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
