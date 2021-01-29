<?php

namespace Sunnysideup\Ecommerce\Dev;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Permission;
use SilverStripe\View\ArrayData;

trait DebugTrait
{
    /**
     * Get the value of a field on this object, automatically inserting the value into any available casting objects
     * that have been specified.
     *
     * @param string $fieldName
     * @param array $arguments
     * @param bool $cache Cache this object
     * @param string $cacheName a custom cache name
     * @return object|DBField
     */
    public function obj($fieldName, $arguments = [], $cache = false, $cacheName = null)
    {
        if (Permission::check('ADMIN')) {
            $list = call_user_func_array([$this, $fieldName], $arguments ?: []);
            return $this->arrayToUl($list);
        }
    }

    /**
     * for debug purposes!
     * @param string $method
     */
    public function XML_val(?string $method, $arguments = [])
    {
        if (Permission::check('ADMIN')) {
            if (! is_array($arguments)) {
                $arguments = [$arguments];
            }
            return $this->arrayToUl($this->{$method}(...$arguments));
        }
    }

    public function ClassName(): string
    {
        return static::class;
    }

    protected function arrayToUl($mixed): string
    {
        if ($mixed === false) {
            return '<span style="color: grey">[NO]</span>';
        } elseif ($mixed === true) {
            return '<span style="color: grey">[YES]</span>';
        } elseif ($mixed === null) {
            return '<span style="color: grey">[NULL]</span>';
        } elseif ($mixed === '') {
            return '<span style="color: grey">[EMPTY STRING]</span>';
        } elseif (is_array($mixed) && count($mixed) === 0) {
            return '<span style="color: grey">[EMPTY ARRAY]</span>';
        } elseif (is_object($mixed)) {
            if ($mixed instanceof ArrayData) {
                return $this->arrayToUl($mixed->toMap());
            } elseif ($mixed instanceof ArrayList) {
                return $this->arrayToUl($mixed->toArray());
            } elseif ($mixed instanceof DataList) {
                return $this->arrayToUl($mixed->map('ID', 'Title')->toArray());
            }
            return print_r($mixed, 1);
        } elseif (is_array($mixed)) {
            $html = '';
            $html .= '<ul>';
            $isAssoc = $this->isAssoc($mixed);
            $isLarge = count($mixed) > 20;
            $after = '';
            $style = '';
            $keyString = '';
            if ($isLarge) {
                $style = 'display: inline;';
                $after = ', ';
            }
            foreach ($mixed as $key => $item) {
                if ($isAssoc) {
                    $keyString = '<strong>' . $key . '</strong>: ';
                }
                $html .= '<li style="' . $style . '">' . $keyString . $this->arrayToUl($item) . $after . '</li>';
            }
            return $html . '</ul>';
        }
        return '<span style="color: green">' . $mixed . '</span>';
    }

    protected function isAssoc(array $arr)
    {
        if ($arr === []) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
