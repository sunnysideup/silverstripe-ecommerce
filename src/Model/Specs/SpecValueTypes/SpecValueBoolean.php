<?php

namespace Sunnysideup\Ecommerce\Model\Specs\SpecValueTypes;

use Sunnysideup\Ecommerce\Model\Specs\SpecValue;

class SpecValueBoolean extends SpecValue
{
    private static $table_name = 'SpecValueBoolean';

    private static $db = [
        'Value' => 'Boolean',
    ];

    private static $indexes = [
        'Value' => true,
    ];

    public function getValue(): bool
    {
        return (bool) $this->Value;
    }

    public function setValue($value): static
    {
        $this->Value = (bool) $value;
        return $this;
    }
}
