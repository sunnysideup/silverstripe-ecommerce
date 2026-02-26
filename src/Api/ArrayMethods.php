<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Model\List\ArrayList;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

class ArrayMethods
{
    public static function avoid_empty_id_filters(DataList $list, array $idList, ?string $fieldName = 'ID')
    {
        $idList = self::filter_array($idList);
        if ($idList[0] < 1 && 1 === count($idList)) {
            return Injector::inst()->get(ArrayList::class);
        }

        return $list->filter([$fieldName => $idList]);
    }

    /**
     * return an array of ids that can be ued for ORM filters...
     *
     * @param mixed $array - hopefully an array
     */
    public static function filter_array($array): array
    {
        if (! is_array($array)) {
            $array = [];
        }

        if ([] === $array) {
            $array = [-1 => -1];
        }

        return array_values(array_unique($array));
    }

    /**
     * creates a sort string from a list of ID arrays...
     *
     * @param string
     */
    public static function create_sort_statement_from_id_array(array $ids, ?string $className = '', ?bool $includeElse = false): string
    {
        $ids = ArrayMethods::filter_array($ids);
        $ifStatement = 'CASE ';
        $count = 0;
        $singleton = DataObject::singleton($className);
        $baseTable = $singleton->baseTable();
        $tableName = $singleton->stageTable($baseTable, Versioned::get_stage());
        foreach ($ids as $id) {
            $ifStatement .= ' WHEN "' . $tableName . "\".\"ID\" = {$id} THEN {$count}";
            ++$count;
        }

        if ($includeElse) {
            $ifStatement .= ' ELSE 999999999 ';
        }

        return $ifStatement . ' END';
    }
}
