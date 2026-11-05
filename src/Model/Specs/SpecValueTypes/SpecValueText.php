<?php

namespace Sunnysideup\Ecommerce\Model\Specs\SpecValueTypes;

use Sunnysideup\Ecommerce\Model\Specs\SpecValue;

class SpecValueText extends SpecValue
{
    private static $table_name = 'SpecValueText';

    private static $db = [
        'Value' => 'Varchar(255)',
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
