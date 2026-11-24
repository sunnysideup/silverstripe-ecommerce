<?php

namespace Sunnysideup\Ecommerce\Model\Specs\SpecValueTypes;

use Sunnysideup\Ecommerce\Model\Specs\SpecValue;

class SpecValueNumber extends SpecValue
{
    private static $table_name = 'SpecValueNumber';

    private static $db = [
        'Value' => 'Float',
    ];

    private static $indexes = [
        'Value' => true,
    ];

    public function getValueForType(): float
    {
        return (float) $this->Value;
    }

    public function setValueForType($value): static
    {
        $this->Value = (float) $value;
        return $this;
    }
}
