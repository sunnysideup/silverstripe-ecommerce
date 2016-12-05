<?php


class EcommerceTaskCacheTest extends BuildTask
{
    protected $title = 'Test Silverstripe Cache';

    protected $description = 'Basic test for the silverstripe cache. It will show the date and time the cache was made.';

    public function run($request)
    {
        $cachekey = 'foo';
        $cache = SS_Cache::factory($cachekey);
        $result = $cache->load($cachekey);
        if (!$result || isset($_GET['reload'])) {
            $time = time();
            for($i = 1; $i < $time; $i = $i + 75) {
                $temp = $time / $time - rand(0,10);
            }
            $result = date('Y-m-d H:i:s');;
            DB::alteration_message('not from cache: '.$result, 'deleted');
            $cache->save($result, $cachekey);
        } else {
            DB::alteration_message('from cache: '.$result, 'created');
        }
    }

}
