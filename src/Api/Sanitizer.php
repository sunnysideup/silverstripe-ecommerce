<?php

namespace Sunnysideup\Ecommerce\Api;

/**
 * usage
 * ```php
 *     $data = Sanitizer::remove_from_data_array($data);
 * ```
 */
class Sanitizer
{
    public static function remove_from_data_array(array $data)
    {
        unset($data['AccountInfo']);
        unset($data['LoginDetails']);
        unset($data['LoggedInAsNote']);
        unset($data['PasswordCheck1']);
        unset($data['PasswordCheck2']);
        unset($data['Password']);
        return $data;
    }
}
