<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Core\ClassInfo;

class ArrayMethods
{
    /**
     * return an array that can be ued for ORM filters...
     *
     * @param  mixed $array - hopefully an array
     *
     * @return array
     */
    public static function filter_array($array): array
    {
        if (! is_array($array)) {
            $array = [];
        }
        if (count($array) === 0) {
            $array = [0 => 0];
        }

        return $array;
    }

    /**
     * creates a sort string from a list of ID arrays...
     *
     * @param array $ids - list of product IDs
     *
     * @return string
     */
    public static function create_where_from_id_array(array $ids, ?string $className): string
    {
        $ids = ArrayMethods::filter_array($ids);
        $ifStatement = 'CASE ';
        $count = 0;
        $stage = self::get_stage();
        $tableClasses = ClassInfo::dataClassesFor($className);
        $table = array_shift($tableClasses);
        foreach ($ids as $id) {
            $ifStatement .= ' WHEN "' . $table . $stage . "\".\"ID\" = ${id} THEN ${count}";
            ++$count;
        }
        return $ifStatement . ' END';
    }

    /**
     * Returns a versioned record stage table suffix (i.e "" or "_Live")
     *
     * @return string
     */
    protected static function get_stage()
    {
        $stage = '';

        if (Versioned::get_stage() === 'Live') {
            $stage = '_Live';
        }

        return $stage;
    }
}
