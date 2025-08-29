<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\SSViewer;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Control\CartResponse;

class CartResponseAsArray
{
    protected static $forceReload = false;

    public static function set_force_reload()
    {
        self::$forceReload = true;
    }

    public const HIDE_CLASS = 'hide';
    public const SHOW_CLASS = 'show';

    public const REMOVE_LINK_CLASS = 'removeLink';
    public const ADD_LINK_CLASS = 'addLink';
    public const PRODUCT_ACTIONS_CLASS = 'productActions';

    public static function implode_messages(?array $messages = null): string
    {
        //merge messages
        $messagesImploded = '';
        if (is_array($messages) && count($messages)) {
            foreach ($messages as $messageArray) {
                $messagesImploded .= '<span class="' . $messageArray['Type'] . '">' . $messageArray['Message'] . '</span>';
            }
        }
        return $messagesImploded;
    }

    public static function return_cart_array(?array $messages = [], ?array $additionalData = null, ?string $status = 'success'): array
    {

        Config::modify()->set(SSViewer::class, 'source_file_comments', false);

        //init Order - IMPORTANT
        $currentOrder = ShoppingCart::current_order();

        //THIS LINE TAKES UP MOST OF THE TIME OF THE RESPONSE!!!
        //HOWEVER, YOU MUST NOT CHANGE IT!
        $currentOrder->calculateOrderAttributes($recalculate = true);

        $ajaxObject = $currentOrder->AJAXDefinitions();
        // populate Javascript
        $js = [];

        //must be first
        if (isset($_REQUEST['loadingindex'])) {
            $js['body'] = [
                't' => 'loadingindex',
                'v' => $_REQUEST['loadingindex'],
            ];
        }

        //order items

        $js['.' . self::PRODUCT_ACTIONS_CLASS . ' .' . self::REMOVE_LINK_CLASS] = [
            'class' => self::HIDE_CLASS,
            'removeClass' => self::SHOW_CLASS,
        ];
        $js['.' . self::PRODUCT_ACTIONS_CLASS . ' .' . self::ADD_LINK_CLASS] = [
            'class' => self::SHOW_CLASS,
            'removeClass' => self::HIDE_CLASS,
        ];
        $items = $currentOrder->Items();
        if ($items->exists()) {
            foreach ($items as $item) {
                $js = $item->updateForAjax($js);
                $buyable = $item->getBuyableCached(true);
                if ($buyable) {
                    //products in cart
                    //HACK TO INCLUDE PRODUCT IN PRODUCT VARIATION
                    $js['#' . $buyable->Product()->AJAXDefinitions()->UniqueIdentifier() . ' .' . self::REMOVE_LINK_CLASS] = [
                        'class' => self::SHOW_CLASS,
                        'removeClass' => self::HIDE_CLASS,
                    ];
                    $js['#' . $buyable->Product()->AJAXDefinitions()->UniqueIdentifier() . ' .' . self::ADD_LINK_CLASS] = [
                        'class' => self::HIDE_CLASS,
                        'removeClass' => self::SHOW_CLASS,
                    ];
                }
            }
        }


        //order modifiers
        $modifiers = $currentOrder->Modifiers();
        if ($modifiers->exists()) {
            foreach ($modifiers as $modifier) {
                $js = $modifier->updateForAjax($js);
            }
        }

        //order
        $js = $currentOrder->updateForAjax($js);

        //messages
        $messagesImploded = trim(self::implode_messages($messages));
        $id = '#' . $ajaxObject->TableMessageID();
        $js[$id] = [
            'html' => $messagesImploded,
            'class' => $messagesImploded ? 'show' : 'hide',
        ];
        //TO DO: set it up in such a way that it specifically requests one of these
        $templates = EcommerceConfig::get(CartResponse::class, 'cart_responses_required');
        foreach ($templates as $idMethod => $template) {
            $minNumberOfItems = 0;
            if (is_array($template)) {
                $template = $template['template'] ?? null;
                $minNumberOfItems = $template['min_number_of_items'] ?? 0;
            }
            if (! $template) {
                continue;
            }
            $selector = $ajaxObject->{$idMethod}();
            $classOrID = '#';
            if (false !== stripos($idMethod, 'class')) {
                $classOrID = '.';
            }
            if ($minNumberOfItems && $items->count() < $minNumberOfItems) {
                $data = '';
            } else {
                $data = ' ' . $currentOrder->RenderWith($template);
            }

            $js[$classOrID . $selector] = [
                'html' => $data,
            ];
        }
        //now can check if it needs to be reloaded
        if (self::$forceReload) {
            $js['body'] = ['callback' => 'window.location.reload();'];
            self::$forceReload = false;
        }
        //merge and return
        if (is_array($additionalData) && count($additionalData)) {
            $js = array_merge($js, $additionalData);
        }
        foreach ($js as $key => $value) {
            if (isset($value['html']) && is_object($value['html'])) {
                $js[$key]['html'] = $value['html']->forTemplate();
            }
        }
        return $js;
    }
}
