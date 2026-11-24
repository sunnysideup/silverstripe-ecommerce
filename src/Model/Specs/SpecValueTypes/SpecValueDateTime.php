<?php

namespace Sunnysideup\Ecommerce\Model\Specs\SpecValueTypes;

use Sunnysideup\Ecommerce\Model\Specs\SpecValue;

class SpecValueDateTime extends SpecValue
{
    private static $table_name = 'SpecValueDateTime';

    private static $db = [
        'Value' => 'Datetime',
    ];

    private static $indexes = [
        'Value' => true,
    ];

    public function getValueForType(): ?string
    {
        return $this->Value;
    }

    public function setValueForType($value): static
    {
        $this->Value = $value;
        return $this;
    }
}
