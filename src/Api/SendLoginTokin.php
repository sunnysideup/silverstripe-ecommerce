<?php

namespace Sunnysideup\Ecommerce\Api;

use Firesphere\MagicLogin\Config\TokenConfig;
use Firesphere\MagicLogin\Controllers\TokenLoginHandler;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Member;
use Sunnysideup\OneTimeCode\OneTimeCodeApi;

class SendLoginToken
{
    use Configurable;
    use Injectable;

    public function send(string $email, ?string $backURL = '', ?HTTPRequest $request = null): bool
    {
        if (! $email) {
            return false;
        }

        $request ??= Controller::curr()?->getRequest();
        if (! $request) {
            return false;
        }
        Injector::inst()
            ->get(OneTimeCodeApi::class)
            ->SendOneTimeCode(['Email' => $email], $request);
        return true;
    }
}
