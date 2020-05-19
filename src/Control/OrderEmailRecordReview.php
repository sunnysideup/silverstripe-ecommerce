<?php

namespace Sunnysideup\Ecommerce\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use Sunnysideup\Ecommerce\Model\Process\OrderEmailRecord;

class OrderEmailRecordReview extends Controller
{
    private static $allowed_actions = [
        'read' => 'ShopAdmin',
    ];

    public static function review_link($email)
    {
        return Config::inst()->get(OrderEmailRecordReview::class, 'url_segment') . '/read/' . $email->ID;
    }

    public function read($request)
    {
        $id = intval($request->param('ID'));
        $email = OrderEmailRecord::get()->byID($id);

        return $email->Content;
    }
}
