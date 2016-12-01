<?php


/**
 * @description: cleans up old (abandonned) carts...
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class EcommerceTaskCacheTest extends BuildTask
{
    protected $title = 'Test Silverstripe Cache';

    protected $description = 'Basic test for the silverstripe cache.It will show a time and date that determines how old the cache is.';

    /**
     *@return int - number of carts destroyed
     **/
    public function run($request)
    {
        $cachekey = 'foo';
        $cache = SS_Cache::factory($cachekey);
        if (!($result = $cache->load($cachekey))) {
            $result = date('Y-m-d H:i:s');;
            $cache->save($result, $cachekey);
        }
        echo $result;
    }

}
