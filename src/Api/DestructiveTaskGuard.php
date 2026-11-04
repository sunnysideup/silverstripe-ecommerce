<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Security\SecurityToken;

/**
 * Protects dev tasks (BuildTasks) that delete or bulk-change data.
 *
 * Tasks are normally started by opening a link (GET), which means that another website, an email
 * or an image tag can start them for a logged-in admin. A task that uses this guard
 * - over the web: only runs after the admin has confirmed on a page (a POST with the SecurityID),
 * - on the command line (sake): runs straight away,
 * - can be restricted to non-live or dev-only sites.
 *
 * Usage, at the top of the run method of a task:
 *
 *     if (! DestructiveTaskGuard::mayRun($request, $this, 'Deletes all products.')) {
 *         return;
 *     }
 *
 * For a task that is only destructive with a certain option (e.g. ?purge=1), call it only when that option is present.
 */
class DestructiveTaskGuard
{
    use Configurable;

    /**
     * the task may run anywhere (after confirmation).
     */
    public const ANYWHERE = '';

    /**
     * the task is refused on a live site (setup, migration and "remove everything" tasks).
     */
    public const NOT_LIVE = 'not_live';

    /**
     * the task only runs in dev mode.
     */
    public const DEV_ONLY = 'dev_only';

    /**
     * Set this to true to allow the tasks that are restricted with NOT_LIVE to run on a live site.
     * (DEV_ONLY tasks can never run on a live site.)
     */
    private static bool $allow_restricted_tasks_on_live = false;

    /**
     * Returns true if the task is allowed to run now. If not, then it has already
     * shown the reason or the confirmation form, and the task has to stop.
     */
    public static function mayRun(HTTPRequest|null $request, BuildTask $task, string $whatItDoes, string $restriction = self::ANYWHERE): bool
    {
        $title = (string) $task->getTitle();

        // the command line is not reachable from a web page.
        if (Director::is_cli()) {
            return true;
        }

        if (self::DEV_ONLY === $restriction && ! Director::isDev()) {
            DB::alteration_message('"' . Convert::raw2xml($title) . '" can only be run in dev mode.', 'error');

            return false;
        }

        if (self::NOT_LIVE === $restriction && Director::isLive() && ! static::config()->get('allow_restricted_tasks_on_live')) {
            DB::alteration_message('"' . Convert::raw2xml($title) . '" can not be run on a live site.', 'error');

            return false;
        }


        if ($request && $request->isPOST() && SecurityToken::inst()->checkRequest($request)) {
            return true;
        }

        echo self::confirmationForm($request, $title, $whatItDoes);

        return false;
    }

    /**
     * The form that the admin has to submit before the task runs.
     * It posts to the same address (with the same options) and includes the SecurityID.
     */
    protected static function confirmationForm(HTTPRequest $request, string $title, string $whatItDoes): string
    {
        $token = SecurityToken::inst();
        $action = Director::baseURL() . ltrim($request->getURL(true), '/');

        return '<form method="post" action="' . Convert::raw2att($action) . '" style="padding: 1rem; margin: 1rem 0; border: 2px solid #c00; background: #fff4f4;">'
            . '<input type="hidden" name="' . Convert::raw2att($token->getName()) . '" value="' . Convert::raw2att($token->getValue()) . '" />'
            . '<h3>' . Convert::raw2xml($title) . '</h3>'
            . '<p><strong>' . Convert::raw2xml($whatItDoes) . '</strong></p>'
            . '<p>Nothing has been changed yet. Are you sure you want to run this task?</p>'
            . '<button type="submit">Yes, run this task now</button>'
            . '</form>';
    }
}
