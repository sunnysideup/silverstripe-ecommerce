<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

class ShopAccountFormValidator extends RequiredFields
{
    /**
     * @var int
     */
    private static $minimum_password_length = 7;

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
                $uniqueFieldValue = Convert::raw2sql($data[$uniqueFieldName]);
                //can't be taken
                $otherMembersWithSameEmail = Member::get()
                    ->filter([$uniqueFieldName => $uniqueFieldValue])
                    ->exclude(['ID' => $loggedInMemberID]);
                if ($otherMembersWithSameEmail->exists()) {
                    //we allow existing email
                    // if we are currently NOT logged in
                    // in case we place an order!
                    if ($allowExistingEmail) {
                        //do nothing
                    } else {
                        $message = _t(
                            'Account.ALREADYTAKEN',
                            '{uniqueFieldValue} is not available. Please log in or use another {uniqueFieldName}.',
                            ['uniqueFieldValue' => $uniqueFieldValue, 'uniqueFieldName' => strtolower($uniqueFieldName)]
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
            $minLength = Config::inst()->get(ShopAccountFormValidator::class, 'minimum_password_length');
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
}
