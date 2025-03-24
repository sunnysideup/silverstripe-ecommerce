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

    public static function return_cart_array(array $messages = [], ?array $additionalData = null, $status = 'success'): array
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
            $js[] = [
                't' => 'loadingindex',
                'v' => $_REQUEST['loadingindex'],
            ];
        }

        //order items

        $inCartArray = [];
        $items = $currentOrder->Items();
        if ($items->exists()) {
            foreach ($items as $item) {
                $js = $item->updateForAjax($js);
                $buyable = $item->getBuyableCached(true);
                if ($buyable) {
                    //products in cart
                    //HACK TO INCLUDE PRODUCT IN PRODUCT VARIATION
                    $inCartArray[] = $buyable->Product()->AJAXDefinitions()->UniqueIdentifier();
                }
            }
        }

        //in cart items
        $js[] = [
            't' => 'replaceclass',
            's' => $inCartArray,
            'p' => $currentOrder->AJAXDefinitions()->ProductListItemClassName(),
            'v' => $currentOrder->AJAXDefinitions()->ProductListItemInCartClassName(),
            'without' => $currentOrder->AJAXDefinitions()->ProductListItemNotInCartClassName(),
        ];

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
        $messagesImploded = self::implode_messages($messages);
        if (is_array($messages)) {
            $js[] = [
                't' => 'id',
                's' => $ajaxObject->TableMessageID(),
                'p' => 'innerHTML',
                'v' => $messagesImploded,
                'isOrderMessage' => true,
            ];
            $js[] = [
                't' => 'id',
                's' => $ajaxObject->TableMessageID(),
                'p' => 'hide',
                'v' => 0,
            ];
        } else {
            $js[] = [
                't' => 'id',
                's' => $ajaxObject->TableMessageID(),
                'p' => 'hide',
                'v' => 1,
            ];
        }

        //TO DO: set it up in such a way that it specifically requests one of these
        $templates = EcommerceConfig::get(CartResponse::class, 'cart_responses_required');
        foreach ($templates as $idMethod => $template) {
            $selector = $ajaxObject->{$idMethod}();
            $classOrID = 'id';
            if (false !== stripos($selector, 'class')) {
                $classOrID = 'class';
            }
            $js[] = [
                't' => $classOrID,
                's' => $selector,
                'p' => 'innerHTML',
                //note the space is a hack to return something!
                'v' => ' ' . $currentOrder->RenderWith($template),
            ];
        }
        //now can check if it needs to be reloaded
        if (self::$forceReload) {
            $js = [
                'reload' => 1,
            ];
        } else {
            $js[] = [
                'reload' => 0,
            ];
        }

        //merge and return
        if (is_array($additionalData) && count($additionalData)) {
            $js = array_merge($js, $additionalData);
        }
        //TODO: remove doubles?
        //turn HTMLText (et al.) objects into text
        foreach ($js as $key => $node) {
            if (isset($node['v'])) {
                if ($node['v'] instanceof DBField) {
                    $js[$key]['v'] = $node['v']->forTemplate();
                }
            }
        }
        return $js;
    }
}
