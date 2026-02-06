<?php

namespace Sunnysideup\Ecommerce\Control;

use SilverStripe\Control\Director;
use Sunnysideup\Ecommerce\Api\CartResponseAsArray;

/**
 * @description: returns the cart as JSON
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
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
     * can also be:
     * ```php
     * private static $cart_responses_required = [
     *     'SideBarCartID' => [
     *         'template' => 'Sidebar_Cart_Inner',
     *         'min_number_of_items' => 5,
     *     ],
     * ];
     * ```
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
        CartResponseAsArray::set_force_reload();
    }

    /**
     * turn the json headers on or off...
     * useful if you want to use the json data
     * but not the associated header.
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
    public function ReturnCartData(
        array $messages = [],
        ?array $additionalData = null,
        $status = 'success'
    ): string {
        //add header
        if ($this->includeHeaders) {
            $this->addHeader('Content-Type', 'application/json');
        }

        //bad status
        if ('success' !== $status) {
            $messagesImploded = CartResponseAsArray::implode_messages($messages);
            $this->setStatusCode(400, $messagesImploded);
        }

        $js = CartResponseAsArray::return_cart_array($messages, $additionalData, $status);

        $flag = Director::isDev() ? JSON_PRETTY_PRINT : 0;
        $json = json_encode($js, $flag);
        $json = preg_replace('/[\t\r\n]+/', ' ', $json);

        return preg_replace('/\s{2,}/', ' ', $json);
    }
}
