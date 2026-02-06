<?php

namespace Sunnysideup\Ecommerce\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use Sunnysideup\Ecommerce\Model\Process\OrderEmailRecord;

/**
 * Class \Sunnysideup\Ecommerce\Control\OrderEmailRecordReview
 */
class OrderEmailRecordReview extends Controller
{
    private static $allowed_actions = [
        'read' => 'ShopAdmin',
    ];

    private static $url_segment = 'admin/ecommerce-email-preview';

    public static function review_link($email)
    {
        return Config::inst()->get(OrderEmailRecordReview::class, 'url_segment') . '/read/' . $email->ID;
    }

    public function read($request)
    {
        $id = (int) $request->param('ID');
        $email = OrderEmailRecord::get_by_id($id);
        if ($email) {
            return $email->Content;
        }

        return _t('OrderEmailRecordReview.ERROR_EMAIL_COULD_NOT_BE_FOUND', 'Sorry, the content of this email is not available.');
    }
}
