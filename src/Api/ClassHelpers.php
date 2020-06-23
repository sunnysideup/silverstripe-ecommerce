<?php

namespace Sunnysideup\Ecommerce\Api;

class ClassHelpers
{
    /**
     * Sanitise a model class' name for inclusion in a link
     *
     * @param string $class
     * @return string
     */
    public static function sanitise_class_name($class)
    {
        return str_replace('\\', '-', $class);
    }

    /**
     * Unsanitise a model class' name from a URL param
     *
     * @param string $class
     * @return string
     */
    public static function unsanitise_class_name($class)
    {
        return str_replace('-', '\\', $class);
    }
}
