<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\FieldType\DBBoolean;
use Sunnysideup\Ecommerce\Api\ShoppingCart;

class EcommerceTaskDebugCart extends BuildTask
{
    protected $title = 'Debug your cart';

    protected $description = 'Check all the values in your cart to find any potential errors.';

    public function run($request)
    {
        $order = ShoppingCart::current_order();
        echo self::debug_object($order);
    }

    public static function debug_object($obj)
    {
        $html = '
            <h2>' . $obj->ClassName . '</h2><ul>';
        $fields = Config::inst()->get($obj->ClassName, 'db');

        //db
        if (count($fields) > 0) {
            foreach ($fields as $key => $type) {
                $value = self::cleanup_value($type, $obj->{$key});
                $html .= "<li><b>{$key} ({$type}):</b> " . $value . '</li>';
            }
        }

        //casted variables
        $fields = Config::inst()->get($obj->ClassName, 'casting', Config::UNINHERITED);
        if (count($fields) > 0) {
            foreach ($fields as $key => $type) {
                $method = $key;
                if ($obj->hasMethod($method)) {
                    $value = $obj->{$method}();
                } else {
                    $method = 'get' . $key;
                    $value = $obj->hasMethod($method) ? $obj->{$method}() : $obj->{$key};
                }
                $value = self::cleanup_value($type, $value);
                $html .= "<li><b>{$key} ({$type}):</b> " . $value . '</li>';
            }
        }

        //has_one
        $fields = Config::inst()->get($obj->ClassName, 'has_one', Config::UNINHERITED);
        if (count($fields) > 0) {
            foreach ($fields as $key => $type) {
                $value = '';
                $field = $key . 'ID';
                $object = $obj->{$key}();
                if ($object && ($object && $object->exists())) {
                    $value = ', ' . $object->getTitle();
                }
                $html .= "<li><b>{$key} ({$type}):</b> " . $obj->{$field} . $value . ' </li>';
            }
        }
        //to do: has_many and many_many

        return $html . '</ul>';
    }

    private static function cleanup_value($type, $value)
    {
        switch ($type) {
            case 'HTMLText':
                $value = substr(strip_tags( (string) $value), 0, 100);

                break;
            case DBBoolean::class:
                $value = $value ? 'YES' : 'NO';

                break;
            default:
                break;
        }

        return $value;
    }
}
