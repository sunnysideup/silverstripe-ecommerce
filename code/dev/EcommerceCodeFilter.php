<?php
/**
 */
class EcommerceCodeFilter extends Object
{
    /**
     * @var array
     */
    protected $regexReplacements = array(
        '/[^A-Za-z0-9.\-_]+/u' => '', // remove non-ASCII chars, only allow alphanumeric, dashes and dots.
        '/[\-]{2,}/u' => '-', // remove duplicate dashes
        '/[\_]{2,}/u' => '_', // remove duplicate underscores
    );

    /**
     * @var array
     */
    protected $straightReplacements = array(
        '&amp;' => '-and-', //change ampersands to -and-
        '&' => '-and-', //change ampersands to -and-
        ' ' => '-', // remove whitespace
    );

    /**
     * makes sure that code is unique and gets rid of special characters
     * should be run in onBeforeWrite.
     *
     * @param DataObject | String $obj
     */
    public function checkCode($obj, $fieldName = 'Code')
    {
        //exception dealing with Strings
        $isObject = true;
        if (!is_object($obj)) {
            $str = $obj;
            $obj = new DataObject();
            $obj->$fieldName = strval($str);
            $isObject = false;
        }
        $s = trim($obj->$fieldName);
        foreach ($this->regexReplacements as $regex => $replace) {
            $s = preg_replace($regex, $replace, $s);
        }
        foreach ($this->straightReplacements as $find => $replace) {
            $s = str_replace($find, $replace, $s);
        }
        $s = trim($s);
        //check for other ones.
        if ($s) {
            $count = 2;
            $code = $s;
            while ($isObject && $obj::get()->filter(array($fieldName => $s))->exclude(array('ID' => $obj->ID))->Count()) {
                $s = $code.'_'.$count;
                ++$count;
            }
        }
        $obj->$fieldName = $s;

        return $obj->$fieldName;
    }
}
