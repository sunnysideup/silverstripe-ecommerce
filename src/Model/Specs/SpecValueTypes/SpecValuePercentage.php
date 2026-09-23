<?php

namespace Sunnysideup\Ecommerce\Model\Specs\SpecValueTypes;

use Sunnysideup\Ecommerce\Model\Specs\SpecValue;

class SpecValuePercentage extends SpecValue
{
    private static $table_name = 'SpecValuePercentage';

    private static $db = [
        'Value' => 'Percentage',
    ];

    private static $indexes = [
        'Value' => [
            'type' => 'unique',
            'columns' => ['Value', 'ParentID'],
        ],
    ];

    public function getValue(): float
    {
        return (float) $this->Value;
    }

    public function setValue($value): static
    {
        $this->Value = (float) $value;
        return $this;
    }
}
