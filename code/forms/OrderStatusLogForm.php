<?php


/**
 * @description: this class is the base class for Order Log Forms in the checkout form...
 *
 * @see OrderLog
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderStatusLogForm extends Form
{
    /**
     * @var Order
     */
    protected $order;

    /**
     *NOTE: we semi-enforce using the OrderLog_Controller here to deal with the submission of the OrderStatusLogForm
     * You can use your own Logs or an extension of OrderLog_Controller by setting the first parameter (optionalController)
     * to your own controller.
     *
     *@param Controller $optionalController
     *@param string $name
     *@param FieldList $fields
     *@param FieldList $actions
     *@param Validator $optionalValidator
     **/
    public function __construct(
        Controller $optionalController = null,
        $name,
        FieldList $fields,
        FieldList $actions,
        Validator $optionalValidator = null
    ) {
        if (! $optionalController) {
            $controllerClassName = EcommerceConfig::get('OrderStatusLogForm', 'controller_class');
            $optionalController = new $controllerClassName();
        }
        if (! $optionalValidator) {
            $validatorClassName = EcommerceConfig::get('OrderStatusLogForm', 'validator_class');
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
        Requirements::javascript(THIRDPARTY_DIR . '/jquery-form/jquery.form.js');
        //add JS for the Log - added in Log
        $oldData = Session::get("FormInfo.{$this->FormName()}.data");
        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            $this->loadDataFrom($oldData);
        }
        $this->extend('updateOrderStatusLogForm', $this);
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
