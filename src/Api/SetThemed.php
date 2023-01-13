<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Core\Config\Config;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\SSViewer;

class SetThemed
{
    protected static $changed = 0;

    protected static $stage_before = true;

    public static function start()
    {
        $themed = Config::inst()->get(SSViewer::class, 'theme_enabled');
        self::$stage_before = Versioned::get_stage();
        if (! $themed) {
            Versioned::set_stage(Versioned::LIVE);
            ++self::$changed;
            Config::nest();
            Config::modify()->update(SSViewer::class, 'theme_enabled', true);
        }
    }

    public static function end()
    {
        if (self::$changed) {
            if (self::$stage_before && Versioned::LIVE !== self::$stage_before) {
                Versioned::set_stage(self::$stage_before);
            }
            Config::unnest();
            // Config::modify()->update(SSViewer::class, 'theme_enabled', false);
            --self::$changed;
        }
    }
}
