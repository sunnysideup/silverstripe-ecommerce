<?php

namespace Sunnysideup\Ecommerce\Model\Specs\SpecValueTypes;

use Sunnysideup\Ecommerce\Model\Specs\SpecValue;

class SpecValueDate extends SpecValue
{
    private static $table_name = 'SpecValueDate';

    private static $db = [
        'Value' => 'Date',
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
