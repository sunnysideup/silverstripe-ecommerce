<?php

namespace Sunnysideup\Ecommerce\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigAjax;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Pages\CartPage;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * Class \Sunnysideup\Ecommerce\Control\EcommerceSiteTreeExtensionController
 *
 * @property \PageController|\Sunnysideup\Ecommerce\Control\EcommerceSiteTreeExtensionController $owner
 */
class EcommerceSiteTreeExtensionController extends Extension
{
    /**
     * standard SS method.
     * Runs before the Page::init method is called.
     */
    public function onBeforeInit()
    {
        //$this->secureHostSwitcher();
        Requirements::javascript('https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js');
        //Requirements::javascript(Director::protocol()."ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
        //todo: check if we even need this (via ShoppingCartsRequirements.ss)
        // if ($this->getOwner()->dataRecord) {
        //     if (is_a(
        //         $this->getOwner()->dataRecord,
        //         EcommerceConfigClassNames::getName(Product::class)
        //     ) ||
        //         is_a(
        //             $this->getOwner()->dataRecord,
        //             EcommerceConfigClassNames::getName(ProductGroup::class)
        //         )
        //     ) {
        //         Controller::curr()->getRequest()->getSession()->set('ContinueShoppingLink', $this->getOwner()->Link());
        //     }
        // }
    }

    /**
     * Standard SS method.
     * Runs after the Page::init method is called.
     */
    public function onAfterInit()
    {
        $jsFiles = [
            'cart_js_file_location',
            'dialogue_js_file_location',
            'quantity_field_js_location',
        ];
        foreach ($jsFiles as $fileConfigString) {
            $location = EcommerceConfig::get(EcommerceConfigAjax::class, $fileConfigString);
            if ($location) {
                Requirements::javascript('sunnysideup/ecommerce: ' . $location);
            }
        }
        Requirements::themedCSS('client/css/Cart');
        Requirements::themedCSS('client/css/jquery.colorbox');
    }

    /**
     * This returns a link that displays just the cart, for use in ajax calls.
     *
     * @see ShoppingCart::showcart
     * It uses AjaxSimpleCart.ss to render the cart.
     *
     * @return string
     */
    public function SimpleCartLinkAjax()
    {
        return EcommerceConfig::get(ShoppingCartController::class, 'url_segment') . '/showcart/?ajax=1';
    }

    /**
     * returns the current order.
     *
     * @return Order
     */
    public function Cart()
    {
        return ShoppingCart::current_order();
    }

    /**
     * @return string (Link)
     */
    public function ContinueShoppingLink()
    {
        $link = Controller::curr()->getRequest()->getSession()->get('ContinueShoppingLink');
        if (! $link) {
            $link = Director::baseURL();
        }

        return $link;
    }

    /**
     * Is the page a secure page?
     *
     * @return bool
     */
    public function isSecurePage()
    {
        return $this->getOwner()->dataRecord instanceof CartPage;
    }

    /*
     * Redirect users if found on incorrect domain
     * Detects if $_GET['session'] is present, sets session
     * and redirects back to "clean URL"
     * Both _SECURE_URL and _STANDARD_URL must be defined,
     * and include protocol (http(s)://mydomain.com) with no trailing slash.
     *    protected function secureHostSwitcher()
     *     {
     *    if (!DEFINED('_SECURE_URL') || !DEFINED('_STANDARD_URL')) {
     *        return false;
     *    }
     *
     *    $protocol = Director::is_https() ? 'https://' : 'http://';
     *    $currentUrlFull = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
     *    list($currentUrlFull) = explode('#', $currentUrlFull);
     *    $currentUrlWithoutHost = $_SERVER['REQUEST_URI'];
     *    //remove fragment...just to keep it simple...
     *    list($currentUrlWithoutHost) = explode('#', $currentUrlWithoutHost);
     *    $sessionPartOfURL = '';
     *    $sessionID = session_id();
     *    if ($sessionID) {
     *        if (strpos($currentUrlWithoutHost, '?')) {
     *            $sessionPartOfURL .= '&';
     *        } else {
     *            $sessionPartOfURL = '?';
     *        }
     *        $sessionPartOfURL .= 'session='.$sessionID;
     *        $currentUrlWithoutHost .= $sessionPartOfURL;
     *    }
     *
     *        $isSecure = $this->getOwner()->isSecurePage();
     *
     *    if ($isSecure && !preg_match('/^'.preg_quote(_SECURE_URL, '/').'/', $currentUrlFull)) {
     *        return $this->getOwner()->redirect(_SECURE_URL.$currentUrlWithoutHost);
     *    } elseif (!$isSecure && !preg_match('/^'.preg_quote(_STANDARD_URL, '/').'/', $currentUrlFull)) {
     *        return $this->getOwner()->redirect(_STANDARD_URL.$currentUrlWithoutHost);
     *    }
     *
     *    if ($sessionID = $this->getOwner()->request->getVar('session')) {
     *        $currentUrlFull = str_replace('?session='.$sessionID, '', (string) $currentUrlFull);
     *        $currentUrlFull = str_replace('&session='.$sessionID, '', (string) $currentUrlFull);
     *        // force hard-coded session setting
     *        @session_write_close();
     *        @session_id($sessionID);
     *        @session_start();
     *        header('location: '.$currentUrlFull, 302);
     *        exit;
     *    }
     *}
     */
}
