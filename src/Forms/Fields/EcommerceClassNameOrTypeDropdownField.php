<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\DropdownField;

/**
 * this is a dropdown field just for selecting the right
 * classname.
 * usage:
 * EcommerceClassNameOrTypeDropdownField::create('ClassName', 'Type or so', 'MyBaseClass');.
 */
class EcommerceClassNameOrTypeDropdownField extends DropdownField
{
    /**
     * @var array
     */
    protected $availableClasses = [];

    /**
     * @var string
     */
    protected $sourceClass = '';

    /**
     * @var bool
     */
    protected $includeBaseClass = false;

    /**
     * @param string $name             - this is usually classname, as in MyTable.ClassName
     * @param string $title            - e.g. type of object
     * @param string $sourceClass      - e.g. MyDataObject
     * @param array  $availableClasses - e.g. Array(MyFavouriteClassName, MyOtherFavouriteClassName)
     *
     * @return EcommerceClassNameOrTypeDropdownField
     */
    public function __construct(
        ?string $name = 'ClassName',
        ?string $title = 'Type',
        ?string $sourceClass = '',
        ?array $availableClasses = [],
        ?string $value = ''
    ) {
        $this->sourceClass = $sourceClass;
        $this->availableClasses = $availableClasses;
        parent::__construct($name, $title, [], $value);
        $this->addExtraClass('dropdown');
    }

    public function getSource()
    {
        if ($this->includeBaseClass) {
            $classes[] = $this->sourceClass;
        } else {
            $classes = [];
        }
        $classes += ClassInfo::subclassesFor($this->sourceClass);

        if (! count($this->availableClasses)) {
            $this->availableClasses = $classes;
        } elseif ($this->includeBaseClass) {
            $this->availableClasses[] = $this->sourceClass;
        }
        $dropdownArray = [];
        if ($this->getHasEmptyDefault()) {
            $dropdownArray[''] = $this->emptyString;
        }
        if ($classes) {
            foreach ($classes as $key => $className) {
                if (class_exists($key)) {
                    if (in_array($className, $this->availableClasses, true)) {
                        $obj = singleton($className);
                        if ($obj) {
                            $dropdownArray[$className] = $obj->i18n_singular_name();
                        }
                    }
                }
            }
        }
        if (! count($dropdownArray)) {
            $dropdownArray = [$this->sourceClass => _t('EcommerceClassNameOrTypeDropdownField.CAN_NOT_CREATE', "Can't create.")];
        }

        return $dropdownArray;
    }

    /**
     * @param array $array - e.g. Array(MyFavouriteClassName, MyOtherFavouriteClassName)
     */
    public function setAvailableClasses(array $array): self
    {
        $this->availableClasses = $array;

        return $this;
    }

    /**
     * @param bool $bool
     */
    public function setIncludeBaseClass($bool): self
    {
        $this->includeBaseClass = $bool;

        return $this;
    }

    /*
    function Field($properties = array()) {
        $this->source = $this->getSource();
        return parent::Field($properties);
    }
    */
}
