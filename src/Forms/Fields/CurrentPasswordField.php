<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use SilverStripe\Forms\PasswordField;

/**
 * The CURRENT password of the logged-in member (asked for when changing the email address or the password).
 *
 * It is a PasswordField, but it does not get the "password" CSS class on its holder.
 * client/javascript/EcomPasswordField.js hides and shows every `div.password.field.text` together
 * with the "update password" link, and this field is shown / hidden on its own
 * (see ShopAccountForm::getCurrentPasswordScript()).
 */
class CurrentPasswordField extends PasswordField
{
    public function Type()
    {
        return 'currentpassword';
    }
}
