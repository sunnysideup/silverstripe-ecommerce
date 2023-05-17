<?php

namespace Sunnysideup\Ecommerce\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\Form;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Forms\OrderStatusLogForm;
use Sunnysideup\Ecommerce\Model\Order;

/**
 * This controller allows you to submit Log forms from anywhere on the site,
 * especially the cart / checkout page.
 *
 */
class OrderStatusLogFormController extends Controller
{
    /**
     * @var Order
     */
    protected $currentOrder;

    /**
     * @var array
     */
    private static $allowed_actions = [
        'removeLog',
    ];

    /**
     * @param string $action
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

    public function removeLog()
    {
        //See issue 149
    }

    /**
     * init Class
     * sets order
     * creates virtual methods.
     */
    protected function init()
    {
        parent::init();
        $this->currentOrder = ShoppingCart::current_order();
        $this->initVirtualMethods();
    }

    /**
     * Inits the virtual methods from the name of the Log forms to
     * redirect the action method to the form class.
     */
    protected function initVirtualMethods()
    {
        if ($this->currentOrder) {
            $forms = $this->currentOrder->getLogForms($this);
            if ($forms) {
                foreach ($forms as $form) {
                    $this->addWrapperMethod($form->getName(), 'getOrderStatusLogForm');
                    self::$allowed_actions[] = $form->getName(); // add all these forms to the list of allowed actions also
                }
            }
        }
    }

    /**
     * Return a specific {@link OrderStatusLogForm} by it's name.
     *
     * @param string $name The name of the form to return
     *
     * @return null|OrderStatusLogForm
     */
    protected function getOrderStatusLogForm(string $name)
    {
        if ($this->currentOrder) {
            $forms = $this->currentOrder->getLogForms($this);
            if ($forms) {
                foreach ($forms as $form) {
                    if ($form->getName() === $name) {
                        return $form;
                    }
                }
            }
        }

        return null;
    }
}
