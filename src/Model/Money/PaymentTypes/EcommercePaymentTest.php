<?php

namespace Sunnysideup\Ecommerce\Model\Money\PaymentTypes;

use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;

/**
 * Payment object representing a generic test payment.
 */
class EcommercePaymentTest extends EcommercePayment
{
    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $singular_name = 'Ecommerce Test Payment';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $plural_name = 'Ecommerce Test Payments';

    public function i18n_singular_name()
    {
        return $this->Config()->get('singular_name');
    }

    public function i18n_plural_name()
    {
        return $this->Config()->get('plural_name');
    }

    public function getPaymentFormRequirements(): array
    {
        return [];
    }
}
