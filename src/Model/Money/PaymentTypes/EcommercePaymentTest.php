<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Model\Money\PaymentTypes;

use Override;
use Sunnysideup\Ecommerce\Model\Money\EcommercePayment;

/**
 * Payment object representing a generic test payment.
 */
class EcommercePaymentTest extends EcommercePayment
{
    private static $table_name = 'EcommercePaymentTest';

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

    #[Override]
    public function i18n_singular_name()
    {
        return $this->Config()->get('singular_name');
    }

    #[Override]
    public function plural_name()
    {
        return $this->Config()->get('plural_name');
    }

    #[Override]
    public function getPaymentFormRequirements(): array
    {
        return [];
    }
}
