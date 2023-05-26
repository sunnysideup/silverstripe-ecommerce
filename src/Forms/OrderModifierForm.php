<?php

namespace Sunnysideup\Ecommerce\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\Validator;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Api\Sanitizer;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Control\OrderModifierFormController;
use Sunnysideup\Ecommerce\Forms\Validation\OrderModifierFormValidator;
use Sunnysideup\Ecommerce\Model\Order;

/**
 * @description: this class is the base class for modifier forms in the checkout form... we could do with more stuff here....
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 */
class OrderModifierForm extends Form
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var string
     */
    private static $controller_class = OrderModifierFormController::class;

    private static $validator_class = OrderModifierFormValidator::class;

    /**
     *NOTE: we semi-enforce using the OrderModifier_Controller here to deal with the submission of the OrderModifierForm
     * You can use your own modifiers or an extension of OrderModifier_Controller by setting the first parameter (optionalController)
     * to your own controller.
     *
     * @param Controller $optionalController
     */
    public function __construct(
        ?Controller $optionalController,
        string $name,
        FieldList $fields,
        FieldList $actions,
        Validator $optionalValidator = null
    ) {
        if (! $optionalController) {
            $controllerClassName = EcommerceConfig::get(OrderModifierForm::class, 'controller_class');
            $optionalController = new $controllerClassName();
        }
        if (! $optionalValidator) {
            $validatorClassName = EcommerceConfig::get(OrderModifierForm::class, 'validator_class');
            $optionalValidator = new $validatorClassName();
        }
        parent::__construct($optionalController, $name, $fields, $actions, $optionalValidator);

        //extension point
        $this->extend('updateFields', $fields);
        $this->setFields($fields);
        $this->extend('updateActions', $actions);
        $this->setActions($actions);
        $this->extend('updateValidator', $optionalValidator);
        $this->setValidator($optionalValidator);

        $this->setAttribute('autocomplete', 'off');
        Requirements::themedCSS('client/css/' . ClassInfo::shortName(self::class));
        $this->addExtraClass($this->myLcFirst(ucwords($name)));
        Requirements::javascript('https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js');
        // Requirements::javascript('silverstripe/admin: thirdparty/jquery-form/jquery.form.js');
        Requirements::block('silverstripe/admin: thirdparty/jquery-form/jquery.form.js');
        //add JS for the modifier - added in modifier

        $oldData = Controller::curr()->getRequest()->getSession()->get("FormInfo.{$this->FormName()}.data");
        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            $this->loadDataFrom($oldData);
        }
        $this->extend('updateOrderModifierForm', $this);
    }

    /**
     * @param string $status
     * @param string $message
     */
    public function redirect($status = 'success', $message = '')
    {
        //return ShoppingCart::singleton()->addmessage($status, $message);
    }

    /**
     * @param string $status
     * @param string $message
     *
     * @return ShoppingCart Response (JSON / Redirect Back)
     */
    public function submit(array $data, Form $form, $message = 'order updated', $status = 'good')
    {
        //to do - add other checks here...
        return ShoppingCart::singleton()->setMessageAndReturn($message, $status);
    }

    /**
     * saves the form into session.
     */
    public function saveDataToSession()
    {
        $data = $this->getData();
        $data = Sanitizer::remove_from_data_array($data);
        $this->setSessionData($data);
    }

    protected function myLcFirst($str)
    {
        return lcfirst($str);
    }
}
