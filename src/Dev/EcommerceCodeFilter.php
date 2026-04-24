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
        '/[^A-Za-z0-9.\-_]+/u' => '', // remove all characters that aren't alphanumeric, dots, dashes, or underscores
        '/[\-]{2,}/u' => '-', // remove duplicate dashes
        '/[_]{2,}/u' => '_', // remove duplicate underscores
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
     * @param DataObject $obj
     * @param string     $fieldName
     */
    public function checkCode(object $obj, string $fieldName): string
    {

        $s = trim((string) $obj->{$fieldName});
        foreach ($this->regexReplacements as $regex => $replace) {
            $s = preg_replace($regex, (string) $replace, (string) $s);
        }

        foreach ($this->straightReplacements as $find => $replace) {
            $s = str_replace($find, $replace, $s);
        }

        $s = trim((string) $s);
        //check for other ones.
        if ($s !== '' && $s !== '0') {
            $className = $obj::class;
            if ($className::get()->filter([$fieldName => $s])->exclude(['ID' => $obj->ID])->exists()) {
                user_error(sprintf('Code %s already exists for %s.', $s, $className), E_USER_WARNING);
            }
        }

        $obj->{$fieldName} = $s;

        return $obj->{$fieldName};
    }
}
