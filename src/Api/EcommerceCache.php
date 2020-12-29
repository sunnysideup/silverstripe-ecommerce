<?php

namespace Sunnysideup\Ecommerce\Helpers;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Core\Injector\Injectable;

/**
 * Provides a standard interface for caching product and group information
 *
 * Can be used in conjuction with the standard Silverstripe Partial caching
 * functionality.
 */
class EcommerceCache
{
    use Injectable;

    /**
     * @var CacheInterface
     */
    protected $cacheBackend = null;


    public static function inst() : EcommerceCache
    {
        return Injector::inst()->get(self::class);
    }

    /**
     * Set the cache object to use when storing / retrieving partial cache blocks.
     *
     * @param CacheInterface $cacheBackend
     */
    public function setCacheBackend(CacheInterface $cacheBackend) : EcommerceCache
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
        return $this->cacheBackend ?: Injector::inst()->get(CacheInterface::class . '.Ecommerce');
    }

    /**
     * @param string $cacheKey
     * @param string $filterKey
     *
     * @return string
     */
    protected function cacheKeyRefiner($cacheKey) : string
    {
        return $cacheKey . '_' . Versioned::get_reading_mode();
    }

    public function hasCache(string $cacheKey) : bool
    {
        if ($this->AllowCaching()) {
            $cache = $this->getCacheBackend();
            $cacheKey = $this->cacheKeyRefiner($cacheKey);
            return $cache->has($cacheKey);
        }
        return false;

    }

    /**
     * Retrieve an object from the cache
     *
     * @param string $cacheKey
     *
     * @return mixed|null
     */
    public function retrieve(string $cacheKey, ?bool $alreadyUnserialized = false)
    {
        if ($this->hasCache($cacheKey)) {
            $cache = $this->getCacheBackend();
            $cacheKey = $this->cacheKeyRefiner($cacheKey);
            $data = $cache->get($cacheKey);
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
     *
     * @return bool
     */
    public function save($cacheKey, $data, ?bool $alreadySerialized = false) : bool
    {
        if ($this->AllowCaching()) {
            $cache = $this->getCacheBackend();
            $cacheKey = $this->cacheKeyRefiner($cacheKey);
            if ($alreadySerialized === false) {
                $data = serialize($data);
            }
            $cache->set($cacheKey, $data);

            return true;
        }

        return false;
    }

    public function AllowCaching() : bool
    {
        return true;
    }
}
