<?php

namespace Sunnysideup\Ecommerce\Api;

/**
 * usage
 * ```php
 *     $data = Sanitizer::remove_from_data_array($data);
 * ```.
 */
class Sanitizer
{
    /**
     * removes sensitive data from DataArray
     *
     * @param array $data
     * @return array
     */
    public static function remove_from_data_array(array $data): array
    {
        unset(
            $data['AccountInfo'],
            $data['LoginDetails'],
            $data['LoggedInAsNote'],
            $data['PasswordCheck1'],
            $data['PasswordCheck2'],
            $data['Password'],
        );

        return $data;
    }

    public static function html_array_to_text(array $array)
    {
        return self::html_to_text(implode('; ', $array));
    }

    public static function html_to_text($html)
    {
        return
            strtolower(
                trim(
                    preg_replace(
                        '#\s+#',
                        ' ',
                        strip_tags(
                            str_replace(
                                '<',
                                ' <',
                                $html
                            )
                        )
                    )
                )
            );
    }
}
