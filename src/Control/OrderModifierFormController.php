<?php

namespace Sunnysideup\Ecommerce\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\Form;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Model\Order;

/**
 * This controller allows you to submit modifier forms from anywhere on the site,
 * Most likely this will be from the the cart / checkout page.
 */
class OrderModifierFormController extends Controller
{
    /**
     * @var Order
     */
    protected $currentOrder;

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
     * @ToDO: check this method
     * It looks like this: /$ClassName/$action/
     *
     * @param null|mixed $action
     *
     * @return string
     */
    public function Link($action = null)
    {
        $URLSegment = Config::inst()->get(static::class, 'url_segment');
        if (! $URLSegment) {
            $URLSegment = static::class;
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
     * sets virtual methods and order.
     */
    protected function init()
    {
        parent::init();
        $this->currentOrder = ShoppingCart::current_order();
        $this->initVirtualMethods();
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
