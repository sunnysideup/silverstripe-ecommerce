<?php

namespace Sunnysideup\Ecommerce\Api;

class ClassHelpers
{
    /**
     * Sanitise a model class' name for inclusion in a link.
     *
     * @param string $class
     *
     * @return string
     */
    public static function sanitise_class_name($class)
    {
        return str_replace('\\', '-', $class);
    }

    /**
     * Unsanitise a model class' name from a URL param.
     *
     * @param string $class
     *
     * @return string
     */
    public static function unsanitise_class_name($class)
    {
        return str_replace('-', '\\', $class);
    }

    /**
     * @param object $obj
     * @param bool   $showError
     */
    public static function check_for_instance_of($obj, string $className, ?bool $showError = true): bool
    {
        if ($obj instanceof $className) {
            return true;
        }
        if ($showError) {
            user_error('object provided is not an instanceof expected class ' . $className . ' instead it is a ' . get_class($obj));
        }

        return false;
    }
}
