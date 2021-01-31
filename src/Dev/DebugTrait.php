<?php

namespace Sunnysideup\Ecommerce\Dev;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Permission;
use SilverStripe\View\ArrayData;

/**
 * small trait to make non-Viewable objects printable.
 */
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
            return
                $this->arrayToUl($this->{$method}(...$arguments)) .
                '<div style="color: blue; font-size: 12px; margin-top: 10px;">⇒' . get_class($this) . '::<strong>' . $method . '</strong></div>
                <hr style="margin-bottom: 30px;"/>';
        }
    }

    public function ClassName(): string
    {
        return static::class;
    }

}
