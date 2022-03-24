<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Core\Config\Config;
use SilverStripe\View\SSViewer;

use SilverStripe\Versioned\Versioned;

class SetThemed
{
    protected static $changed = true;
    protected static $stage = true;

    public static function start()
    {
        $themed = Config::inst()->get(SSViewer::class, 'theme_enabled');
        self::$stage = Versioned::get_stage();
        if (! $themed) {
            Versioned::set_stage(Versioned::LIVE);
            self::$changed = true;
            Config::nest();
            Config::modify()->update(SSViewer::class, 'theme_enabled', true);
        }
    }

    public static function end()
    {
        if (self::$changed) {
            Versioned::set_stage(self::$stage);
            Config::unnest();
            // Config::modify()->update(SSViewer::class, 'theme_enabled', false);
            self::$changed = false;
        }
    }
}
