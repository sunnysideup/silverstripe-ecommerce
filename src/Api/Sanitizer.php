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
    public static function remove_from_data_array(array $data)
    {
        unset($data['AccountInfo'], $data['LoginDetails'], $data['LoggedInAsNote'], $data['PasswordCheck1'], $data['PasswordCheck2'], $data['Password']);

        return $data;
    }
}
