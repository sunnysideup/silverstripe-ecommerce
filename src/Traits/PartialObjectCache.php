<?php

namespace Sunnysideup\Ecommerce\Traits;

/**
 * To make this trait work, you will need to add a method `getFieldsToCache` to
 * any object that you are adding this to.
 */
trait PartialObjectCache
{

    /**
     * @var bool
     */
    protected $hasCache = false;

    /**
     * apply any available cache.
     * @param  string $hash
     * @return self
     */
    protected function setCacheHash(?string $hash = ''): self
    {
        if ($hash) {
            $this->applyCacheFromHash($hash);
        }

        return $this;
    }


    protected function getSerializedObject(?array $data = [])
    {
        $variables = [];
        foreach ($this->getFieldsToCache() as $variable) {
            $value = $this->{$variable};
            if (is_object($value) && is_a($value, DataObject::class)) {
                if (! empty($value->ClassName) && ! empty($value->ID)) {
                    $variables[$variable]['ClassName'] = $value->ClassName;
                    $variables[$variable]['ID'] = $value->ID;
                }
            } elseif (is_object($value)) {
                //do nothing - dont cache
            } else {
                $variables[$variable] = $value;
            }
        }

        return serialize($variables);
    }

    /**
     * @param string $data optional
     */
    protected function getHash(?string $data = ''): string
    {
        if (! $data) {
            $data = $this->getSerializedObject();
        }

        return 'search' . crc32($data);
    }

    protected function setCacheForHash(): string
    {
        $data = $this->getSerializedObject();
        $hash = $this->getHash($data);

        EcommerceCache::inst()->save($hash, $data, true);

        return $hash;
    }

    /**
     * return array of values from hash
     */
    protected function getCacheForHash(string $hash): array
    {
        $array = [];
        $cache = EcommerceCache::inst();
        if ($cache->hasCache($hash)) {
            $array = $cache->retrieve($hash);
            if (! is_array($array)) {
                $array = [];
            }
        }

        return $array;
    }

    /**
     * apply to objects
     */
    protected function applyCacheFromHash(string $hash)
    {
        if($this->config()->get('use_cache')) {
            $array = $this->getCacheForHash($hash);
            if ($array && count($array)) {
                $this->hasCache = true;
                foreach ($array as $variable => $value) {
                    if (in_array($variable, self::FIELDS_TO_CACHE, true)) {
                        $this->{$variable} = $this->arrayToObject($value);
                    }
                }
            }
        }
    }

    /**
     * turns an array of ClassName and ID into objects.
     * and if this pattern does not match then the original value
     * @param array $value['ID' => , 'ClassName']
     *
     * @return mixed
     */
    protected function arrayToObject($value)
    {
        if (is_array($value) && 2 === count($value) && isset($value['ID'], $value['ClassName'])) {
            $className = $value['ClassName'];
            $id = (int) $value['ID'];
            if (class_exists($className) && $id) {
                return $className::get()->byId($id);
            }
        }

        return $value;
    }
}
