<?php

namespace Sunnysideup\Ecommerce\Control;

use Controller;
use ShoppingCart;
use Config;
use Director;
use Form;



/**
 * This controller allows you to submit modifier forms from anywhere on the site,
 * Most likely this will be from the the cart / checkout page.
 */
class OrderModifierFormController extends Controller
{
    /**
     * @var Order
     */
    protected $currentOrder = null;

    /**
     * @var string
     */
    private static $url_segment = 'ecommercemodifierformcontroller';

    /**
     * @var array
     */
    private static $allowed_actions = [
        'removemodifier',
    ];

    /**
     * sets virtual methods and order.
     */

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * OLD:     public function init() (ignore case)
  * NEW:     protected function init() (COMPLEX)
  * EXP: Controller init functions are now protected  please check that is a controller.
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
    protected function init()
    {
        parent::init();
        $this->currentOrder = ShoppingCart::current_order();
        $this->initVirtualMethods();
    }

    /**
     * @ToDO: check this method
     * It looks like this: /$ClassName/$action/
     *
     * @return string
     */
    public function Link($action = null)
    {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $this->class (case sensitive)
  * NEW: $this->class (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        $URLSegment = Config::inst()->get($this->class, 'url_segment');
        if (! $URLSegment) {
            $URLSegment = get_class($this);
        }

        return Controller::join_links(
            Director::BaseURL(),
            $URLSegment,
            $action
        );
    }

    public function removemodifier()
    {
        //@TODO: See issue 149
    }

    /**
     * Inits the virtual methods from the name of the modifier forms to
     * redirect the action method to the form class.
     */
    protected function initVirtualMethods()
    {
        if ($this->currentOrder) {
            if ($forms = $this->currentOrder->getModifierForms($this)) {
                foreach ($forms as $form) {
                    if (! ($form instanceof Form)) {
                        $form = $form->Form;
                    }
                    $this->addWrapperMethod($form->getName(), 'getOrderModifierForm');
                    self::$allowed_actions[] = $form->getName(); // add all these forms to the list of allowed actions also
                }
            }
        }
    }

    /**
     * Return a specific {@link OrderModifierForm} by it's name.
     *
     * @param string $name The name of the form to return
     *
     * @return Form
     */
    protected function getOrderModifierForm($name)
    {
        if ($this->currentOrder) {
            if ($forms = $this->currentOrder->getModifierForms($this)) {
                foreach ($forms as $form) {
                    if (! ($form instanceof Form)) {
                        $form = $form->Form;
                    }

                    if ($form->getName() === $name) {
                        return $form;
                    }
                }
            }
        }
    }
}

