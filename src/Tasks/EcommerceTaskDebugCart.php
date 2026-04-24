<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class EcommerceTaskDebugCart extends BuildTask
{
    protected string $title = 'Debug your cart';

    protected static string $description = 'Check all the values in your cart to find any potential errors.';

    protected static string $commandName = 'ecommerce-debug-cart';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $order = ShoppingCart::current_order();
        $output->writeForHtml(self::debug_object($order));

        return Command::SUCCESS;
    }

    public static function debug_object($obj): string
    {
        $html = '
            <h2>' . $obj->ClassName . '</h2><ul>';
        $fields = Config::inst()->get($obj->ClassName, 'db');

        //db
        if (count($fields) > 0) {
            foreach ($fields as $key => $type) {
                $value = self::cleanup_value($type, $obj->{$key});
                $html .= sprintf('<li><b>%s (%s):</b> ', $key, $type) . $value . '</li>';
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
                $html .= sprintf('<li><b>%s (%s):</b> ', $key, $type) . $value . '</li>';
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

                $html .= sprintf('<li><b>%s (%s):</b> ', $key, $type) . $obj->{$field} . $value . ' </li>';
            }
        }

        //to do: has_many and many_many

        return $html . '</ul>';
    }

    private static function cleanup_value($type, $value)
    {
        switch ($type) {
            case 'HTMLText':
                $value = substr(strip_tags((string) $value), 0, 100);

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
