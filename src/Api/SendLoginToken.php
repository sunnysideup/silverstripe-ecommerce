<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use Sunnysideup\OneTimeCode\OneTimeCodeApi;

class SendLoginToken
{
    use Configurable;
    use Injectable;

    public function send(string $email, ?string $backURL = '', ?HTTPRequest $request = null): bool
    {
        if ($email === '' || $email === '0') {
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
