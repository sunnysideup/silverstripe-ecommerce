<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Core\ClassInfo;
use Sunnysideup\Ecommerce\Interfaces\BuyableModel;

class ClassHelpers
{
    /**
     * Length of the code that represents a buyable class name in (share) links.
     */
    private const BUYABLE_CODE_LENGTH = 8;

    /**
     * Turns a buyable class name into a short, lowercase, hex code (e.g. 'a1b2c3d4')
     * so that class names do not show in links.
     *
     * This is NOT encryption: no secret is used and anyone can work out the code
     * for a class name. It can be reversed with `decode_buyable_class_name`.
     */
    public static function encode_buyable_class_name(string $class): string
    {
        return substr(md5(strtolower(ltrim($class, '\\'))), 0, self::BUYABLE_CODE_LENGTH);
    }

    /**
     * Finds the buyable class name for a code created with `encode_buyable_class_name`.
     * For backwards compatibility, old-style links (sanitised class names) still work.
     * Returns null if no buyable class matches.
     */
    public static function decode_buyable_class_name(string $code): ?string
    {
        $code = trim($code);
        if ('' === $code) {
            return null;
        }
        $lowerCode = strtolower($code);
        foreach (ClassInfo::implementorsOf(BuyableModel::class) as $class) {
            if (self::encode_buyable_class_name($class) === $lowerCode) {
                return $class;
            }
        }
        // legacy links
        $class = self::unsanitise_class_name($code);
        if (class_exists($class) && in_array(BuyableModel::class, class_implements($class), true)) {
            return $class;
        }

        return null;
    }

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

    public static function class_name_for_db_query($className)
    {
        return str_replace('\\', '\\\\', $className);
    }

    /**
     * @param object $obj
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
