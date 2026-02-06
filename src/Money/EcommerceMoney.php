<?php

namespace Sunnysideup\Ecommerce\Money;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBMoney;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;

/**
 * Class \Sunnysideup\Ecommerce\Money\EcommerceMoney
 *
 * @property \SilverStripe\ORM\FieldType\DBMoney|\Sunnysideup\Ecommerce\Money\EcommerceMoney $owner
 */
class EcommerceMoney extends Extension
{
    /**
     * @var string
     */
    private static $default_format = 'NiceShortSymbol';

    /**
     * returns the symbol for a currency, e.g. $.
     *
     * @param string $currency
     *
     * @return string
     */
    public static function get_default_symbol(?string $currency = 'NZD')
    {
        $money = DBMoney::create();
        $money->setCurrency($currency);

        return $money->getSymbol();
    }

    /**
     * returns the short symbol for a currency
     * This is shorter than the default one.
     *
     * @param string $currency
     *
     * @return string
     */
    public static function get_short_symbol(?string $currency = 'NZD')
    {
        $symbol = self::get_default_symbol($currency);
        if ($symbol && $currency) {
            $i = 0;
            while ($i < mb_strlen((string) $symbol) && substr((string) $symbol, $i, 1) === substr($currency, $i, 1)) {
                ++$i;
            }

            return substr((string) $symbol, $i);
        }

        return '$';
    }

    /**
     * returns the long symbol for a currency.
     *
     * @param string $currency
     *
     * @return string
     */
    public static function get_long_symbol(?string $currency = 'NZD')
    {
        $symbol = self::get_default_symbol($currency);
        if ($symbol && mb_strlen((string) $symbol) < 3) {
            $symbol = substr((string) $currency, 0, 3 - mb_strlen((string) $symbol)) . $symbol;
        }

        return $symbol;
    }

    /**
     * returns the default symbol for a site.
     * with or without html.
     *
     * @param bool $html
     *
     * @return string
     */
    public function NiceDefaultSymbol($html = true)
    {
        return self::get_default_symbol($this->getOwner()->currency) === self::get_short_symbol($this->getOwner()->currency) ? $this->NiceShortSymbol($html) : $this->NiceLongSymbol($html);
    }

    /**
     * returns the short symbol for a site.
     * with or without html.
     *
     * @param bool $html
     *
     * @return string
     */
    public function NiceShortSymbol($html = true)
    {
        $symbol = self::get_short_symbol($this->getOwner()->currency);
        if ($html) {
            $symbol = "<span class=\"currencyHolder currencyHolderShort currency{$this->getOwner()->currency}\"><span class=\"currencySymbol\">{$symbol}</span></span>";
        }
        $amount = $this->getOwner()->getAmount();

        $formatter = $this->getOwner()->getFormatter();
        $data = $formatter->format($amount);

        return DBField::create_field('HTMLText', $data);
    }

    /**
     * returns the long symbol for a site.
     * with or without html.
     *
     * @param bool $html
     *
     * @return string
     */
    public function NiceLongSymbol($html = true)
    {
        $symbol = self::get_long_symbol($this->getOwner()->currency);
        $short = self::get_short_symbol($this->getOwner()->currency);
        $pre = substr((string) $symbol, 0, mb_strlen((string) $symbol) - mb_strlen((string) $short));
        if ($html) {
            $symbol = "<span class=\"currencyHolder currencyHolderLong currency{$this->getOwner()->currency}\"><span class=\"currencyPreSymbol\">{$pre}</span><span class=\"currencySymbol\">{$short}</span></span>";
        } else {
            $symbol = $pre . $short;
        }
        $amount = $this->getOwner()->getAmount();
        $currency = $this->getOwner()->getCurrency();

        $formatter = $this->getOwner()->getFormatter();
        $data = $currency ? $symbol . $formatter->formatCurrency($amount, $currency) : $formatter->format($amount);

        return DBField::create_field('HTMLText', $data);
    }

    /**
     * returns a currency like this: 8,001 usd / 12.12 nzd.
     *
     * @param bool $html
     *
     * @return string
     */
    public function SymbolNumberAndCode($html = true)
    {
        $symbol = self::get_short_symbol($this->getOwner()->currency);
        if ($html) {
            $symbol = "<span class=\"currencySymbol\">{$symbol}</span>";
        }
        $code = strtolower((string) $this->getOwner()->currency);
        if ($html) {
            $code = "<span class=\"currencyHolder\">{$code}</span>";
        }
        $amount = $this->getOwner()->getAmount();

        $data = is_numeric($amount) ? $symbol . $this->getOwner()->currencyLib->toCurrency($amount, [
            'symbol' => '',
            'precision' => 0,
        ]) . ' ' . $code : '';

        return DBField::create_field('HTMLText', $data);
    }

    /**
     * returns the default format for a site for currency.
     *
     * @param bool $html
     *
     * @return string
     */
    public function NiceDefaultFormat($html = true)
    {
        $function = EcommerceConfig::get(EcommerceMoney::class, 'default_format');

        return $this->getOwner()->{$function}($html);
    }
}
