<?php


class ShopAccountFormValidator extends RequiredFields
{
    /**
     * Ensures member unique id stays unique and other basic stuff...
     *
     * @param array $data               = array Form Field Data
     * @param bool  $allowExistingEmail - see comment below
     *
     * @return bool
     **/
    public function php($data, $allowExistingEmail = false)
    {
        $this->form->saveDataToSession();
        $valid = parent::php($data);
        $uniqueFieldName = Member::get_unique_identifier_field();
        $loggedInMember = Member::currentUser();
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
                if ($otherMembersWithSameEmail->count()) {
                    //we allow existing email
                    // if we are currently NOT logged in
                    // in case we place an order!
                    if ($allowExistingEmail) {
                        //do nothing
                    } else {
                        $message = _t(
                            'Account.ALREADYTAKEN',
                            '{uniqueFieldValue} is already taken by another member. Please log in or use another {uniqueFieldName}.',
                            ['uniqueFieldValue' => $uniqueFieldValue, 'uniqueFieldName' => $uniqueFieldName]
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
        if (isset($data['PasswordCheck1']) && isset($data['PasswordCheck2'])) {
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
            if (! $loggedInMember && ! $data['PasswordCheck1'] && EcommerceConfig::get('EcommerceRole', 'must_have_account_to_purchase')) {
                $this->validationError(
                    'PasswordCheck1',
                    _t('Account.SELECTPASSWORD', 'Please select a password.'),
                    'required'
                );
                $valid = false;
            }
            $letterCount = strlen($data['PasswordCheck1']);
            $minLength = Config::inst()->get('ShopAccountFormValidator', 'minimum_password_length');
            if ($letterCount > 0 && $letterCount < $minLength) {
                $this->validationError(
                    'PasswordCheck1',
                    _t('Account.PASSWORDMINIMUMLENGTH', 'Password does not meet minimum standards.'),
                    'required'
                );
                $valid = false;
            }
        }
        if (isset($data['FirstName'])) {
            if (strlen($data['FirstName']) < 2) {
                $this->validationError(
                    'FirstName',
                    _t('Account.NOFIRSTNAME', 'Please enter your first name.'),
                    'required'
                );
                $valid = false;
            }
        }
        if (isset($data['Surname'])) {
            if (strlen($data['Surname']) < 2) {
                $this->validationError(
                    'Surname',
                    _t('Account.NOSURNAME', 'Please enter your surname.'),
                    'required'
                );
                $valid = false;
            }
        }
        if (! $valid) {
            $this->form->sessionMessage(_t('Account.ERRORINFORM', 'We could not save your details, please check your errors below.'), 'bad');
        }

        return $valid;
    }
}

