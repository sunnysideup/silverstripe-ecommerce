<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use SilverStripe\Security\SecurityToken;

/**
 * Helper for actions that change data but are reached through plain links (GET).
 *
 * A GET request must never change anything (it can be triggered by another website,
 * an image tag, a link prefetcher, etc...).  Instead, a GET request shows a small
 * "are you sure?" page with a form.  Only a POST request with a valid SecurityID is
 * then allowed to do the actual work.
 *
 * Usage in a controller action:
 *
 *     if (! ConfirmAction::isConfirmed($request)) {
 *         return ConfirmAction::form($request, 'Do you really want to do this?', 'Yes, do it');
 *     }
 *     // ... do the work ...
 */
class ConfirmAction
{
    /**
     * true if this is a POST request with a valid security token.
     */
    public static function isConfirmed(HTTPRequest $request): bool
    {
        return $request->isPOST() && SecurityToken::inst()->checkRequest($request);
    }

    /**
     * Returns a (minimal) HTML page with a form that POSTs back to the current URL.
     */
    public static function form(HTTPRequest $request, string $message, string $buttonLabel): string
    {
        $action = Controller::join_links(Director::baseURL(), $request->getURL());
        $token = SecurityToken::inst();

        return '<!DOCTYPE html><html><head><meta charset="utf-8" /><meta name="robots" content="noindex" />'
            . '<meta name="viewport" content="width=device-width, initial-scale=1" /><title>'
            . Convert::raw2xml($message) . '</title></head><body style="font-family: sans-serif; padding: 2rem;">'
            . '<form method="post" action="' . Convert::raw2att($action) . '">'
            . '<input type="hidden" name="' . Convert::raw2att($token->getName()) . '" value="' . Convert::raw2att($token->getValue()) . '" />'
            . '<p>' . Convert::raw2xml($message) . '</p>'
            . '<p><button type="submit">' . Convert::raw2xml($buttonLabel) . '</button> '
            . '<a href="' . Convert::raw2att(Director::baseURL()) . '">' . Convert::raw2xml(_t('ConfirmAction.CANCEL', 'Cancel')) . '</a></p>'
            . '</form></body></html>';
    }
}
