<?php

/**
 *
 * @author: Nicolaas - modules [at] sunnysideup.co.nz
 **/

// optional settings that may be useful
//setlocale (LC_TIME, 'en_NZ@dollar', 'en_NZ.UTF-8', 'en_NZ', 'nz', 'nz');
//date_default_timezone_set("NZ");



// CACHING RECOMMENDATION - you can overrule that in the mysite _config.php file...
SS_Cache::set_cache_lifetime('any', 86000, 10);
