<?php

namespace Sunnysideup\Ecommerce\Api;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Control\Director;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * Provides a standard interface for caching product and group information.
 *
 * Can be used in conjuction with the standard Silverstripe Partial caching
 * functionality.
 *
 * usage:
 * ```php
 *     $myCachedData = EcommerceCache::inst()->retrieve($key);
 *     EcommerceCache::inst()->save($key, $myUncachedData);
 * ```
 */
class EcommerceCache implements Flushable
{
    use Injectable;

    /**
     * @var CacheInterface
     */
    protected $cacheBackend;

    protected static $productCacheKey = [
        'ProductCount' => 0,
        'ProductMaxLastEdited' => 0,
        'ProductGroupCount' => 0,
        'ProductGroupMaxLastEdited' => 0,
    ];

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
     * Silverstripe Cache.
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

    public function hasCache(string $cacheKey, $cacheKeyAlreadyRefined = false): bool
    {
        if ($this->AllowCaching()) {
            if (! $cacheKeyAlreadyRefined) {
                $cacheKey = $this->cacheKeyRefiner($cacheKey);
            }

            return $this->getCacheBackend()->has($cacheKey);
        }

        return false;
    }

    public function productCacheKey()
    {
        if (0 === self::$productCacheKey['ProductCount']) {
            self::$productCacheKey['ProductCount'] = Product::get()->count();
        }
        if (0 === self::$productCacheKey['ProductMaxLastEdited']) {
            self::$productCacheKey['ProductMaxLastEdited'] = strtotime(Product::get()->max('LastEdited'));
        }
        if (0 === self::$productCacheKey['ProductGroupCount']) {
            self::$productCacheKey['ProductGroupCount'] = ProductGroup::get()->count();
        }
        if (0 === self::$productCacheKey['ProductGroupMaxLastEdited']) {
            self::$productCacheKey['ProductGroupMaxLastEdited'] = strtotime(ProductGroup::get()->max('LastEdited'));
        }

        return implode('_', self::$productCacheKey);
    }

    /**
     * Retrieve an object from the cache.
     *
     * @return null|mixed
     */
    public function retrieve(string $cacheKey, ?bool $alreadyUnserialized = false)
    {
        $cacheKey = $this->cacheKeyRefiner($cacheKey);
        $data = $this->getCacheBackend()->get($cacheKey);
        if ($data) {
            if (false === $alreadyUnserialized) {
                $data = unserialize($data);
            }

            return $data;
        }

        return null;
    }

    public function retrieveAsIdList(string $cacheKey, ?bool $alreadyUnserialized = false)
    {
        return ArrayMethods::filter_array($this->retrieve($cacheKey, $alreadyUnserialized));
    }

    /**
     * returns true when the data is saved...
     *
     * @param string $cacheKey - key under which the data is saved...
     * @param mixed  $data
     */
    public function save(string $cacheKey, $data, ?bool $alreadySerialized = false): bool
    {
        if ($this->AllowCaching()) {
            $cacheKey = $this->cacheKeyRefiner($cacheKey);
            if (false === $alreadySerialized) {
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
    public function cacheKeyRefiner($cacheKey): string
    {
        if (is_array($cacheKey)) {
            $cacheKey = implode('_', $cacheKey);
        }
        $cacheKey .=
            '_' . Versioned::get_reading_mode() .
            '_' . Director::get_environment_type();
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
