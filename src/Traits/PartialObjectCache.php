<?php

/**
 * To make this trait work, you will need to add a method `getFieldsToCache` to
 * any object that you are adding this to.
 *
 */
trait PartialObjectCache
{

    /**
     * @var bool
     */
    protected $hasCache = false;

    public function setCacheHash(?string $hash = ''): self
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
                if (! ( empty($value->ClassName) || empty($value->ID) ) ) {
                    $variables[$variable]['ClassName'] = $value->ClassName;
                    $variables[$variable]['ID'] = $value->ID;
                }
            } elseif (is_object($value)) {
                //do nothing
            } else {
                $variables[$variable] = $value;
            }
        }

        return serialize($variables);
    }

    /**
     * @param string $data optional
     */
    protected function getHash(?string $data = ''): int
    {
        if (! $data) {
            $data = $this->getSerializedObject();
        }

        return crc32($data);
    }

    protected function setCacheForHash(): float
    {
        $data = $this->getSerializedObject();
        $hash = $this->getHash($data);

        EcommerceCache::inst()->save($hash, $data, true);

        return $hash;
    }

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

    protected function applyCacheFromHash(string $hash): array
    {
        $array = $this->getCacheForHash($hash);
        if ($array && count($array) && $this->config()->get('use_cache')) {
            $this->hasCache = true;
            foreach ($array as $variable => $value) {
                if (in_array($variable, self::FIELDS_TO_CACHE, true)) {
                    $this->{$variable} = $this->arrayToObject($value);
                }
            }
        }

        return $array;
    }

    /**
     * turns an array of ClassName and ID into objects
     * @param  array $value['ID' => , 'ClassName']
     * @return DataObject
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
