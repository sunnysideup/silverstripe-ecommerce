<?php

namespace Sunnysideup\Ecommerce\Model\Fieldtypes;

use SilverStripe\Core\ClassInfo;



use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBPolymorphicForeignKey;
use Sunnysideup\Ecommerce\Forms\Fields\BuyableSelectField;
use Sunnysideup\Ecommerce\Interfaces\BuyableModel;

/**
 * NOTE: this is not yet being used!!!
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @subpackage: model

 **/
class BuyableFieldType extends DBPolymorphicForeignKey
{
    public function scaffoldFormField($title = null, $params = null)
    {
        // Opt-out of form field generation - Scaffolding should be performed on
        // the has_many end, or set programatically.
        // @todo - Investigate suitable FormField
        return new BuyableSelectField($this->name, $title);
    }

    public function compositeDatabaseFields()
    {

        // Ensure the table level cache exists
        if (empty(self::$classname_spec_cache[$this->tableName])) {
            self::$classname_spec_cache[$this->tableName] = [];
        }

        // Ensure the field level cache exists
        if (empty(self::$classname_spec_cache[$this->tableName][$this->name])) {
            // Get all class names

            /**
             * ### @@@@ START REPLACEMENT @@@@ ###
             * WHY: automated upgrade
             * OLD: $className (case sensitive)
             * NEW: $className (COMPLEX)
             * EXP: Check if the class name can still be used as such
             * ### @@@@ STOP REPLACEMENT @@@@ ###
             */
            $classNames = ClassInfo::implementorsOf(BuyableModel::class);

            $schema = DB::get_schema();
            if ($schema->hasField($this->tableName, "{$this->name}Class")) {
                $existing = DB::query("SELECT DISTINCT \"{$this->name}Class\" FROM \"{$this->tableName}\"")->column();

                /**
                 * ### @@@@ START REPLACEMENT @@@@ ###
                 * WHY: automated upgrade
                 * OLD: $className (case sensitive)
                 * NEW: $className (COMPLEX)
                 * EXP: Check if the class name can still be used as such
                 * ### @@@@ STOP REPLACEMENT @@@@ ###
                 */
                $classNames = array_unique(array_merge($classNames, $existing));
            }

            self::$classname_spec_cache[$this->tableName][$this->name]

                /**
                 * ### @@@@ START REPLACEMENT @@@@ ###
                 * WHY: automated upgrade
                 * OLD: $className (case sensitive)
                 * NEW: $className (COMPLEX)
                 * EXP: Check if the class name can still be used as such
                 * ### @@@@ STOP REPLACEMENT @@@@ ###
                 */
                = "Enum(array('" . implode("', '", array_filter($classNames)) . "'))";
        }

        return [
            'ID' => 'Int',
            'Class' => self::$classname_spec_cache[$this->tableName][$this->name],
        ];
    }
}
