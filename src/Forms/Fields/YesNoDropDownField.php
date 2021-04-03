<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use ArrayAccess;

use SilverStripe\Forms\DropdownField;

/**
 * this is a dropdown field just for selecting the right
 * classname.
 * usage:
 * EcommerceClassNameOrTypeDropdownField::create('ClassName', 'Type or so', 'MyBaseClass');
 **/
class YesNoDropDownField extends DropdownField
{
    /**
     * @var string
     */
    public const ANY_IE_NO_SELECTION = '-- any --';

    /**
     * @param string $name The field name
     * @param string $title The field title
     * @param array|ArrayAccess $source A map of the dropdown items
     * @param mixed $value The current value
     */
    public function __construct(
        $name = '',
        $title = '',
        $source = [],
        $value = null
    ) {
        if (empty($source)) {
            $source = [
                null => self::ANY_IE_NO_SELECTION,
                '0' => 'No',
                '1' => 'Yes',
            ];
        }
        parent::__construct($name, $title, $source, $value);
        $this->addExtraClass('dropdown-yes-no');
    }
}
