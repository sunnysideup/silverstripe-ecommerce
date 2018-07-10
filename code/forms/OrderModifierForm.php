<?php

/**
 * @description: this class is the base class for modifier forms in the checkout form... we could do with more stuff here....
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderModifierForm extends Form
{
    /**
     * @var Order
     */
    protected $order;

    /**
     *NOTE: we semi-enforce using the OrderModifier_Controller here to deal with the submission of the OrderModifierForm
     * You can use your own modifiers or an extension of OrderModifier_Controller by setting the first parameter (optionalController)
     * to your own controller.
     *
     *@param $optionalController Controller
     *@param $name String
     *@param $fields FieldList
     *@param $actions FieldList
     *@param $validator SS_Validator
     **/
    public function __construct(
        Controller $optionalController = null,
        $name,
        FieldList $fields,
        FieldList $actions,
        Validator $optionalValidator = null
    ) {
        if (!$optionalController) {
            $controllerClassName = EcommerceConfig::get('OrderModifierForm', 'controller_class');
            $optionalController = new $controllerClassName();
        }
        if (!$optionalValidator) {
            $validatorClassName = EcommerceConfig::get('OrderModifierForm', 'validator_class');
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
        Requirements::themedCSS($this->ClassName, 'ecommerce');
        $this->addExtraClass($this->myLcFirst(ucwords($name)));
        Requirements::javascript(THIRDPARTY_DIR.'/jquery-form/jquery.form.js');
        //add JS for the modifier - added in modifier
        $oldData = Session::get("FormInfo.{$this->FormName()}.data");
        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            $this->loadDataFrom($oldData);
        }
        $this->extend('updateOrderModifierForm', $this);
    }

    protected function myLcFirst($str)
    {
        if (function_exists('lcfirst') === false) {
            function lcfirst($str)
            {
                $str[0] = strtolower($str[0]);

                return $str;
            }
        } else {
            return lcfirst($str);
        }
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
     * @param array  $data
     * @param Form   $form
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
     *
     * @param array $data - data from form.
     */
    public function saveDataToSession()
    {
        $data = $this->getData();
        Session::set("FormInfo.{$this->FormName()}.data", $data);
    }
}
