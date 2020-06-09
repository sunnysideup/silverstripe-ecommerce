<?php

namespace Sunnysideup\Ecommerce\Model\Money;

use CMSEditLinkAPI;


use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBCurrency;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Control\ShoppingCartController;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Money\EcommerceMoney;
use Sunnysideup\Ecommerce\Tasks\EcommerceTaskDebugCart;

/**
 * Object to manage currencies.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: money
 * Precondition : There should always be at least one currency usable.
 **/
class EcommerceCurrency extends DataObject implements EditableEcommerceObject
{
    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $table_name = 'EcommerceCurrency';

    private static $db = [
        'Code' => 'Varchar(3)',
        'Name' => 'Varchar(100)',
        'InUse' => 'Boolean',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $indexes = [
        'Code' => true,
        'InUse' => true,
        'Name' => true,
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $casting = [
        'IsDefault' => 'Boolean',
        'IsDefaultNice' => 'Varchar',
        'InUseNice' => 'Varchar',
        'ExchangeRate' => 'Double',
        'DefaultSymbol' => 'Varchar',
        'ShortSymbol' => 'Varchar',
        'LongSymbol' => 'Varchar',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $searchable_fields = [
        'Code' => 'PartialMatchFilter',
        'Name' => 'PartialMatchFilter',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $field_labels = [
        'Code' => 'Short Code',
        'Name' => 'Name',
        'InUse' => 'It is available for use?',
        'ExchangeRate' => 'Exchange Rate',
        'ExchangeRateExplanation' => 'Exchange Rate explanation',
        'IsDefaultNice' => 'Is default currency for site',
        'DefaultSymbol' => 'Default symbol',
        'ShortSymbol' => 'Short symbol',
        'LongSymbol' => 'Long symbol',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $summary_fields = [
        'Code' => 'Code',
        'Name' => 'Name',
        'InUseNice' => 'Available',
        'IsDefaultNice' => 'Default Currency',
        'ExchangeRate' => 'Exchange Rate',
    ]; //note no => for relational fields

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Currency';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Currencies';

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $default_sort = [
        'InUse' => 'DESC',
        'Name' => 'ASC',
        'Code' => 'ASC',
        'ID' => 'DESC',
    ];

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $defaults = [
        'InUse' => true,
    ];

    private static $currencies = [
        'AFA' => 'afghanistan afghanis',
        'ALL' => 'albania leke',
        'DZD' => 'algeria dinars',
        'ARS' => 'argentina pesos',
        'AUD' => 'australia dollars',
        'ATS' => 'austria schillings*',
        'BSD' => 'bahamas dollars',
        'BHD' => 'bahrain dinars',
        'BDT' => 'bangladesh taka',
        'BBD' => 'barbados dollars',
        'BEF' => 'belgium francs*',
        'BMD' => 'bermuda dollars',
        'BRL' => 'brazil reais',
        'BGN' => 'bulgaria leva',
        'CAD' => 'canada dollars',
        'XOF' => 'cfa bceao francs',
        'XAF' => 'cfa beac francs',
        'CLP' => 'chile pesos',
        'CNY' => 'china yuan renminbi',
        'COP' => 'colombia pesos',
        'CRC' => 'costa rica colones',
        'HRK' => 'croatia kuna',
        'CYP' => 'cyprus pounds',
        'CZK' => 'czech republic koruny',
        'DKK' => 'denmark kroner',
        'DOP' => 'dominican republic pesos',
        'XCD' => 'eastern caribbean dollars',
        'EGP' => 'egypt pounds',
        'EEK' => 'estonia krooni',
        'EUR' => 'euro',
        'FJD' => 'fiji dollars',
        'DEM' => 'germany deutsche marks*',
        'XAU' => 'gold ounces',
        'NLG' => 'holland (netherlands) guilders*',
        'HKD' => 'hong kong dollars',
        'HUF' => 'hungary forint',
        'ISK' => 'iceland kronur',
        'XDR' => 'imf special drawing right',
        'INR' => 'india rupees',
        'IDR' => 'indonesia rupiahs',
        'IRR' => 'iran rials',
        'IQD' => 'iraq dinars',
        'ILS' => 'israel new shekels',
        'JMD' => 'jamaica dollars',
        'JPY' => 'japan yen',
        'JOD' => 'jordan dinars',
        'KES' => 'kenya shillings',
        'KRW' => 'korea (south) won',
        'KWD' => 'kuwait dinars',
        'LBP' => 'lebanon pounds',
        'MYR' => 'malaysia ringgits',
        'MTL' => 'malta liri',
        'MUR' => 'mauritius rupees',
        'MXN' => 'mexico pesos',
        'MAD' => 'morocco dirhams',
        'NZD' => 'new zealand dollars',
        'NOK' => 'norway kroner',
        'OMR' => 'oman rials',
        'PKR' => 'pakistan rupees',
        'XPD' => 'palladium ounces',
        'PEN' => 'peru nuevos soles',
        'PHP' => 'philippines pesos',
        'PLN' => 'poland zlotych',
        'QAR' => 'qatar riyals',
        'ROL' => 'romania lei',
        'RUB' => 'russia rubles',
        'SAR' => 'saudi arabia riyals',
        'XAG' => 'silver ounces',
        'SGD' => 'singapore dollars',
        'SKK' => 'slovakia koruny',
        'SIT' => 'slovenia tolars',
        'ZAR' => 'south africa rand',
        'LKR' => 'sri lanka rupees',
        'SDD' => 'sudan dinars',
        'SEK' => 'sweden kronor',
        'CHF' => 'switzerland francs',
        'TWD' => 'taiwan new dollars',
        'THB' => 'thailand baht',
        'TTD' => 'trinidad and tobago dollars',
        'TND' => 'tunisia dinars',
        'TRY' => 'turkey new lira',
        'AED' => 'united arab emirates dirhams',
        'gbp' => 'united kingdom pounds',
        'USD' => 'united states dollars',
        'VEB' => 'venezuela bolivares',
        'VND' => 'vietnam dong',
        'ZMK' => 'zambia kwacha',
    ];

    public function i18n_singular_name()
    {
        return _t('EcommerceCurrency.CURRENCY', 'Currency');
    }

    public function i18n_plural_name()
    {
        return _t('EcommerceCurrency.CURRENCIES', 'Currencies');
    }

    /**
     * Standard SS Method.
     *
     * @param Member $member
     *
     * @var bool
     */
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
     * Standard SS Method.
     *
     * @param Member $member
     *
     * @var bool
     */
    public function canView($member = null, $context = [])
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
     * Standard SS Method.
     *
     * @param Member $member
     *
     * @var bool
     */
    public function canEdit($member = null, $context = [])
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
     * Standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canDelete($member = null, $context = [])
    {
        if (! $this->InUse && EcommerceCurrency::get()->Count() > 1) {
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

        return false;
    }

    /**
     * NOTE: when there is only one currency we return an empty DataList
     * as one currency is meaningless.
     *
     * @return \SilverStripe\ORM\DataList | null
     */
    public static function ecommerce_currency_list()
    {
        $dos = EcommerceCurrency::get()
            ->Filter(['InUse' => 1])
            ->Sort(
                [
                    "IF(\"Code\" = '" . strtoupper(EcommerceConfig::get(EcommerceCurrency::class, 'default_currency')) . "', 0, 1)" => 'ASC',
                    'Name' => 'ASC',
                    'Code' => 'ASC',
                ]
            );
        if ($dos->count() < 2) {
            return null;
        }

        return $dos;
    }

    public static function get_list()
    {
        return EcommerceCurrency::get()
            ->filter(['InUse' => 1])
            ->sort(
                [
                    "IF(\"Code\" = '" . EcommerceConfig::get(EcommerceCurrency::class, 'default_currency') . "', 0, 1)" => 'ASC',
                    'Name' => 'ASC',
                    'Code' => 'ASC',
                ]
            );
    }

    /**
     * @param float | DBCurrency $price
     * @param Order $order
     *
     * @return \SilverStripe\ORM\FieldType\DBMoney | \SilverStripe\ORM\FieldType\DBField
     */
    public static function get_money_object_from_order_currency($price, Order $order = null)
    {
        if ($price instanceof DBCurrency) {
            $price = $price->getValue();
        }
        if (! $order) {
            $order = ShoppingCart::current_order();
        }
        $currency = $order->CurrencyUsed();
        $currencyCode = $currency->Code;
        if ($order) {
            if ($order->HasAlternativeCurrency()) {
                $exchangeRate = $order->ExchangeRate;
                if ($exchangeRate && $exchangeRate !== 1) {
                    $price = $exchangeRate * $price;
                }
            }
        }

        $updatedCurrencyCode = Injector::inst()->get(EcommerceCurrency::class)->extend('updateCurrencyCodeForMoneyObect', $currencyCode);
        if ($updatedCurrencyCode !== null && is_array($updatedCurrencyCode) && count($updatedCurrencyCode)) {
            $currencyCode = $updatedCurrencyCode[0];
        }

        return DBField::create_field(
            'Money',
            [
                'Amount' => $price,
                'Currency' => $currencyCode,
            ]
        );
    }

    /**
     * returns the default currency.
     */
    public static function default_currency()
    {
        return DataObject::get_one(
            EcommerceCurrency::class,
            [
                'Code' => trim(strtolower(EcommerceConfig::get(EcommerceCurrency::class, 'default_currency'))),
                'InUse' => 1,
            ]
        );
    }

    /**
     * returns the default currency as Code.
     *
     * @return string - e.g. NZD
     */
    public static function default_currency_code()
    {
        $code = '';
        $obj = self::default_currency();
        if ($obj) {
            $code = $obj->Code;
        }
        if (! $code) {
            $code = EcommerceConfig::get(EcommerceCurrency::class, 'default_currency');
        }
        if (! $code) {
            $code = 'NZD';
        }

        return strtoupper($code);
    }

    /**
     * @return int
     */
    public static function default_currency_id()
    {
        $currency = self::default_currency();

        return $currency ? $currency->ID : 0;
    }

    /**
     * Only returns a currency when it is a valid currency.
     *
     * @param string $currencyCode - the code of the currency, e.g. nzd
     *
     * @return EcommerceCurrency | DataObject null
     */
    public static function get_one_from_code($currencyCode)
    {
        return DataObject::get_one(
            EcommerceCurrency::class,
            [
                'Code' => trim(strtoupper($currencyCode)),
                'InUse' => 1,
            ]
        );
    }

    /**
     * STANDARD SILVERSTRIPE STUFF.
     **/
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fieldLabels = $this->fieldLabels();
        $codeField = $fields->dataFieldByName('Code');
        $codeField->setRightTitle('e.g. NZD, use uppercase codes');
        $titleField = $fields->dataFieldByName('Name');
        $titleField->setRightTitle('e.g. New Zealand Dollar');
        $fields->addFieldToTab('Root.Main', new ReadonlyField('IsDefaulNice', $fieldLabels['IsDefaultNice'], $this->getIsDefaultNice()));
        if (! $this->isDefault()) {
            $fields->addFieldToTab('Root.Main', new ReadonlyField('ExchangeRate', $fieldLabels['ExchangeRate'], $this->ExchangeRate()));
            $fields->addFieldToTab('Root.Main', new ReadonlyField('ExchangeRateExplanation', $fieldLabels['ExchangeRateExplanation'], $this->ExchangeRateExplanation()));
        }
        $fields->addFieldsToTab('Root.Main', [
            new HeaderField('Symbols', 'Symbols'),
            new ReadonlyField('DefaultSymbol', 'Default'),
            new ReadonlyField('ShortSymbol', 'Short'),
            new ReadonlyField('LongSymbol', 'Long'),
        ]);

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

    public function DefaultSymbol()
    {
        return $this->getDefaultSymbol();
    }

    public function getDefaultSymbol()
    {
        return EcommerceMoney::get_default_symbol($this->Code);
    }

    public function ShortSymbol()
    {
        return $this->getShortSymbol();
    }

    public function getShortSymbol()
    {
        return EcommerceMoney::get_short_symbol($this->Code);
    }

    public function LongSymbol()
    {
        return $this->getLongSymbol();
    }

    public function getLongSymbol()
    {
        return EcommerceMoney::get_long_symbol($this->Code);
    }

    /**
     * casted variable method.
     *
     * @return bool
     */
    public function IsDefault()
    {
        return $this->getIsDefault();
    }

    public function getIsDefault()
    {
        if ($this->exists()) {
            if (! $this->Code) {
                user_error('This currency (ID = ' . $this->ID . ') does not have a code ');
            }
        }

        return strtoupper($this->Code) === strtoupper(EcommerceConfig::get(EcommerceCurrency::class, 'default_currency'));
    }

    /**
     * casted variable method.
     *
     * @return string
     */
    public function IsDefaultNice()
    {
        return $this->getIsDefaultNice();
    }

    public function getIsDefaultNice()
    {
        if ($this->getIsDefault()) {
            return _t('EcommerceCurrency.YES', 'Yes');
        }
        return _t('EcommerceCurrency.NO', 'No');
    }

    /**
     * casted variable method.
     *
     * @return string
     */
    public function InUseNice()
    {
        return $this->getInUseNice();
    }

    public function getInUseNice()
    {
        if ($this->InUse) {
            return _t('EcommerceCurrency.YES', 'Yes');
        }
        return _t('EcommerceCurrency.NO', 'No');
    }

    /**
     * casted variable.
     * @alias for getExchangeRate
     *
     * @return float
     */
    public function ExchangeRate()
    {
        return $this->getExchangeRate();
    }

    /**
     * @return float
     */
    public function getExchangeRate()
    {
        $exchangeRateProviderClassName = EcommerceConfig::get(EcommerceCurrency::class, 'exchange_provider_class');
        $exchangeRateProvider = new $exchangeRateProviderClassName();

        return $exchangeRateProvider->ExchangeRate(EcommerceConfig::get(EcommerceCurrency::class, 'default_currency'), $this->Code);
    }

    /**
     * casted variable.
     *
     * @return string
     */
    public function ExchangeRateExplanation()
    {
        return $this->getExchangeRateExplanation();
    }

    /**
     * @return string
     */
    public function getExchangeRateExplanation(): string
    {
        $string = '1 ' . EcommerceConfig::get(EcommerceCurrency::class, 'default_currency') . ' = ' . round($this->getExchangeRate(), 3) . ' ' . $this->Code;
        $exchangeRate = $this->getExchangeRate();
        $exchangeRateError = '';
        if (! $exchangeRate) {
            $exchangeRate = 1;
            $exchangeRateError = _t('EcommerceCurrency.EXCHANGE_RATE_ERROR', 'Error in exchange rate. ');
        }
        return $string .
            ', 1 ' . $this->Code . ' = ' . round(1 / $exchangeRate, 3) . ' ' .
            EcommerceConfig::get(EcommerceCurrency::class, 'default_currency') . '. ' .
            $exchangeRateError;
    }

    /**
     * @return bool
     */
    public function IsCurrent()
    {
        $order = ShoppingCart::current_order();

        return $order ? $order->CurrencyUsedID === $this->ID : false;
    }

    /**
     * Returns the link that can be used in the shopping cart to
     * set the preferred currency to this one.
     * For example: /shoppingcart/setcurrency/nzd/
     * Dont be fooled by the set_ part in the set_currency_link....
     *
     * @return string
     */
    public function Link()
    {
        return ShoppingCartController::set_currency_link($this->Code);
    }

    /**
     * returns the link type.
     *
     * @return string (link | default | current)
     */
    public function LinkingMode()
    {
        $linkingMode = '';
        if ($this->IsDefault()) {
            $linkingMode .= ' default';
        }
        if ($this->IsCurrent()) {
            $linkingMode .= ' current';
        } else {
            $linkingMode .= ' link';
        }

        return $linkingMode;
    }

    public function validate()
    {
        $result = parent::validate();
        $errors = [];
        if (! $this->Code || mb_strlen($this->Code) !== 3) {
            $errors[] = 'The code must be 3 characters long.';
        }
        if (! $this->Name) {
            $errors[] = 'The name is required.';
        }
        if (! count($errors)) {
            $this->Code = strtoupper($this->Code);
            // Check that there are no 2 same code currencies in use
            if ($this->isChanged('Code')) {
                if (EcommerceCurrency::get()->where("UPPER(\"Code\") = '" . $this->Code . "'")->exclude('ID', intval($this->ID) - 0)->count()) {
                    $errors[] = "There is alreay another currency in use which code is '{$this->Code}'.";
                }
            }
        }
        foreach ($errors as $error) {
            $result->addError($error);
        }

        return $result;
    }

    /**
     * Standard SS Method
     * Adds the default currency.
     */
    public function populateDefaults()
    {
        parent::populateDefaults();
        $this->InUse = true;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        // Check that there is always at least one currency in use
        $this->Code = strtoupper($this->Code);
        if (! $this->InUse) {
            $list = self::get_list();
            if ($list->count() === 0 || ($list->Count() === 1 && $list->First()->ID === $this->ID)) {
                $this->InUse = true;
            }
        }
    }

    /**
     * Standard SS Method
     * Adds the default currency.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        if (! self::default_currency()) {
            self::create_new(EcommerceConfig::get(EcommerceCurrency::class, 'default_currency'));
        }
    }

    /**
     * checks if a currency exists, creates it and returns it.
     *
     * @param string $code
     * @param string $name OPTIONAL
     */
    public static function create_new($code, $name = '')
    {
        $code = trim(strtoupper($code));
        if (! $name) {
            $currencies = Config::inst()->get(EcommerceCurrency::class, 'currencies');
            if (isset($currencies[$code])) {
                $name = $currencies[$code];
            } else {
                $name = $code;
            }
        }
        $name = ucwords($name);
        $currency = DataObject::get_one(
            EcommerceCurrency::class,
            ['Code' => $code],
            $cacheDataObjectGetOne = false
        );
        if ($currency) {
            $currency->Name = $name;
            $currency->InUse = true;
        } else {
            $currency = EcommerceCurrency::create(
                [
                    'Code' => $code,
                    'Name' => $name,
                    'InUse' => true,
                ]
            );
        }
        $valid = $currency->write();
        if ($valid) {
            return $currency;
        }
    }

    /**
     * Debug helper method.
     * Can be called from /shoppingcart/debug/.
     *
     * @return string
     */
    public function debug()
    {
        return EcommerceTaskDebugCart::debug_object($this);
    }
}
