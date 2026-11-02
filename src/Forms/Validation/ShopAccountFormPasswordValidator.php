<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Authenticator;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

// extra checks to make sure the password is valid....
class ShopAccountFormPasswordValidator
{
    /**
     * returns the new password that has been entered OR an empty string.
     *
     * IMPORTANT: the password is returned exactly as it was typed.  Passwords must NEVER be
     * escaped (e.g. Convert::raw2sql) as that would change what is stored
     * and the customer would not be able to log in with the password they typed.
     * The ORM and the password encryptor take care of the rest.
     *
     * @param array $data (data from form)
     *
     * @return string
     */
    public static function clean_password($data)
    {
        if (! is_array($data)) {
            return '';
        }
        if (isset($data['PasswordCheck1'], $data['PasswordCheck2'])) {
            if (is_string($data['PasswordCheck1']) && $data['PasswordCheck1'] === $data['PasswordCheck2']) {
                return $data['PasswordCheck1'];
            }
        } elseif (isset($data['PasswordCheck1']) && is_string($data['PasswordCheck1'])) {
            return $data['PasswordCheck1'];
        }

        return '';
    }

    /**
     * Can a (new) password be set for this member without having to provide the current password?
     * Only if the member has no password yet (e.g. a new member or a member that logs in with a one-time code).
     */
    public static function can_set_password_without_current_password(?Member $member): bool
    {
        return ! $member || ! $member->exists() || ! $member->Password;
    }

    /**
     * Checks if the password provided is the current password for the member
     * (same method as used by the core change password form).
     */
    public static function is_current_password(Member $member, ?string $password): bool
    {
        if (! $password) {
            return false;
        }
        $authenticators = Security::singleton()->getApplicableAuthenticators(Authenticator::CHECK_PASSWORD);
        foreach ($authenticators as $authenticator) {
            if (! $authenticator->checkPassword($member, $password)->isValid()) {
                return false;
            }
        }

        return $authenticators !== [];
    }

    /**
     * The messages of a validation result as one (plain text) string.
     */
    public static function messages_as_string(ValidationResult $result): string
    {
        $messages = [];
        foreach ($result->getMessages() as $message) {
            if (! empty($message['message'])) {
                $messages[] = strip_tags((string) $message['message']);
            }
        }

        return implode(' ', $messages);
    }
}
