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

    public function getValue(): ?string
    {
        return $this->Value;
    }

    public function setValue($value): static
    {
        $this->Value = $value;
        return $this;
    }
}
