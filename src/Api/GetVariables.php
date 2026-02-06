<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;

class GetVariables
{
    use Configurable;

    /**
     * @var string
     */
    private static $equal_alternative = '~';

    /**
     * @var string
     */
    private static $exception_for_tilde = '_____';

    /**
     * @var string
     */
    private static $ampersand_alternative = '...';

    /**
     * @var string
     */
    private static $exception_for_comma = '-----';

    public static function array_to_url_string(array $array): string
    {
        // build query
        $string = http_build_query($array);

        // avoid equal characters
        $string = str_replace(
            Config::inst()->get(static::class, 'equal_alternative'),
            Config::inst()->get(static::class, 'exception_for_tilde'),
            $string
        );

        // turn = into ~
        $string = str_replace('=', Config::inst()->get(static::class, 'equal_alternative'), $string);

        // make sure that there are no commas
        $string = str_replace(
            Config::inst()->get(static::class, 'ampersand_alternative'),
            Config::inst()->get(static::class, 'exception_for_comma'),
            $string
        );

        // turn & into commas
        return str_replace(
            ['&amp;', '&'],
            Config::inst()->get(static::class, 'ampersand_alternative'),
            $string
        );
    }

    public static function url_string_to_array(string $string): array
    {
        $array = explode(Config::inst()->get(static::class, 'ampersand_alternative'), $string);
        $newArray = [];
        foreach ($array as $subString) {
            if ($subString !== '' && $subString !== '0') {
                $string = str_replace(
                    Config::inst()->get(static::class, 'exception_for_comma'),
                    Config::inst()->get(static::class, 'ampersand_alternative'),
                    $subString
                );
                $items = explode(
                    Config::inst()->get(static::class, 'equal_alternative'),
                    $subString,
                    2
                );
                if (count($items) === 2) {
                    list($key, $value) = $items;
                    $key = str_replace(
                        Config::inst()->get(static::class, 'exception_for_tilde'),
                        Config::inst()->get(static::class, 'equal_alternative'),
                        $key
                    );
                    $value = str_replace(
                        Config::inst()->get(static::class, 'exception_for_tilde'),
                        Config::inst()->get(static::class, 'equal_alternative'),
                        $value
                    );
                    $newArray[$key] = Convert::raw2sql($value);
                } elseif (count($items) === 1) {
                    list($key) = $items;
                    $key = str_replace(
                        Config::inst()->get(static::class, 'exception_for_tilde'),
                        Config::inst()->get(static::class, 'equal_alternative'),
                        $key
                    );
                    $newArray[$key] = '';
                }
            }
        }

        return $newArray;
    }
}
