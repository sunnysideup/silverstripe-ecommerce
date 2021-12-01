<?php

namespace Sunnysideup\Ecommerce\Forms\Validation;

use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Model\Address\BillingAddress;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Pages\CheckoutPage;

/**
 * @Description: checks the data for the OrderForm, before submission.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 */
class OrderFormValidator extends RequiredFields
{
    /**
     * Ensures member unique id stays unique and other basic stuff...
     *
     * @param array $data = Form Data
     *
     * @return bool
     */
    public function php($data)
    {
        $valid = parent::php($data);
        $checkoutPage = DataObject::get_one(CheckoutPage::class);
        if ($checkoutPage && $checkoutPage->TermsAndConditionsMessage) {
            if (isset($data['ReadTermsAndConditions'])) {
                if (! $data['ReadTermsAndConditions']) {
                    $this->validationError(
                        'ReadTermsAndConditions',
                        $checkoutPage->TermsAndConditionsMessage,
                        'required'
                    );
                    $valid = false;
                }
            }
        }
        $order = ShoppingCart::current_order();
        if (! $order) {
            $this->validationError(
                Order::class,
                _t('OrderForm.ORDERNOTFOUND', 'There was an error in processing your order, please try again or contact the administrator.'),
                'required'
            );
            $valid = false;
        }
        $billingAddress = BillingAddress::get_by_id($order->BillingAddressID);
        if (! $billingAddress) {
            $this->validationError(
                BillingAddress::class,
                _t('OrderForm.MUSTHAVEBILLINGADDRESS', 'All orders must have a billing address, please go back and add your details.'),
                'required'
            );
            $valid = false;
        }

        return $valid;
    }
}
