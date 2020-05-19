<?php

namespace Sunnysideup\Ecommerce\Control;





use Sunnysideup\Ecommerce\Api\ShoppingCart;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;




/**
 * This controller allows you to submit Log forms from anywhere on the site,
 * especially the cart / checkout page.
 */
class OrderStatusLogFormController extends Controller
{
    /**
     * @var Order
     */
    protected $currentOrder = null;

    /**
     * @var array
     */
    private static $allowed_actions = [
        'removeLog',
    ];

    /**
     * init Class
     * sets order
     * creates virtual methods.
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
     * @param string $action
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

    public function removeLog()
    {
        //See issue 149
    }

    /**
     * Inits the virtual methods from the name of the Log forms to
     * redirect the action method to the form class.
     */
    protected function initVirtualMethods()
    {
        if ($this->currentOrder) {
            if ($forms = $this->currentOrder->getLogForms($this)) {
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
     * @return Form
     */
    protected function getOrderStatusLogForm($name)
    {
        if ($this->currentOrder) {
            if ($forms = $this->currentOrder->getLogForms($this)) {
                foreach ($forms as $form) {
                    if ($form->getName() === $name) {
                        return $form;
                    }
                }
            }
        }
    }
}

