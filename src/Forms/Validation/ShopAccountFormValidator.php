<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Api\AttemptLimiter;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

class ShopAccountFormValidator extends RequiredFields
{
    /**
     * @var int
     */
    private static $minimum_password_length = 8;

    /**
     * How many times (per visitor and per time period) can someone enter an email address that
     * already has an account.  After that, every email address is refused for a while.
     * Set to zero for no limit.
     *
     * @var int
     */
    private static $max_taken_email_attempts = 5;

    /**
     * The time period (in seconds) for max_taken_email_attempts.
     *
     * @var int
     */
    private static $max_taken_email_attempts_period_in_seconds = 3600;

    /**
     * Ensures member unique id stays unique and other basic stuff...
     *
     * @param array $data               = array Form Field Data
     * @param bool  $allowExistingEmail - see comment below
     *
     * @return bool
     */
    public function php($data, $allowExistingEmail = false)
    {
        $this->form->saveDataToSession();
        $valid = parent::php($data);
        $uniqueFieldName = Member::config()->get('unique_identifier_field');
        $loggedInMember = Security::getCurrentUser();
        $loggedInMemberID = 0;
        if (isset($data[$uniqueFieldName]) && $data[$uniqueFieldName]) {
            $isShopAdmin = false;
            if ($loggedInMember) {
                $loggedInMemberID = $loggedInMember->ID;
                if ($loggedInMember->IsShopAdmin()) {
                    $isShopAdmin = true;
                }
            }
            if ($isShopAdmin || $allowExistingEmail) {
                //do nothing
            } else {
                // NB: do not strip characters from the value (e.g. "@" and "." in an email address):
                // the lookup below must find exactly what Member::onBeforeWrite() will look for (it trims, nothing else).
                // The ORM parameterises the value, so there is no need to escape it.
                $uniqueFieldValue = trim((string) $data[$uniqueFieldName]);
                // This check tells people if an email address already has an account.
                // To stop people from using it to find out which email addresses have accounts,
                // we limit how often someone can run into an email address that is taken.
                // Once the limit has been reached, every email address is refused (whether it is taken or not).
                $limiter = AttemptLimiter::create(
                    'RegistrationEmailTaken',
                    (int) Config::inst()->get(ShopAccountFormValidator::class, 'max_taken_email_attempts'),
                    (int) Config::inst()->get(ShopAccountFormValidator::class, 'max_taken_email_attempts_period_in_seconds')
                );
                if ($limiter->isLimited()) {
                    $this->validationError(
                        $uniqueFieldName,
                        _t(
                            'Account.TOOMANYATTEMPTS',
                            'Too many attempts. Please try again later or log in.'
                        ),
                        'required'
                    );
                    $this->form->sessionMessage(_t('Account.ERRORINFORM', 'We could not save your details, please check your errors below.'), 'bad');

                    return false;
                }
                //can't be taken
                $otherMembersWithSameEmail = Member::get()
                    ->filter([$uniqueFieldName => $uniqueFieldValue])
                    ->exclude(['ID' => $loggedInMemberID]);
                if ($otherMembersWithSameEmail->exists()) {
                    // Existing emails are allowed (see the `$allowExistingEmail` check above) when we are NOT logged in
                    // and are placing an order, so we only get here for the account form and for logged-in people.
                    $limiter->record();
                    // NB: we do not repeat the email address and we do not say that an account exists.
                    // (Someone can still find out, but only a few times per hour: see the limiter above.)
                    $message = _t(
                        'Account.ALREADYTAKEN',
                        'This {uniqueFieldName} can not be used. If you already have an account, please log in. Otherwise, please use another {uniqueFieldName}.',
                        ['uniqueFieldName' => strtolower($uniqueFieldName)]
                    );
                    $this->validationError(
                        $uniqueFieldName,
                        $message,
                        'required'
                    );
                    $valid = false;
                }
            }
        }
        // check password fields are the same before saving
        if (isset($data['PasswordCheck1'], $data['PasswordCheck2'])) {
            if ($data['PasswordCheck1'] !== $data['PasswordCheck2']) {
                $this->validationError(
                    'PasswordCheck1',
                    _t('Account.PASSWORDSERROR', 'Passwords do not match.'),
                    'required'
                );
                $valid = false;
            }
            //if you are not logged in, you have not provided a password and the settings require you to be logged in then
            //we have a problem
            if (! $loggedInMember && ! $data['PasswordCheck1'] && EcommerceConfig::get(EcommerceRole::class, 'must_have_account_to_purchase')) {
                $this->validationError(
                    'PasswordCheck1',
                    _t('Account.SELECTPASSWORD', 'Please select a password.'),
                    'required'
                );
                $valid = false;
            }
            $letterCount = strlen((string) $data['PasswordCheck1']);
            $minLength = self::getMinimumPasswordLength();
            if ($letterCount > 0 && $letterCount < $minLength) {
                $this->validationError(
                    'PasswordCheck1',
                    _t('Account.PASSWORDMINIMUMLENGTH', 'Password does not meet minimum standards. It must be at least {minLength} characters long.', ['minLength' => $minLength]),
                    'required'
                );
                $valid = false;
            }
        }
        if (isset($data['FirstName']) && strlen((string) $data['FirstName']) < 2) {
            $this->validationError(
                'FirstName',
                _t('Account.NOFIRSTNAME', 'Please enter your first name.'),
                'required'
            );
            $valid = false;
        }
        if (isset($data['Surname']) && strlen((string) $data['Surname']) < 2) {
            $this->validationError(
                'Surname',
                _t('Account.NOSURNAME', 'Please enter your surname.'),
                'required'
            );
            $valid = false;
        }
        $validExtended = $this->extend('updatePHP', $data, $this);
        if (false === $validExtended) {
            $valid = false;
        }
        if (! $valid) {
            $this->form->sessionMessage(_t('Account.ERRORINFORM', 'We could not save your details, please check your errors below.'), 'bad');
        }

        return $valid;
    }

    public static function getMinimumPasswordLength()
    {
        $minLength = (int) Config::inst()->get(ShopAccountFormValidator::class, 'minimum_password_length');
        $validator = Member::password_validator();
        return max($minLength, $validator->getMinLength());
    }
}
