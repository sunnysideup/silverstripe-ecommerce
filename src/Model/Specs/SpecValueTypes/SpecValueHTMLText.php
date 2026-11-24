<?php

namespace Sunnysideup\Ecommerce\Model\Specs\SpecValueTypes;

use Sunnysideup\Ecommerce\Model\Specs\SpecValue;

class SpecValueHTMLText extends SpecValue
{
    private static $table_name = 'SpecValueHTMLText';

    private static $db = [
        'Value' => 'HTMLText',
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
