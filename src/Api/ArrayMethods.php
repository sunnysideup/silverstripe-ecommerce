<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

class ArrayMethods
{
    /**
     * return an array that can be ued for ORM filters...
     *
     * @param mixed $array - hopefully an array
     */
    public static function filter_array($array): array
    {
        if (! is_array($array)) {
            $array = [];
        }
        if (0 === count($array)) {
            $array = [0 => 0];
        }

        return array_values(array_unique($array));
    }

    /**
     * creates a sort string from a list of ID arrays...
     *
     * @param array $ids - list of product IDs
     */
    public static function create_sort_statement_from_id_array(array $ids, ?string $className = ''): string
    {
        $ids = ArrayMethods::filter_array($ids);
        $ifStatement = 'CASE ';
        $count = 0;
        $stage = self::get_stage();
        $dataClasses = ClassInfo::dataClassesFor($className);
        $table = DataObject::getSchema()->tableName(array_shift($dataClasses));
        foreach ($ids as $id) {
            $ifStatement .= ' WHEN "' . $table . $stage . "\".\"ID\" = {$id} THEN {$count}";
            ++$count;
        }

        return $ifStatement . ' END';
    }

    /**
     * Returns a versioned record stage table suffix (i.e "" or "_Live").
     *
     * @return string
     */
    protected static function get_stage()
    {
        $stage = '';

        if ('Live' === Versioned::get_stage()) {
            $stage = '_Live';
        }

        return $stage;
    }
}
