<?php

namespace Sunnysideup\Ecommerce\Api;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Control\Director;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Versioned\Versioned;

/**
 * Provides a standard interface for caching product and group information
 *
 * Can be used in conjuction with the standard Silverstripe Partial caching
 * functionality.
 */
class EcommerceCache implements Flushable
{
    use Injectable;

    /**
     * @var CacheInterface
     */
    protected $cacheBackend;

    public static function inst(): EcommerceCache
    {
        return Injector::inst()->get(self::class);
    }

    /**
     * Set the cache object to use when storing / retrieving partial cache blocks.
     */
    public function setCacheBackend(CacheInterface $cacheBackend): EcommerceCache
    {
        $this->cacheBackend = $cacheBackend;

        return $this;
    }

    /**
     * Get the cache object to use when storing / retrieving stuff in the
     * Silverstripe Cache
     *
     * @return CacheInterface
     */
    public function getCacheBackend()
    {
        if (! $this->cacheBackend) {
            $this->cacheBackend = Injector::inst()->get(CacheInterface::class . '.Ecommerce');
        }
        return $this->cacheBackend;
    }

    public function hasCache(string $cacheKey): bool
    {
        if ($this->AllowCaching()) {
            $cacheKey = $this->cacheKeyRefiner($cacheKey);

            return $this->getCacheBackend()->has($cacheKey);
        }
        return false;
    }

    /**
     * Retrieve an object from the cache
     *
     * @return mixed|null
     */
    public function retrieve(string $cacheKey, ?bool $alreadyUnserialized = false)
    {
        if ($this->hasCache($cacheKey)) {
            $cacheKey = $this->cacheKeyRefiner($cacheKey);
            $data = $this->getCacheBackend()->get($cacheKey);
            if ($alreadyUnserialized === false) {
                $data = unserialize($data);
            }

            return $data;
        }

        return null;
    }

    /**
     * returns true when the data is saved...
     *
     * @param string $cacheKey - key under which the data is saved...
     * @param mixed  $data
     */
    public function save($cacheKey, $data, ?bool $alreadySerialized = false): bool
    {
        if ($this->AllowCaching()) {
            $cacheKey = $this->cacheKeyRefiner($cacheKey);
            if ($alreadySerialized === false) {
                $data = serialize($data);
            }
            $this->getCacheBackend()->set($cacheKey, $data);

            return true;
        }

        return false;
    }

    public function AllowCaching(): bool
    {
        return true;
    }

    public function clear()
    {
        $this->getCacheBackend()->clear();
    }

    public static function flush()
    {
        EcommerceCache::inst()->clear();
    }

    /**
     * @param string $cacheKey
     */
    protected function cacheKeyRefiner($cacheKey): string
    {
        $cacheKey .= '_' . Versioned::get_reading_mode() . '_' . Director::get_environment_type();
        $arrayOfReservedChars = [
            '{',
            '}',
            '(',
            ')',
            '/',
            '\\',
            '@',
            ':',
            '.',
        ];
        return str_replace($arrayOfReservedChars, '_', $cacheKey);
    }
}
