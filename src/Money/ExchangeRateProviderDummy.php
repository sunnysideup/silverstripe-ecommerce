<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Money;

use Override;

class ExchangeRateProviderDummy extends ExchangeRateProvider
{
    /**
     * @param string $fromCode
     * @param string $toCode
     */
    #[Override]
    public function ExchangeRate($fromCode, $toCode)
    {
        return 1;
    }
}
