<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Controllers\ModelAsController;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Security;
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
     * Returns the "are you sure?" page: a message and a form that POSTs back to the current URL.
     *
     * The page is shown inside the layout of the website (the same way as the login page is),
     * so that it looks like the rest of the site.  If that is not possible for some reason,
     * a minimal HTML page is returned instead.
     */
    public static function form(HTTPRequest $request, string $message, string $buttonLabel): string
    {
        // This page has a security token in it, so it must never be cached (not by the browser, not by a CDN).
        // NB: it has to be forced (true), otherwise caching that is forced on anonymous pages by other code
        // (see simple-template-caching) wins over it.
        HTTPCacheControlMiddleware::singleton()->disableCache(true);

        $formHtml = self::formHtml($request, $message, $buttonLabel);
        try {
            $themed = self::themedPage($request, $formHtml);
            if ('' !== $themed) {
                return $themed;
            }
        } catch (\Throwable $throwable) {
            // fall through to the plain page.
        }

        return self::plainPage($message, $formHtml);
    }

    /**
     * The message and the form (without any page around it).
     */
    protected static function formHtml(HTTPRequest $request, string $message, string $buttonLabel): string
    {
        $action = Controller::join_links(Director::baseURL(), $request->getURL());
        $token = SecurityToken::inst();

        return '<div class="confirm-action">'
            . '<p class="confirm-action__message">' . Convert::raw2xml($message) . '</p>'
            . '<form class="confirm-action__form" method="post" action="' . Convert::raw2att($action) . '">'
            . '<input type="hidden" name="' . Convert::raw2att($token->getName()) . '" value="' . Convert::raw2att($token->getValue()) . '" />'
            . '<p class="confirm-action__buttons">'
            . '<button type="submit" class="button action confirm-action__confirm">' . Convert::raw2xml($buttonLabel) . '</button> '
            . '<a class="confirm-action__cancel" href="' . Convert::raw2att(Director::baseURL()) . '">' . Convert::raw2xml(_t('ConfirmAction.CANCEL', 'Cancel')) . '</a>'
            . '</p>'
            . '</form>'
            . '</div>';
    }

    /**
     * Renders the form inside the page template of the website.
     * This is the same approach as the one that Security uses for the login page: a page
     * object that is not in the database is used to render the "Page" template.
     *
     * @return string empty if there is no page class to use
     */
    protected static function themedPage(HTTPRequest $request, string $formHtml): string
    {
        $pageClass = Config::inst()->get(Security::class, 'page_class');
        if (! $pageClass || ! class_exists($pageClass)) {
            return '';
        }

        /** @var SiteTree $holderPage */
        $holderPage = Injector::inst()->create($pageClass);
        $holderPage->Title = _t('ConfirmAction.TITLE', 'Please confirm');
        $holderPage->URLSegment = 'confirm';
        $holderPage->ShowInMenus = false;
        $holderPage->ShowInSearch = false;
        // not a real page: the random negative ID stops page caches from using it (as Security does).
        $holderPage->ID = -1 * random_int(1, 10000000);

        $controller = ModelAsController::controller_for($holderPage);
        $controller->setRequest($request);
        $controller->doInit();

        // doInit may have redirected (or set an error), in which case we do not render anything.
        $response = $controller->getResponse();
        if ($response && $response->isFinished()) {
            return '';
        }

        return (string) $controller
            ->customise(['Content' => DBField::create_field('HTMLText', $formHtml), 'Form' => ''])
            ->renderWith(['Page']);
    }

    /**
     * A minimal page, for when the page template of the website can not be used.
     */
    protected static function plainPage(string $message, string $formHtml): string
    {
        return '<!DOCTYPE html><html><head><meta charset="utf-8" /><meta name="robots" content="noindex" />'
            . '<meta name="viewport" content="width=device-width, initial-scale=1" /><title>'
            . Convert::raw2xml($message) . '</title></head><body style="font-family: sans-serif; padding: 2rem;">'
            . $formHtml
            . '</body></html>';
    }
}
