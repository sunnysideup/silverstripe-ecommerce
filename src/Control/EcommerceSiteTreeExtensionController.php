<?php

namespace Sunnysideup\Ecommerce\Control;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use SilverStripe\Control\Director;
use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigAjax;
use Sunnysideup\Ecommerce\Pages\CartPage;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use SilverStripe\Core\Injector\Injector;

/**
 * ### @@@@ START REPLACEMENT @@@@ ###
 * WHY: automated upgrade
 * OLD:  extends Extension (ignore case)
 * NEW:  extends Extension (COMPLEX)
 * EXP: Check for use of $this->anyVar and replace with $this->anyVar[$this->owner->ID] or consider turning the class into a trait
 * ### @@@@ STOP REPLACEMENT @@@@ ###
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

        /**
         * ### @@@@ START REPLACEMENT @@@@ ###
         * WHY: automated upgrade
         * OLD: THIRDPARTY_DIR . '/jquery/jquery.js' (case sensitive)
         * NEW: 'silverstripe/admin: thirdparty/jquery/jquery.js' (COMPLEX)
         * EXP: Check for best usage and inclusion of Jquery
         * ### @@@@ STOP REPLACEMENT @@@@ ###
         */
        Requirements::javascript('sunnysideup/ecommerce: silverstripe/admin: thirdparty/jquery/jquery.js');
        //Requirements::block(THIRDPARTY_DIR."/jquery/jquery.js");
        //Requirements::javascript(Director::protocol()."ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
        //todo: check if we even need this (via ShoppingCartsRequirements.ss)
        if ($this->owner->dataRecord) {

            /**
             * ### @@@@ START REPLACEMENT @@@@ ###
             * WHY: automated upgrade
             * OLD:  Object:: (case sensitive)
             * NEW:  SilverStripe\\Core\\Injector\\Injector::inst()-> (COMPLEX)
             * EXP: Check if this is the right implementation, this is highly speculative.
             * ### @@@@ STOP REPLACEMENT @@@@ ###
             */
            if (
                is_a(
                    $this->owner->dataRecord,
                    EcommerceConfigClassNames::getName(Product::class)
                ) ||
                is_a(
                    $this->owner->dataRecord,
                    EcommerceConfigClassNames::getName(ProductGroup::class)
                )
            ) {

                /**
                 * ### @@@@ START REPLACEMENT @@@@ ###
                 * WHY: automated upgrade
                 * OLD: Session:: (case sensitive)
                 * NEW: SilverStripe\Control\Controller::curr()->getRequest()->getSession()-> (COMPLEX)
                 * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly.
                 * ### @@@@ STOP REPLACEMENT @@@@ ###
                 */
                SilverStripe\Control\Controller::curr()->getRequest()->getSession()->set('ContinueShoppingLink', $this->owner->Link());
            }
        }
    }

    /**
     * Standard SS method.
     * Runs after the Page::init method is called.
     */
    public function onAfterInit()
    {
        Requirements::javascript(EcommerceConfig::get(EcommerceConfigAjax::class, 'cart_js_file_location'));
        Requirements::javascript(EcommerceConfig::get(EcommerceConfigAjax::class, 'dialogue_js_file_location'));
        Requirements::themedCSS('sunnysideup/ecommerce: Cart', 'ecommerce');
        Requirements::themedCSS('sunnysideup/ecommerce: jquery.colorbox', 'ecommerce');
    }

    /**
     * This returns a link that displays just the cart, for use in ajax calls.
     *
     * @see ShoppingCart::showcart
     * It uses AjaxSimpleCart.ss to render the cart.
     *
     * @return string
     **/
    public function SimpleCartLinkAjax()
    {
        return EcommerceConfig::get(ShoppingCartController::class, 'url_segment') . '/showcart/?ajax=1';
    }

    /**
     * returns the current order.
     *
     * @return Order
     **/
    public function Cart()
    {
        return ShoppingCart::current_order();
    }

    /**
     * @return string (Link)
     */
    public function ContinueShoppingLink()
    {

        /**
         * ### @@@@ START REPLACEMENT @@@@ ###
         * WHY: automated upgrade
         * OLD: Session:: (case sensitive)
         * NEW: SilverStripe\Control\Controller::curr()->getRequest()->getSession()-> (COMPLEX)
         * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly.
         * ### @@@@ STOP REPLACEMENT @@@@ ###
         */
        $link = SilverStripe\Control\Controller::curr()->getRequest()->getSession()->get('ContinueShoppingLink');
        if (! $link) {
            $link = Director::baseURL();
        }

        return $link;
    }

    /**
     * Is the page a secure page?
     *
     * @return true/false
     */
    public function isSecurePage()
    {
        return $this->owner->dataRecord instanceof CartPage;
    }

    /**
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
    *        $isSecure = $this->owner->isSecurePage();
    *
    *    if ($isSecure && !preg_match('/^'.preg_quote(_SECURE_URL, '/').'/', $currentUrlFull)) {
    *        return $this->owner->redirect(_SECURE_URL.$currentUrlWithoutHost);
    *    } elseif (!$isSecure && !preg_match('/^'.preg_quote(_STANDARD_URL, '/').'/', $currentUrlFull)) {
    *        return $this->owner->redirect(_STANDARD_URL.$currentUrlWithoutHost);
    *    }
    *
    *    if ($sessionID = $this->owner->request->getVar('session')) {
    *        $currentUrlFull = str_replace('?session='.$sessionID, '', $currentUrlFull);
    *        $currentUrlFull = str_replace('&session='.$sessionID, '', $currentUrlFull);
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
