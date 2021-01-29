<?php

namespace Sunnysideup\Ecommerce\Dev;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;

trait DebugTrait
{

/**
     * for debug purposes!
     * @param string
     */
    public function XML_val(?string $method, $arguments = [])
    {
        if(! is_array($arguments)) {
            $arguments = [$arguments];
        }
        if(Permission::check('ADMIN')) {
            return $this->arrayToUl($this->$method(...$arguments), 1);
        }
    }

    protected function arrayToUl( $mixed ) : string
    {
        if($mixed === false) {
            return '<span style="color: grey">[NO]</span>';
        } elseif($mixed === true) {
            return '<span style="color: grey">[YES]</span>';
        } elseif($mixed === null) {
            return '<span style="color: grey">[NULL]</span>';
        } elseif($mixed === '') {
            return '<span style="color: grey">[EMPTY STRING]</span>';
        }elseif(is_array($mixed) && count($mixed) === 0) {
            return '<span style="color: grey">[EMPTY ARRAY]</span>';
        }elseif(is_object($mixed)) {
            return var_dump($mixed, 1);
        } elseif(is_array($mixed) ) {
            $html = '';
            $html .= '<ul>';
            $isAssoc = $this->isAssoc($mixed);
            $isLarge = count($mixed) > 20;
            $after = '';
            $style = '';
            $keyString = '';
            if($isLarge) {
                $style = 'display: inline;';
                $after = ', ';
            }
            foreach ( $mixed as $key => $item ) {
                if($isAssoc) {
                    $keyString .= '<strong>'.$key.'</strong>: ';
                }
                $html .= '<li style="'.$style.'">'. $keyString . $this->arrayToUl($item). $after . '</li>';
            }
            return $html . '</ul>';
        } else {
            return '<span style="color: green">' . $mixed  . '</span>';
        }
    }

    public function ClassName() : string
    {
        return get_class($this);
    }

    protected function isAssoc(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

}
