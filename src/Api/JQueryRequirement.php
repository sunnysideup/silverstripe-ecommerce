<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\View\Requirements;

/**
 * Loads jQuery for the (few) front-end pages and forms of the ecommerce module that need it.
 *
 * jQuery comes from a CDN. To make sure that the file has not been changed by anyone
 * else, set the SRI hash (Subresource Integrity) for the file.  When there is no hash
 * the file is loaded without the integrity check.
 *
 * You can work out the hash like this:
 *
 *     curl -s https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js | openssl dgst -sha384 -binary | openssl base64 -A
 *
 * and then add it to your config, e.g. in app/_config/ecommerce.yml:
 *
 *     Sunnysideup\Ecommerce\Api\JQueryRequirement:
 *       integrity: 'sha384-xxxxxxxx'
 *
 * If you change the url, then you MUST also change the integrity (or set it to an empty string).
 */
class JQueryRequirement
{
    use Configurable;
    use Injectable;

    /**
     * @var string
     */
    private static $url = 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js';

    /**
     * e.g. sha384-xxxxxxxx
     *
     * @var string
     */
    private static $integrity = '';

    public static function require(): void
    {
        $url = (string) static::config()->get('url');
        $integrity = trim((string) static::config()->get('integrity'));
        if ('' !== $integrity) {
            Requirements::javascript($url, [
                'integrity' => $integrity,
                'crossorigin' => 'anonymous',
            ]);
        } else {
            Requirements::javascript($url);
        }
    }
}
