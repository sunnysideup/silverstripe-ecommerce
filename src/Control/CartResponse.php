<?php

namespace Sunnysideup\Ecommerce\Control;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\SSViewer;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * @description: returns the cart as JSON
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: control
 */
class CartResponse extends EcommerceResponse
{
    /**
     * Should the page be reloaded rather than using AJAX?
     *
     * @var bool
     */
    protected $includeHeaders = true;

    /**
     * Should the page be reloaded rather than using AJAX?
     *
     * @var bool
     */
    private static $force_reload = false;

    /**
     * @var array
     */
    private static $cart_responses_required = [
        'SideBarCartID' => 'Sidebar_Cart_Inner',
    ];

    /**
     * Sets the $force_reload to true;.
     */
    public static function set_force_reload()
    {
        self::$force_reload = true;
    }

    /**
     * turn the json headers on or off...
     * useful if you want to use the json data
     * but not the associated header.
     *
     * @param $b
     */
    public function setIncludeHeaders(bool $b): self
    {
        $this->includeHeaders = $b;

        return $this;
    }

    /**
     * Builds json object to be returned via ajax.
     *
     * @param array  $messages (Type, Message)
     * @param string $status
     *
     * @return string HEADER + JSON
     */
    public function ReturnCartData(array $messages = [], array $additionalData = null, $status = 'success')
    {
        //add header
        if ($this->includeHeaders) {
            $this->addHeader('Content-Type', 'application/json');
        }

        Config::modify()->update(SSViewer::class, 'source_file_comments', false);
        //merge messages
        $messagesImploded = '';
        if (is_array($messages) && count($messages)) {
            foreach ($messages as $messageArray) {
                $messagesImploded .= '<span class="' . $messageArray['Type'] . '">' . $messageArray['Message'] . '</span>';
            }
        }

        //bad status
        if ('success' !== $status) {
            $this->setStatusCode(400, $messagesImploded);
        }

        //init Order - IMPORTANT
        $currentOrder = ShoppingCart::current_order();

        //THIS LINE TAKES UP MOST OF THE TIME OF THE RESPONSE!!!
        $currentOrder->calculateOrderAttributes($force = false);

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
        if ($items->count()) {
            foreach ($items as $item) {
                $js = $item->updateForAjax($js);
                $buyable = $item->Buyable(true);
                if ($buyable) {
                    //products in cart
                    $inCartArray[] = $buyable->AJAXDefinitions()->UniqueIdentifier();
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
        if ($modifiers->count()) {
            foreach ($modifiers as $modifier) {
                $js = $modifier->updateForAjax($js);
            }
        }

        //order
        $js = $currentOrder->updateForAjax($js);

        //messages
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
        if (self::$force_reload) {
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
        $json = json_encode($js);
        $json = str_replace('\t', ' ', $json);
        $json = str_replace('\r', ' ', $json);
        $json = str_replace('\n', ' ', $json);
        $json = preg_replace('#\s\s+#', ' ', $json);
        if (Director::isDev()) {
            $json = str_replace('{', "\r\n{", $json);
        }

        return $json;
    }
}
