<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Security\Member;

/**
 * @Description: allows customer to make additional payments for their order
 *
 * @package: ecommerce
 * @authors: Silverstripe, Jeremy, Nicolaas
 **/
class OrderFormAddressValidator extends ShopAccountFormValidator
{
    /**
     * Ensures member unique id stays unique and other basic stuff...
     *
     * @param array $data = Form Data
     *
     * @return bool
     */
    public function php($data, $allowExistingEmail = false)
    {
        $this->form->saveDataToSession();
        if (Member::currentUserID()) {
            $allowExistingEmail = false;
        } else {
            $allowExistingEmail = true;
        }
        if (! isset($data['UseShippingAddress']) || ! $data['UseShippingAddress']) {
            foreach (array_keys($this->required) as $key) {
                if (substr($key, 0, 8) === 'Shipping') {
                    unset($this->required[$key]);
                }
            }
        }
        $valid = parent::php($data, $allowExistingEmail);
        if ($this->form->uniqueMemberFieldCanBeUsed($data)) {
            //do nothing
        } else {
            $uniqueFieldName = Member::config()->get('unique_identifier_field');
            $this->validationError(
                $uniqueFieldName,
                _t(
                    'OrderForm.EMAILFROMOTHERUSER',
                    'Sorry, an account with that email is already in use by another customer. If this is your email address then please log in first before placing your order.'
                ),
                'required'
            );
            $valid = false;
        }
        if (! $valid) {
            $this->form->sessionMessage(_t('OrderForm.ERRORINFORM', 'We could not proceed with your order, please check your errors below.'), 'error');
        }

        return $valid;
    }
}
