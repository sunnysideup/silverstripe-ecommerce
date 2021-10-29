<?php

namespace Sunnysideup\Ecommerce\Traits;

use Sunnysideup\Ecommerce\Api\EcommerceCache;

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
     * returns TRUE if applied and false if not applied.
     */
    protected function partialCacheApplyVariablesFromCache(string $hash): bool
    {
        return $this->partialCacheApplyCacheFromHash($hash);
    }

    protected function partialCacheGetSerializedObject(?array $data = [])
    {
        $variables = [];
        foreach ($this->partialCacheGetFieldsToCache() as $variable) {
            $value = $this->{$variable};
            if (is_object($value) && is_a($value, DataObject::class)) {
                if (! empty($value->ClassName) && ! empty($value->ID)) {
                    $variables[$variable]['ClassName'] = $value->ClassName;
                    $variables[$variable]['ID'] = $value->ID;
                }
            } elseif (is_object($value)) {
                // can't cache
            } else {
                $variables[$variable] = $value;
            }
        }

        return serialize($variables);
    }

    /**
     * return true on successful caching.
     */
    protected function partialCacheSetCacheForHash(string $hash): bool
    {
        $data = $this->partialCacheGetSerializedObject();
        // data is already serialized, hence the TRUE as third param.
        return EcommerceCache::inst()->save($hash, $data, true);
    }

    /**
     * return array of values from hash.
     */
    protected function partialCacheGetCacheForHash(string $hash): array
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
     * apply to objects.
     */
    protected function partialCacheApplyCacheFromHash(string $hash): bool
    {
        if ($this->config()->get('use_partial_cache')) {
            $array = $this->partialCacheGetCacheForHash($hash);
            if ($array && count($array)) {
                $this->hasCache = true;
                foreach ($array as $variable => $value) {
                    if (in_array($variable, $this->partialCacheGetFieldsToCache(), true)) {
                        $this->{$variable} = $this->partialCacheArrayToObject($value);
                    }
                }
            }
        }

        return $this->hasCache;
    }

    /**
     * turns an array of ClassName and ID into objects.
     * and if this pattern does not match then the original value.
     *
     * @param array $value['ID' => , 'ClassName']
     *
     * @return mixed
     */
    protected function partialCacheArrayToObject($value)
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

    protected function partialCacheGetFieldsToCache(): array
    {
        if (method_exists($this, 'hasMethod')) {
            if ($this->hasMethod('partialCacheGetFieldsToCacheCustom')) {
                return $this->partialCacheGetFieldsToCacheCustom();
            }
        }

        return self::PARTIAL_CACHE_FIELDS_TO_CACHE;
    }
}
