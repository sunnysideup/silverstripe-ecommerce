<?php

namespace Sunnysideup\Ecommerce\Interfaces;

use Sunnysideup\Ecommerce\Pages\Product;

interface GenericProductSearchBooster
{
    public function getBoostValueForProduct(Product $product): float;
}
