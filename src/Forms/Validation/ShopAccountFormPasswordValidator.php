<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Core\Convert;
use SilverStripe\View\ViewableData;

/***
 * extra checks to make sure the password is valid....
 *
 *
 *
 *
 */


/**
 * ### @@@@ START REPLACEMENT @@@@ ###
 * WHY: automated upgrade
 * OLD:  extends Object (ignore case)
 * NEW:  extends ViewableData (COMPLEX)
 * EXP: This used to extend Object, but object does not exist anymore. You can also manually add use Extensible, use Injectable, and use Configurable
 * ### @@@@ STOP REPLACEMENT @@@@ ###
 */
class ShopAccountFormPasswordValidator extends ViewableData
{
    /**
     * returns a valid, mysql safe password OR an empty string.
     *
     * @param $data (data from form)
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
