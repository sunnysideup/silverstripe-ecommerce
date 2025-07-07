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

        /** @var TokenConfig $config */
        $config = Injector::inst()->get(TokenConfig::class);

        if ($config->isSameBrowser()) {
            $token = uniqid('logintoken', true);
            $cookieDuration = ((1 / 24) / 60) * $config->getTokenLifetime();

            Cookie::set(TokenLoginHandler::SECURITY_TOKEN, $token, $cookieDuration);
            $request->getSession()->set(TokenLoginHandler::SECURITY_TOKEN, $token);
        }

        $member = $this->getMemberAndValidate($email);
        if (! $member) {
            return false;
        }

        $member->generateToken();
        $member->write();

        $signinLink = $this->getSigninLink($member, $backURL);

        Email::create()
            ->setHTMLTemplate('Firesphere\\MagicLogin\\Email\\TokenLoginEmail')
            ->setData($member)
            ->addData('SigninLink', $signinLink)
            ->setSubject(_t(
                'Firesphere\\MagicLogin\\Controllers\\TokenLoginHandler.MAILSUBJECT',
                'Your magic log-in link'
            ))
            ->setTo($member->Email)
            ->send();

        return true;
    }

    protected function getSigninLink(Member $member, ?string $backURL = ''): string
    {
        $url = sprintf('/Security/login/token/token?token=%s', $member->LoginToken);

        if ($backURL) {
            $url .= '&BackURL=' . urlencode($backURL);
        }

        return Director::absoluteURL($url);
    }

    protected function getMemberAndValidate(string $email): ?Member
    {
        $field = Member::config()->get('unique_identifier_field') ?? 'Email';

        /** @var Member|null $member */
        $member = Member::get()
            ->filter([$field => $email])
            ->first();

        if (
            ! $member ||
            ! $member->isInDB() ||
            ! filter_var($member->Email, FILTER_VALIDATE_EMAIL) ||
            ($member->TokenExpires && $member->TokenExpires > time())
        ) {
            return null;
        }

        return $member;
    }
}
