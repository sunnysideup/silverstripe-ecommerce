<?php

/***
 * extra checks to make sure the password is valid....
 *
 *
 *
 *
 */

class ShopAccountForm_PasswordValidator extends Object
{
    /**
     * returns a valid, mysql safe password OR an empty string.
     *
     * @param data (data from form)
     *
     * @return string
     */
    public static function clean_password($data)
    {
        if (isset($data['PasswordCheck1']) && isset($data['PasswordCheck2'])) {
            if ($data['PasswordCheck1'] == $data['PasswordCheck2']) {
                return Convert::raw2sql($data['PasswordCheck1']);
            }
        }

        return '';
    }
}
