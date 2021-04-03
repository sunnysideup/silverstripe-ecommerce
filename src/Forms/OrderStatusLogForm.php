<?php

namespace Sunnysideup\Ecommerce\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\Validator;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Api\Sanitizer;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Control\OrderStatusLogFormController;
use Sunnysideup\Ecommerce\Forms\Validation\OrderStatusLogFormValidator;
use Sunnysideup\Ecommerce\Model\Order;

/**
 * @description: this class is the base class for Order Log Forms in the checkout form...
 *
 * @see OrderLog
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: forms
 */
class OrderStatusLogForm extends Form
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var string
     */
    private static $controller_class = OrderStatusLogFormController::class;

    /**
     * @var string
     */
    private static $validator_class = OrderStatusLogFormValidator::class;

    /**
     *NOTE: we semi-enforce using the OrderLog_Controller here to deal with the submission of the OrderStatusLogForm
     * You can use your own Logs or an extension of OrderLog_Controller by setting the first parameter (optionalController)
     * to your own controller.
     *
     * @param string $name
     */
    public function __construct(
        Controller $optionalController = null,
        $name,
        FieldList $fields,
        FieldList $actions,
        Validator $optionalValidator = null
    ) {
        if (! $optionalController) {
            $controllerClassName = EcommerceConfig::get(OrderStatusLogForm::class, 'controller_class');
            $optionalController = new $controllerClassName();
        }
        if (! $optionalValidator) {
            $validatorClassName = EcommerceConfig::get(OrderStatusLogForm::class, 'validator_class');
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

        Requirements::themedCSS('client/css/client/css' . ClassInfo::shortName($this->ClassName));
        Requirements::javascript('silverstripe/admin: thirdparty/jquery-form/jquery.form.js');
        //add JS for the Log - added in Log
        $oldData = Controller::curr()->getRequest()->getSession()->get("FormInfo.{$this->FormName()}.data");
        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            $this->loadDataFrom($oldData);
        }
        $this->extend('updateOrderStatusLogForm', $this);
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
}
