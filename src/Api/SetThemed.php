<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Core\Config\Config;
use SilverStripe\View\SSViewer;

class SetThemed
{
    protected static $changed = true;

    public static function start()
    {
        $themed = Config::inst()->get(SSViewer::class, 'theme_enabled');
        if (! $themed) {
            self::$changed = true;
            Config::nest();
            Config::modify()->update(SSViewer::class, 'theme_enabled', true);
        }
    }

    public static function end()
    {
        if (self::$changed) {
            Config::unnest();
            // Config::modify()->update(SSViewer::class, 'theme_enabled', false);
            self::$changed = false;
        }
    }
}
