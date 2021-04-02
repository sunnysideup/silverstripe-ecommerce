<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

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
     * @param string $name             - this is usually classname, as in MyTable.ClassName
     * @param string $title            - e.g. type of object
     * @param array $value      - e.g. MyDataObject
     * @param value $value      - e.g. MyDataObject
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
