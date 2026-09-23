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
        'Value' => [
            'type' => 'unique',
            'columns' => ['Value', 'ParentID'],
        ],
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
