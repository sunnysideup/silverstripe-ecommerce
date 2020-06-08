<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Core\Convert;

/***
 * extra checks to make sure the password is valid....
 *
 */
class ShopAccountFormPasswordValidator
{
    /**
     * returns a valid, mysql safe password OR an empty string.
     *
     * @param array $data (data from form)
     *
     * @return string
     */
    public static function clean_password($data)
    {
        if (isset($data['PasswordCheck1']) && isset($data['PasswordCheck2'])) {
            if ($data['PasswordCheck1'] === $data['PasswordCheck2']) {
                return Convert::raw2sql($data['PasswordCheck1']);
            }
        }

        return '';
    }
}
