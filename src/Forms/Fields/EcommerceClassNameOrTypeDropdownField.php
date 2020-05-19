<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\DropdownField;

/**
 * this is a dropdown field just for selecting the right
 * classname.
 * usage:
 * EcommerceClassNameOrTypeDropdownField::create('ClassName', 'Type or so', 'MyBaseClass');
 **/
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
        $name = 'ClassName',
        $title = 'Type',
        $sourceClass = '',
        $availableClasses = [],
        $value = '',
        $form = null
    ) {
        $this->sourceClass = $sourceClass;
        $this->availableClasses = $availableClasses;
        parent::__construct($name, $title, [], $value, $form);
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

            /**
             * ### @@@@ START REPLACEMENT @@@@ ###
             * WHY: automated upgrade
             * OLD: $className (case sensitive)
             * NEW: $className (COMPLEX)
             * EXP: Check if the class name can still be used as such
             * ### @@@@ STOP REPLACEMENT @@@@ ###
             */
            foreach ($classes as $key => $className) {
                if (class_exists($key)) {

                    /**
                     * ### @@@@ START REPLACEMENT @@@@ ###
                     * WHY: automated upgrade
                     * OLD: $className (case sensitive)
                     * NEW: $className (COMPLEX)
                     * EXP: Check if the class name can still be used as such
                     * ### @@@@ STOP REPLACEMENT @@@@ ###
                     */
                    if (in_array($className, $this->availableClasses, true)) {

                        /**
                         * ### @@@@ START REPLACEMENT @@@@ ###
                         * WHY: automated upgrade
                         * OLD: $className (case sensitive)
                         * NEW: $className (COMPLEX)
                         * EXP: Check if the class name can still be used as such
                         * ### @@@@ STOP REPLACEMENT @@@@ ###
                         */
                        $obj = singleton($className);
                        if ($obj) {

                            /**
                             * ### @@@@ START REPLACEMENT @@@@ ###
                             * WHY: automated upgrade
                             * OLD: $className (case sensitive)
                             * NEW: $className (COMPLEX)
                             * EXP: Check if the class name can still be used as such
                             * ### @@@@ STOP REPLACEMENT @@@@ ###
                             */
                            $dropdownArray[$className] = $obj->i18n_singular_name();
                        }
                    }
                }
            }
        }
        if (! count($dropdownArray)) {
            $dropdownArray = [$this->sourceClass => _t('EcommerceClassNameOrTypeDropdownField.CAN_NOT_CREATE', "Can't create.") . $title];
        }

        return $dropdownArray;
    }

    /**
     * @param array $array - e.g. Array(MyFavouriteClassName, MyOtherFavouriteClassName)
     */
    public function setAvailableClasses(array $array)
    {
        $this->availableClasses = $array;
    }

    /**
     * @param bool $bool
     */
    public function setIncludeBaseClass($bool)
    {
        $this->includeBaseClass = $bool;
    }

    /*
    function Field($properties = array()) {
        $this->source = $this->getSource();
        return parent::Field($properties);
    }
    */
}
