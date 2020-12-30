<?php

namespace Sunnysideup\Ecommerce\Api;

class ArrayMethods
{
    /**
     * return an array that can be ued for ORM filters...
     *
     * @param  mixed $array - hopefully an array
     *
     * @return array
     */
    public static function filter_array($array) : array
    {
        if(! is_array($array)) {
            $array = [];
        }
        if(count($array) === 0) {
            $array = [0 => 0];
        }

        return $array;
    }
}
