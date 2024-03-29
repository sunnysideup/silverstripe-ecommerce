<?php

namespace Sunnysideup\Ecommerce\Dev;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;

class EcommerceCodeFilter
{
    use Configurable;
    use Extensible;
    use Injectable;

    /**
     * @var array
     */
    protected $regexReplacements = [
        '/[^A-Za-z0-9.\-_]+/u' => '', // remove non-ASCII chars, only allow alphanumeric, dashes and dots.
        '/[\-]{2,}/u' => '-', // remove duplicate dashes
        '/[\_]{2,}/u' => '_', // remove duplicate underscores
    ];

    /**
     * @var array
     */
    protected $straightReplacements = [
        '&amp;' => '-and-', //change ampersands to -and-
        '&' => '-and-', //change ampersands to -and-
        ' ' => '-', // remove whitespace
    ];

    /**
     * makes sure that code is unique and gets rid of special characters
     * should be run in onBeforeWrite.
     *
     * @param DataObject|string $obj
     * @param mixed             $fieldName
     */
    public function checkCode($obj, $fieldName = 'Code')
    {
        //exception dealing with Strings
        $isObject = true;
        if (! is_object($obj)) {
            $str = $obj;
            $obj = new DataObject();
            $obj->{$fieldName} = strval($str);
            $isObject = false;
        }
        $s = trim((string) $obj->{$fieldName});
        foreach ($this->regexReplacements as $regex => $replace) {
            $s = preg_replace($regex, $replace, (string) $s);
        }
        foreach ($this->straightReplacements as $find => $replace) {
            $s = str_replace($find, $replace, $s);
        }
        $s = trim((string) $s);
        //check for other ones.
        if ($s) {
            $count = 2;
            $code = $s;
            while ($isObject && $obj::get()->filter([$fieldName => $s])->exclude(['ID' => $obj->ID])->exists()) {
                $s = $code . '_' . $count;
                ++$count;
            }
        }
        $obj->{$fieldName} = $s;

        return $obj->{$fieldName};
    }
}
