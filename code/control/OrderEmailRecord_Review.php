<?php


class OrderEmailRecord_Review extends Controller
{
    private static $allowed_actions = [
        'read' => 'ShopAdmin',
    ];

    public static function review_link($email)
    {
        return Config::inst()->get('OrderEmailRecord_Review', 'url_segment') . '/read/' . $email->ID;
    }

    public function read($request)
    {
        $id = intval($request->param('ID'));
        $email = OrderEmailRecord::get()->byID($id);

        return $email->Content;
    }
}
