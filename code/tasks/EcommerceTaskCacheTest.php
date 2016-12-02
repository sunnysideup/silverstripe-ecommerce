<?php


class EcommerceTaskCacheTest extends BuildTask
{
    protected $title = 'Test Silverstripe Cache';

    protected $description = 'Basic test for the silverstripe cache. It will show the date and time the cache was made.';

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