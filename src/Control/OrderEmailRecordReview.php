<?php

namespace Sunnysideup\Ecommerce\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Process\OrderEmailRecord;

/**
 * Shows the stored HTML of an email that was sent to a customer (in an iframe in the CMS).
 *
 * The stored email contains data that came from customers (names, addresses, notes, etc...)
 * and it is shown on the same origin as the CMS. To make sure that nothing in it can ever run
 * as a script in the session of a staff member, the response is sandboxed (see the Content-Security-Policy header).
 */
class OrderEmailRecordReview extends Controller
{
    /**
     * CSP for the email preview:
     * - sandbox: no scripts, forms, plugins, and the page gets its own (empty) origin.
     *   (links can still be opened in a new tab and those tabs are not sandboxed).
     * - default-src 'none': nothing is loaded, except for the exceptions below.
     * - img-src: emails show images.
     * - style-src: emails use inline styles.
     *
     * @var string
     */
    private static $content_security_policy = "sandbox allow-popups allow-popups-to-escape-sandbox; default-src 'none'; img-src * data:; style-src 'unsafe-inline'; form-action 'none'; base-uri 'none'";

    private static $allowed_actions = [
        'read' => '->canReadEmails',
    ];

    private static $url_segment = 'admin/ecommerce-email-preview';

    public static function review_link($email)
    {
        return Config::inst()->get(OrderEmailRecordReview::class, 'url_segment') . '/read/' . $email->ID;
    }

    /**
     * Only shop staff (admins, order processors and shop assistants) can read the emails that were sent to customers.
     */
    public function canReadEmails(): bool
    {
        return Permission::check('ADMIN')
            || EcommerceRole::current_member_is_shop_admin()
            || EcommerceRole::current_member_can_process_orders()
            || EcommerceRole::current_member_is_shop_assistant();
    }

    public function read($request)
    {
        // the page can show personal details: never cache it.
        HTTPCacheControlMiddleware::singleton()->disableCache(true);

        $id = (int) $request->param('ID');
        $email = $id > 0 ? OrderEmailRecord::get_by_id($id) : null;
        if ($email && ! $email->canView()) {
            // same answer as for an email that does not exist: do not reveal which IDs exist.
            $email = null;
        }

        $response = HTTPResponse::create();
        if ($email) {
            $body = (string) $email->Content;
        } else {
            $response->setStatusCode(404);
            $body = '<p>' . htmlspecialchars(
                _t('OrderEmailRecordReview.ERROR_EMAIL_COULD_NOT_BE_FOUND', 'Sorry, the content of this email is not available.'),
                ENT_QUOTES,
                'UTF-8'
            ) . '</p>';
        }

        $response->addHeader('Content-Type', 'text/html; charset=utf-8');
        $response->addHeader('X-Content-Type-Options', 'nosniff');
        $response->addHeader('Referrer-Policy', 'no-referrer');
        $response->addHeader('Content-Security-Policy', (string) $this->config()->get('content_security_policy'));

        return $response->setBody($body);
    }
}
