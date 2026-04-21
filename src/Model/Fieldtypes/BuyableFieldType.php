<?php

namespace Sunnysideup\Ecommerce\Model\Fieldtypes;

use Override;
use SilverStripe\Forms\FormField;
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
 */
class BuyableFieldType extends DBPolymorphicForeignKey
{
    private static $classname_spec_cache = [];

    #[Override]
    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        // Opt-out of form field generation - Scaffolding should be performed on
        // the has_many end, or set programatically.
        // @todo - Investigate suitable FormField
        return BuyableSelectField::create($this->name, $title);
    }

    #[Override]
    public function compositeDatabaseFields(): array
    {
        // Ensure the table level cache exists
        if (empty(self::$classname_spec_cache[$this->tableName])) {
            self::$classname_spec_cache[$this->tableName] = [];
        }

        // Ensure the field level cache exists
        if (empty(self::$classname_spec_cache[$this->tableName][$this->name])) {
            // Get all class names

            $classNames = ClassInfo::implementorsOf(BuyableModel::class);

            $schema = DB::get_schema();
            if ($schema->hasField($this->tableName, $this->name . 'Class')) {
                $existing = DB::query(sprintf('SELECT DISTINCT "%sClass" FROM "%s"', $this->name, $this->tableName))->column();
                $classNames = array_unique(array_merge($classNames, $existing));
            }

            self::$classname_spec_cache[$this->tableName][$this->name]
                = "Enum(array('" . implode("', '", array_filter($classNames)) . "'))";
        }

        return [
            'ID' => 'Int',
            'Class' => self::$classname_spec_cache[$this->tableName][$this->name],
        ];
    }
}
