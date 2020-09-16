<?php

namespace Sunnysideup\Ecommerce\Helpers;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Versioned\Versioned;

/**
 * Provides a standard interface for caching product and group information
 *
 * Can be used in conjuction with the standard Silverstripe Partial caching
 * functionality.
 */
class CachingHelper extends Extension
{
    /**
     * @var CacheInterface
     */
    protected $cacheBackend = null;

    /**
     * Set the cache object to use when storing / retrieving partial cache blocks.
     *
     * @param CacheInterface $cacheBackend
     */
    public function setCacheBackend(CacheInterface $cacheBackend)
    {
        $this->cacheBackend = $cacheBackend;

        return $this->owner;
    }

    /**
     * Get the cache object to use when storing / retrieving stuff in the
     * Silverstripe Cache
     *
     * @return CacheInterface
     */
    public function getCacheBackend()
    {
        return $this->cacheBackend ?: Injector::inst()->get(CacheInterface::class . '.EcomPG');
    }

    /**
     * Retrieve an object from the cache
     *
     * @param string $cacheKey
     *
     * @return mixed
     */
    protected function retrieveFromCache(string $cacheKey)
    {
        if ($this->owner->AllowCaching()) {
            $cache = $this->getCacheBackend();
            $data = $cache->get($cacheKey);

            if (!$cache->has($cacheKey)) {
                return;
            }

            return $data;
        }

        return;
    }

    /**
     * returns true when the data is saved...
     *
     * @param mixed  $data
     * @param string $cacheKey - key under which the data is saved...
     *
     * @return bool
     */
    protected function saveIntoCache($data, $cacheKey)
    {
        if ($this->owner->AllowCaching()) {
            $cache = $this->getCacheBackend();
            $cache->set($cacheKey, $data);

            return true;
        }

        return false;
    }

    /**
     * @param string $cacheKey
     * @param string $filterKey
     *
     * @return string
     */
    public function cacheKey($cacheKey)
    {
        $cacheKey .= '_' . $this->owner->ID . '_' . Versioned::get_reading_mode();

        return $cacheKey ;
    }

}
